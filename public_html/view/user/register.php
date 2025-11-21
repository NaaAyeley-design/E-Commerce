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
$page_description = 'Join KenteKart - Connect with authentic Ghanaian fashion and support talented artisans.';
$standalone_page = true;
$body_class = 'auth-page register-page';
$additional_css = ['register.css']; // Custom registration page styles

// Include header
include __DIR__ . '/../templates/header.php';
?>

<!-- Cultural Badge -->
<div class="cultural-badge">
    <i class="fas fa-heart"></i> Supporting 500+ Ghanaian Artisans
</div>

<!-- Back to Home Link -->
<a href="<?php echo url('index.php'); ?>" class="back-home">
    <i class="fas fa-arrow-left"></i> Back to Home
</a>

<div class="auth-container register-container">
    <div class="register-header">
        <h2>Begin Your Cultural Journey</h2>
        <p>Join KenteKart and connect with authentic Ghanaian fashion while supporting artisan communities</p>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step active">1</div>
        <div class="step">2</div>
        <div class="step">3</div>
    </div>

    <form id="registerForm" class="ajax-form" method="post" action="<?php echo url('actions/register_customer_action.php'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-grid">
            <div class="form-group name">
                <label for="name"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Enter your full name" required>
            </div>

            <div class="form-group email">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
            </div>

            <div class="form-group password">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Create a secure password" required>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar"></div>
                    <span class="strength-text">Password strength</span>
                </div>
            </div>

            <div class="location-grid">
                <div class="form-group location">
                    <label for="country"><i class="fas fa-globe"></i> Country</label>
                    <select id="country" name="country" class="form-input" required>
                        <option value="">Select Country</option>
                        <option value="GH">Ghana</option>
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="UK">United Kingdom</option>
                        <option value="DE">Germany</option>
                        <option value="NG">Nigeria</option>
                        <option value="KE">Kenya</option>
                        <option value="ZA">South Africa</option>
                        <option value="FR">France</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group location">
                    <label for="city"><i class="fas fa-map-marker-alt"></i> City</label>
                    <input type="text" id="city" name="city" class="form-input" placeholder="Your city" required>
                </div>
            </div>

            <div class="form-group contact">
                <label for="contact"><i class="fas fa-phone"></i> Contact Number</label>
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
            <i class="fas fa-star"></i> Begin My Journey
        </button>
    </form>

    <div id="response" class="response"></div>

    <div class="auth-links">
        <p>Already have an account? <a href="login.php">Sign in here</a></p>
    </div>
</div>

<?php
// Additional JavaScript for register page
$additional_js = ['assets/js/register.js'];

// Include footer
include __DIR__ . '/../templates/footer.php';
?>