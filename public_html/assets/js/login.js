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

    // Mark form as handled to prevent global handler from interfering
    loginForm.setAttribute('data-handled', 'true');
    
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

        // Submit form via AJAX with timeout
        const controller = new AbortController();
        const timeoutMs = 30000; // 30s timeout (increased for slow connections)
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        fetch(loginForm.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            // Check if response is OK
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            // Parse JSON response
            return response.json().catch(() => {
                // If JSON parsing fails, try to get text
                return response.text().then(text => {
                    throw new Error('Invalid JSON response: ' + text);
                });
            });
        })
        .then(data => {
            clearTimeout(timeoutId);
            setLoadingState(false);
            
            // Handle response - only show the message text, not the JSON structure
            if (data && data.success === true) {
                // Extract only the message text
                const message = (data.message && typeof data.message === 'string') 
                    ? data.message 
                    : 'Login successful! Redirecting...';
                showSuccess(message);
                
                // Redirect after short delay
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = loginForm.action.replace('actions/process_login.php', 'view/user/dashboard.php');
                    }
                }, 1000);
            } else {
                // Extract only the message text for errors
                const errorMessage = (data && data.message && typeof data.message === 'string')
                    ? data.message
                    : 'Login failed. Please check your credentials and try again.';
                showError(errorMessage);
            }
        })
        .catch(err => {
            clearTimeout(timeoutId);
            setLoadingState(false);
            
            if (err.name === 'AbortError') {
                console.warn('Login error: request aborted (timeout)');
                showError('Login request timed out. This usually means:\n' +
                         '• The server is not responding\n' +
                         '• Database connection is slow\n' +
                         '• Network issues\n\n' +
                         'Please check:\n' +
                         '1. MySQL/MariaDB is running in XAMPP\n' +
                         '2. Your internet connection\n' +
                         '3. Try again in a few moments');
            } else if (err.message && err.message.includes('Failed to fetch')) {
                console.error('Login error: Network error', err);
                showError('Cannot connect to server. Please check:\n' +
                         '• Apache is running in XAMPP\n' +
                         '• You are accessing the correct URL\n' +
                         '• No firewall is blocking the connection');
            } else {
                console.error('Login error:', err);
                // Extract message from error if it's a JSON string
                let errorMessage = 'An error occurred during login. Please try again.';
                if (err.message) {
                    try {
                        // Try to parse if it's a JSON string
                        const parsed = JSON.parse(err.message);
                        if (parsed.message) {
                            errorMessage = parsed.message;
                        } else {
                            errorMessage = err.message;
                        }
                    } catch (e) {
                        // Not JSON, use the error message as-is
                        errorMessage = err.message;
                    }
                }
                showError(errorMessage);
            }
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
