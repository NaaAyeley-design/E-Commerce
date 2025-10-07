<?php
/**
 * Simple Working Login - Bypasses Complex Database Class
 */

// Start session
session_start();

// Simple database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ecommerce_authent';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle login
$error = '';
if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        // Check user in database
        $sql = "SELECT customer_id, customer_name, customer_email, customer_pass, user_role FROM customer WHERE customer_email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['customer_pass'])) {
            // Login successful
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['customer_name'];
            $_SESSION['user_email'] = $user['customer_email'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Legacy session variables
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['customer_name'];
            $_SESSION['customer_email'] = $user['customer_email'];
            
            // Redirect based on role
            if ($user['user_role'] == 1) {
                header('Location: view/admin/dashboard.php');
            } else {
                header('Location: view/user/dashboard.php');
            }
            exit;
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Please enter both email and password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 400px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px;
            box-sizing: border-box;
        }
        button { 
            background: #007bff; 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            width: 100%;
        }
        button:hover { background: #0056b3; }
        .error { 
            color: red; 
            margin: 15px 0; 
            padding: 10px;
            background: #ffe6e6;
            border-radius: 5px;
        }
        .success { color: green; margin: 15px 0; }
        .credentials {
            background: #e6f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Simple Login</h2>
        
        <?php if ($error): ?>
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
        
        <div class="credentials">
            <h4>Test Credentials:</h4>
            <p><strong>Admin:</strong> admin@test.com / admin123</p>
            <p><strong>User:</strong> user@test.com / user123</p>
        </div>
        
        <p><a href="view/user/login.php">← Back to Normal Login</a></p>
    </div>
</body>
</html>
