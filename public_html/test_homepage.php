<?php
/**
 * Test Homepage Display
 */

require_once __DIR__ . '/../settings/core.php';

echo "<h2>🔍 Homepage Test</h2>";

echo "<h3>Session Status:</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "Session status: " . session_status() . "<br>";
echo "Session data: " . json_encode($_SESSION ?? []) . "<br>";

echo "<h3>Login Status:</h3>";
$is_logged_in = is_logged_in();
echo "Is logged in: " . ($is_logged_in ? "YES" : "NO") . "<br>";

echo "<h3>What should be displayed:</h3>";
if ($is_logged_in) {
    echo "✅ Should show: Go to Dashboard button<br>";
    echo "✅ Should show: Browse Products button<br>";
} else {
    echo "✅ Should show: Create Account button<br>";
    echo "✅ Should show: Sign In button<br>";
}

echo "<h3>URLs that should be generated:</h3>";
echo "Register URL: " . url('view/user/register.php') . "<br>";
echo "Login URL: " . url('view/user/login.php') . "<br>";
echo "Dashboard URL: " . url('view/user/dashboard.php') . "<br>";

echo "<hr>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
echo "<p><a href='view/user/login.php'>Go to Login Page</a></p>";
echo "<p><a href='view/user/register.php'>Go to Register Page</a></p>";
?>
