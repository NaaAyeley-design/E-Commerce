<?php
/**
 * Quick Login for Testing
 */

// Suppress error reporting
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../settings/core.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear any rate limiting
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'rate_limit_') === 0) {
        unset($_SESSION[$key]);
    }
}

// Handle login
if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $user = new user_class();
        $customer_data = $user->login_customer($email, $password);
        
        if ($customer_data) {
            // Set session data
            $_SESSION['user_id'] = $customer_data['customer_id'];
            $_SESSION['user_name'] = $customer_data['customer_name'];
            $_SESSION['user_email'] = $customer_data['customer_email'];
            $_SESSION['user_role'] = $customer_data['user_role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Legacy session variables
            $_SESSION['customer_id'] = $customer_data['customer_id'];
            $_SESSION['customer_name'] = $customer_data['customer_name'];
            $_SESSION['customer_email'] = $customer_data['customer_email'];
            
            // Redirect based on role
            if ($customer_data['user_role'] == 1) {
                header('Location: view/admin/dashboard.php');
            } else {
                header('Location: view/user/dashboard.php');
            }
            exit;
        } else {
            $error = "Invalid email or password";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Login</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Quick Login (No Rate Limiting)</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="admin@test.com" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" value="admin123" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <hr>
    <p><strong>Test Credentials:</strong></p>
    <ul>
        <li>Admin: admin@test.com / admin123</li>
        <li>User: user@test.com / user123</li>
    </ul>
    
    <p><a href="view/user/login.php">← Back to Normal Login</a></p>
</body>
</html>
