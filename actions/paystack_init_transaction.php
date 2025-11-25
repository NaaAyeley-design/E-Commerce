<?php
/**
 * Paystack Transaction Initialization
 * Handles payment initialization requests from checkout
 */

// Start output buffering
ob_start();

// Include core settings and Paystack config
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';

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
    echo json_encode(['status' => 'error', 'message' => 'Please login to complete payment']);
    ob_end_flush();
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$customer_email = isset($input['email']) ? trim($input['email']) : '';

// Validate inputs
if (!$amount || $amount <= 0) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
    ob_end_flush();
    exit;
}

if (!$customer_email || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    ob_end_flush();
    exit;
}

try {
    // Generate unique reference
    $customer_id = get_user_id();
    $reference = 'KENTE-' . $customer_id . '-' . time();
    
    error_log("Initializing Paystack transaction - Customer: $customer_id, Amount: $amount GHS, Email: $customer_email");
    
    // Initialize Paystack transaction
    $paystack_response = paystack_initialize_transaction($amount, $customer_email, $reference);
    
    if (!$paystack_response) {
        throw new Exception("No response from Paystack API");
    }
    
    // Check Paystack response structure
    if (isset($paystack_response['status']) && $paystack_response['status'] === true) {
        // Store transaction reference in session for verification later
        $_SESSION['paystack_ref'] = $reference;
        $_SESSION['paystack_amount'] = $amount;
        $_SESSION['paystack_timestamp'] = time();
        
        error_log("Paystack transaction initialized successfully - Reference: $reference");
        
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'authorization_url' => $paystack_response['data']['authorization_url'],
            'reference' => $reference,
            'access_code' => $paystack_response['data']['access_code'] ?? '',
            'message' => 'Redirecting to payment gateway...'
        ]);
        ob_end_flush();
        exit;
    } else {
        error_log("Paystack API error: " . json_encode($paystack_response));
        
        $error_message = $paystack_response['message'] ?? 'Payment gateway error';
        throw new Exception($error_message);
    }
    
} catch (Exception $e) {
    error_log("Error initializing Paystack transaction: " . $e->getMessage());
    
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize payment: ' . $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}

