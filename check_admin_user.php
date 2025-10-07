<?php
/**
 * Check Admin User in Database
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<h2>🔍 Checking Admin User</h2>";
    
    // Check if victor@gmail.com exists
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_email = ?");
    $stmt->execute(['victor@gmail.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found: {$user['customer_name']}<br>";
        echo "📧 Email: {$user['customer_email']}<br>";
        echo "👑 Role: {$user['user_role']}<br>";
        echo "🔑 Password hash: " . substr($user['customer_pass'], 0, 20) . "...<br>";
        
        // Test password verification
        $test_passwords = ['admin123', 'password', 'victor123', '123456'];
        foreach ($test_passwords as $pwd) {
            if (password_verify($pwd, $user['customer_pass'])) {
                echo "✅ Password '$pwd' works!<br>";
                break;
            } else {
                echo "❌ Password '$pwd' failed<br>";
            }
        }
    } else {
        echo "❌ User victor@gmail.com not found<br>";
        
        // Show all users
        $stmt = $pdo->query("SELECT customer_email, customer_name, user_role FROM customer");
        $users = $stmt->fetchAll();
        echo "<h3>All users in database:</h3>";
        foreach ($users as $u) {
            echo "- {$u['customer_email']} ({$u['customer_name']}) - Role: {$u['user_role']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
