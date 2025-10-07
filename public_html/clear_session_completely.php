<?php
/**
 * Clear Session Completely
 */

// Start session
session_start();

echo "<h2>🧹 Clearing Session Completely</h2>";

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

echo "<p style='color: green; font-size: 18px;'>✅ Session completely cleared!</p>";

echo "<h3>What should happen now:</h3>";
echo "<p>1. The homepage should show 'Create Account' and 'Sign In' buttons</p>";
echo "<p>2. You should be able to log in fresh</p>";
echo "<p>3. After login, you'll see 'Go to Dashboard' button</p>";

echo "<hr>";
echo "<h3>Test the flow:</h3>";
echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Homepage</a></p>";
echo "<p><a href='view/user/login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Login Page</a></p>";
echo "<p><a href='view/user/register.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Register Page</a></p>";

echo "<hr>";
echo "<h3>Test Credentials:</h3>";
echo "<p><strong>Email:</strong> admin@test.com</p>";
echo "<p><strong>Password:</strong> admin123</p>";
?>
