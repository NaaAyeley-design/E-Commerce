<?php
/**
 * Simple Login Test
 */

echo "<h2>🔍 Simple Login Test</h2>";

// Test credentials
$test_email = 'admin@test.com';
$test_password = 'admin123';

try {
    // Test direct database query
    echo "<h3>1. Direct Database Test</h3>";
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $stmt = $pdo->prepare("SELECT customer_id, customer_name, customer_email, customer_pass, user_role FROM customer WHERE customer_email = ?");
    $stmt->execute([$test_email]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        echo "✅ User found in database<br>";
        echo "📧 Email: {$customer['customer_email']}<br>";
        echo "👤 Name: {$customer['customer_name']}<br>";
        
        if (password_verify($test_password, $customer['customer_pass'])) {
            echo "✅ Password verification: SUCCESS<br>";
        } else {
            echo "❌ Password verification: FAILED<br>";
        }
    } else {
        echo "❌ User not found<br>";
    }
    
    echo "<hr>";
    echo "<h3>2. User Class Test</h3>";
    
    // Test user class
    require_once __DIR__ . '/settings/core.php';
    require_once __DIR__ . '/class/user_class.php';
    
    $user = new user_class();
    echo "✅ User class instantiated<br>";
    
    $result = $user->login_customer($test_email, $test_password);
    
    if ($result) {
        echo "✅ User class login: SUCCESS<br>";
        echo "📋 Returned data: " . json_encode($result) . "<br>";
    } else {
        echo "❌ User class login: FAILED<br>";
    }
    
    echo "<hr>";
    echo "<h3>3. Controller Test</h3>";
    
    require_once __DIR__ . '/controller/user_controller.php';
    
    $result = login_user_ctr($test_email, $test_password);
    echo "📋 Controller result: " . $result . "<br>";
    
    if ($result === "success") {
        echo "✅ Controller login: SUCCESS<br>";
    } else {
        echo "❌ Controller login: FAILED<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>🎯 Test Complete</strong></p>";
?>
