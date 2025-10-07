<?php
/**
 * Test Web Login
 * This simulates what happens when you submit the login form
 */

// Suppress error reporting to prevent code from showing
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

echo "<h2>🔍 Web Login Test</h2>";

// Simulate POST data
$_POST['email'] = 'admin@test.com';
$_POST['password'] = 'admin123';
$_POST['remember'] = '';
$_POST['ajax'] = '1';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h3>1. Testing URL Function</h3>";
require_once __DIR__ . '/../settings/core.php';
echo "BASE_URL: " . BASE_URL . "<br>";
echo "url('../actions/process_login.php'): " . url('../actions/process_login.php') . "<br>";

echo "<h3>2. Testing Login Process</h3>";

try {
    require_once __DIR__ . '/../controller/user_controller.php';
    require_once __DIR__ . '/../controller/general_controller.php';
    
    // Test the login function directly
    $result = login_user_ctr($_POST['email'], $_POST['password'], false);
    
    echo "Login result: " . $result . "<br>";
    
    if ($result === "success") {
        echo "✅ Login successful!<br>";
        echo "Session data:<br>";
        if (isset($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                echo "&nbsp;&nbsp;- $key: " . (is_array($value) ? json_encode($value) : $value) . "<br>";
            }
        }
    } else {
        echo "❌ Login failed: $result<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Testing AJAX Response</h3>";

// Simulate what the AJAX response would be
if ($result === "success") {
    $redirect_url = BASE_URL . '/view/user/dashboard.php';
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
        $redirect_url = BASE_URL . '/view/admin/dashboard.php';
    }
    
    $response = [
        'success' => true, 
        'message' => 'Login successful! Redirecting...',
        'redirect' => $redirect_url
    ];
    
    echo "AJAX Response: " . json_encode($response) . "<br>";
} else {
    $response = ['success' => false, 'message' => $result];
    echo "AJAX Response: " . json_encode($response) . "<br>";
}

echo "<hr>";
echo "<p><strong>🎯 Test Complete</strong></p>";
echo "<p><a href='view/user/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Login Page</a></p>";
?>
