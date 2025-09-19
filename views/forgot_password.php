<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - E-Commerce Platform</title>
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

        /* Main Container */
        .forgot-container {
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

        .forgot-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--secondary-gradient);
        }

        /* Header */
        .forgot-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .forgot-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .forgot-header p {
            color: var(--gray);
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .forgot-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: var(--secondary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }

        .forgot-icon::before {
            content: '🔒';
            font-size: 2rem;
        }

        /* Form Styles */
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
            padding: 16px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light-gray);
        }

        .form-input:focus {
            outline: none;
            border-color: #f5576c;
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
            transform: translateY(-2px);
        }

        .form-input.error {
            border-color: #dc3545;
            animation: shake 0.5s ease-in-out;
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
            background: var(--secondary-gradient);
            color: var(--white);
            margin-bottom: 20px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
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

        /* Back to Login Link */
        .back-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }

        .back-link a {
            color: #f5576c;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-link a:hover {
            color: #f093fb;
            text-decoration: underline;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .forgot-container {
                padding: 40px 25px;
                margin: 10px;
            }

            .forgot-header h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="forgot-icon"></div>
            <h2>Forgot Password?</h2>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <div id="response" class="response"></div>

        <form id="forgotForm" method="post" action="../actions/forgot_password_action.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
            </div>

            <button type="submit" class="btn">Send Reset Link</button>
        </form>

        <div class="back-link">
            <p>Remember your password? <a href="/ecommerce-authent/views/login.php">Sign in here</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotForm');
            const submitBtn = form.querySelector('.btn');
            const responseDiv = document.getElementById('response');
            const emailInput = document.getElementById('email');
            
            // Email validation
            emailInput.addEventListener('blur', function() {
                validateEmail(this);
            });

            function validateEmail(input) {
                const value = input.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                input.classList.remove('error');
                
                if (value && !emailRegex.test(value)) {
                    input.classList.add('error');
                    return false;
                }
                
                return true;
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateEmail(emailInput)) {
                    showResponse('Please enter a valid email address.', 'error');
                    return;
                }

                // Add loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;

                // Get form data
                const formData = new FormData(form);
                
                // Submit form via AJAX
                fetch('/ecommerce-authent/actions/forgot_password_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Remove loading state
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;

                    if (data === 'success') {
                        showResponse('Password reset link sent to your email!', 'success');
                        form.reset();
                    } else {
                        showResponse(data, 'error');
                    }
                })
                .catch(error => {
                    // Remove loading state
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    
                    showResponse('An error occurred. Please try again.', 'error');
                    console.error('Error:', error);
                });
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
        });
    </script>
</body>
</html>

