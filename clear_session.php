<?php
/**
 * Clear Session and Rate Limiting
 */

// Start session
session_start();

echo "<h2>Session Cleared</h2>";

// Clear all rate limiting
$cleared = 0;
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'rate_limit_') === 0) {
        unset($_SESSION[$key]);
        $cleared++;
    }
}

echo "<p style='color: green;'>✅ Cleared $cleared rate limiting entries</p>";

// Clear all session data
session_destroy();
session_start();

echo "<p style='color: green;'>✅ Session completely cleared</p>";

echo "<hr>";
echo "<p><strong>You can now login without any restrictions!</strong></p>";
echo "<p><a href='public_html/view/user/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
echo "<p><a href='public_html/simple_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Use Simple Login</a></p>";
?>
