<?php
/**
 * Direct Login Test
 */

// Suppress error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Include core settings
require_once __DIR__ . '/settings/core.php';

echo "<h2>Direct Login Test</h2>";

// Test database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    echo "<p>Host: " . DB_HOST . ", Database: " . DB_NAME . ", User: " . DB_USERNAME . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test user_class
try {
    $user = new user_class();
    echo "<p style='color: green;'>✅ User class loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ User class failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test login with admin credentials
$email = 'admin@test.com';
$password = 'admin123';

echo "<h3>Testing Login with: $email / $password</h3>";

try {
    $customer_data = $user->login_customer($email, $password);
    
    if ($customer_data) {
        echo "<p style='color: green;'>✅ Login successful!</p>";
        echo "<p>User ID: " . $customer_data['customer_id'] . "</p>";
        echo "<p>Name: " . $customer_data['customer_name'] . "</p>";
        echo "<p>Email: " . $customer_data['customer_email'] . "</p>";
        echo "<p>Role: " . $customer_data['user_role'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Login failed - Invalid credentials</p>";
        
        // Check if user exists
        $sql = "SELECT customer_id, customer_name, customer_email, customer_pass FROM customer WHERE customer_email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $customer = $stmt->fetch();
        
        if ($customer) {
            echo "<p>User found in database:</p>";
            echo "<p>ID: " . $customer['customer_id'] . "</p>";
            echo "<p>Name: " . $customer['customer_name'] . "</p>";
            echo "<p>Email: " . $customer['customer_email'] . "</p>";
            echo "<p>Password hash: " . substr($customer['customer_pass'], 0, 20) . "...</p>";
            
            // Test password verification
            if (password_verify($password, $customer['customer_pass'])) {
                echo "<p style='color: green;'>✅ Password verification successful</p>";
            } else {
                echo "<p style='color: red;'>❌ Password verification failed</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ User not found in database</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Login test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='public_html/view/user/login.php'>← Back to Login</a></p>";
?>
