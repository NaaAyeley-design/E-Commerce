<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Authentication System</title>
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
        }

        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }

        /* Container Styles */
        .auth-container {
            background: var(--white);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .auth-container:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
            transition: var(--transition);
        }

        /* Home Page Styles */
        .home-container {
            text-align: center;
            background: var(--white);
            padding: 60px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            animation: slideInUp 0.6s ease-out;
        }

        .home-container h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }

        .home-container p {
            color: var(--gray);
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .nav-link {
            display: inline-block;
            padding: 15px 30px;
            text-decoration: none;
            color: var(--white);
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .nav-link.register {
            background: var(--primary-gradient);
        }

        .nav-link.login {
            background: var(--secondary-gradient);
        }

        .nav-link:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        /* Form Styles */
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            font-size: 2rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .auth-header p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light-gray);
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

        /* Button Styles */
        .btn {
            width: 100%;
            padding: 15px;
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
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--secondary-gradient);
            color: var(--white);
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
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Error and Success Messages */
        .message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
            display: none;
            animation: slideInUp 0.3s ease-out;
        }

        .message.show {
            display: block;
        }

        .message-success {
            background: var(--success-gradient);
            color: var(--white);
        }

        .message-error {
            background: var(--error-gradient);
            color: var(--white);
        }

        /* Links */
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

        /* Logout Button */
        .logout-btn {
            background: var(--error-gradient);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: fixed;
            top: 20px;
            right: 20px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-container, .home-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .home-container h1 {
                font-size: 2rem;
            }

            .nav-links {
                flex-direction: column;
                align-items: center;
            }

            .nav-link {
                width: 100%;
                max-width: 250px;
                text-align: center;
            }

            .auth-header h2 {
                font-size: 1.5rem;
            }

            .logout-btn {
                position: relative;
                top: auto;
                right: auto;
                width: 100%;
                margin-top: 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .auth-container, .home-container {
                padding: 20px 15px;
            }

            .form-input, .btn {
                padding: 12px;
            }
        }

        /* Floating Labels Effect */
        .form-group.floating {
            position: relative;
        }

        .form-group.floating label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            background: var(--white);
            padding: 0 5px;
            transition: var(--transition);
            pointer-events: none;
            color: var(--gray);
        }

        .form-group.floating .form-input:focus + label,
        .form-group.floating .form-input:valid + label {
            top: 0;
            font-size: 0.8rem;
            color: #667eea;
            font-weight: 600;
        }

        /* Glass morphism effect for modern look */
        .glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body>
    <!-- Demo Home Page -->
    <div class="home-container">
        <h1>E-Commerce Platform</h1>
        <p>Welcome to your modern shopping experience</p>
        <div class="nav-links">
            <a href="views\register.php" class="nav-link register">Create Account</a>
            <a href="views\login.php" class="nav-link login">Sign In</a>


        </div>
    </div>

    <!-- Demo Login Form (hidden by default) -->
    <div class="auth-container" style="display: none;" id="loginForm">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Sign in to continue to your account</p>
        </div>
        
        <div class="message message-error" id="errorMessage">
            Invalid email or password. Please try again.
        </div>
        
        <div class="message message-success" id="successMessage">
            Login successful! Redirecting...
        </div>
        
        <form id="loginFormElement">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Create one here</a></p>
        </div>
    </div>

    <!-- Demo Register Form (hidden by default) -->
    <div class="auth-container" style="display: none;" id="registerForm">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Join us and start your shopping journey</p>
        </div>
        
        <div class="message message-error" id="regErrorMessage">
            Please check your information and try again.
        </div>
        
        <form id="registerFormElement">
            <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" id="firstName" name="firstName" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" id="lastName" name="lastName" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="regEmail">Email Address</label>
                <input type="email" id="regEmail" name="email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="regPassword">Password</label>
                <input type="password" id="regPassword" name="password" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" required>
            </div>
            
            <button type="submit" class="btn btn-secondary">Create Account</button>
        </form>
        
        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>

    <script>
        // Demo functionality for button loading states
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Add loading state
                this.classList.add('loading');
                
                // Remove loading state after 2 seconds (demo)
                setTimeout(() => {
                    this.classList.remove('loading');
                    
                    // Show success message
                    const successMsg = document.querySelector('.message-success');
                    if (successMsg) {
                        successMsg.classList.add('show');
                        
                        // Hide after 3 seconds
                        setTimeout(() => {
                            successMsg.classList.remove('show');
                        }, 3000);
                    }
                }, 2000);
            });
        });

        // Demo form validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.validity.valid) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        });
    </script>
</body>
</html>