<?php
/**
 * Registration Page
 * 
 * User registration form with validation and progress indicators
 */

// Suppress error reporting to prevent code from showing
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';

// Set page variables
$page_title = 'Create Account';
$page_description = 'Join our e-commerce platform and start shopping with exclusive deals and offers.';
$standalone_page = true;
$body_class = 'auth-page register-page';
$additional_css = ['registercss.css']; // Custom registration page styles

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-container register-container">
    <!-- Back to Home Link -->
    <a href="<?php echo url('index.php'); ?>" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="register-header">
        <h2>Create Account</h2>
        <p>Join our e-commerce platform and start shopping!</p>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step active">1</div>
        <div class="step">2</div>
        <div class="step">3</div>
    </div>

    <form id="registerForm" class="ajax-form" method="post" action="<?php echo url('../actions/register_customer_action.php'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-grid">
            <div class="form-group name">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Enter your full name" required>
            </div>

            <div class="form-group email">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
            </div>

            <div class="form-group password">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Create a secure password" required>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar"></div>
                    <span class="strength-text">Password strength</span>
                </div>
            </div>

            <div class="location-grid">
                <div class="form-group location">
                    <label for="country">Country</label>
                    <select id="country" name="country" class="form-input" required>
                        <option value="">Select Country</option>
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="UK">United Kingdom</option>
                        <option value="AU">Australia</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="JP">Japan</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group location">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" class="form-input" placeholder="Your city" required>
                </div>
            </div>

            <div class="form-group contact">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" class="form-input" placeholder="Your phone number" required>
            </div>
            
            <!-- Terms and Conditions -->
            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="terms" name="terms" class="custom-checkbox" required>
                    <label for="terms">
                        I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                        <a href="#" target="_blank">Privacy Policy</a>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-user-plus"></i> Create My Account
        </button>
    </form>

    <div id="response" class="response"></div>

    <div class="auth-links">
        <p>Already have an account? <a href="login.php">Sign in here</a></p>
    </div>
</div>

<?php
// Additional JavaScript for register page
$additional_js = ['register.js'];

// Include footer
include __DIR__ . '/../templates/footer.php';
?>
