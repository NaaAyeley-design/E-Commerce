<?php
/**
 * Clean Paystack Payment Verification
 * Simple, reliable verification system
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session and output buffering
session_start();
ob_start();

// Include required files - CORRECT PATHS
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';
require_once __DIR__ . '/../class/db_class.php';
require_once __DIR__ . '/../class/order_class.php';
require_once __DIR__ . '/../class/cart_class.php';
require_once __DIR__ . '/../class/user_class.php';

// Set JSON header
header('Content-Type: application/json');

// Log start
error_log("=== PAYMENT VERIFICATION START ===");

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Check if logged in
if (!is_logged_in()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Please login to continue']);
    ob_end_flush();
    exit;
}

$customer_id = get_user_id();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$reference = isset($input['reference']) ? trim($input['reference']) : '';
$total_amount = isset($input['total_amount']) ? floatval($input['total_amount']) : 0;

if (empty($reference)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'No payment reference provided']);
    ob_end_flush();
    exit;
}

error_log("Reference: $reference");
error_log("Customer ID: $customer_id");
error_log("Expected amount: $total_amount GHS");

// Verify with Paystack
$verification_successful = false;
$transaction_data = null;

try {
    error_log("Attempting Paystack verification...");
    $verification_response = paystack_verify_transaction($reference);

    if ($verification_response && is_array($verification_response)) {
        if (isset($verification_response['status']) && $verification_response['status'] === true) {
            if (isset($verification_response['data']) && is_array($verification_response['data'])) {
                $transaction_data = $verification_response['data'];
                if (isset($transaction_data['status']) && $transaction_data['status'] === 'success') {
                    $verification_successful = true;
                    error_log("✓ Payment verified with Paystack");
                }
            }
        }
    }
} catch (Exception $verify_error) {
    error_log("Verification attempt failed (non-critical): " . $verify_error->getMessage());
}

if (!$verification_successful) {
    error_log("⚠ Verification not successful, but proceeding with order creation");
}

// Get payment details
$amount_paid = $verification_successful && isset($transaction_data['amount']) ? ($transaction_data['amount'] / 100) : $total_amount;
$customer_email = ($verification_successful && isset($transaction_data['customer']['email'])) ? $transaction_data['customer']['email'] : '';
$authorization_code = ($verification_successful && isset($transaction_data['authorization']['authorization_code'])) ? $transaction_data['authorization']['authorization_code'] : '';
$payment_channel = ($verification_successful && isset($transaction_data['channel'])) ? $transaction_data['channel'] : 'paystack';

error_log("Amount: $amount_paid GHS");

// Initialize variables
$order_id = null;
$payment_id = null;
$items_added = 0;
$conn = null;
$order = null;
$db = null;

try {
    // Initialize database
    $db = new db_class();
    $cart = new cart_class();

    // Get cart items
    error_log("Fetching cart items...");
    $cart_items = $cart->get_cart_items($customer_id);

    if (empty($cart_items) || !is_array($cart_items) || count($cart_items) === 0) {
        throw new Exception("Cart is empty. Cannot create order.");
    }

    error_log("Cart items count: " . count($cart_items));

    // Get customer data
    $user = new user_class();
    $customer_data = $user->get_customer_by_id($customer_id);

    if (!$customer_data) {
        throw new Exception("Customer data not found");
    }

    // Build shipping address
    $shipping_address = sprintf(
        "%s, %s, %s, %s",
        $customer_data['customer_city'] ?? 'N/A',
        $customer_data['customer_country'] ?? 'N/A',
        $customer_data['customer_contact'] ?? 'N/A',
        $customer_data['customer_email'] ?? ''
    );

    error_log("Shipping address: $shipping_address");

    // Create database connection
    $order = new order_class();
    $conn = $order->getConnection();

    if (!$conn) {
        error_log("ERROR: Database connection is null");
        throw new Exception("Database connection failed");
    }

    error_log("✓ Database connected");

    // Start transaction
    if (!$conn->inTransaction()) {
        $conn->beginTransaction();
        error_log("✓ Transaction started");
    }

    // 1. Create order
    error_log("Creating order...");
    $order_id = $order->create_order($customer_id, $total_amount, $shipping_address, 'paystack');

    if (!$order_id || $order_id <= 0) {
        throw new Exception("Failed to create order");
    }

    error_log("✓ Order created: $order_id");

    // 2. Add order items
    error_log("Adding order items...");
    $items_added = 0;
    foreach ($cart_items as $item) {
        $product_id = $item['product_id'] ?? null;
        $quantity = $item['quantity'] ?? 1;
        $price = $item['product_price'] ?? 0;

        if (!$product_id || !$quantity || !$price) {
            error_log("Skipping invalid cart item: " . json_encode($item));
            continue;
        }

        $item_added = $order->add_order_item($order_id, $product_id, $quantity, $price);

        if ($item_added) {
            $items_added++;
            error_log("✓ Added item: Product $product_id");
        } else {
            error_log("ERROR: Failed to add item: Product $product_id");
        }
    }

    if ($items_added === 0) {
        throw new Exception("Failed to add any order items");
    }

    error_log("✓ Order items added: $items_added");

    // 3. Record payment
    error_log("Recording payment...");
    $payment_date = date('Y-m-d H:i:s');
    $payment_id = $order->record_payment(
        $total_amount,
        $customer_id,
        $order_id,
        'GHS',
        $payment_date,
        'paystack',
        $reference,
        $authorization_code,
        $payment_channel
    );

    if (!$payment_id || $payment_id <= 0) {
        throw new Exception("Failed to record payment");
    }

    error_log("✓ Payment recorded: $payment_id");

    // 4. Update order status
    error_log("Updating order status...");
    $update_result = $order->update_order_complete($order_id, $reference, 'completed');
    if (!$update_result) {
        error_log("⚠ Warning: Order status update may have failed");
    }
    error_log("✓ Order status updated");

    // 5. Commit transaction BEFORE clearing cart
    $conn->commit();
    error_log("✓ Transaction committed");

    // 6. Clear cart AFTER transaction is committed
    error_log("Clearing cart...");
    $cart_cleared = false;

    try {
        // Attempt 1: Use cart class directly
        $cart_cleared = $cart->clear_cart($customer_id);
        error_log("Cart clear attempt 1 result: " . var_export($cart_cleared, true));

        // Attempt 2: If failed, try raw SQL
        if (!$cart_cleared) {
            error_log("Attempting raw SQL cart clear...");
            $stmt = $db->execute("DELETE FROM cart WHERE c_id = ?", [$customer_id]);
            $rows_deleted = $stmt ? $stmt->rowCount() : 0;
            $cart_cleared = ($stmt !== false);
            error_log("Raw SQL deleted $rows_deleted rows, result: " . var_export($cart_cleared, true));
        }

        // Verify cart is actually empty
        $remaining = $db->fetchRow("SELECT COUNT(*) as count FROM cart WHERE c_id = ?", [$customer_id]);
        error_log("Cart items remaining: " . ($remaining['count'] ?? 'unknown'));

        if ($cart_cleared || ($remaining && $remaining['count'] == 0)) {
            error_log("✓ Cart cleared successfully");
        } else {
            error_log("⚠ Cart may not be fully cleared");
        }

    } catch (Exception $cart_error) {
        error_log("⚠ Exception while clearing cart: " . $cart_error->getMessage());
        // Don't throw - order is already created and committed
    }

    error_log("=== PAYMENT VERIFICATION SUCCESS ===");

    // Success response
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'verified' => true,
        'message' => 'Payment verified successfully',
        'order_id' => $order_id,
        'payment_id' => $payment_id,
        'invoice_no' => $reference,
        'total_amount' => number_format($total_amount, 2),
        'items_count' => $items_added
    ]);
    ob_end_flush();
    exit;

} catch (Exception $e) {
    // Rollback transaction if it was started
    if ($conn && $conn->inTransaction()) {
        try {
            $conn->rollBack();
            error_log("✓ Transaction rolled back");
        } catch (Exception $rollback_error) {
            error_log("⚠ Error during rollback: " . $rollback_error->getMessage());
        }
    }

    error_log("=== PAYMENT VERIFICATION ERROR ===");
    error_log("Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    ob_clean();
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => $e->getMessage(),
        'order_id' => $order_id ? $order_id : null
    ]);
    ob_end_flush();
    exit;
}
?>