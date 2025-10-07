<?php
/**
 * Debug Login Page
 * This will show us exactly what's happening during login
 */

// Suppress error reporting
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/../settings/core.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>🔍 DEBUG: Form Submitted</h2>";
    echo "<h3>POST Data:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    echo "<h3>Extracted Data:</h3>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Password: " . (empty($password) ? "EMPTY" : "PROVIDED") . "<br>";
    echo "Remember: " . ($remember ? "YES" : "NO") . "<br>";
    
    if (!empty($email) && !empty($password)) {
        echo "<h3>Testing Login Process:</h3>";
        
        try {
            require_once __DIR__ . '/../controller/user_controller.php';
            
            echo "✅ Controller loaded<br>";
            echo "🔍 Calling login_user_ctr()...<br>";
            
            $result = login_user_ctr($email, $password, $remember);
            
            echo "📋 Login result: " . htmlspecialchars($result) . "<br>";
            
            if ($result === "success") {
                echo "✅ LOGIN SUCCESSFUL!<br>";
                echo "<h3>Session Data:</h3>";
                echo "<pre>" . print_r($_SESSION, true) . "</pre>";
                
                // Determine redirect
                $redirect_url = BASE_URL . '/view/user/dashboard.php';
                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
                    $redirect_url = BASE_URL . '/view/admin/dashboard.php';
                }
                
                echo "<h3>Redirect URL:</h3>";
                echo $redirect_url . "<br>";
                
                echo "<hr>";
                echo "<p style='color: green; font-size: 18px;'><strong>🎉 LOGIN IS WORKING! Redirecting in 3 seconds...</strong></p>";
                echo "<script>setTimeout(function(){ window.location.href = '" . $redirect_url . "'; }, 3000);</script>";
                
            } else {
                echo "❌ LOGIN FAILED: " . htmlspecialchars($result) . "<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "<br>";
            echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
        }
    } else {
        echo "❌ Missing email or password<br>";
    }
    
    echo "<hr>";
}

include __DIR__ . '/view/templates/header.php';
?>

<div class="auth-container login-container">
    <h2>🔍 Debug Login Page</h2>
    <p>This page will show exactly what happens during login</p>
    
    <form method="post" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" value="admin@test.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-input" value="admin123" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
        </div>

        <button type="submit" class="btn btn-primary">
            🔍 Debug Login
        </button>
    </form>
    
    <hr>
    <h3>Current Session Data:</h3>
    <pre><?php print_r($_SESSION ?? []); ?></pre>
    
    <hr>
    <p><a href="view/user/login.php">Go to Regular Login Page</a></p>
    <p><a href="simple_login_test.php">Go to Simple Login Test</a></p>
</div>

<?php include __DIR__ . '/view/templates/footer.php'; ?>
