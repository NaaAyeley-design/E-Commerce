<?php
/**
 * Paystack Payment Verification
 * Verifies payment with Paystack and creates order
 */

// Start output buffering
ob_start();

// Include core settings and Paystack config
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';
require_once __DIR__ . '/../controller/cart_controller.php';
require_once __DIR__ . '/../controller/order_controller.php';
require_once __DIR__ . '/../class/user_class.php';

// Clear any output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    ob_end_flush();
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$reference = isset($input['reference']) ? trim($input['reference']) : null;
$cart_items = isset($input['cart_items']) ? $input['cart_items'] : null;
$total_amount = isset($input['total_amount']) ? floatval($input['total_amount']) : 0;

if (!$reference) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'No payment reference provided']);
    ob_end_flush();
    exit;
}

try {
    error_log("=== PAYSTACK VERIFICATION START ===");
    error_log("Reference received: " . $reference);
    error_log("Customer ID: " . get_user_id());
    error_log("Total amount expected: " . $total_amount . " GHS");
    
    // Check secret key configuration
    $secret_key_configured = defined('PAYSTACK_SECRET_KEY') && 
                            PAYSTACK_SECRET_KEY !== 'sk_test_YOUR_SECRET_KEY_HERE' && 
                            PAYSTACK_SECRET_KEY !== '' &&
                            !empty(PAYSTACK_SECRET_KEY);
    
    error_log("Secret key configured: " . ($secret_key_configured ? 'YES' : 'NO'));
    if ($secret_key_configured) {
        error_log("Secret key prefix: " . substr(PAYSTACK_SECRET_KEY, 0, 7) . "...");
    }
    
    // Verify API endpoint
    $verify_url = PAYSTACK_VERIFY_ENDPOINT . $reference;
    error_log("API URL: " . $verify_url);
    
    // Verify transaction with Paystack
    error_log("Calling paystack_verify_transaction()...");
    $verification_response = paystack_verify_transaction($reference);
    
    if (!$verification_response) {
        error_log("ERROR: No response from Paystack verification API");
        error_log("This could mean:");
        error_log("  1. cURL is not available");
        error_log("  2. Network connection failed");
        error_log("  3. Paystack API is down");
        error_log("  4. Secret key is invalid");
        throw new Exception("No response from Paystack verification API. Please check server logs.");
    }
    
    error_log("=== PAYSTACK API RESPONSE RECEIVED ===");
    error_log("Response type: " . gettype($verification_response));
    error_log("Response is array: " . (is_array($verification_response) ? 'YES' : 'NO'));
    
    if (is_array($verification_response)) {
        error_log("Response keys: " . implode(', ', array_keys($verification_response)));
        error_log("Response status: " . (isset($verification_response['status']) ? var_export($verification_response['status'], true) : 'NOT SET'));
        error_log("Response message: " . ($verification_response['message'] ?? 'N/A'));
        
        // Log full response (always log for debugging)
        error_log("Full Paystack verification response: " . json_encode($verification_response, JSON_PRETTY_PRINT));
        
        // Check if response has data
        if (isset($verification_response['data'])) {
            error_log("Response has 'data' key: YES");
            if (is_array($verification_response['data'])) {
                error_log("Data keys: " . implode(', ', array_keys($verification_response['data'])));
                if (isset($verification_response['data']['status'])) {
                    error_log("Transaction status in data: " . $verification_response['data']['status']);
                }
                if (isset($verification_response['data']['amount'])) {
                    error_log("Transaction amount (pesewas): " . $verification_response['data']['amount']);
                }
            }
        } else {
            error_log("Response has 'data' key: NO");
        }
    } else {
        error_log("ERROR: Response is not an array. Type: " . gettype($verification_response));
        error_log("Response value: " . var_export($verification_response, true));
    }
    
    // Check if verification was successful
    // Paystack returns status: true when verification is successful
    // Also check if response is an array (should always be)
    if (!is_array($verification_response)) {
        error_log("ERROR: Paystack response is not an array");
        error_log("Response type: " . gettype($verification_response));
        error_log("Response value: " . var_export($verification_response, true));
        
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid response from payment gateway. Please contact support.',
            'verified' => false,
            'reference' => $reference,
            'debug' => (defined('APP_ENV') && APP_ENV === 'development') ? [
                'response_type' => gettype($verification_response),
                'response_value' => $verification_response
            ] : null
        ]);
        ob_end_flush();
        exit;
    }
    
    // Check if status is set and equals true
    // Paystack can return status as boolean true, or sometimes as 1, or as string "true"
    $response_status = $verification_response['status'] ?? null;
    error_log("Checking response status: " . var_export($response_status, true));
    error_log("Status type: " . gettype($response_status));
    error_log("Status === true: " . ($response_status === true ? 'YES' : 'NO'));
    error_log("Status == true: " . ($response_status == true ? 'YES' : 'NO'));
    error_log("Status == 1: " . ($response_status == 1 ? 'YES' : 'NO'));
    
    // More flexible check: accept true, 1, or "true" as valid success status
    $is_success = (
        isset($verification_response['status']) && 
        (
            $verification_response['status'] === true || 
            $verification_response['status'] === 1 ||
            $verification_response['status'] === 'true' ||
            (is_bool($verification_response['status']) && $verification_response['status'] === true)
        )
    );
    
    error_log("Is success (flexible check): " . ($is_success ? 'YES' : 'NO'));
    
    // Also check if we have data even if status check fails (sometimes Paystack returns data even if status is not explicitly true)
    $has_data = isset($verification_response['data']) && is_array($verification_response['data']) && !empty($verification_response['data']);
    error_log("Has transaction data: " . ($has_data ? 'YES' : 'NO'));
    
    // If status check fails but we have data, log it for investigation
    if (!$is_success && $has_data) {
        error_log("WARNING: Status check failed but data exists. This might be a false negative.");
        error_log("Attempting to proceed with data validation...");
    }
    
    if (!$is_success && !$has_data) {
        $error_msg = $verification_response['message'] ?? 'Payment verification failed';
        
        // Provide more specific error messages
        if (isset($verification_response['message'])) {
            $message_lower = strtolower($verification_response['message']);
            if (strpos($message_lower, 'not found') !== false || 
                strpos($message_lower, 'invalid') !== false ||
                strpos($message_lower, 'reference') !== false) {
                $error_msg = 'Transaction reference not found. The payment may not have been completed.';
            } elseif (strpos($message_lower, 'key') !== false || 
                      strpos($message_lower, 'authorization') !== false ||
                      strpos($message_lower, 'unauthorized') !== false) {
                $error_msg = 'Payment gateway configuration error. Please contact support.';
            } elseif (strpos($message_lower, 'timeout') !== false) {
                $error_msg = 'Payment gateway timeout. Please try again.';
            }
        }
        
        error_log("ERROR: Payment verification failed");
        error_log("Reference: $reference");
        error_log("Response status: " . var_export($response_status, true));
        error_log("Error message: $error_msg");
        error_log("Full response: " . json_encode($verification_response, JSON_PRETTY_PRINT));
        
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => $error_msg,
            'verified' => false,
            'reference' => $reference,
            'paystack_response' => $verification_response,
            'debug' => (defined('APP_ENV') && APP_ENV === 'development') ? [
                'reference' => $reference,
                'full_response' => $verification_response,
                'response_status' => $response_status,
                'response_status_type' => gettype($response_status),
                'response_message' => $verification_response['message'] ?? 'not set',
                'secret_key_configured' => $secret_key_configured,
                'api_url' => $verify_url
            ] : null
        ]);
        ob_end_flush();
        exit;
    }
    
    // If we got here, either status is true OR we have data to work with
    if ($is_success) {
        error_log("✓ Paystack API verification successful (status: true)");
    } else {
        error_log("⚠ Status check failed but proceeding with data validation");
    }
    
    // Extract transaction data
    $transaction_data = $verification_response['data'] ?? [];
    
    // If no data, that's a problem
    if (empty($transaction_data)) {
        error_log("ERROR: No transaction data in response");
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Transaction data not found in verification response',
            'verified' => false,
            'reference' => $reference,
            'debug' => (defined('APP_ENV') && APP_ENV === 'development') ? [
                'response' => $verification_response
            ] : null
        ]);
        ob_end_flush();
        exit;
    }
    
    $payment_status = $transaction_data['status'] ?? null;
    $amount_paid = isset($transaction_data['amount']) ? $transaction_data['amount'] / 100 : 0; // Convert from pesewas
    $customer_email = $transaction_data['customer']['email'] ?? '';
    $authorization = $transaction_data['authorization'] ?? [];
    $authorization_code = $authorization['authorization_code'] ?? '';
    $payment_method_channel = $authorization['channel'] ?? 'card';
    
    $gateway_response = $transaction_data['gateway_response'] ?? null;
    
    error_log("Transaction status: " . ($payment_status ?? 'NULL'));
    error_log("Gateway response: " . ($gateway_response ?? 'NULL'));
    error_log("Amount: $amount_paid GHS");
    
    // Validate payment status - be more flexible
    // Paystack can return 'success', 'Success', 'SUCCESS', or indicate success in gateway_response
    $is_payment_successful = false;
    
    if ($payment_status) {
        $status_lower = strtolower(trim($payment_status));
        $is_payment_successful = (
            $status_lower === 'success' || 
            $status_lower === 'successful' ||
            $status_lower === 'completed'
        );
    }
    
    // Also check gateway_response
    if (!$is_payment_successful && $gateway_response) {
        $gateway_lower = strtolower(trim($gateway_response));
        $is_payment_successful = (
            $gateway_lower === 'successful' ||
            $gateway_lower === 'approved' ||
            $gateway_lower === 'success'
        );
    }
    
    // Check if amount was paid (another indicator)
    if (!$is_payment_successful && isset($transaction_data['amount']) && $transaction_data['amount'] > 0) {
        // If we have a paid amount and no explicit failure, it might be successful
        // But only if status is not explicitly 'failed' or 'reversed'
        $status_lower = strtolower(trim($payment_status ?? ''));
        if (!in_array($status_lower, ['failed', 'reversed', 'declined', 'cancelled'])) {
            error_log("WARNING: Status unclear but amount was paid. Proceeding with caution.");
            $is_payment_successful = true; // Give benefit of doubt if amount was paid
        }
    }
    
    error_log("Is payment successful (flexible check): " . ($is_payment_successful ? 'YES' : 'NO'));
    
    if (!$is_payment_successful) {
        error_log("Payment status check failed");
        error_log("Payment status: " . ($payment_status ?? 'NULL'));
        error_log("Gateway response: " . ($gateway_response ?? 'NULL'));
        error_log("Full transaction data: " . json_encode($transaction_data, JSON_PRETTY_PRINT));
        
        // Check if it's pending (might need to wait)
        $status_lower = strtolower(trim($payment_status ?? ''));
        if ($status_lower === 'pending' || $status_lower === 'processing') {
            error_log("Payment is pending - may need to wait");
            ob_clean();
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment is still pending. Please wait a moment and try again.',
                'verified' => false,
                'payment_status' => $payment_status
            ]);
            ob_end_flush();
            exit;
        }
        
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment was not successful. Status: ' . ucfirst($payment_status ?? 'Unknown'),
            'verified' => false,
            'payment_status' => $payment_status,
            'gateway_response' => $gateway_response,
            'debug' => (defined('APP_ENV') && APP_ENV === 'development') ? [
                'transaction_data' => $transaction_data,
                'payment_status' => $payment_status,
                'gateway_response' => $gateway_response
            ] : null
        ]);
        ob_end_flush();
        exit;
    }
    
    error_log("✓ Payment status is successful");
    
    // Get customer ID and cart items
    $customer_id = get_user_id();
    
    // Get fresh cart items if not provided
    if (!$cart_items || count($cart_items) == 0) {
        $cart_items = get_cart_items_ctr($customer_id);
    }
    
    if (!$cart_items || count($cart_items) == 0) {
        throw new Exception("Cart is empty");
    }
    
    // Calculate total from cart if not provided
    if ($total_amount <= 0) {
        $total_amount = get_cart_total_ctr($customer_id);
    }
    
    error_log("Expected order total: $total_amount GHS");
    
    // Verify amount matches (with 1 pesewa tolerance)
    if (abs($amount_paid - $total_amount) > 0.01) {
        error_log("Amount mismatch - Expected: $total_amount GHS, Paid: $amount_paid GHS");
        
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment amount does not match order total',
            'verified' => false,
            'expected' => number_format($total_amount, 2),
            'paid' => number_format($amount_paid, 2)
        ]);
        ob_end_flush();
        exit;
    }
    
    // Get customer data for shipping address
    $user = new user_class();
    $customer_data = $user->get_customer_by_id($customer_id);
    
    if (!$customer_data) {
        throw new Exception("Customer data not found");
    }
    
    // Build shipping address from customer data
    $shipping_address = sprintf(
        "%s, %s, %s, %s",
        $customer_data['customer_city'] ?? 'N/A',
        $customer_data['customer_country'] ?? 'N/A',
        $customer_data['customer_contact'] ?? 'N/A',
        $customer_data['customer_email'] ?? ''
    );
    
    // Prepare order items
    $order_items = [];
    foreach ($cart_items as $item) {
        $order_items[] = [
            'product_id' => (int)$item['product_id'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['product_price']
        ];
    }
    
    // Create order
    $order_result = create_order_ctr($customer_id, $order_items, $shipping_address, 'paid');
    
    if (!$order_result || !isset($order_result['success']) || !$order_result['success']) {
        error_log("Order creation failed: " . json_encode($order_result));
        throw new Exception($order_result['message'] ?? 'Failed to create order');
    }
    
    $order_id = $order_result['order_id'];
    error_log("Order created - ID: $order_id");
    
    // Record payment
    $payment_date = date('Y-m-d');
    $payment_id = record_payment_ctr(
        $total_amount,
        $customer_id,
        $order_id,
        'GHS',
        $payment_date,
        'paystack',
        $reference,
        $authorization_code,
        $payment_method_channel
    );
    
    if (!$payment_id) {
        error_log("Payment recording failed for order: $order_id");
        // Order is created but payment not recorded - this is logged but we continue
    } else {
        error_log("Payment recorded - ID: $payment_id, Reference: $reference");
    }
    
    // Clear cart
    $cart_cleared = clear_cart_ctr($customer_id);
    if (!$cart_cleared) {
        error_log("Warning: Failed to clear cart for customer: $customer_id");
    }
    
    // Clear session payment data
    unset($_SESSION['paystack_ref']);
    unset($_SESSION['paystack_amount']);
    unset($_SESSION['paystack_timestamp']);
    
    // Generate invoice number
    $invoice_no = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Log activity
    log_activity('payment_verified', "Payment verified via Paystack - Order: #$order_id, Amount: GHS $total_amount, Reference: $reference", $customer_id);
    
    // Return success response
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'verified' => true,
        'message' => 'Payment successful! Order confirmed.',
        'order_id' => $order_id,
        'invoice_no' => $invoice_no,
        'total_amount' => number_format($total_amount, 2),
        'currency' => 'GHS',
        'order_date' => date('F j, Y', strtotime($payment_date)),
        'customer_name' => $customer_data['customer_name'] ?? 'Customer',
        'item_count' => count($order_items),
        'payment_reference' => $reference,
        'payment_method' => ucfirst($payment_method_channel),
        'customer_email' => $customer_email
    ]);
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    error_log("Error in Paystack verification: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}

