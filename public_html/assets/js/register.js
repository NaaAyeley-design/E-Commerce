/**
 * Registration Page JavaScript
 * 
 * Handles registration form submission with AJAX and enhanced UX
 */

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = registerForm.querySelector('button[type="submit"]');
    const passwordInput = document.getElementById('password');
    const passwordStrength = document.getElementById('passwordStrength');
    const strengthBar = passwordStrength.querySelector('.strength-bar');
    const strengthText = passwordStrength.querySelector('.strength-text');
    
    // Form submission handler
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearErrors();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        setLoadingState(true);
        
        // Prepare form data
        const formData = new FormData(registerForm);

        
        // Submit form via AJAX
        fetch(registerForm.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response received:', text);
                throw new Error('Invalid response format');
            }
            return response.json();
        })
        .then(data => {
            setLoadingState(false);

            if (data && data.success) {
                showSuccess(data.message || 'Registration successful. Redirecting...');

                // Decide redirect URL: prefer server-provided absolute URL, fallback to replacing filename
                const serverRedirect = data.redirect;
                let redirectUrl = null;
                if (serverRedirect && typeof serverRedirect === 'string') {
                    redirectUrl = serverRedirect;
                } else {
                    const newPath = window.location.pathname.replace('register.php', 'login.php');
                    redirectUrl = window.location.origin + newPath + window.location.search + window.location.hash;
                }

                // Perform redirect after short delay
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1500);
            } else {
                showError((data && data.message) || 'Registration failed. Please try again.');
            }
        })
        .catch(error => {
            setLoadingState(false);
            console.error('Registration error (fetch):', error);
            showError('An error occurred during registration. Please try again.');
        });
    });
    
    // Password strength indicator
    passwordInput.addEventListener('input', function() {
        updatePasswordStrength(this.value);
    });
    
    // Real-time validation
    const inputs = registerForm.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => clearFieldError(input));
    });
    
    /**
     * Validate the entire form
     */
    function validateForm() {
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        // Check terms acceptance
        const termsCheckbox = document.getElementById('terms');
        if (!termsCheckbox.checked) {
            showFieldError(termsCheckbox, 'You must accept the Terms of Service and Privacy Policy');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Validate individual field
     */
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        
        // Required field validation
        if (field.hasAttribute('required') && !value) {
            showFieldError(field, `${getFieldLabel(fieldName)} is required`);
            return false;
        }
        
        // Specific field validations
        switch (fieldName) {
            case 'email':
                if (value && !isValidEmail(value)) {
                    showFieldError(field, 'Please enter a valid email address');
                    return false;
                }
                break;
                
            case 'name':
                if (value && !isValidName(value)) {
                    showFieldError(field, 'Please enter a valid name (letters, spaces, hyphens, and apostrophes only)');
                    return false;
                }
                break;
                
            case 'password':
                if (value && value.length < 6) {
                    showFieldError(field, 'Password must be at least 6 characters long');
                    return false;
                }
                break;
                
            case 'contact':
                if (value && !isValidPhone(value)) {
                    showFieldError(field, 'Please enter a valid phone number');
                    return false;
                }
                break;
        }
        
        clearFieldError(field);
        return true;
    }
    
    /**
     * Update password strength indicator
     */
    function updatePasswordStrength(password) {
        let strength = 0;
        let strengthLabel = 'Weak';
        
        if (password.length >= 6) strength += 1;
        if (password.match(/[a-z]/)) strength += 1;
        if (password.match(/[A-Z]/)) strength += 1;
        if (password.match(/[0-9]/)) strength += 1;
        if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
        
        // Update strength bar
        strengthBar.style.width = (strength * 20) + '%';
        
        // Update strength text and color
        switch (strength) {
            case 0:
            case 1:
                strengthLabel = 'Very Weak';
                strengthBar.style.backgroundColor = '#dc3545';
                break;
            case 2:
                strengthLabel = 'Weak';
                strengthBar.style.backgroundColor = '#fd7e14';
                break;
            case 3:
                strengthLabel = 'Fair';
                strengthBar.style.backgroundColor = '#ffc107';
                break;
            case 4:
                strengthLabel = 'Good';
                strengthBar.style.backgroundColor = '#20c997';
                break;
            case 5:
                strengthLabel = 'Strong';
                strengthBar.style.backgroundColor = '#28a745';
                break;
        }
        
        strengthText.textContent = strengthLabel;
    }
    
    /**
     * Show field-specific error
     */
    function showFieldError(field, message) {
        clearFieldError(field);
        
        field.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }
    
    /**
     * Clear field-specific error
     */
    function clearFieldError(field) {
        field.classList.remove('error');
        
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    /**
     * Clear all form errors
     */
    function clearErrors() {
        const errorElements = registerForm.querySelectorAll('.field-error');
        errorElements.forEach(error => error.remove());
        
        const errorInputs = registerForm.querySelectorAll('.error');
        errorInputs.forEach(input => input.classList.remove('error'));
        
        hideMessage();
    }
    
    /**
     * Set loading state for form
     */
    function setLoadingState(loading) {
        if (loading) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        } else {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create My Account';
        }
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        showMessage(message, 'success');
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        showMessage(message, 'error');
    }
    
    /**
     * Show message with type
     */
    function showMessage(message, type) {
        // Use toast notification if available
        if (typeof Toast !== 'undefined') {
            if (type === 'success') {
                Toast.success(message);
            } else if (type === 'error') {
                Toast.error(message);
            } else {
                Toast.info(message);
            }
        }
        
        // Also show inline message for form context
        hideMessage();
        const messageDiv = document.createElement('div');
        messageDiv.className = `response ${type} show`;
        messageDiv.textContent = message;
        
        registerForm.insertBefore(messageDiv, registerForm.firstChild);
        
        // Auto-hide error messages after 5 seconds
        if (type === 'error') {
            setTimeout(() => {
                hideMessage();
            }, 5000);
        }
    }
    
    /**
     * Hide message
     */
    function hideMessage() {
        const existingMessage = registerForm.querySelector('.response');
        if (existingMessage) {
            existingMessage.remove();
        }
    }
    
    /**
     * Validation helper functions
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidName(name) {
        const nameRegex = /^[a-zA-Z\s\-']+$/;
        return nameRegex.test(name) && name.length >= 2;
    }
    
    function isValidPhone(phone) {
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 10;
    }
    
    function getFieldLabel(fieldName) {
        const labels = {
            'name': 'Full Name',
            'email': 'Email Address',
            'password': 'Password',
            'country': 'Country',
            'city': 'City',
            'contact': 'Contact Number',
            'terms': 'Terms and Conditions'
        };
        return labels[fieldName] || fieldName;
    }
    
    // Add CSS for field errors if not already present
    if (!document.querySelector('#register-error-styles')) {
        const style = document.createElement('style');
        style.id = 'register-error-styles';
        style.textContent = `
            .field-error {
                color: #dc3545;
                font-size: 0.875rem;
                margin-top: 5px;
                display: block;
            }
            
            .form-input.error {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
            }
            
            .password-strength {
                margin-top: 8px;
            }
            
            .strength-bar {
                height: 4px;
                background-color: #e9ecef;
                border-radius: 2px;
                transition: all 0.3s ease;
                width: 0%;
            }
            
            .strength-text {
                font-size: 0.75rem;
                color: #6c757d;
                margin-top: 4px;
                display: block;
            }
        `;
        document.head.appendChild(style);
    }
});

