<?php
/**
 * Test Paystack Verification
 * 
 * This script helps diagnose Paystack verification issues
 * Access it via: /actions/test_paystack_verification.php?reference=KENTE-1-1234567890
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';

header('Content-Type: application/json');

// Only allow logged-in admin users
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$reference = isset($_GET['reference']) ? trim($_GET['reference']) : null;

if (!$reference) {
    echo json_encode([
        'error' => 'Please provide a reference in URL: ?reference=KENTE-1-1234567890',
        'example' => 'Use a reference from a recent payment attempt'
    ], JSON_PRETTY_PRINT);
    exit;
}

$test_results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'reference' => $reference,
    'tests' => []
];

// Test 1: Check if Paystack secret key is configured
$test_results['tests']['secret_key_configured'] = [
    'result' => defined('PAYSTACK_SECRET_KEY') && 
                PAYSTACK_SECRET_KEY !== 'sk_test_YOUR_SECRET_KEY_HERE' && 
                !empty(PAYSTACK_SECRET_KEY) ? 'YES' : 'NO',
    'key_preview' => defined('PAYSTACK_SECRET_KEY') ? substr(PAYSTACK_SECRET_KEY, 0, 10) . '...' : 'NOT SET'
];

// Test 2: Check if cURL is available
$test_results['tests']['curl_available'] = [
    'result' => function_exists('curl_init') ? 'YES' : 'NO'
];

// Test 3: Test Paystack API connection
if ($test_results['tests']['curl_available']['result'] === 'YES' && 
    $test_results['tests']['secret_key_configured']['result'] === 'YES') {
    
    try {
        $verification_url = PAYSTACK_VERIFY_ENDPOINT . $reference;
        $test_results['tests']['verification_url'] = [
            'url' => $verification_url,
            'note' => 'This is the URL being called'
        ];
        
        // Make the actual API call
        $verification_response = paystack_verify_transaction($reference);
        
        $test_results['tests']['api_call'] = [
            'result' => $verification_response ? 'SUCCESS' : 'FAILED',
            'response' => $verification_response
        ];
        
        // Analyze the response
        if ($verification_response) {
            if (isset($verification_response['status']) && $verification_response['status'] === true) {
                $test_results['tests']['verification_status'] = [
                    'result' => 'SUCCESS',
                    'transaction_status' => $verification_response['data']['status'] ?? 'unknown',
                    'amount' => isset($verification_response['data']['amount']) ? 
                                ($verification_response['data']['amount'] / 100) . ' GHS' : 'unknown',
                    'customer_email' => $verification_response['data']['customer']['email'] ?? 'unknown'
                ];
            } else {
                $test_results['tests']['verification_status'] = [
                    'result' => 'FAILED',
                    'error_message' => $verification_response['message'] ?? 'Unknown error',
                    'full_response' => $verification_response
                ];
            }
        } else {
            $test_results['tests']['verification_status'] = [
                'result' => 'NO_RESPONSE',
                'error' => 'No response from Paystack API'
            ];
        }
        
    } catch (Exception $e) {
        $test_results['tests']['api_call'] = [
            'result' => 'EXCEPTION',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
} else {
    $test_results['tests']['api_call'] = [
        'result' => 'SKIPPED',
        'reason' => 'Prerequisites not met (cURL or secret key)'
    ];
}

// Test 4: Check reference format
$test_results['tests']['reference_format'] = [
    'reference' => $reference,
    'format_valid' => preg_match('/^[A-Z0-9\-]+$/', $reference) ? 'YES' : 'NO',
    'starts_with_kente' => strpos($reference, 'KENTE-') === 0 ? 'YES' : 'NO',
    'parts' => explode('-', $reference)
];

echo json_encode($test_results, JSON_PRETTY_PRINT);

