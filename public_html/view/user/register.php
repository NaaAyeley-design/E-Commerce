<?php
/**
 * Registration Page - Split Screen Design
 * 
 * Modern split-screen registration with decorative left panel and form on right
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
$body_class = 'auth-page register-page split-screen';
$additional_css = ['register.css']; // Custom registration page styles

// Include header
include __DIR__ . '/../templates/header.php';
?>

<style>
/* Split Screen Registration Layout */
.register-split-container {
    display: flex;
    min-height: 100vh;
    width: 100%;
    overflow: hidden;
}

/* Left Side - Design Panel */
.register-design-panel {
    flex: 1;
    background: #8B4513;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 60px 80px;
    overflow: hidden;
    z-index: 1;
}

/* Decorative Circles with Glow */
.design-circle {
    position: absolute;
    border-radius: 50%;
    opacity: 0.25;
    z-index: 0;
}

.circle-large {
    width: 300px;
    height: 300px;
    background: #FFD700;
    top: -100px;
    left: -100px;
    box-shadow: 
        0 0 60px rgba(255, 215, 0, 0.6),
        0 0 120px rgba(255, 215, 0, 0.4),
        0 0 180px rgba(255, 215, 0, 0.2),
        0 0 240px rgba(255, 215, 0, 0.1);
}

.circle-medium {
    width: 200px;
    height: 200px;
    background: #D2691E;
    bottom: -50px;
    right: -50px;
    box-shadow: 
        0 0 50px rgba(210, 105, 30, 0.6),
        0 0 100px rgba(210, 105, 30, 0.4),
        0 0 150px rgba(210, 105, 30, 0.2),
        0 0 200px rgba(210, 105, 30, 0.1);
}

.circle-small {
    width: 150px;
    height: 150px;
    background: #FFA500;
    top: 50%;
    right: 10%;
    transform: translateY(-50%);
    box-shadow: 
        0 0 40px rgba(255, 165, 0, 0.6),
        0 0 80px rgba(255, 165, 0, 0.4),
        0 0 120px rgba(255, 165, 0, 0.2),
        0 0 160px rgba(255, 165, 0, 0.1);
}

.circle-extra {
    width: 180px;
    height: 180px;
    background: #FFD700;
    top: 30%;
    left: 20%;
    box-shadow: 
        0 0 45px rgba(255, 215, 0, 0.5),
        0 0 90px rgba(255, 215, 0, 0.3),
        0 0 135px rgba(255, 215, 0, 0.2);
}

/* Left Panel Content */
.design-content {
    position: relative;
    z-index: 2;
    max-width: 500px;
}

.design-logo {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3.5rem;
    font-weight: 700;
    color: #FFFFFF;
    letter-spacing: 0.15em;
    margin-bottom: 40px;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
}

.design-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 600;
    color: #FFFFFF;
    margin-bottom: 20px;
    line-height: 1.3;
    text-shadow: 1px 1px 6px rgba(0, 0, 0, 0.3);
}

.design-subtitle {
    font-family: 'Spectral', serif;
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2);
}

/* Right Side - Form Panel */
.register-form-panel {
    flex: 1;
    background: #FFF9F0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 60px 80px;
    overflow-y: auto;
}

.register-form-container {
    width: 100%;
    max-width: 550px;
}

/* Form Header */
.register-form-header {
    text-align: center;
    margin-bottom: 40px;
}

.register-form-header h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.25rem;
    font-weight: 600;
    color: #2C1810;
    margin-bottom: 10px;
}

.register-form-header p {
    font-family: 'Spectral', serif;
    font-size: 1rem;
    color: #6B5B4F;
    line-height: 1.6;
}

/* Progress Steps */
.progress-steps {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
}

.progress-step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Spectral', serif;
    font-size: 1rem;
    font-weight: 600;
    border: 2px solid #D2691E;
    background: #FFF9F0;
    color: #D2691E;
    transition: all 0.3s ease;
}

.progress-step.active {
    background: #D2691E;
    color: #FFFFFF;
    box-shadow: 0 2px 8px rgba(210, 105, 30, 0.3);
}

/* Form Styles */
.register-form {
    width: 100%;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
    font-weight: 500;
    color: #2C1810;
    margin-bottom: 8px;
}

.form-group label i {
    color: #D2691E;
    font-size: 1rem;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E8DDD0;
    border-radius: 6px;
    font-family: 'Spectral', serif;
    font-size: 0.95rem;
    color: #2C1810;
    background: #FFFFFF;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #D2691E;
    box-shadow: 0 0 0 3px rgba(210, 105, 30, 0.1);
}

.form-input::placeholder {
    color: #9B8B7F;
}

/* Role Selection */
.role-selection {
    margin-bottom: 30px;
    padding: 20px;
    background: #FFFFFF;
    border-radius: 8px;
    border: 2px solid #E8DDD0;
}

.role-selection > label {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #2C1810;
}

.role-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.role-option {
    display: flex;
    align-items: center;
    padding: 18px;
    border: 2px solid #E8DDD0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #FFFFFF;
    position: relative;
}

.role-option:hover {
    border-color: #D2691E;
    box-shadow: 0 2px 8px rgba(210, 105, 30, 0.15);
}

.role-option input[type="radio"] {
    margin-right: 12px;
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #D2691E;
}

.role-option.selected {
    border-color: #D2691E;
    background: #FFF9F0;
    box-shadow: 0 2px 12px rgba(210, 105, 30, 0.2);
}

.role-option-content {
    flex: 1;
}

.role-option-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.role-option-title strong {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.125rem;
    color: #2C1810;
}

.role-option-title i {
    font-size: 1.25rem;
    color: #D2691E;
}

.role-option-desc {
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    color: #6B5B4F;
    margin: 0;
}

/* Designer Fields */
.designer-fields {
    grid-column: 1 / -1;
    margin-top: 20px;
    padding: 25px;
    background: #FFF9F0;
    border-radius: 8px;
    border: 2px solid #D2691E;
}

.designer-fields h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.25rem;
    color: #2C1810;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.designer-fields h3 i {
    color: #D2691E;
}

.designer-fields > p {
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    color: #6B5B4F;
    margin: 0 0 20px 0;
}

/* Form Grid */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.location-grid {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Password Strength */
.password-strength {
    margin-top: 8px;
}

.strength-bar {
    height: 4px;
    background: #E8DDD0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-bar-fill {
    height: 100%;
    width: 0%;
    background: #D2691E;
    transition: all 0.3s ease;
}

.strength-text {
    font-family: 'Spectral', serif;
    font-size: 0.75rem;
    color: #6B5B4F;
}

/* Checkbox */
.checkbox-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.checkbox-wrapper input[type="checkbox"] {
    margin-top: 4px;
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #D2691E;
}

.checkbox-wrapper label {
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    color: #2C1810;
    line-height: 1.5;
    margin: 0;
}

.checkbox-wrapper a {
    color: #D2691E;
    text-decoration: underline;
}

/* Submit Button */
.submit-button {
    width: 100%;
    padding: 16px 32px;
    background: linear-gradient(135deg, #D2691E 0%, #B8621E 100%);
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-family: 'Spectral', serif;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(210, 105, 30, 0.3);
}

.submit-button:hover {
    background: linear-gradient(135deg, #B8621E 0%, #A0522D 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(210, 105, 30, 0.4);
}

.submit-button:active {
    transform: translateY(0);
}

.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Auth Links */
.auth-links {
    text-align: center;
    margin-top: 25px;
}

.auth-links p {
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
    color: #6B5B4F;
    margin: 0;
}

.auth-links a {
    color: #D2691E;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.auth-links a:hover {
    color: #B8621E;
    text-decoration: underline;
}

/* Response Messages */
#response {
    margin-top: 20px;
    padding: 12px 16px;
    border-radius: 6px;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
    display: none;
}

#response.success {
    background: #D4EDDA;
    color: #155724;
    border: 1px solid #C3E6CB;
    display: block;
}

#response.error {
    background: #F8D7DA;
    color: #721C24;
    border: 1px solid #F5C6CB;
    display: block;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .register-split-container {
        flex-direction: column;
    }
    
    .register-design-panel {
        min-height: 40vh;
        padding: 40px 40px;
    }
    
    .register-form-panel {
        padding: 40px 40px;
    }
    
    .design-logo {
        font-size: 2.5rem;
    }
    
    .design-title {
        font-size: 1.75rem;
    }
}

@media (max-width: 768px) {
    .register-design-panel {
        min-height: 35vh;
        padding: 30px 30px;
    }
    
    .register-form-panel {
        padding: 30px 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .location-grid {
        grid-template-columns: 1fr;
    }
    
    .role-options {
        grid-template-columns: 1fr;
    }
    
    .design-logo {
        font-size: 2rem;
        margin-bottom: 20px;
    }
    
    .design-title {
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    
    .design-subtitle {
        font-size: 1rem;
    }
    
    .register-form-header h2 {
        font-size: 1.75rem;
    }
    
    .circle-large {
        width: 200px;
        height: 200px;
    }
    
    .circle-medium {
        width: 150px;
        height: 150px;
    }
    
    .circle-small {
        width: 120px;
        height: 120px;
    }
    
    .circle-extra {
        width: 140px;
        height: 140px;
    }
}

@media (max-width: 480px) {
    .register-design-panel {
        min-height: 30vh;
        padding: 20px 20px;
    }
    
    .register-form-panel {
        padding: 20px 15px;
    }
    
    .design-logo {
        font-size: 1.75rem;
    }
    
    .design-title {
        font-size: 1.25rem;
    }
    
    .design-subtitle {
        font-size: 0.9rem;
    }
}
</style>

<div class="register-split-container">
    <!-- Left Side - Design Panel -->
    <div class="register-design-panel">
        <!-- Decorative Circles -->
        <div class="design-circle circle-large"></div>
        <div class="design-circle circle-medium"></div>
        <div class="design-circle circle-small"></div>
        <div class="design-circle circle-extra"></div>
        
        <!-- Content -->
        <div class="design-content">
            <h1 class="design-logo">KENTEKART</h1>
            <h2 class="design-title">Welcome to Authentic African Fashion</h2>
            <p class="design-subtitle">Join a community of creators and conscious consumers celebrating Ghanaian heritage</p>
        </div>
</div>

    <!-- Right Side - Form Panel -->
    <div class="register-form-panel">
        <div class="register-form-container">
            <!-- Form Header -->
            <div class="register-form-header">
        <h2>Begin Your Cultural Journey</h2>
        <p>Join KenteKart and connect with authentic Ghanaian fashion while supporting artisan communities</p>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
                <div class="progress-step active">1</div>
                <div class="progress-step">2</div>
                <div class="progress-step">3</div>
    </div>

            <!-- Registration Form -->
            <form id="registerForm" class="register-form ajax-form" method="post" action="<?php echo url('actions/register_customer_action.php'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
                <!-- Role Selection -->
                <div class="form-group role-selection full-width">
                    <label>
                        <i class="fas fa-user-tag"></i>
                        I want to sign up as a:
                    </label>
                    <div class="role-options">
                        <label class="role-option" for="role-customer">
                            <input type="radio" id="role-customer" name="user_role" value="2" class="role-radio" required checked>
                            <div class="role-option-content">
                                <div class="role-option-title">
                                    <span style="font-size: 1.25rem;">🛍️</span>
                                    <strong>Customer/Buyer</strong>
                                </div>
                                <p class="role-option-desc">Shop authentic Ghanaian fashion</p>
                            </div>
                        </label>
                        <label class="role-option" for="role-designer">
                            <input type="radio" id="role-designer" name="user_role" value="3" class="role-radio" required>
                            <div class="role-option-content">
                                <div class="role-option-title">
                                    <span style="font-size: 1.25rem;">🎨</span>
                                    <strong>Designer/Producer</strong>
                                </div>
                                <p class="role-option-desc">Sell my products on KenteKart</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Form Fields -->
        <div class="form-grid">
            <div class="form-group name">
                        <label for="name">
                            <i class="fas fa-user"></i>
                            Full Name
                        </label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Enter your full name" required>
            </div>

            <div class="form-group email">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
            </div>

                    <div class="form-group password full-width">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Create a secure password" required>
                <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthBarFill"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Password strength</span>
                </div>
            </div>

            <div class="location-grid">
                <div class="form-group location">
                            <label for="country">
                                <i class="fas fa-globe"></i>
                                Country
                            </label>
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
                            <label for="city">
                                <i class="fas fa-map-marker-alt"></i>
                                City
                            </label>
                    <input type="text" id="city" name="city" class="form-input" placeholder="Your city" required>
                </div>
            </div>

                    <div class="form-group contact full-width">
                        <label for="contact">
                            <i class="fas fa-phone"></i>
                            Contact Number
                        </label>
                <input type="tel" id="contact" name="contact" class="form-input" placeholder="Your phone number" required>
            </div>
                    
                    <!-- Designer/Producer Specific Fields -->
                    <div id="designer-fields" class="designer-fields full-width" style="display: none;">
                        <h3>
                            <i class="fas fa-store"></i>
                            Business Information
                        </h3>
                        <p>Help us set up your producer profile</p>
                        
                        <div class="form-group">
                            <label for="business_name">
                                <i class="fas fa-building"></i>
                                Business Name <span style="color: #6B5B4F; font-weight: normal;">(Optional)</span>
                            </label>
                            <input type="text" id="business_name" name="business_name" class="form-input" placeholder="Your business or brand name">
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">
                                <i class="fas fa-info-circle"></i>
                                Brief Description/Bio <span style="color: #6B5B4F; font-weight: normal;">(Optional)</span>
                            </label>
                            <textarea id="bio" name="bio" class="form-input" rows="3" placeholder="Tell us about your products and craftsmanship..." style="resize: vertical; min-height: 80px;"></textarea>
                        </div>
                    </div>
            
            <!-- Terms and Conditions -->
                    <div class="form-group full-width">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="terms" name="terms" class="custom-checkbox" required>
                    <label for="terms">
                        I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                        <a href="#" target="_blank">Privacy Policy</a>
                    </label>
                </div>
            </div>
        </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-button">
                    <i class="fas fa-star"></i>
                    BEGIN MY JOURNEY
        </button>
    </form>

            <!-- Response Messages -->
    <div id="response" class="response"></div>

            <!-- Auth Links -->
    <div class="auth-links">
        <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide designer fields based on role selection
document.addEventListener('DOMContentLoaded', function() {
    const roleRadios = document.querySelectorAll('input[name="user_role"]');
    const designerFields = document.getElementById('designer-fields');
    const roleOptions = document.querySelectorAll('.role-option');
    
    function updateRoleSelection() {
        const selectedRole = document.querySelector('input[name="user_role"]:checked')?.value;
        
        // Show/hide designer fields
        if (selectedRole === '3') {
            designerFields.style.display = 'block';
        } else {
            designerFields.style.display = 'none';
        }
        
        // Update visual selection
        roleOptions.forEach(option => {
            const radio = option.querySelector('.role-radio');
            if (radio.checked) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    }
    
    // Add event listeners
    roleRadios.forEach(radio => {
        radio.addEventListener('change', updateRoleSelection);
    });
    
    // Initial update
    updateRoleSelection();
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthBarFill = document.getElementById('strengthBarFill');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput && strengthBarFill && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let strengthLabel = 'Weak';
            let strengthColor = '#EF4444';
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            const strengthPercent = (strength / 5) * 100;
            strengthBarFill.style.width = strengthPercent + '%';
            
            if (strength <= 2) {
                strengthLabel = 'Weak';
                strengthColor = '#EF4444';
            } else if (strength <= 3) {
                strengthLabel = 'Fair';
                strengthColor = '#F59E0B';
            } else if (strength <= 4) {
                strengthLabel = 'Good';
                strengthColor = '#10B981';
            } else {
                strengthLabel = 'Strong';
                strengthColor = '#10B981';
            }
            
            strengthBarFill.style.background = strengthColor;
            strengthText.textContent = 'Password strength: ' + strengthLabel;
            strengthText.style.color = strengthColor;
        });
    }
});
</script>

<?php
// Additional JavaScript for register page
$additional_js = ['assets/js/register.js'];

// Include footer
include __DIR__ . '/../templates/footer.php';
?>
