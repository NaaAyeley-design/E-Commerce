/**
 * Registration Page JavaScript
 * 
 * Handles registration form submission with AJAX and enhanced UX
 */

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = registerForm.querySelector('button[type="submit"]');
    const responseDiv = document.getElementById('response');
    
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
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            setLoadingState(false);
            
            if (data === 'success') {
                showSuccess('Registration successful! Redirecting to login...');
                
                // Redirect to login page after short delay
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showError(data);
            }
        })
        .catch(error => {
            setLoadingState(false);
            console.error('Registration error:', error);
            showError('An error occurred during registration. Please try again.');
        });
    });
    
    /**
     * Validate the entire form
     */
    function validateForm() {
        let isValid = true;
        
        // Check required fields
        const requiredFields = registerForm.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                clearFieldError(field);
            }
        });
        
        // Validate email
        const emailField = document.getElementById('email');
        if (emailField.value && !isValidEmail(emailField.value)) {
            showFieldError(emailField, 'Please enter a valid email address');
            isValid = false;
        }
        
        // Validate password
        const passwordField = document.getElementById('password');
        if (passwordField.value && passwordField.value.length < 6) {
            showFieldError(passwordField, 'Password must be at least 6 characters');
            isValid = false;
        }
        
        // Validate terms checkbox
        const termsField = document.getElementById('terms');
        if (!termsField.checked) {
            showFieldError(termsField, 'You must accept the terms and conditions');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
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
        hideMessage();
        
        responseDiv.className = `response ${type} show`;
        responseDiv.textContent = message;
        
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
        responseDiv.className = 'response';
        responseDiv.textContent = '';
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
            
            .custom-checkbox.error + label {
                color: #dc3545;
            }
        `;
        document.head.appendChild(style);
    }
});
