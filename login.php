<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Commerce Platform</title>
    <script src="login.js" defer></script>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --error-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --gray: #6c757d;
            --dark: #343a40;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>');
            pointer-events: none;
            z-index: -1;
        }

        /* Floating shapes animation */
        body::after {
            content: '';
            position: fixed;
            top: 20%;
            right: 10%;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            pointer-events: none;
            z-index: -1;
        }

        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Back to Home Button */
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-home:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Main Container */
        .login-container {
            background: var(--white);
            padding: 50px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .login-container:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
            transition: var(--transition);
        }

        /* Welcome Section */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--gray);
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .welcome-graphic {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }

        .welcome-graphic::before {
            content: '👋';
            font-size: 2rem;
            animation: float 3s ease-in-out infinite;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            position: relative;
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light-gray);
            position: relative;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-input:valid {
            border-color: #28a745;
        }

        .form-input.error {
            border-color: #dc3545;
            animation: shake 0.5s ease-in-out;
        }

        /* Input Icons */
        .form-group::before {
            content: '';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            margin-top: 14px; /* Adjust for label height */
            width: 20px;
            height: 20px;
            background-size: contain;
            background-repeat: no-repeat;
            opacity: 0.5;
            pointer-events: none;
            z-index: 2;
        }

        .form-group.email::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="%236c757d" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/></svg>');
        }

        .form-group.password::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="%236c757d" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>');
        }

        /* Remember Me Checkbox */
        .remember-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .custom-checkbox {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #e9ecef;
            border-radius: 4px;
            position: relative;
            cursor: pointer;
            transition: var(--transition);
        }

        .custom-checkbox:checked {
            background: var(--primary-gradient);
            border-color: transparent;
        }

        .custom-checkbox:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Button Styles */
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--primary-gradient);
            color: var(--white);
            margin-bottom: 20px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn.loading {
            color: transparent;
            pointer-events: none;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 22px;
            height: 22px;
            border: 3px solid transparent;
            border-top: 3px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Response Message */
        .response {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-align: center;
            display: none;
            animation: slideInUp 0.3s ease-out;
        }

        .response.show {
            display: block;
        }

        .response.success {
            background: var(--success-gradient);
            color: var(--white);
        }

        .response.error {
            background: var(--error-gradient);
            color: var(--white);
        }

        /* Social Login Section */
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .divider span {
            background: var(--white);
            padding: 0 15px;
        }

        /* Register Link */
        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }

        .auth-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .auth-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Demo Mode Indicator */
        .demo-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--success-gradient);
            color: var(--white);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            animation: pulse 2s ease-in-out infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                padding: 40px 25px;
                margin: 10px;
            }

            .login-header h2 {
                font-size: 2rem;
            }

            .back-home, .demo-badge {
                position: relative;
                top: auto;
                left: auto;
                right: auto;
                display: block;
                text-align: center;
                margin-bottom: 20px;
            }

            .remember-section {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .login-container {
                padding: 30px 20px;
            }

            .form-input, .btn {
                padding: 14px;
            }

            .login-header h2 {
                font-size: 1.8rem;
            }

            .welcome-graphic {
                width: 60px;
                height: 60px;
            }

            .welcome-graphic::before {
                font-size: 1.5rem;
            }
        }

        /* Success Animation Enhancements */
        .login-container.success {
            animation: slideInUp 0.6s ease-out, pulse 1s ease-in-out 0.6s;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Focus states for accessibility */
        .form-input:focus,
        .btn:focus,
        .custom-checkbox:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">← Back to Home</a>
    
    <div class="login-container">
        <div class="login-header">
            <div class="welcome-graphic"></div>
            <h2>Welcome Back</h2>
            <p>Sign in to continue to your account</p>
        </div>

        <div id="response" class="response"></div>

        <form id="loginForm" method="post" action="login_customer_action.php">
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
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>

        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Create one here</a></p>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
        // Form validation and submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = form.querySelector('.btn');
            const responseDiv = document.getElementById('response');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Input validation
            const inputs = form.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateInput(this);
                });

                input.addEventListener('blur', function() {
                    validateInput(this);
                });

                // Add focus animations
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            function validateInput(input) {
                const value = input.value.trim();
                
                // Remove previous error state
                input.classList.remove('error');
                
                // Basic validation
                if (!value) {
                    return false;
                }

                // Email validation
                if (input.type === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        input.classList.add('error');
                        return false;
                    }
                }

                // Password validation
                if (input.type === 'password') {
                    if (value.length < 6) {
                        input.classList.add('error');
                        return false;
                    }
                }

                return true;
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate all inputs
                let isValid = true;
                inputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    showResponse('Please enter valid email and password.', 'error');
                    return;
                }

                // Add loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                loadingOverlay.classList.add('show');

                // Simulate login process (replace with actual AJAX call to your login_customer_action.php)
                setTimeout(() => {
                    // Remove loading state
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    loadingOverlay.classList.remove('show');

                    // Show success message (this would be replaced by actual server response)
                    showResponse('Login successful! Redirecting to dashboard...', 'success');
                    
                    // Add success animation to container
                    document.querySelector('.login-container').classList.add('success');
                    
                    // Redirect after success (optional)
                    setTimeout(() => {
                        window.location.href = 'index.php'; // or dashboard.php
                    }, 2000);
                }, 2000);
            });

            function showResponse(message, type) {
                responseDiv.textContent = message;
                responseDiv.className = `response ${type} show`;
                
                // Auto hide after 5 seconds for error messages
                if (type === 'error') {
                    setTimeout(() => {
                        responseDiv.classList.remove('show');
                    }, 5000);
                }
            }

            // Add some visual feedback for the remember checkbox
            const rememberCheckbox = document.getElementById('remember');
            rememberCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                }
            });

            // Add keyboard navigation enhancement
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                    form.dispatchEvent(new Event('submit'));
                }
            });

            // Add some interactive elements
            const welcomeGraphic = document.querySelector('.welcome-graphic');
            welcomeGraphic.addEventListener('click', function() {
                this.style.transform = 'rotate(360deg) scale(1.2)';
                setTimeout(() => {
                    this.style.transform = 'rotate(0deg) scale(1)';
                }, 600);
            });
        });
    </script>
</body>
</html>