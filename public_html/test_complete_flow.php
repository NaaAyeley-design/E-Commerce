<?php
/**
 * Test Complete Login Flow
 */

echo "<h2>🔍 Complete Login Flow Test</h2>";

// Test 1: Homepage buttons
echo "<h3>1. Homepage Buttons Test</h3>";
echo "<p><a href='index.php' target='_blank'>View Homepage (should show Login/Register buttons)</a></p>";

// Test 2: Login page
echo "<h3>2. Login Page Test</h3>";
echo "<p><a href='view/user/login.php' target='_blank'>View Login Page</a></p>";

// Test 3: Direct login test
echo "<h3>3. Direct Login Test</h3>";
echo "<form method='post' action='' style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<p><label>Email: <input type='email' name='email' value='admin@test.com' required></label></p>";
echo "<p><label>Password: <input type='password' name='password' value='admin123' required></label></p>";
echo "<p><button type='submit'>Test Login</button></p>";
echo "</form>";

// Handle login test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h4>Login Test Results:</h4>";
    
    try {
        require_once __DIR__ . '/../settings/core.php';
        require_once __DIR__ . '/../controller/user_controller.php';
        
        $result = login_user_ctr($_POST['email'], $_POST['password'], false);
        
        if ($result === "success") {
            echo "✅ Login successful!<br>";
            echo "Session data: " . json_encode($_SESSION) . "<br>";
            
            $redirect_url = BASE_URL . '/view/user/dashboard.php';
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
                $redirect_url = BASE_URL . '/view/admin/dashboard.php';
            }
            
            echo "Should redirect to: $redirect_url<br>";
            echo "<p><a href='$redirect_url' target='_blank'>Test Dashboard Link</a></p>";
            echo "<p><a href='index.php' target='_blank'>View Homepage (should now show Dashboard button)</a></p>";
        } else {
            echo "❌ Login failed: $result<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<h3>4. Navigation Test</h3>";
echo "<p><a href='index.php'>Homepage</a> | <a href='view/user/login.php'>Login</a> | <a href='view/user/register.php'>Register</a></p>";
?>
