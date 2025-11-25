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
    error_log("Verifying Paystack transaction - Reference: $reference");
    
    // Verify transaction with Paystack
    $verification_response = paystack_verify_transaction($reference);
    
    if (!$verification_response) {
        throw new Exception("No response from Paystack verification API");
    }
    
    error_log("Paystack verification response: " . json_encode($verification_response));
    
    // Check if verification was successful
    if (!isset($verification_response['status']) || $verification_response['status'] !== true) {
        $error_msg = $verification_response['message'] ?? 'Payment verification failed';
        error_log("Payment verification failed: $error_msg");
        
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => $error_msg,
            'verified' => false
        ]);
        ob_end_flush();
        exit;
    }
    
    // Extract transaction data
    $transaction_data = $verification_response['data'] ?? [];
    $payment_status = $transaction_data['status'] ?? null;
    $amount_paid = isset($transaction_data['amount']) ? $transaction_data['amount'] / 100 : 0; // Convert from pesewas
    $customer_email = $transaction_data['customer']['email'] ?? '';
    $authorization = $transaction_data['authorization'] ?? [];
    $authorization_code = $authorization['authorization_code'] ?? '';
    $payment_method_channel = $authorization['channel'] ?? 'card';
    
    error_log("Transaction status: $payment_status, Amount: $amount_paid GHS");
    
    // Validate payment status
    if ($payment_status !== 'success') {
        error_log("Payment status is not successful: $payment_status");
        
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment was not successful. Status: ' . ucfirst($payment_status),
            'verified' => false,
            'payment_status' => $payment_status
        ]);
        ob_end_flush();
        exit;
    }
    
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

