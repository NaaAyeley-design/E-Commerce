<?php
/**
 * Direct Paystack Verification Test
 * Tests Paystack verification API directly
 * 
 * Usage: Visit this file in browser with ?reference=YOUR_REFERENCE
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/paystack_config.php';

// Get reference from URL
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';

if (empty($reference)) {
    die("Please provide a reference: ?reference=YOUR_REFERENCE");
}

echo "<h1>Paystack Verification Test</h1>";
echo "<h2>Reference: $reference</h2>";

// Check configuration
echo "<h3>Configuration Check:</h3>";
echo "<ul>";
echo "<li>Secret Key Defined: " . (defined('PAYSTACK_SECRET_KEY') ? 'YES' : 'NO') . "</li>";
if (defined('PAYSTACK_SECRET_KEY')) {
    $sk = PAYSTACK_SECRET_KEY;
    echo "<li>Secret Key Prefix: " . substr($sk, 0, 7) . "...</li>";
    echo "<li>Secret Key Length: " . strlen($sk) . "</li>";
    echo "<li>Secret Key Empty: " . (empty($sk) ? 'YES' : 'NO') . "</li>";
    echo "<li>Secret Key is Placeholder: " . ($sk === 'sk_test_YOUR_SECRET_KEY_HERE' ? 'YES' : 'NO') . "</li>";
}
echo "<li>Verify Endpoint: " . PAYSTACK_VERIFY_ENDPOINT . $reference . "</li>";
echo "<li>cURL Available: " . (function_exists('curl_init') ? 'YES' : 'NO') . "</li>";
echo "</ul>";

// Test verification
echo "<h3>Verification Test:</h3>";
echo "<pre>";

try {
    error_log("=== DIRECT VERIFICATION TEST ===");
    error_log("Reference: $reference");
    
    $verification_response = paystack_verify_transaction($reference);
    
    echo "=== VERIFICATION RESPONSE ===\n";
    echo "Response Type: " . gettype($verification_response) . "\n";
    echo "Is Array: " . (is_array($verification_response) ? 'YES' : 'NO') . "\n\n";
    
    if (is_array($verification_response)) {
        echo "Response Keys: " . implode(', ', array_keys($verification_response)) . "\n";
        echo "Response Status: " . (isset($verification_response['status']) ? var_export($verification_response['status'], true) : 'NOT SET') . "\n";
        echo "Response Message: " . ($verification_response['message'] ?? 'N/A') . "\n\n";
        
        if (isset($verification_response['data'])) {
            echo "=== TRANSACTION DATA ===\n";
            $data = $verification_response['data'];
            echo "Status: " . ($data['status'] ?? 'N/A') . "\n";
            echo "Amount (pesewas): " . ($data['amount'] ?? 'N/A') . "\n";
            echo "Amount (GHS): " . (isset($data['amount']) ? ($data['amount'] / 100) : 'N/A') . "\n";
            echo "Reference: " . ($data['reference'] ?? 'N/A') . "\n";
            echo "Customer Email: " . (isset($data['customer']) ? ($data['customer']['email'] ?? 'N/A') : 'N/A') . "\n";
        }
        
        echo "\n=== FULL RESPONSE (JSON) ===\n";
        echo json_encode($verification_response, JSON_PRETTY_PRINT);
    } else {
        echo "ERROR: Response is not an array!\n";
        echo "Response Value: " . var_export($verification_response, true) . "\n";
    }
    
    echo "\n=== VERIFICATION RESULT ===\n";
    if (is_array($verification_response) && isset($verification_response['status']) && $verification_response['status'] === true) {
        echo "✓ VERIFICATION SUCCESSFUL\n";
        if (isset($verification_response['data']['status']) && $verification_response['data']['status'] === 'success') {
            echo "✓ PAYMENT STATUS: SUCCESS\n";
        } else {
            echo "✗ PAYMENT STATUS: " . ($verification_response['data']['status'] ?? 'UNKNOWN') . "\n";
        }
    } else {
        echo "✗ VERIFICATION FAILED\n";
        echo "Reason: " . ($verification_response['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";

echo "<h3>Check Server Error Log:</h3>";
echo "<p>Check your PHP error log for detailed logging output.</p>";
echo "<p>Error log location: " . ini_get('error_log') . "</p>";

