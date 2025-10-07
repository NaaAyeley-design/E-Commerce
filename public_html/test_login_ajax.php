<?php
/**
 * Test Login AJAX
 * This simulates what happens when the login form is submitted via AJAX
 */

// Suppress error reporting
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Simulate AJAX request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'admin@test.com';
$_POST['password'] = 'admin123';
$_POST['ajax'] = '1';

echo "<h2>🔍 Test Login AJAX</h2>";

echo "<h3>Simulated POST Data:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h3>Processing Login...</h3>";

try {
    require_once __DIR__ . '/../settings/core.php';
    require_once __DIR__ . '/../controller/user_controller.php';
    require_once __DIR__ . '/../controller/general_controller.php';
    
    echo "✅ Controllers loaded<br>";
    
    // Get and sanitize input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    echo "Email: $email<br>";
    echo "Password: " . (empty($password) ? "EMPTY" : "PROVIDED") . "<br>";
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error_msg = 'Email and password are required.';
        echo "❌ Validation failed: $error_msg<br>";
        $response = ['success' => false, 'message' => $error_msg];
    } else {
        // Additional validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please enter a valid email address.';
            echo "❌ Email validation failed: $error_msg<br>";
            $response = ['success' => false, 'message' => $error_msg];
        } else {
            echo "✅ Input validation passed<br>";
            
            // Attempt login
            echo "🔍 Calling login_user_ctr()...<br>";
            $result = login_user_ctr($email, $password, $remember);
            
            echo "Login result: $result<br>";
            
            if ($result === "success") {
                echo "✅ Login successful!<br>";
                
                // Determine redirect URL based on user role
                $redirect_url = BASE_URL . '/view/user/dashboard.php';
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
                    $redirect_url = BASE_URL . '/view/admin/dashboard.php';
                }
                
                echo "Redirect URL: $redirect_url<br>";
                echo "Session data: " . json_encode($_SESSION) . "<br>";
                
                $response = [
                    'success' => true, 
                    'message' => 'Login successful! Redirecting...',
                    'redirect' => $redirect_url
                ];
            } else {
                echo "❌ Login failed: $result<br>";
                $response = ['success' => false, 'message' => $result];
            }
        }
    }
    
    echo "<h3>AJAX Response:</h3>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h3>Raw JSON Response:</h3>";
    echo json_encode($response);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
