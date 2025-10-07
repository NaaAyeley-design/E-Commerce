<?php
/**
 * Clear Rate Limiting and Test Login
 */

// Start session
session_start();

echo "<h2>Rate Limiting & Login Debug</h2>";

// Clear all rate limiting
$cleared = 0;
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'rate_limit_') === 0) {
        unset($_SESSION[$key]);
        $cleared++;
    }
}

echo "<p style='color: green;'>✅ Cleared $cleared rate limiting entries</p>";

// Test database connection
require_once __DIR__ . '/settings/db_cred.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check users
    $stmt = $pdo->query("SELECT customer_id, customer_name, customer_email, user_role FROM customer");
    $users = $stmt->fetchAll();
    
    echo "<h3>Available Users:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        $role = $user['user_role'] == 1 ? 'Admin' : 'User';
        echo "<li><strong>" . htmlspecialchars($user['customer_email']) . "</strong> - " . $role . "</li>";
    }
    echo "</ul>";
    
    // Test login with admin credentials
    echo "<h3>Testing Login:</h3>";
    $email = 'admin@test.com';
    $password = 'admin123';
    
    $sql = "SELECT customer_id, customer_name, customer_email, customer_pass, user_role FROM customer WHERE customer_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $customer = $stmt->fetch();
    
    if ($customer && password_verify($password, $customer['customer_pass'])) {
        echo "<p style='color: green;'>✅ Login test successful with admin@test.com / admin123</p>";
    } else {
        echo "<p style='color: red;'>❌ Login test failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><strong>You can now try logging in again!</strong></p>";
echo "<p><a href='public_html/view/user/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
?>
