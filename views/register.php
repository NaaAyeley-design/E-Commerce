<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - E-Commerce Platform</title>
    <script src="../js/register.js" defer></script>
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
        .register-container {
            background: var(--white);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--secondary-gradient);
        }

        .register-container:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
            transition: var(--transition);
        }

        /* Header Styles */
        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .register-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .register-header p {
            color: var(--gray);
            font-size: 1rem;
            margin-bottom: 10px;
        }

        /* Progress Indicator */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .progress-steps::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            height: 2px;
            background: var(--secondary-gradient);
            width: 33%;
            transition: width 0.5s ease;
            z-index: 2;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray);
            z-index: 3;
            position: relative;
            transition: var(--transition);
        }

        .step.active {
            background: var(--secondary-gradient);
            color: var(--white);
            transform: scale(1.1);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
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
            position: relative;
        }

        .form-input:focus {
            outline: none;
            border-color: #f5576c;
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
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
            right: 15px;
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

        .form-group.name::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>');
        }

        .form-group.email::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2A2 2 0 0 0 0 4v.793c.106.032.22.058.341.085C1.081 5.104 2.16 5.289 3.5 5.289c1.34 0 2.419-.185 3.159-.411.121-.027.235-.053.341-.085V4a2 2 0 0 0-2-2H2z"/><path d="M2 3a1 1 0 0 0-1 1v8.5l4.778-2.667C7.169 9.247 8.52 9 10 9c1.48 0 2.831.247 4.222.833L19 12.5V4a1 1 0 0 0-1-1H2z"/></svg>');
        }

        .form-group.password::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>');
        }

        .form-group.location::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>');
        }

        .form-group.contact::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122L9.98 10.97a.68.68 0 0 1-.198-.013c-.59-.166-.1168-.505-1.414-1.801C6.869 7.857 6.53 6.364 6.364 5.774a.681.681 0 0 1-.013-.198l.540-1.805a.678.678 0 0 0-.122-.58L4.975 1.33z"/></svg>');
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
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Response Message */
        .response {
            margin-top: 20px;
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

        /* Login Link */
        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }

        .auth-links a {
            color: #f5576c;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .auth-links a:hover {
            color: #f093fb;
            text-decoration: underline;
        }

        /* Two Column Layout for Location Fields */
        .location-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .register-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .register-header h2 {
                font-size: 1.8rem;
            }

            .back-home {
                position: relative;
                top: auto;
                left: auto;
                display: block;
                text-align: center;
                margin-bottom: 20px;
            }

            .location-grid {
                grid-template-columns: 1fr;
            }

            .progress-steps {
                margin-bottom: 20px;
            }

            .step {
                width: 25px;
                height: 25px;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .register-container {
                padding: 25px 15px;
            }

            .form-input, .btn {
                padding: 12px;
            }

            .register-header h2 {
                font-size: 1.5rem;
            }
        }

        /* Success Animation */
        @keyframes checkmark {
            0% {
                height: 0;
                width: 0;
                opacity: 1;
            }
            20% {
                height: 0;
                width: 7px;
                opacity: 1;
            }
            40% {
                height: 14px;
                width: 7px;
                opacity: 1;
            }
            100% {
                height: 14px;
                width: 7px;
                opacity: 1;
            }
        }

        .success-checkmark {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            stroke-width: 2;
            stroke: #4facfe;
            stroke-miterlimit: 10;
            margin-right: 10px;
            box-shadow: inset 0px 0px 0px #4facfe;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .success-checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #4facfe;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .success-checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #4facfe;
            }
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-home">← Back to Home</a>
    
    <div class="register-container">
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

        <form id="registerForm" method="post" action="../actions/register_customer_action.php">
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
                </div>

                <div class="location-grid">
                    <div class="form-group location">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" class="form-input" placeholder="Your country" required>
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
            </div>

            <button type="submit" class="btn">Create My Account</button>
        </form>

        <div id="response" class="response"></div>

        <div class="auth-links">
            <p>Already have an account? <a href="/ecommerce-authent/views/login.php">Sign in here</a></p>
        </div>
    </div>

    <script>
        // Form validation and submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = form.querySelector('.btn');
            const responseDiv = document.getElementById('response');
            const steps = document.querySelectorAll('.step');
            const progressBar = document.querySelector('.progress-steps::after');
            
            // Input validation
            const inputs = form.querySelectorAll('.form-input');
            inputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    validateInput(this);
                    updateProgressSteps();
                });

                input.addEventListener('blur', function() {
                    validateInput(this);
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

                // Phone validation
                if (input.type === 'tel') {
                    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
                    if (!phoneRegex.test(value)) {
                        input.classList.add('error');
                        return false;
                    }
                }

                return true;
            }

            function updateProgressSteps() {
                const totalInputs = inputs.length;
                const filledInputs = Array.from(inputs).filter(input => 
                    input.value.trim() && validateInput(input)
                ).length;
                
                const progress = Math.floor((filledInputs / totalInputs) * 3);
                
                steps.forEach((step, index) => {
                    if (index < progress) {
                        step.classList.add('active');
                    } else {
                        step.classList.remove('active');
                    }
                });
                
                // Update progress bar width
                const progressPercentage = (progress / 3) * 100;
                document.documentElement.style.setProperty('--progress-width', `${progressPercentage}%`);
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
                    showResponse('Please fill in all fields correctly.', 'error');
                    return;
                }

                // Add loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;

                // Simulate form submission (replace with actual AJAX call)
                setTimeout(() => {
                    // Remove loading state
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;

                    // Show success message (this would be replaced by actual server response)
                    showResponse('Account created successfully! Redirecting to login...', 'success');
                    
                    // Redirect after success (optional)
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                }, 2000);
            });

            function showResponse(message, type) {
                responseDiv.textContent = message;
                responseDiv.className = `response ${type} show`;
                
                // Auto hide after 5 seconds
                setTimeout(() => {
                    responseDiv.classList.remove('show');
                }, 5000);
            }

            // Add some interactive feedback
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });

        // Add dynamic progress bar width update
        const style = document.createElement('style');
        style.textContent = `
            :root {
                --progress-width: 0%;
            }
            .progress-steps::after {
                width: var(--progress-width) !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>