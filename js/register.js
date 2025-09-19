document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("registerForm");
    const submitBtn = form.querySelector('.btn');
    const responseDiv = document.getElementById("response");
    const steps = document.querySelectorAll('.step');
    
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

        // Get form data
        const formData = new FormData(form);
        
        // Submit form via AJAX
        fetch('/ecommerce-authent/actions/register_customer_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Remove loading state
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;

            if (data === 'success') {
                showResponse('Account created successfully! Redirecting to login...', 'success');
                
                // Redirect after success
                setTimeout(() => {
                    window.location.href = '/ecommerce-authent/views/login.php';
                }, 2000);
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
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            responseDiv.classList.remove('show');
        }, 5000);
    }

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
});
