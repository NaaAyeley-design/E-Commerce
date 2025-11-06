/**
 * E-Commerce Platform Main JavaScript
 * 
 * This file contains all the JavaScript functionality for the e-commerce platform.
 * Extracted from individual PHP files for better organization and maintainability.
 */

// Main application object
const ECommerceApp = {
    // Configuration
    config: {
        baseUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, ''),
        apiUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/../actions',
        assetsUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/assets'
    },

    // Initialize the application
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupFormValidation();
    },

    // Setup global event listeners
    setupEventListeners: function() {
        document.addEventListener('DOMContentLoaded', () => {
            this.handlePageLoad();
        });

        // Handle form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                this.handleAjaxForm(e.target);
            }
        });

        // Handle button clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn')) {
                this.handleButtonClick(e.target);
            }
        });
    },

    // Initialize components when page loads
    initializeComponents: function() {
        this.initializeCards();
        this.initializeButtons();
        this.initializeInputs();
        this.initializeProgressSteps();
        this.initializeCheckboxes();
        this.initializeGraphics();
    },

    // Handle page load
    handlePageLoad: function() {
        // Add loading animations
        const containers = document.querySelectorAll('.auth-container, .login-container, .register-container, .home-container');
        containers.forEach(container => {
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease-out';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });

        // Initialize specific page functionality
        const currentPage = this.getCurrentPage();
        switch(currentPage) {
            case 'login':
                this.initializeLoginPage();
                break;
            case 'register':
                this.initializeRegisterPage();
                break;
            case 'forgot-password':
                this.initializeForgotPasswordPage();
                break;
            case 'dashboard':
                this.initializeDashboardPage();
                break;
        }
    },

    // Get current page identifier
    getCurrentPage: function() {
        const path = window.location.pathname;
        if (path.includes('login')) return 'login';
        if (path.includes('register')) return 'register';
        if (path.includes('forgot')) return 'forgot-password';
        if (path.includes('dashboard')) return 'dashboard';
        return 'home';
    },

    // Initialize cards with hover effects
    initializeCards: function() {
        const cards = document.querySelectorAll('.card, .stat-card');
        cards.forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });
    },

    // Initialize buttons with enhanced effects
    initializeButtons: function() {
        const buttons = document.querySelectorAll('.btn, .nav-link');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                if (!this.classList.contains('loading')) {
                    this.style.transform = 'translateY(-2px) scale(1.05)';
                }
            });
            
            button.addEventListener('mouseleave', function() {
                if (!this.classList.contains('loading')) {
                    this.style.transform = 'translateY(0) scale(1)';
                }
            });
        });
    },

    // Initialize input fields with validation and effects
    initializeInputs: function() {
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            // Add focus animations
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
                ECommerceApp.validateInput(this);
            });

            // Real-time validation
            input.addEventListener('input', function() {
                ECommerceApp.validateInput(this);
            });
        });
    },

    // Initialize progress steps for registration
    initializeProgressSteps: function() {
        const steps = document.querySelectorAll('.step');
        const inputs = document.querySelectorAll('.form-input');
        
        if (steps.length > 0 && inputs.length > 0) {
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    this.updateProgressSteps();
                });
            });
        }
    },

    // Update progress steps based on form completion
    updateProgressSteps: function() {
        const inputs = document.querySelectorAll('.form-input');
        const steps = document.querySelectorAll('.step');
        
        if (inputs.length === 0 || steps.length === 0) return;

        const totalInputs = inputs.length;
        const filledInputs = Array.from(inputs).filter(input => 
            input.value.trim() && this.validateInput(input, false)
        ).length;
        
        const progress = Math.floor((filledInputs / totalInputs) * steps.length);
        
        steps.forEach((step, index) => {
            if (index < progress) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });
        
        // Update progress bar width
        const progressPercentage = (progress / steps.length) * 100;
        document.documentElement.style.setProperty('--progress-width', `${progressPercentage}%`);
    },

    // Initialize checkboxes with animations
    initializeCheckboxes: function() {
        const checkboxes = document.querySelectorAll('.custom-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                }
            });
        });
    },

    // Initialize interactive graphics
    initializeGraphics: function() {
        const graphics = document.querySelectorAll('.welcome-graphic, .forgot-icon');
        graphics.forEach(graphic => {
            graphic.addEventListener('click', function() {
                this.style.transform = 'rotate(360deg) scale(1.2)';
                setTimeout(() => {
                    this.style.transform = 'rotate(0deg) scale(1)';
                }, 600);
            });
        });
    },

    // Setup form validation
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    },

    // Validate individual input
    validateInput: function(input, showError = true) {
        const value = input.value.trim();
        
        // Remove previous error state
        input.classList.remove('error');
        
        // Basic validation
        if (!value && input.required) {
            if (showError) input.classList.add('error');
            return false;
        }

        // Email validation
        if (input.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                if (showError) input.classList.add('error');
                return false;
            }
        }

        // Password validation
        if (input.type === 'password' && value) {
            if (value.length < 6) {
                if (showError) input.classList.add('error');
                return false;
            }
        }

        // Phone validation
        if (input.type === 'tel' && value) {
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            if (!phoneRegex.test(value)) {
                if (showError) input.classList.add('error');
                return false;
            }
        }

        return true;
    },

    // Validate entire form
    validateForm: function(form) {
        const inputs = form.querySelectorAll('.form-input');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                isValid = false;
            }
        });

        return isValid;
    },

    // Handle AJAX form submissions
    handleAjaxForm: function(form) {
        const submitBtn = form.querySelector('.btn[type="submit"]');
        const responseDiv = form.querySelector('.response') || document.getElementById('response');
        
        if (!this.validateForm(form)) {
            this.showResponse('Please fill in all fields correctly.', 'error', responseDiv);
            return;
        }

        // Add loading state
        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }

        // Show loading overlay if exists
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('show');
        }

        // Get form data
        const formData = new FormData(form);
        
        // Submit form via AJAX
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            this.handleFormResponse(data, form, submitBtn, responseDiv, loadingOverlay);
        })
        .catch(error => {
            this.handleFormError(error, submitBtn, responseDiv, loadingOverlay);
        });
    },

    // Handle form response
    handleFormResponse: function(data, form, submitBtn, responseDiv, loadingOverlay) {
        // Remove loading state
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
        if (loadingOverlay) {
            loadingOverlay.classList.remove('show');
        }

        if (data === 'success') {
            this.handleSuccessResponse(form, responseDiv);
        } else {
            this.showResponse(data, 'error', responseDiv);
        }
    },

    // Handle successful form submission
    handleSuccessResponse: function(form, responseDiv) {
        const currentPage = this.getCurrentPage();
        
        switch(currentPage) {
            case 'login':
                this.showResponse('Login successful! Redirecting to dashboard...', 'success', responseDiv);
                document.querySelector('.login-container').classList.add('success');
                setTimeout(() => {
                    window.location.href = this.config.baseUrl + '/view/user/dashboard.php';
                }, 2000);
                break;
                
            case 'register':
                this.showResponse('Account created successfully! Redirecting to login...', 'success', responseDiv);
                setTimeout(() => {
                    window.location.href = this.config.baseUrl + '/view/user/login.php';
                }, 2000);
                break;
                
            case 'forgot-password':
                this.showResponse('Password reset link sent to your email!', 'success', responseDiv);
                form.reset();
                break;
                
            default:
                this.showResponse('Operation completed successfully!', 'success', responseDiv);
        }
    },

    // Handle form errors
    handleFormError: function(error, submitBtn, responseDiv, loadingOverlay) {
        // Remove loading state
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
        if (loadingOverlay) {
            loadingOverlay.classList.remove('show');
        }
        
        this.showResponse('An error occurred. Please try again.', 'error', responseDiv);
        console.error('Error:', error);
    },

    // Show response message
    showResponse: function(message, type, responseDiv) {
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
        
        if (!responseDiv) return;
        
        responseDiv.textContent = message;
        responseDiv.className = `response ${type} show`;
        
        // Auto hide after 5 seconds for error messages
        if (type === 'error') {
            setTimeout(() => {
                responseDiv.classList.remove('show');
            }, 5000);
        }
    },

    // Handle button clicks
    handleButtonClick: function(button) {
        // Add click animation
        button.style.transform = 'scale(0.95)';
        setTimeout(() => {
            button.style.transform = '';
        }, 150);
    },

    // Initialize login page specific functionality
    initializeLoginPage: function() {
        // Add keyboard navigation enhancement
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                const form = document.getElementById('loginForm');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });

        // Check for URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const error = urlParams.get('error');
        
        if (message === 'logged_out') {
            this.showResponse('You have been logged out successfully.', 'success', document.getElementById('response'));
        } else if (error === 'login_required') {
            this.showResponse('Please log in to access that page.', 'error', document.getElementById('response'));
        }
    },

    // Initialize register page specific functionality
    initializeRegisterPage: function() {
        // Update progress steps on page load
        setTimeout(() => {
            this.updateProgressSteps();
        }, 100);
    },

    // Initialize forgot password page specific functionality
    initializeForgotPasswordPage: function() {
        // Focus on email input
        const emailInput = document.getElementById('email');
        if (emailInput) {
            setTimeout(() => {
                emailInput.focus();
            }, 500);
        }
    },

    // Initialize dashboard page specific functionality
    initializeDashboardPage: function() {
        // Add interactive functionality to dashboard elements
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('click', function() {
                // Add click animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });

        // Add hover effects to action buttons
        const actionButtons = document.querySelectorAll('.action-buttons .btn');
        actionButtons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    },

    // Utility functions
    utils: {
        // Debounce function for performance
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Throttle function for performance
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            }
        },

        // Format currency
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },

        // Format date
        formatDate: function(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(date));
        }
    }
};

// Initialize the application when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        ECommerceApp.init();
    });
} else {
    ECommerceApp.init();
}

// Export for use in other scripts
window.ECommerceApp = ECommerceApp;
