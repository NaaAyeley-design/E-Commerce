<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Ecommerce Auth</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="registration-container">
        <div class="registration-wrapper">
            <!-- Step 1: User Type Selection -->
            <div id="step1" class="registration-step active">
                <div class="registration-header">
                    <h1>Create Your Account</h1>
                    <p>Choose your account type to get started</p>
                </div>
                
                <div class="user-type-selection">
                    <div class="selection-card" data-type="customer">
                        <div class="card-icon">🛒</div>
                        <h3>Customer / Buyer</h3>
                        <p>Shop and purchase products from our marketplace</p>
                        <div class="card-arrow">→</div>
                    </div>
                    
                    <div class="selection-card" data-type="designer">
                        <div class="card-icon">🎨</div>
                        <h3>Designer / Producer</h3>
                        <p>Create and sell your products on our platform</p>
                        <div class="card-arrow">→</div>
                    </div>
                </div>
                
                <div class="back-to-login">
                    <p>Already have an account? <a href="login.php">Sign in</a></p>
                </div>
            </div>

            <!-- Step 2: Registration Form -->
            <div id="step2" class="registration-step">
                <div class="registration-header">
                    <button class="back-button" id="backButton">← Back</button>
                    <h1>Complete Your Registration</h1>
                    <p id="userTypeLabel">Register as <span id="selectedType"></span></p>
                </div>
                
                <form id="registrationForm" class="registration-form" method="POST" action="register_customer_action.php">
                    <input type="hidden" id="userType" name="user_type" value="">
                    
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="full_name" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Create a password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required placeholder="Confirm your password">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a></label>
                    </div>
                    
                    <button type="submit" class="submit-button">Create Account</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="register.js"></script>
</body>
</html>

