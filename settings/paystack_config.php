<?php
/**
 * Paystack Configuration
 * Secure payment gateway settings for KenteKart
 */

// Include database credentials
require_once __DIR__ . '/db_cred.php';
require_once __DIR__ . '/core.php';

// ============================================
// PAYSTACK API KEYS CONFIGURATION
// ============================================
// 
// IMPORTANT: You MUST replace these placeholder values with your actual Paystack API keys!
// 
// How to get your keys:
// 1. Log in to Paystack Dashboard: https://dashboard.paystack.com
// 2. Navigate to: Settings → API Keys & Webhooks
// 3. Copy your keys:
//    - Public Key: Starts with "pk_test_" (for test) or "pk_live_" (for production)
//    - Secret Key: Starts with "sk_test_" (for test) or "sk_live_" (for production)
// 
// Test Mode Keys (for development):
//    - Use keys starting with "pk_test_" and "sk_test_"
//    - These are safe to use for testing
// 
// Live Mode Keys (for production):
//    - Use keys starting with "pk_live_" and "sk_live_"
//    - Only use these when your site is live and ready for real payments
//    - NEVER commit live keys to version control!
// 
// ============================================

// Paystack Secret Key (used for server-side API calls)
// ✅ CONFIGURED: Your secret key has been set
// Get this from: https://dashboard.paystack.com/#/settings/developer
define('PAYSTACK_SECRET_KEY', 'sk_test_3d244acd2567f1b7fe4fd8b4874c85bddf6c704b');

// Paystack Public Key (used for client-side payment initialization)
// ✅ CONFIGURED: Your public key has been set
// This key is safe to expose in JavaScript (it's public by design)
define('PAYSTACK_PUBLIC_KEY', 'pk_test_03eb6cd9268039c31ccb85fda2821e31171ba1dc');

// Paystack API URLs
define('PAYSTACK_API_URL', 'https://api.paystack.co');
define('PAYSTACK_INIT_ENDPOINT', PAYSTACK_API_URL . '/transaction/initialize');
define('PAYSTACK_VERIFY_ENDPOINT', PAYSTACK_API_URL . '/transaction/verify/');

// Application Environment
define('PAYSTACK_ENVIRONMENT', APP_ENV === 'production' ? 'live' : 'test');

// Callback URL - Update this to match your domain
// These URLs are used by Paystack to redirect users after payment
define('PAYSTACK_CALLBACK_URL', BASE_URL . '/view/payment/paystack_callback.php');
define('PAYSTACK_SUCCESS_URL', BASE_URL . '/view/payment/payment_success.php');
define('PAYSTACK_CANCEL_URL', BASE_URL . '/view/cart/view_cart.php');

/**
 * Initialize a Paystack transaction
 * 
 * @param float $amount Amount in GHS (will be converted to pesewas)
 * @param string $email Customer email
 * @param string $reference Optional reference (will be generated if not provided)
 * @return array Response with 'status' and 'data' containing authorization_url
 */
function paystack_initialize_transaction($amount, $email, $reference = null) {
    // Generate unique reference if not provided
    if (!$reference) {
        $customer_id = get_user_id();
        $reference = 'KENTE-' . $customer_id . '-' . time();
    }
    
    // Convert GHS to pesewas (1 GHS = 100 pesewas)
    $amount_in_pesewas = round($amount * 100);
    
    // Validate amount
    if ($amount_in_pesewas < 100) { // Minimum 1 GHS
        return [
            'status' => false,
            'message' => 'Amount must be at least 1 GHS'
        ];
    }
    
    $data = [
        'amount' => $amount_in_pesewas,
        'email' => $email,
        'reference' => $reference,
        'callback_url' => PAYSTACK_CALLBACK_URL,
        'metadata' => [
            'currency' => 'GHS',
            'app' => 'KenteKart',
            'environment' => PAYSTACK_ENVIRONMENT,
            'customer_id' => get_user_id()
        ]
    ];
    
    $response = paystack_api_request('POST', PAYSTACK_INIT_ENDPOINT, $data);
    
    return $response;
}

/**
 * Verify a Paystack transaction
 * 
 * @param string $reference Transaction reference
 * @return array Response with transaction details
 */
function paystack_verify_transaction($reference) {
    $response = paystack_api_request('GET', PAYSTACK_VERIFY_ENDPOINT . $reference);
    
    return $response;
}

/**
 * Make a request to Paystack API
 * 
 * @param string $method HTTP method (GET, POST, etc)
 * @param string $url Full API endpoint URL
 * @param array $data Optional data to send
 * @return array API response decoded as array
 */
function paystack_api_request($method, $url, $data = null) {
    // Check if cURL is available
    if (!function_exists('curl_init')) {
        error_log("Paystack API Error: cURL is not available");
        return [
            'status' => false,
            'message' => 'cURL is not available on this server'
        ];
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Set headers
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Send data for POST/PUT requests
    if (in_array($method, ['POST', 'PUT', 'PATCH']) && $data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    // Handle curl errors
    if ($curl_error) {
        error_log("Paystack API CURL Error: $curl_error");
        return [
            'status' => false,
            'message' => 'Connection error: ' . $curl_error
        ];
    }
    
    // Decode response
    $result = json_decode($response, true);
    
    // Log for debugging (only in development)
    if (APP_ENV === 'development') {
        error_log("Paystack API Response (HTTP $http_code): " . json_encode($result));
    }
    
    return $result;
}

/**
 * Get currency symbol for display
 */
function get_currency_symbol($currency = 'GHS') {
    $symbols = [
        'GHS' => '₵',
        'USD' => '$',
        'EUR' => '€',
        'NGN' => '₦'
    ];
    
    return $symbols[$currency] ?? $currency;
}

/**
 * Format amount for display
 */
function format_amount($amount, $currency = 'GHS') {
    return get_currency_symbol($currency) . number_format($amount, 2);
}

?>

