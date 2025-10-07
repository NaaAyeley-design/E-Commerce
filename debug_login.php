<?php
/**
 * Debug Login Issues
 */

// Suppress error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Include database credentials
require_once __DIR__ . '/settings/db_cred.php';

echo "<h2>Login Debug Information</h2>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check what users exist
    echo "<h3>Users in Database:</h3>";
    $stmt = $pdo->query("SELECT customer_id, customer_name, customer_email, user_role FROM customer");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p style='color: red;'>❌ No users found in database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['customer_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['customer_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['customer_email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['user_role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test login with admin credentials
    echo "<h3>Testing Login with admin@test.com:</h3>";
    $email = 'admin@test.com';
    $password = 'admin123';
    
    $sql = "SELECT customer_id, customer_name, customer_email, customer_pass, user_role FROM customer WHERE customer_email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        echo "<p>✅ User found in database</p>";
        echo "<p>Stored password hash: " . substr($customer['customer_pass'], 0, 20) . "...</p>";
        
        // Test password verification
        if (password_verify($password, $customer['customer_pass'])) {
            echo "<p style='color: green;'>✅ Password verification successful!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed!</p>";
            echo "<p>Testing with fresh hash...</p>";
            $fresh_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "<p>Fresh hash: " . substr($fresh_hash, 0, 20) . "...</p>";
            if (password_verify($password, $fresh_hash)) {
                echo "<p style='color: orange;'>⚠️ Fresh hash works - stored hash might be corrupted</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ User not found in database!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='public_html/view/user/login.php'>← Back to Login</a></p>";
?>
