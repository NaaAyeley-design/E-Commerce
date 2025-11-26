<?php
/**
 * SIMPLIFIED Paystack Payment Verification
 * This is a diagnostic version to identify the exact failure point
 */

ob_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';
require_once __DIR__ . '/../controller/cart_controller.php';
require_once __DIR__ . '/../controller/order_controller.php';
require_once __DIR__ . '/../class/user_class.php';
require_once __DIR__ . '/../class/order_class.php';

ob_clean();
header('Content-Type: application/json');

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Check login
if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'] ?? null;
$total_amount = floatval($input['total_amount'] ?? 0);

if (!$reference) {
    echo json_encode(['status' => 'error', 'message' => 'No reference provided']);
    exit;
}

$debug_info = [
    'step' => 'start',
    'reference' => $reference,
    'customer_id' => get_user_id(),
    'total_amount' => $total_amount
];

try {
    // STEP 1: Verify with Paystack
    $debug_info['step'] = 'calling_paystack';
    $verification_response = paystack_verify_transaction($reference);
    
    $debug_info['paystack_response_received'] = !empty($verification_response);
    $debug_info['response_type'] = gettype($verification_response);
    $debug_info['response_status'] = $verification_response['status'] ?? 'NOT_SET';
    $debug_info['response_message'] = $verification_response['message'] ?? 'NOT_SET';
    
    // Check if verification succeeded
    if (!is_array($verification_response)) {
        throw new Exception("Invalid response from Paystack: " . gettype($verification_response));
    }
    
    // Check status - be VERY flexible
    $has_status_true = (
        isset($verification_response['status']) && 
        ($verification_response['status'] === true || $verification_response['status'] === 1)
    );
    
    $has_data = isset($verification_response['data']) && !empty($verification_response['data']);
    
    $debug_info['has_status_true'] = $has_status_true;
    $debug_info['has_data'] = $has_data;
    
    // If no status and no data, fail
    if (!$has_status_true && !$has_data) {
        throw new Exception("Payment verification failed: " . ($verification_response['message'] ?? 'Unknown error'));
    }
    
    // Extract transaction data
    $transaction_data = $verification_response['data'] ?? [];
    $payment_status = strtolower($transaction_data['status'] ?? '');
    $amount_paid = isset($transaction_data['amount']) ? ($transaction_data['amount'] / 100) : 0;
    
    $debug_info['step'] = 'extracted_data';
    $debug_info['payment_status'] = $payment_status;
    $debug_info['amount_paid'] = $amount_paid;
    
    // Check payment status
    if (!in_array($payment_status, ['success', 'successful', 'completed'])) {
        throw new Exception("Payment not successful. Status: " . $payment_status);
    }
    
    // Verify amount
    if (abs($amount_paid - $total_amount) > 0.01) {
        throw new Exception("Amount mismatch. Expected: $total_amount, Paid: $amount_paid");
    }
    
    $debug_info['step'] = 'creating_order';
    
    // STEP 2: Create order
    $customer_id = get_user_id();
    $cart_items = get_cart_items_ctr($customer_id);
    
    if (empty($cart_items)) {
        throw new Exception("Cart is empty");
    }
    
    // Calculate total
    $order_total = 0;
    foreach ($cart_items as $item) {
        $order_total += ($item['product_price'] * ($item['quantity'] ?? 1));
    }
    
    // Get customer data
    $user = new user_class();
    $customer_data = $user->get_customer_by_id($customer_id);
    
    if (!$customer_data) {
        throw new Exception("Customer data not found");
    }
    
    $shipping_address = sprintf(
        "%s, %s, %s",
        $customer_data['customer_city'] ?? 'N/A',
        $customer_data['customer_country'] ?? 'N/A',
        $customer_data['customer_contact'] ?? 'N/A'
    );
    
    // Initialize order class
    $order = new order_class();
    $conn = $order->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // Create order
        $order_id = $order->create_order($customer_id, $order_total, $shipping_address, 'pending');
        
        if (!$order_id || $order_id <= 0) {
            throw new Exception("Failed to create order");
        }
        
        $debug_info['order_id'] = $order_id;
        $debug_info['step'] = 'adding_items';
        
        // Add order items
        foreach ($cart_items as $item) {
            $item_added = $order->add_order_item(
                $order_id,
                $item['product_id'],
                $item['quantity'] ?? 1,
                $item['product_price']
            );
            
            if (!$item_added) {
                throw new Exception("Failed to add order item: " . $item['product_id']);
            }
        }
        
        $debug_info['step'] = 'recording_payment';
        
        // Record payment
        $payment_id = $order->record_payment(
            $total_amount,
            $customer_id,
            $order_id,
            'GHS',
            date('Y-m-d H:i:s'),
            'paystack',
            $reference,
            $transaction_data['authorization']['authorization_code'] ?? null,
            $transaction_data['authorization']['channel'] ?? 'card'
        );
        
        if (!$payment_id || $payment_id <= 0) {
            throw new Exception("Failed to record payment");
        }
        
        $debug_info['payment_id'] = $payment_id;
        $debug_info['step'] = 'updating_order';
        
        // Update order status
        $order_updated = $order->update_order_complete($order_id, $reference, 'completed');
        
        if (!$order_updated) {
            // Try just status update
            $order->update_order_status($order_id, 'completed');
        }
        
        $debug_info['step'] = 'committing';
        
        // Commit
        $conn->commit();
        
        $debug_info['step'] = 'clearing_cart';
        
        // Clear cart
        clear_cart_ctr($customer_id);
        
        // Clear session
        unset($_SESSION['paystack_ref']);
        unset($_SESSION['paystack_amount']);
        
        $debug_info['step'] = 'success';
        
        // Success
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'verified' => true,
            'message' => 'Payment successful! Order confirmed.',
            'order_id' => $order_id,
            'invoice_no' => $reference,
            'debug' => $debug_info
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $debug_info['error'] = $e->getMessage();
    $debug_info['error_trace'] = $e->getTraceAsString();
    
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => $e->getMessage(),
        'debug' => $debug_info
    ]);
    exit;
}

