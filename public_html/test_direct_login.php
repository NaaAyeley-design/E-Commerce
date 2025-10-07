<?php
/**
 * Direct Login Test
 * This bypasses all the complex logic and tests login directly
 */

// Start session
session_start();

echo "<h2>🔍 Direct Login Test</h2>";

// Test credentials
$test_email = 'admin@test.com';
$test_password = 'admin123';

echo "<h3>1. Testing Database Connection</h3>";

try {
    // Direct PDO connection
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Database connected<br>";
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found: {$user['customer_name']}<br>";
        
        // Check password
        if (password_verify($test_password, $user['customer_pass'])) {
            echo "✅ Password verified<br>";
            
            // Set session
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['customer_name'];
            $_SESSION['user_email'] = $user['customer_email'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['logged_in'] = true;
            
            echo "✅ Session set<br>";
            echo "<h3>Session Data:</h3>";
            echo "<pre>" . print_r($_SESSION, true) . "</pre>";
            
            echo "<hr>";
            echo "<h3>🎉 LOGIN SUCCESSFUL!</h3>";
            echo "<p><a href='view/user/dashboard.php'>Go to Dashboard</a></p>";
            echo "<p><a href='view/admin/dashboard.php'>Go to Admin Dashboard</a></p>";
            
        } else {
            echo "❌ Password verification failed<br>";
        }
    } else {
        echo "❌ User not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>2. Test Form</h3>";
?>

<form method="post" action="">
    <p>
        <label>Email: <input type="email" name="email" value="admin@test.com" required></label>
    </p>
    <p>
        <label>Password: <input type="password" name="password" value="admin123" required></label>
    </p>
    <p>
        <button type="submit">Test Login</button>
    </p>
</form>

<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    echo "<h3>Form Submitted</h3>";
    echo "Email: $email<br>";
    echo "Password: " . (empty($password) ? "EMPTY" : "PROVIDED") . "<br>";
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['customer_pass'])) {
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['customer_name'];
            $_SESSION['user_email'] = $user['customer_email'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['logged_in'] = true;
            
            echo "✅ LOGIN SUCCESSFUL!<br>";
            echo "<script>setTimeout(function(){ window.location.href = 'view/user/dashboard.php'; }, 2000);</script>";
        } else {
            echo "❌ Invalid email or password<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}
?>
