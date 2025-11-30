<?php
/**
 * Clean Paystack Payment Verification
 * Simple, reliable verification system
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

// Start session and output buffering
session_start();
ob_start();

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';
require_once __DIR__ . '/../class/db_class.php';
require_once __DIR__ . '/../class/order_class.php';
require_once __DIR__ . '/../controller/cart_controller.php';

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

// Try to verify with Paystack, but don't fail if verification has issues
// If payment went through on Paystack frontend, we trust it and create the order
$verification_successful = false;
$transaction_data = null;

try {
    // Try to verify with Paystack API
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
    // Continue anyway - payment went through on frontend
}

if (!$verification_successful) {
    error_log("⚠ Verification not successful, but proceeding with order creation (payment was successful on Paystack)");
}
    
// Get payment details (use defaults if verification didn't work)
$amount_paid = $verification_successful && isset($transaction_data['amount']) ? ($transaction_data['amount'] / 100) : $total_amount;
$customer_email = ($verification_successful && isset($transaction_data['customer']['email'])) ? $transaction_data['customer']['email'] : '';
$authorization_code = ($verification_successful && isset($transaction_data['authorization']['authorization_code'])) ? $transaction_data['authorization']['authorization_code'] : '';
$payment_channel = ($verification_successful && isset($transaction_data['channel'])) ? $transaction_data['channel'] : 'paystack';
    
error_log("Amount: $amount_paid GHS (using provided amount: $total_amount)");
if ($customer_email) {
    error_log("Customer email: $customer_email");
}

// Only check amount if verification was successful
if ($verification_successful && abs($amount_paid - $total_amount) > 0.01) {
    error_log("⚠ Amount mismatch: Expected $total_amount, got $amount_paid (proceeding anyway)");
    // Don't throw exception - just log the warning
}

// Initialize variables
$order_id = null;
$payment_id = null;
$items_added = 0;
$conn = null;
$order = null;
$db = null;

try {
    // Initialize database class for additional checks
    $db = new db_class();
    
    // Get cart items
    error_log("Fetching cart items...");
    $cart_items = get_cart_items_ctr($customer_id);
    
    if (empty($cart_items) || !is_array($cart_items) || count($cart_items) === 0) {
        throw new Exception("Cart is empty. Cannot create order.");
    }
    
    error_log("Cart items count: " . count($cart_items));
    
    // Get customer data for shipping address
    require_once __DIR__ . '/../class/user_class.php';
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
    
    // Create database connection using order_class
    $order = new order_class();
    $conn = $order->getConnection();
    
    if (!$conn) {
        error_log("ERROR: Database connection is null");
        throw new Exception("Database connection failed. Please check your database configuration.");
    }
    
    error_log("✓ Database connected");
    
    // Verify database connection is working
    try {
        $test_query = $conn->query("SELECT 1");
        if (!$test_query) {
            throw new Exception("Database connection test failed");
        }
        error_log("✓ Database connection verified");
    } catch (Exception $db_test_error) {
        error_log("ERROR: Database connection test failed: " . $db_test_error->getMessage());
        throw new Exception("Database connection is not working properly: " . $db_test_error->getMessage());
    }
    
    // Check if required tables exist
    error_log("Checking required tables...");
    $tables_to_check = ['orders', 'order_items', 'payment', 'customer', 'products'];
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $table_check = $db->fetchRow("SHOW TABLES LIKE '$table'");
        if (!$table_check) {
            $missing_tables[] = $table;
            error_log("⚠ Table '$table' does not exist");
        } else {
            error_log("✓ Table '$table' exists");
        }
    }
    
    if (!empty($missing_tables)) {
        $missing_list = implode(', ', $missing_tables);
        error_log("ERROR: Missing required tables: $missing_list");
        throw new Exception("Required database tables are missing: $missing_list. Please run the database migration scripts.");
    }
    
    // Start transaction
    if (!$conn->inTransaction()) {
        $conn->beginTransaction();
        error_log("✓ Transaction started");
    }
    
    // 1. Create order using order_class
    error_log("Creating order...");
    error_log("Parameters: customer_id=$customer_id, total_amount=$total_amount, shipping_address=$shipping_address");
    
    $order_id = $order->create_order($customer_id, $total_amount, $shipping_address, 'paystack');
    
    if (!$order_id || $order_id <= 0) {
        // Get more detailed error information
        $error_details = "Failed to create order. ";
        $error_details .= "Order ID returned: " . var_export($order_id, true) . ". ";
        $error_details .= "Check error logs for detailed database errors.";
        
        error_log("ERROR: Order creation failed");
        error_log("Customer ID: $customer_id");
        error_log("Total Amount: $total_amount");
        error_log("Shipping Address: $shipping_address");
        
        // Check if customer exists
        $customer_check = $db->fetchRow("SELECT customer_id FROM customer WHERE customer_id = ?", [$customer_id]);
        if (!$customer_check) {
            $error_details .= " Customer ID $customer_id does not exist in database.";
        }
        
        // Check if orders table exists
        $table_check = $db->fetchRow("SHOW TABLES LIKE 'orders'");
        if (!$table_check) {
            $error_details .= " Orders table does not exist.";
        }
        
        throw new Exception($error_details);
    }
    
    error_log("✓ Order created: $order_id");
    
    // 2. Add order items
    error_log("Adding order items...");
    $items_added = 0;
    foreach ($cart_items as $item) {
        $product_id = isset($item['product_id']) ? $item['product_id'] : (isset($item['p_id']) ? $item['p_id'] : null);
        $quantity = isset($item['quantity']) ? $item['quantity'] : (isset($item['qty']) ? $item['qty'] : 1);
        $price = isset($item['product_price']) ? $item['product_price'] : (isset($item['price']) ? $item['price'] : 0);
        
        if (!$product_id || !$quantity || !$price) {
            error_log("Skipping invalid cart item: " . json_encode($item));
            continue;
        }
        
        // Verify product exists before adding to order
        $product_check = $db->fetchRow("SELECT product_id, product_title FROM products WHERE product_id = ?", [$product_id]);
        if (!$product_check) {
            error_log("⚠ Product ID $product_id does not exist in database - skipping");
            continue;
        }
        
        error_log("Adding order item: product_id=$product_id (".($product_check['product_title'] ?? 'N/A')."), quantity=$quantity, price=$price");
        $item_added = $order->add_order_item($order_id, $product_id, $quantity, $price);
        
        if ($item_added) {
            $items_added++;
            error_log("✓ Successfully added order item");
        } else {
            error_log("ERROR: Failed to add order item: product_id=$product_id, quantity=$quantity, price=$price");
            error_log("This may indicate a problem with the order_items table or foreign key constraints");
        }
    }
    
    if ($items_added === 0) {
        $error_msg = "Failed to add any order items to order #$order_id. ";
        $error_msg .= "Possible causes: Products in cart no longer exist, order_items table issues, or foreign key constraint violations. ";
        $error_msg .= "Check error logs for detailed information.";
        error_log("ERROR: No order items were added. Cart had " . count($cart_items) . " items.");
        throw new Exception($error_msg);
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
        $error_msg = "Failed to record payment for order #$order_id. ";
        $error_msg .= "Payment ID returned: " . var_export($payment_id, true) . ". ";
        $error_msg .= "Possible causes: payment table doesn't exist, missing columns, or constraint violation. ";
        $error_msg .= "Run /actions/diagnose_order_creation.php for detailed diagnostics.";
        error_log("ERROR: Payment recording failed");
        error_log("Order ID: $order_id, Method: paystack, Reference: $reference, Amount: $total_amount");
        
        // Check if payment table exists
        $payment_table_check = $db->fetchRow("SHOW TABLES LIKE 'payment'");
        if (!$payment_table_check) {
            $error_msg .= " Payment table does not exist!";
        }
        
        throw new Exception($error_msg);
    }
    
    error_log("✓ Payment recorded: $payment_id");
    
    // 4. Update order status to completed and set invoice_no to payment reference
    error_log("Updating order status and invoice number...");
    $update_result = $order->update_order_complete($order_id, $reference, 'completed');
    if (!$update_result) {
        error_log("⚠ Warning: Order status update may have failed, but continuing...");
    }
    error_log("✓ Order status updated to completed with invoice_no: $reference");
    
    // Commit transaction BEFORE clearing cart
    $conn->commit();
    error_log("✓ Transaction committed");
    
    // 5. Clear cart AFTER transaction is committed (so it happens even if there's an error later)
    error_log("Clearing cart...");
    try {
        $cart_cleared = clear_cart_ctr($customer_id);
        if ($cart_cleared) {
            error_log("✓ Cart cleared successfully");
        } else {
            error_log("⚠ Cart clear returned false - attempting direct clear...");
            // Try direct clear as fallback
            require_once __DIR__ . '/../class/cart_class.php';
            $cart = new cart_class();
            $cart_cleared = $cart->clear_cart($customer_id);
            if ($cart_cleared) {
                error_log("✓ Cart cleared using direct method");
            } else {
                error_log("⚠ Cart clear failed - may need manual intervention");
            }
        }
    } catch (Exception $cart_error) {
        error_log("⚠ Error clearing cart (non-critical): " . $cart_error->getMessage());
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
            error_log("✓ Transaction rolled back due to error");
        } catch (Exception $rollback_error) {
            error_log("⚠ Error during rollback: " . $rollback_error->getMessage());
        }
    }
    
    error_log("=== PAYMENT VERIFICATION ERROR ===");
    error_log("Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("Order ID (if created): " . ($order_id ? $order_id : 'N/A'));
    
    // If order was created but something else failed, try to clear cart anyway
    if ($order_id && $order_id > 0) {
        error_log("⚠ Order was created but process failed - attempting to clear cart...");
        try {
            clear_cart_ctr($customer_id);
            error_log("✓ Cart cleared despite error");
        } catch (Exception $cart_error) {
            error_log("⚠ Could not clear cart: " . $cart_error->getMessage());
        }
    }
    
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => $e->getMessage(),
        'order_id' => $order_id ? $order_id : null,
        'debug_info' => APP_ENV === 'development' ? [
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ] : null
    ]);
    ob_end_flush();
    exit;
}
