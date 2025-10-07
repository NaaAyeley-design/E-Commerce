<?php
/**
 * Forgot Password Page
 * 
 * Password reset request form with modern design
 */

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';

// Set page variables
$page_title = 'Forgot Password';
$page_description = 'Reset your password by entering your email address.';
$standalone_page = true;
$body_class = 'auth-page forgot-password-page';
$additional_css = ['forgot_password.css']; // Custom forgot password page styles

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-container forgot-container">
    <!-- Back to Login Link -->
    <a href="<?php echo url('view/user/login.php'); ?>" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Login
    </a>
    
    <div class="forgot-header">
        <div class="forgot-icon"></div>
        <h2>Forgot Password?</h2>
        <p>Enter your email address and we'll send you a link to reset your password.</p>
    </div>

    <div id="response" class="response"></div>

    <form id="forgotForm" class="ajax-form" method="post" action="<?php echo url('../actions/forgot_password_action.php'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group email">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
        </div>

        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
    </form>

    <div class="auth-links">
        <p>Remember your password? <a href="<?php echo BASE_URL; ?>/view/user/login.php">Sign in here</a></p>
    </div>
</div>

<?php
// Additional JavaScript for forgot password page
$additional_js = ['forgot-password.js'];

// Include footer
include __DIR__ . '/../templates/footer.php';
?>
