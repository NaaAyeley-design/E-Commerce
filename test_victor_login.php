<?php
/**
 * Test Victor Login
 */

echo "<h2>🔍 Testing Victor Login</h2>";

// Clear any existing session
session_start();
session_destroy();
session_start();

try {
    require_once __DIR__ . '/settings/core.php';
    require_once __DIR__ . '/controller/user_controller.php';
    
    $email = 'victor@gmail.com';
    $password = 'admin123';
    
    echo "Testing login for: $email<br>";
    
    $result = login_user_ctr($email, $password, false);
    
    echo "Login result: $result<br>";
    
    if ($result === "success") {
        echo "✅ Login successful!<br>";
        echo "Session data: " . json_encode($_SESSION) . "<br>";
        
        // Test redirect URL
        $redirect_url = BASE_URL . '/view/user/dashboard.php';
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
            $redirect_url = BASE_URL . '/view/admin/dashboard.php';
        }
        
        echo "Should redirect to: $redirect_url<br>";
        echo "<p><a href='$redirect_url' target='_blank'>Test Admin Dashboard</a></p>";
    } else {
        echo "❌ Login failed: $result<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
