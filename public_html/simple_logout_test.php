<?php
/**
 * Simple Logout Test
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Logout Test</h1>";

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Before Logout:</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Logged in: " . (is_logged_in() ? 'YES' : 'NO') . "</p>";

if (is_logged_in()) {
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
    echo "<p>Customer ID: " . ($_SESSION['customer_id'] ?? 'NOT SET') . "</p>";
    
    echo "<h2>Testing Logout:</h2>";
    
    try {
        $result = logout_user_ctr();
        echo "<p>Logout result: " . $result . "</p>";
        
        echo "<h2>After Logout:</h2>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p>Logged in: " . (is_logged_in() ? 'YES' : 'NO') . "</p>";
        
        if ($result === "success") {
            echo "<p style='color: green; font-weight: bold;'>✅ Logout successful!</p>";
            echo "<p><a href='view/user/login.php'>Go to Login Page</a></p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Logout failed: " . $result . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red; font-weight: bold;'>❌ Logout error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: orange;'>Not logged in - cannot test logout</p>";
    echo "<p><a href='view/user/login.php'>Go to Login Page</a></p>";
}
?>
