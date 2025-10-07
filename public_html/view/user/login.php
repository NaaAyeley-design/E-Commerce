<?php
/**
 * Login Page
 * 
 * User login form with modern design and AJAX functionality
 */

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';

// Set page variables
$page_title = 'Login';
$page_description = 'Sign in to your account to access your dashboard and manage your orders.';
$standalone_page = true;
$body_class = 'auth-page login-page';
$additional_css = ['login.css']; // Custom login page styles

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-container login-container">
    <!-- Back to Home Link -->
    <a href="<?php echo url('index.php'); ?>" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="login-header">
        <div class="welcome-graphic"></div>
        <h2>Welcome Back</h2>
        <p>Sign in to continue to your account</p>
    </div>

    <div id="response" class="response"></div>

    <form id="loginForm" method="post" action="<?php echo url('../actions/process_login.php'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group email">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
        </div>

        <div class="form-group password">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
        </div>

        <div class="remember-section">
            <div class="checkbox-wrapper">
                <input type="checkbox" id="remember" name="remember" class="custom-checkbox">
                <label for="remember">Remember me</label>
            </div>
            <a href="<?php echo BASE_URL; ?>/view/user/forgot_password.php" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
    </form>

    <div class="divider">
        <span>or</span>
    </div>

    <div class="auth-links">
        <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/view/user/register.php">Create one here</a></p>
    </div>
</div>

<?php
// Additional JavaScript for login page
$additional_js = ['login.js'];

// Include footer
include __DIR__ . '/../templates/footer.php';
?>
