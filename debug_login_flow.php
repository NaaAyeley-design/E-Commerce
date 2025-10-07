<?php
/**
 * Debug Login Flow
 * This will test the complete login process step by step
 */

echo "<h2>🔍 Debug Login Flow</h2>";

// Test credentials
$test_email = 'admin@test.com';
$test_password = 'admin123';

echo "<h3>1. Testing Direct Login Process</h3>";

try {
    // Test database connection
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Database connected<br>";
    
    // Test user lookup
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found: {$user['customer_name']}<br>";
        
        if (password_verify($test_password, $user['customer_pass'])) {
            echo "✅ Password verified<br>";
            
            // Test session creation
            session_start();
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['customer_name'];
            $_SESSION['user_email'] = $user['customer_email'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['logged_in'] = true;
            
            echo "✅ Session created<br>";
            echo "Session data: " . json_encode($_SESSION) . "<br>";
            
            // Test redirect URL construction
            require_once __DIR__ . '/settings/core.php';
            $redirect_url = BASE_URL . '/view/user/dashboard.php';
            if ($_SESSION['user_role'] == 1) {
                $redirect_url = BASE_URL . '/view/admin/dashboard.php';
            }
            
            echo "✅ Redirect URL: $redirect_url<br>";
            
            // Test if dashboard files exist
            $user_dashboard = PUBLIC_PATH . '/view/user/dashboard.php';
            $admin_dashboard = PUBLIC_PATH . '/view/admin/dashboard.php';
            
            echo "User dashboard exists: " . (file_exists($user_dashboard) ? "YES" : "NO") . "<br>";
            echo "Admin dashboard exists: " . (file_exists($admin_dashboard) ? "YES" : "NO") . "<br>";
            
            echo "<hr>";
            echo "<h3>🎉 Login Process Working!</h3>";
            echo "<p><a href='$redirect_url' target='_blank'>Test Dashboard Link</a></p>";
            
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
echo "<h3>2. Test Login Form</h3>";
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
        require_once __DIR__ . '/settings/core.php';
        require_once __DIR__ . '/controller/user_controller.php';
        
        $result = login_user_ctr($email, $password, false);
        
        echo "Login result: $result<br>";
        
        if ($result === "success") {
            echo "✅ LOGIN SUCCESSFUL!<br>";
            echo "Session data: " . json_encode($_SESSION) . "<br>";
            
            $redirect_url = BASE_URL . '/view/user/dashboard.php';
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
                $redirect_url = BASE_URL . '/view/admin/dashboard.php';
            }
            
            echo "Redirect URL: $redirect_url<br>";
            echo "<script>setTimeout(function(){ window.location.href = '$redirect_url'; }, 2000);</script>";
        } else {
            echo "❌ Login failed: $result<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}
?>
