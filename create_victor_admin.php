<?php
/**
 * Create Victor Admin User
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<h2>🔧 Creating Victor Admin User</h2>";
    
    // Create victor@gmail.com as admin
    $email = 'victor@gmail.com';
    $name = 'Victor Admin';
    $password = 'admin123'; // You can change this
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 1; // Admin role
    
    $sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, user_role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $hashed_password, $role]);
    
    echo "✅ Victor admin user created successfully!<br>";
    echo "📧 Email: $email<br>";
    echo "👤 Name: $name<br>";
    echo "🔑 Password: $password<br>";
    echo "👑 Role: Admin (1)<br>";
    
    // Verify the user was created
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<br>✅ User verified in database<br>";
        echo "User ID: {$user['customer_id']}<br>";
    }
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "⚠️ User already exists, updating password...<br>";
        
        $password = 'admin123';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE customer SET customer_pass = ?, user_role = 1 WHERE customer_email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hashed_password, $email]);
        
        echo "✅ Password updated for victor@gmail.com<br>";
    } else {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}
?>
