/**
 * Toast Notification System
 * 
 * A global notification system for displaying success, error, warning, and info messages
 */

(function() {
    'use strict';

    // Toast container
    let toastContainer = null;

    /**
     * Initialize toast container
     */
    function initToastContainer() {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    /**
     * Create a toast notification
     * @param {string} message - The message to display
     * @param {string} type - The type of toast (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds (default: 3000)
     */
    function createToast(message, type = 'info', duration = 3000) {
        initToastContainer();

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');

        // Create icon
        const icon = document.createElement('i');
        icon.className = getIconClass(type);
        toast.appendChild(icon);

        // Create message text
        const messageText = document.createElement('span');
        messageText.className = 'toast-message';
        messageText.textContent = message;
        toast.appendChild(messageText);

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.setAttribute('aria-label', 'Close notification');
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = () => removeToast(toast);
        toast.appendChild(closeBtn);

        // Add to container
        toastContainer.appendChild(toast);

        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                removeToast(toast);
            }, duration);
        }

        return toast;
    }

    /**
     * Get icon class based on type
     */
    function getIconClass(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    /**
     * Remove toast with animation
     */
    function removeToast(toast) {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    /**
     * Show success toast
     */
    function showSuccess(message, duration = 3000) {
        return createToast(message, 'success', duration);
    }

    /**
     * Show error toast
     */
    function showError(message, duration = 5000) {
        return createToast(message, 'error', duration);
    }

    /**
     * Show warning toast
     */
    function showWarning(message, duration = 4000) {
        return createToast(message, 'warning', duration);
    }

    /**
     * Show info toast
     */
    function showInfo(message, duration = 3000) {
        return createToast(message, 'info', duration);
    }

    // Export to global scope
    window.Toast = {
        show: createToast,
        success: showSuccess,
        error: showError,
        warning: showWarning,
        info: showInfo
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToastContainer);
    } else {
        initToastContainer();
    }
})();

