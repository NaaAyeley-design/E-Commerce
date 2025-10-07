<?php
/**
 * Simple Login Test (Non-AJAX)
 */

// Suppress error reporting
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once __DIR__ . '/../settings/core.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        require_once __DIR__ . '/../controller/user_controller.php';
        
        $result = login_user_ctr($email, $password, false);
        
        if ($result === "success") {
            // Redirect to dashboard
            $redirect_url = BASE_URL . '/view/user/dashboard.php';
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
                $redirect_url = BASE_URL . '/view/admin/dashboard.php';
            }
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $error_message = $result;
        }
    } else {
        $error_message = "Email and password are required.";
    }
}

include __DIR__ . '/view/templates/header.php';
?>

<div class="auth-container login-container">
    <h2>Simple Login Test</h2>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" value="admin@test.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-input" value="admin123" required>
        </div>

        <button type="submit" class="btn btn-primary">
            Sign In (Non-AJAX)
        </button>
    </form>
    
    <hr>
    <p><a href="view/user/login.php">Go to Regular Login Page</a></p>
</div>

<?php include __DIR__ . '/view/templates/footer.php'; ?>
