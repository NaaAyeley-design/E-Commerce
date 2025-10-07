<?php
/**
 * Clear All Session and Cache Data
 */

session_start();

echo "<h2>🧹 Clearing All Data</h2>";

// Clear all session data
session_destroy();
session_start();

echo "<p style='color: green;'>✅ Session cleared</p>";

// Clear any rate limiting
$cleared = 0;
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'rate_limit_') === 0) {
        unset($_SESSION[$key]);
        $cleared++;
    }
}

echo "<p style='color: green;'>✅ Rate limiting cleared</p>";

echo "<hr>";
echo "<h3>🎯 Now Try:</h3>";
echo "<p>1. <strong>Hard refresh</strong> your browser (Ctrl + F5)</p>";
echo "<p>2. Go to the login page</p>";
echo "<p>3. Use these credentials:</p>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@test.com</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='view/user/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Login Page</a></p>";
echo "<p><a href='test_web_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Login System</a></p>";
?>
