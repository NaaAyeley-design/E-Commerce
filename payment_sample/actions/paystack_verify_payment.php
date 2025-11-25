<?php
/**
 * Paystack Callback Handler & Verification
 * Handles payment verification after user returns from Paystack gateway
 */

header('Content-Type: application/json');

require_once '../settings/core.php';
require_once '../settings/paystack_config.php';

error_log("=== PAYSTACK CALLBACK/VERIFICATION ===");

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Session expired. Please login again.'
    ]);
    exit();
}

// Get verification reference from POST data
$input = json_decode(file_get_contents('php://input'), true);
$reference = isset($input['reference']) ? trim($input['reference']) : null;
$cart_items = isset($input['cart_items']) ? $input['cart_items'] : null;
$total_amount = isset($input['total_amount']) ? floatval($input['total_amount']) : 0;

if (!$reference) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No payment reference provided'
    ]);
    exit();
}

// Optional: Verify reference matches session
if (isset($_SESSION['paystack_ref']) && $_SESSION['paystack_ref'] !== $reference) {
    error_log("Reference mismatch - Expected: {$_SESSION['paystack_ref']}, Got: $reference");
    // Allow to proceed anyway, but log it
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
        
        echo json_encode([
            'status' => 'error',
            'message' => $error_msg,
            'verified' => false
        ]);
        exit();
    }
    
    // Extract transaction data
    $transaction_data = $verification_response['data'] ?? [];
    $payment_status = $transaction_data['status'] ?? null;
    $amount_paid = isset($transaction_data['amount']) ? $transaction_data['amount'] / 100 : 0; // Convert from pesewas
    $customer_email = $transaction_data['customer']['email'] ?? '';
    $authorization = $transaction_data['authorization'] ?? [];
    $authorization_code = $authorization['authorization_code'] ?? '';
    $payment_method = $authorization['channel'] ?? 'card';
    $auth_last_four = $authorization['last_four'] ?? 'XXXX';
    
    error_log("Transaction status: $payment_status, Amount: $amount_paid GHS");
    
    // Validate payment status
    if ($payment_status !== 'success') {
        error_log("Payment status is not successful: $payment_status");
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment was not successful. Status: ' . ucfirst($payment_status),
            'verified' => false,
            'payment_status' => $payment_status
        ]);
        exit();
    }
    
    // Ensure we have expected total server-side (calculate from cart if frontend didn't send it)
    require_once '../controllers/cart_controller.php';
    if (!$cart_items || count($cart_items) == 0) {
        $cart_items = get_user_cart_ctr(get_user_id());
    }

    $calculated_total = 0.00;
    if ($cart_items && count($cart_items) > 0) {
        foreach ($cart_items as $ci) {
            if (isset($ci['subtotal'])) {
                $calculated_total += floatval($ci['subtotal']);
            } elseif (isset($ci['product_price']) && isset($ci['qty'])) {
                $calculated_total += floatval($ci['product_price']) * intval($ci['qty']);
            }
        }
    }

    if ($total_amount <= 0) {
        $total_amount = round($calculated_total, 2);
    }

    error_log("Expected order total (server): $total_amount GHS");

    // Verify amount matches (with 1 pesewa tolerance)
    if (abs($amount_paid - $total_amount) > 0.01) {
        error_log("Amount mismatch - Expected: $total_amount GHS, Paid: $amount_paid GHS");

        echo json_encode([
            'status' => 'error',
            'message' => 'Payment amount does not match order total',
            'verified' => false,
            'expected' => number_format($total_amount, 2),
            'paid' => number_format($amount_paid, 2)
        ]);
        exit();
    }
    
    // Payment is verified! Now create the order in our system
    require_once '../controllers/cart_controller.php';
    require_once '../controllers/order_controller.php';
    require_once '../settings/db_class.php';
    
    $customer_id = get_user_id();
    $customer_name = get_user_name();
    
    // Get fresh cart items if not provided
    if (!$cart_items || count($cart_items) == 0) {
        $cart_items = get_user_cart_ctr($customer_id);
    }
    
    if (!$cart_items || count($cart_items) == 0) {
        throw new Exception("Cart is empty");
    }
    
    // Create database connection for transaction
    $db = new db_connection();
    $conn = $db->db_conn();
    
    // Begin database transaction
    mysqli_begin_transaction($conn);
    error_log("Database transaction started");
    
    try {
        // Generate invoice number
        $invoice_no = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $order_date = date('Y-m-d');
        
        // Create order in database
        $order_id = create_order_ctr($customer_id, $invoice_no, $order_date, 'Paid');
        
        if (!$order_id) {
            throw new Exception("Failed to create order in database");
        }
        
        error_log("Order created - ID: $order_id, Invoice: $invoice_no");
        
        // Add order details for each cart item
        foreach ($cart_items as $item) {
            $detail_result = add_order_details_ctr($order_id, $item['p_id'], $item['qty']);
            
            if (!$detail_result) {
                throw new Exception("Failed to add order details for product: {$item['p_id']}");
            }
            
            error_log("Order detail added - Product: {$item['p_id']}, Qty: {$item['qty']}");
        }
        
        // Record payment in database
        $payment_id = record_payment_ctr(
            $total_amount,
            $customer_id,
            $order_id,
            'GHS',
            $order_date,
            'paystack',
            $reference,
            $authorization_code,
            $payment_method
        );
        
        if (!$payment_id) {
            throw new Exception("Failed to record payment");
        }
        
        error_log("Payment recorded - ID: $payment_id, Reference: $reference");
        
        // Empty the customer's cart
        $empty_result = empty_cart_ctr($customer_id);
        
        if (!$empty_result) {
            throw new Exception("Failed to empty cart");
        }
        
        error_log("Cart emptied for customer: $customer_id");
        
        // Commit database transaction
        mysqli_commit($conn);
        error_log("Database transaction committed successfully");
        
        // Clear session payment data
        unset($_SESSION['paystack_ref']);
        unset($_SESSION['paystack_amount']);
        unset($_SESSION['paystack_timestamp']);
        
        // Log user activity
        log_user_activity("Completed payment via Paystack - Invoice: $invoice_no, Amount: GHS $total_amount, Reference: $reference");
        
        // Return success response
        echo json_encode([
            'status' => 'success',
            'verified' => true,
            'message' => 'Payment successful! Order confirmed.',
            'order_id' => $order_id,
            'invoice_no' => $invoice_no,
            'total_amount' => number_format($total_amount, 2),
            'currency' => 'GHS',
            'order_date' => date('F j, Y', strtotime($order_date)),
            'customer_name' => $customer_name,
            'item_count' => count($cart_items),
            'payment_reference' => $reference,
            'payment_method' => ucfirst($payment_method),
            'customer_email' => $customer_email
        ]);
        
    } catch (Exception $e) {
        // Rollback database transaction on error
        mysqli_rollback($conn);
        error_log("Database transaction rolled back: " . $e->getMessage());
        
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in Paystack callback/verification: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
}
?>
