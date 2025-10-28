<?php
/**
 * Test Logout Functionality
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Logout Test</h1>";

// Check current session status
echo "<h2>Current Session Status:</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Logged in: " . (is_logged_in() ? 'YES' : 'NO') . "</p>";
echo "<p>Is admin: " . (is_admin() ? 'YES' : 'NO') . "</p>";

if (is_logged_in()) {
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
    echo "<p>Customer ID: " . ($_SESSION['customer_id'] ?? 'NOT SET') . "</p>";
    echo "<p>User Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "</p>";
    
    echo "<h2>Test Logout:</h2>";
    echo "<p><a href='actions/logout_action.php' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Click to Logout</a></p>";
} else {
    echo "<p style='color: red;'>Not logged in - cannot test logout</p>";
    echo "<p><a href='view/user/login.php'>Go to Login Page</a></p>";
}
?>
