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

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Invalid JSON input: " . json_last_error_msg());
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data']);
    ob_end_flush();
    exit;
}

$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$customer_email = isset($input['email']) ? trim($input['email']) : '';

// Validate inputs with better error messages
if (!isset($input['amount']) || $amount <= 0 || !is_numeric($input['amount'])) {
    error_log("Invalid amount received: " . var_export($input['amount'] ?? 'not set', true));
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid payment amount. Please refresh the page and try again.',
        'debug' => (APP_ENV === 'development' ? 'Amount: ' . var_export($input['amount'] ?? null, true) : null)
    ]);
    ob_end_flush();
    exit;
}

// Ensure minimum amount (1 GHS = 100 pesewas)
if ($amount < 1) {
    error_log("Amount too small: $amount GHS");
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Minimum payment amount is ₵1.00']);
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
    
    error_log("=== PAYSTACK INIT TRANSACTION ===");
    error_log("Customer ID: $customer_id");
    error_log("Amount: $amount GHS");
    error_log("Email: $customer_email");
    error_log("Reference: $reference");
    
    // Check if Paystack secret key is configured
    if (!defined('PAYSTACK_SECRET_KEY') || 
        PAYSTACK_SECRET_KEY === 'sk_test_YOUR_SECRET_KEY_HERE' || 
        empty(PAYSTACK_SECRET_KEY)) {
        error_log("ERROR: Paystack secret key not configured!");
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment gateway not configured. Please contact support.',
            'debug' => 'Paystack secret key is missing or not set'
        ]);
        ob_end_flush();
        exit;
    }
    
    // Initialize Paystack transaction
    error_log("Calling paystack_initialize_transaction()...");
    $paystack_response = paystack_initialize_transaction($amount, $customer_email, $reference);
    
    error_log("Paystack API Response: " . json_encode($paystack_response));
    
    if (!$paystack_response) {
        error_log("ERROR: No response from Paystack API");
        throw new Exception("No response from Paystack API. Please check your API keys and network connection.");
    }
    
    // Check Paystack response structure
    if (isset($paystack_response['status']) && $paystack_response['status'] === true) {
        // Verify we have the authorization URL
        if (!isset($paystack_response['data']['authorization_url'])) {
            error_log("ERROR: Authorization URL missing from Paystack response");
            throw new Exception("Payment gateway response incomplete. Missing authorization URL.");
        }
        
        // Store transaction reference in session for verification later
        $_SESSION['paystack_ref'] = $reference;
        $_SESSION['paystack_amount'] = $amount;
        $_SESSION['paystack_timestamp'] = time();
        
        error_log("SUCCESS: Paystack transaction initialized - Reference: $reference");
        error_log("Authorization URL: " . $paystack_response['data']['authorization_url']);
        
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
        error_log("ERROR: Paystack API returned failure");
        error_log("Response: " . json_encode($paystack_response));
        
        $error_message = $paystack_response['message'] ?? 'Payment gateway error';
        
        // Provide more helpful error messages
        if (strpos($error_message, 'Invalid') !== false || strpos($error_message, 'key') !== false) {
            $error_message = 'Payment gateway configuration error. Please contact support.';
        }
        
        throw new Exception($error_message);
    }
    
} catch (Exception $e) {
    error_log("EXCEPTION: Error initializing Paystack transaction");
    error_log("Exception message: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize payment: ' . $e->getMessage(),
        'debug' => (APP_ENV === 'development' ? $e->getMessage() : null)
    ]);
    ob_end_flush();
    exit;
} catch (Throwable $e) {
    error_log("THROWABLE: Error initializing Paystack transaction");
    error_log("Error message: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred. Please try again.',
        'debug' => (APP_ENV === 'development' ? $e->getMessage() : null)
    ]);
    ob_end_flush();
    exit;
}

