<?php
/**
 * PAYSTACK PAYMENT VERIFICATION - DIAGNOSTIC VERSION
 * 
 * This diagnostic version logs EVERY step to identify exactly where verification fails.
 * Use this temporarily to debug payment verification issues.
 * 
 * After identifying the issue, fix the main verification file and remove this diagnostic version.
 */

// Start output buffering
ob_start();

// Initialize diagnostic tracking
$diagnostic = [
    'timestamp' => date('Y-m-d H:i:s'),
    'steps' => [],
    'errors' => [],
    'success' => false,
    'reference' => null,
    'paystack_api_called' => false,
    'paystack_response_status' => null,
    'payment_verified' => false,
    'database_connected' => false,
    'payment_inserted' => false,
    'order_created' => false,
    'order_items_added' => false,
    'order_updated' => false
];

function log_diagnostic($message, $data = null) {
    global $diagnostic;
    $log_entry = date('H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $log_entry .= ' | Data: ' . json_encode($data);
    }
    error_log($log_entry);
    $diagnostic['steps'][] = $log_entry;
}

log_diagnostic("=== PAYSTACK VERIFICATION DIAGNOSTIC START ===");
log_diagnostic("Timestamp", $diagnostic['timestamp']);

// Include core settings and Paystack config
log_diagnostic("STEP 1: Including core files...");
try {
    require_once __DIR__ . '/../settings/core.php';
    log_diagnostic("✓ core.php included");
} catch (Exception $e) {
    log_diagnostic("✗ core.php failed: " . $e->getMessage());
    $diagnostic['errors'][] = "core.php include failed: " . $e->getMessage();
}

try {
    require_once __DIR__ . '/../settings/paystack_config.php';
    log_diagnostic("✓ paystack_config.php included");
} catch (Exception $e) {
    log_diagnostic("✗ paystack_config.php failed: " . $e->getMessage());
    $diagnostic['errors'][] = "paystack_config.php include failed: " . $e->getMessage();
}

try {
    require_once __DIR__ . '/../controller/cart_controller.php';
    require_once __DIR__ . '/../controller/order_controller.php';
    require_once __DIR__ . '/../class/user_class.php';
    require_once __DIR__ . '/../class/order_class.php';
    log_diagnostic("✓ All controller and class files included");
} catch (Exception $e) {
    log_diagnostic("✗ Controller/class files failed: " . $e->getMessage());
    $diagnostic['errors'][] = "Controller/class include failed: " . $e->getMessage();
}

// Clear output buffer
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// STEP 2: Check request method
log_diagnostic("STEP 2: Checking request method...");
log_diagnostic("Request method", $_SERVER['REQUEST_METHOD']);
log_diagnostic("GET parameters", $_GET);
log_diagnostic("POST parameters", $_POST);
log_diagnostic("php://input", file_get_contents('php://input'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_diagnostic("✗ Invalid request method");
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

// STEP 3: Check user login
log_diagnostic("STEP 3: Checking user authentication...");
if (!function_exists('is_logged_in')) {
    log_diagnostic("✗ is_logged_in() function not found");
    $diagnostic['errors'][] = "is_logged_in() function not found";
} else {
    $is_logged_in = is_logged_in();
    log_diagnostic("User logged in", $is_logged_in);
    if (!$is_logged_in) {
        log_diagnostic("✗ User not logged in");
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Session expired',
            'diagnostic' => $diagnostic
        ]);
        exit;
    }
    log_diagnostic("User ID", get_user_id());
}

// STEP 4: Parse input data
log_diagnostic("STEP 4: Parsing input data...");
$input = json_decode(file_get_contents('php://input'), true);
log_diagnostic("Raw input", file_get_contents('php://input'));
log_diagnostic("Parsed input", $input);

$reference = isset($input['reference']) ? trim($input['reference']) : null;
$cart_items = isset($input['cart_items']) ? $input['cart_items'] : null;
$total_amount = isset($input['total_amount']) ? floatval($input['total_amount']) : 0;

$diagnostic['reference'] = $reference;
log_diagnostic("Reference extracted", $reference);
log_diagnostic("Total amount extracted", $total_amount);

if (!$reference) {
    log_diagnostic("✗ No reference provided");
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No payment reference provided',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

// STEP 5: Check Paystack configuration
log_diagnostic("STEP 5: Checking Paystack configuration...");
$secret_key_configured = defined('PAYSTACK_SECRET_KEY') && 
                        PAYSTACK_SECRET_KEY !== 'sk_test_YOUR_SECRET_KEY_HERE' && 
                        PAYSTACK_SECRET_KEY !== '' &&
                        !empty(PAYSTACK_SECRET_KEY);

log_diagnostic("Secret key defined", defined('PAYSTACK_SECRET_KEY'));
if (defined('PAYSTACK_SECRET_KEY')) {
    $key_prefix = substr(PAYSTACK_SECRET_KEY, 0, 10) . '...';
    log_diagnostic("Secret key prefix", $key_prefix);
    log_diagnostic("Secret key starts with sk_test", strpos(PAYSTACK_SECRET_KEY, 'sk_test_') === 0);
    log_diagnostic("Secret key length", strlen(PAYSTACK_SECRET_KEY));
}

if (!$secret_key_configured) {
    log_diagnostic("✗ Secret key not configured properly");
    $diagnostic['errors'][] = "Paystack secret key not configured";
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Payment gateway not configured',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

log_diagnostic("✓ Paystack configuration OK");

// STEP 6: Check database connection
log_diagnostic("STEP 6: Testing database connection...");
try {
    $order = new order_class();
    $conn = $order->getConnection();
    
    if ($conn) {
        $diagnostic['database_connected'] = true;
        log_diagnostic("✓ Database connected");
        log_diagnostic("Database connection type", get_class($conn));
    } else {
        log_diagnostic("✗ Database connection FAILED");
        $diagnostic['errors'][] = "Database connection failed";
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'diagnostic' => $diagnostic
        ]);
        exit;
    }
} catch (Exception $e) {
    log_diagnostic("✗ Database connection exception: " . $e->getMessage());
    $diagnostic['errors'][] = "Database connection exception: " . $e->getMessage();
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

// STEP 7: Call Paystack API
log_diagnostic("STEP 7: Calling Paystack verification API...");
$verify_url = PAYSTACK_VERIFY_ENDPOINT . $reference;
log_diagnostic("API URL", $verify_url);

try {
    $diagnostic['paystack_api_called'] = true;
    log_diagnostic("Calling paystack_verify_transaction()...");
    
    $verification_response = paystack_verify_transaction($reference);
    
    log_diagnostic("API call completed");
    log_diagnostic("Response type", gettype($verification_response));
    log_diagnostic("Response is array", is_array($verification_response));
    
    if (is_array($verification_response)) {
        log_diagnostic("Response keys", array_keys($verification_response));
        log_diagnostic("Response status", $verification_response['status'] ?? 'NOT SET');
        log_diagnostic("Response message", $verification_response['message'] ?? 'NOT SET');
        
        // Log full response (truncated for large responses)
        $response_json = json_encode($verification_response, JSON_PRETTY_PRINT);
        if (strlen($response_json) > 5000) {
            log_diagnostic("Full response (truncated)", substr($response_json, 0, 5000) . '...');
        } else {
            log_diagnostic("Full response", $verification_response);
        }
        
        if (isset($verification_response['data'])) {
            log_diagnostic("Response has data key", true);
            if (is_array($verification_response['data'])) {
                log_diagnostic("Data keys", array_keys($verification_response['data']));
                log_diagnostic("Transaction status", $verification_response['data']['status'] ?? 'NOT SET');
                log_diagnostic("Transaction amount (pesewas)", $verification_response['data']['amount'] ?? 'NOT SET');
                log_diagnostic("Gateway response", $verification_response['data']['gateway_response'] ?? 'NOT SET');
            }
        } else {
            log_diagnostic("Response has NO data key");
        }
    } else {
        log_diagnostic("✗ Response is not an array", gettype($verification_response));
        $diagnostic['errors'][] = "Paystack response is not an array: " . gettype($verification_response);
    }
    
    if (!$verification_response) {
        log_diagnostic("✗ No response from Paystack API");
        $diagnostic['errors'][] = "No response from Paystack API";
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'No response from payment gateway',
            'diagnostic' => $diagnostic
        ]);
        exit;
    }
    
} catch (Exception $e) {
    log_diagnostic("✗ Paystack API call exception: " . $e->getMessage());
    log_diagnostic("Exception trace", $e->getTraceAsString());
    $diagnostic['errors'][] = "Paystack API exception: " . $e->getMessage();
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Payment gateway error: ' . $e->getMessage(),
        'diagnostic' => $diagnostic
    ]);
    exit;
}

// STEP 8: Verify payment status
log_diagnostic("STEP 8: Verifying payment status...");

if (!is_array($verification_response)) {
    log_diagnostic("✗ Response is not an array");
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid response from payment gateway',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

// Check response status
$response_status = $verification_response['status'] ?? null;
$is_success = (
    isset($verification_response['status']) && 
    (
        $verification_response['status'] === true || 
        $verification_response['status'] === 1
    )
);

$has_data = isset($verification_response['data']) && is_array($verification_response['data']) && !empty($verification_response['data']);

log_diagnostic("Response status check", [
    'status' => $response_status,
    'is_success' => $is_success,
    'has_data' => $has_data
]);

if (!$is_success && !$has_data) {
    log_diagnostic("✗ Payment verification failed - no success status and no data");
    $error_msg = $verification_response['message'] ?? 'Payment verification failed';
    log_diagnostic("Error message", $error_msg);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $error_msg,
        'diagnostic' => $diagnostic
    ]);
    exit;
}

// Extract transaction data
$transaction_data = $verification_response['data'] ?? [];

if (empty($transaction_data)) {
    log_diagnostic("✗ No transaction data in response");
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Transaction data not found',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

$payment_status = strtolower($transaction_data['status'] ?? '');
$amount_paid = isset($transaction_data['amount']) ? ($transaction_data['amount'] / 100) : 0; // Convert pesewas to cedis
$gateway_response = $transaction_data['gateway_response'] ?? null;

log_diagnostic("Transaction details", [
    'payment_status' => $payment_status,
    'amount_paid' => $amount_paid,
    'gateway_response' => $gateway_response
]);

// Check if payment is successful
$is_payment_successful = in_array($payment_status, ['success', 'successful', 'completed']);

if (!$is_payment_successful && $gateway_response) {
    $gateway_lower = strtolower(trim($gateway_response));
    $is_payment_successful = in_array($gateway_lower, ['successful', 'approved', 'success']);
}

log_diagnostic("Payment successful check", $is_payment_successful);

if (!$is_payment_successful) {
    log_diagnostic("✗ Payment status is not successful", $payment_status);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Payment was not successful. Status: ' . ucfirst($payment_status),
        'diagnostic' => $diagnostic
    ]);
    exit;
}

$diagnostic['payment_verified'] = true;
log_diagnostic("✓ Payment verified as successful");

// STEP 9: Verify amount
log_diagnostic("STEP 9: Verifying payment amount...");
log_diagnostic("Amount comparison", [
    'expected' => $total_amount,
    'paid' => $amount_paid,
    'difference' => abs($amount_paid - $total_amount)
]);

if (abs($amount_paid - $total_amount) > 0.01) {
    log_diagnostic("✗ Amount mismatch");
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Payment amount mismatch',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

log_diagnostic("✓ Amount verified");

// STEP 10: Get cart items
log_diagnostic("STEP 10: Getting cart items...");
$customer_id = get_user_id();

if (!$cart_items || count($cart_items) == 0) {
    log_diagnostic("Fetching cart items from database...");
    $cart_items = get_cart_items_ctr($customer_id);
}

log_diagnostic("Cart items count", count($cart_items ?? []));

if (!$cart_items || count($cart_items) == 0) {
    log_diagnostic("✗ Cart is empty");
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Cart is empty',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

// Calculate order total
if ($total_amount <= 0) {
    $total_amount = get_cart_total_ctr($customer_id);
    log_diagnostic("Calculated total from cart", $total_amount);
}

// STEP 11: Get customer data
log_diagnostic("STEP 11: Getting customer data...");
$user = new user_class();
$customer_data = $user->get_customer_by_id($customer_id);

if (!$customer_data) {
    log_diagnostic("✗ Customer data not found");
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Customer data not found',
        'diagnostic' => $diagnostic
    ]);
    exit;
}

log_diagnostic("✓ Customer data retrieved");

$shipping_address = sprintf(
    "%s, %s, %s, %s",
    $customer_data['customer_city'] ?? 'N/A',
    $customer_data['customer_country'] ?? 'N/A',
    $customer_data['customer_contact'] ?? 'N/A',
    $customer_data['customer_email'] ?? ''
);

log_diagnostic("Shipping address", $shipping_address);

// STEP 12: Database transaction - Create order
log_diagnostic("STEP 12: Starting database transaction...");

try {
    $conn->beginTransaction();
    log_diagnostic("✓ Transaction started");
    
    // Create order
    log_diagnostic("Creating order...");
    log_diagnostic("Order parameters", [
        'customer_id' => $customer_id,
        'total_amount' => $total_amount,
        'shipping_address' => $shipping_address
    ]);
    
    $order_id = $order->create_order($customer_id, $total_amount, $shipping_address, 'pending');
    
    if (!$order_id || $order_id <= 0) {
        log_diagnostic("✗ Order creation failed", $order_id);
        throw new Exception("Failed to create order. Returned: " . var_export($order_id, true));
    }
    
    $diagnostic['order_created'] = true;
    log_diagnostic("✓ Order created", ['order_id' => $order_id]);
    
    // Add order items
    log_diagnostic("Adding order items...");
    $items_added = 0;
    foreach ($cart_items as $item) {
        log_diagnostic("Adding item", [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'] ?? 1,
            'price' => $item['product_price']
        ]);
        
        $item_added = $order->add_order_item(
            $order_id,
            $item['product_id'],
            $item['quantity'] ?? 1,
            $item['product_price']
        );
        
        if (!$item_added) {
            log_diagnostic("✗ Failed to add order item", $item['product_id']);
            throw new Exception("Failed to add order item: " . $item['product_id']);
        }
        $items_added++;
    }
    
    $diagnostic['order_items_added'] = true;
    log_diagnostic("✓ Order items added", ['count' => $items_added]);
    
    // Record payment
    log_diagnostic("Recording payment...");
    $payment_date = date('Y-m-d H:i:s');
    $authorization_code = $transaction_data['authorization']['authorization_code'] ?? null;
    $payment_channel = $transaction_data['authorization']['channel'] ?? 'card';
    
    log_diagnostic("Payment parameters", [
        'amount' => $total_amount,
        'customer_id' => $customer_id,
        'order_id' => $order_id,
        'reference' => $reference,
        'authorization_code' => $authorization_code,
        'payment_channel' => $payment_channel
    ]);
    
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
        log_diagnostic("✗ Payment recording failed", $payment_id);
        throw new Exception("Failed to record payment. Returned: " . var_export($payment_id, true));
    }
    
    $diagnostic['payment_inserted'] = true;
    log_diagnostic("✓ Payment recorded", ['payment_id' => $payment_id]);
    
    // Update order status
    log_diagnostic("Updating order status...");
    $order_updated = $order->update_order_complete($order_id, $reference, 'completed');
    
    if (!$order_updated) {
        log_diagnostic("update_order_complete failed, trying update_order_status...");
        $order->update_order_status($order_id, 'completed');
    }
    
    $diagnostic['order_updated'] = true;
    log_diagnostic("✓ Order status updated");
    
    // Commit transaction
    log_diagnostic("Committing transaction...");
    $conn->commit();
    log_diagnostic("✓ Transaction committed");
    
    // Clear cart
    log_diagnostic("Clearing cart...");
    clear_cart_ctr($customer_id);
    log_diagnostic("✓ Cart cleared");
    
    // Clear session
    unset($_SESSION['paystack_ref']);
    unset($_SESSION['paystack_amount']);
    log_diagnostic("✓ Session cleared");
    
    $diagnostic['success'] = true;
    log_diagnostic("=== PAYSTACK VERIFICATION DIAGNOSTIC SUCCESS ===");
    
    // Return success
    ob_clean();
    echo json_encode([
        'success' => true,
        'verified' => true,
        'message' => 'Payment successful! Order confirmed.',
        'order_id' => $order_id,
        'invoice_no' => $reference,
        'diagnostic' => $diagnostic
    ]);
    exit;
    
} catch (Exception $e) {
    log_diagnostic("✗ Transaction error: " . $e->getMessage());
    log_diagnostic("Exception trace", $e->getTraceAsString());
    $diagnostic['errors'][] = "Transaction error: " . $e->getMessage();
    
    try {
        $conn->rollBack();
        log_diagnostic("✓ Transaction rolled back");
    } catch (Exception $rollback_error) {
        log_diagnostic("✗ Rollback failed: " . $rollback_error->getMessage());
    }
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'verified' => false,
        'message' => 'Payment processing error: ' . $e->getMessage(),
        'diagnostic' => $diagnostic
    ]);
    exit;
}

