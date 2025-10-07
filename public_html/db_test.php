<?php
/**
 * Database Connection Test
 */

// Suppress error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Include database credentials
require_once __DIR__ . '/../settings/db_cred.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Username:</strong> " . DB_USERNAME . "</p>";
    
    // Test if customers table exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✅ Customers table found with " . $result['count'] . " records</p>";
    
    // Show test users
    $stmt = $pdo->query("SELECT customer_name, customer_email, user_role FROM customers");
    $users = $stmt->fetchAll();
    
    echo "<h3>Test Users:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        $role = $user['user_role'] == 1 ? 'Admin' : 'User';
        echo "<li>" . htmlspecialchars($user['customer_name']) . " (" . htmlspecialchars($user['customer_email']) . ") - " . $role . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Username:</strong> " . DB_USERNAME . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Homepage</a></p>";
?>
