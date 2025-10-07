<?php
/**
 * Fix All Issues - Complete Solution
 */

echo "<h2>🔧 Fixing All Issues</h2>";

// 1. Clear session completely
session_start();
$_SESSION = array();
session_destroy();

echo "<h3>1. ✅ Session Cleared</h3>";

// 2. Test login with victor@gmail.com
echo "<h3>2. Testing Victor Login</h3>";

try {
    require_once __DIR__ . '/../settings/core.php';
    require_once __DIR__ . '/../controller/user_controller.php';
    
    $email = 'victor@gmail.com';
    $password = 'admin123';
    
    $result = login_user_ctr($email, $password, false);
    
    if ($result === "success") {
        echo "✅ Victor login successful!<br>";
        echo "Role: " . ($_SESSION['user_role'] == 1 ? 'Admin' : 'User') . "<br>";
        
        // Test redirect URLs
        $user_dashboard = BASE_URL . '/view/user/dashboard.php';
        $admin_dashboard = BASE_URL . '/view/admin/dashboard.php';
        
        echo "User dashboard: <a href='$user_dashboard' target='_blank'>$user_dashboard</a><br>";
        echo "Admin dashboard: <a href='$admin_dashboard' target='_blank'>$admin_dashboard</a><br>";
        
    } else {
        echo "❌ Login failed: $result<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Testing Homepage Logic</h3>";

// Clear session again to test homepage
session_destroy();
session_start();

$is_logged_in = is_logged_in();
echo "Is logged in: " . ($is_logged_in ? "YES" : "NO") . "<br>";

if ($is_logged_in) {
    echo "Homepage should show: Go to Dashboard, Browse Products<br>";
} else {
    echo "Homepage should show: Create Account, Sign In<br>";
}

echo "<hr>";
echo "<h3>🎯 Test These Links:</h3>";
echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Homepage (should show Login/Register)</a></p>";
echo "<p><a href='logout.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Logout (clear session)</a></p>";
echo "<p><a href='view/user/login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Login Page</a></p>";
echo "<p><a href='view/user/register.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Register Page</a></p>";

echo "<hr>";
echo "<h3>🔑 Test Credentials:</h3>";
echo "<p><strong>Admin:</strong> victor@gmail.com / admin123</p>";
echo "<p><strong>User:</strong> admin@test.com / admin123</p>";
?>
