/**
 * Login Page JavaScript
 *
 * Handles login form submission with AJAX and enhanced UX
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    // Form submission handler
    loginForm.addEventListener('submit', function(e) {
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
        const formData = new FormData(loginForm);
        formData.append('ajax', '1');

        // Submit form via AJAX
        fetch(loginForm.action, {
            method: 'POST',
            body: formData,
            // Ensure credentials (cookies) are sent and accepted for same-origin
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

            if (data.success) {
                showSuccess(data.message);
                setTimeout(() => {
                    window.location.href = data.redirect || window.location.origin + window.location.pathname.replace('login.php', 'dashboard.php');
                }, 1500);
            } else {
                showError(data.message || 'Login failed');
            }
        })
        .catch(error => {
            setLoadingState(false);
            console.error('Login error:', error);
            showError('An error occurred during login. Please try again.');
        });
    });

    // Real-time validation
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);

    // Clear error on input
    emailInput.addEventListener('input', () => clearFieldError(emailInput));
    passwordInput.addEventListener('input', () => clearFieldError(passwordInput));

    /**
     * Validate the entire form
     */
    function validateForm() {
        let isValid = true;

        if (!validateEmail()) isValid = false;
        if (!validatePassword()) isValid = false;

        return isValid;
    }

    /**
     * Validate email field
     */
    function validateEmail() {
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email) {
            showFieldError(emailInput, 'Email is required');
            return false;
        }

        if (!emailRegex.test(email)) {
            showFieldError(emailInput, 'Please enter a valid email address');
            return false;
        }

        clearFieldError(emailInput);
        return true;
    }

    /**
     * Validate password field
     */
    function validatePassword() {
        const password = passwordInput.value;

        if (!password) {
            showFieldError(passwordInput, 'Password is required');
            return false;
        }

        if (password.length < 6) {
            showFieldError(passwordInput, 'Password must be at least 6 characters');
            return false;
        }

        clearFieldError(passwordInput);
        return true;
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
        const errorElements = loginForm.querySelectorAll('.field-error');
        errorElements.forEach(error => error.remove());

        const errorInputs = loginForm.querySelectorAll('.error');
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
            submitBtn.textContent = 'Signing In...';
        } else {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign In';
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

        loginForm.insertBefore(messageDiv, loginForm.firstChild);

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
        const existingMessage = loginForm.querySelector('.response');
        if (existingMessage) {
            existingMessage.remove();
        }
    }

    // Add CSS for field errors if not already present
    if (!document.querySelector('#login-error-styles')) {
        const style = document.createElement('style');
        style.id = 'login-error-styles';
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
        `;
        document.head.appendChild(style);
    }
});
