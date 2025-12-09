<?php
/**
 * Forgot Password Action
 * 
 * Handles password reset requests with enhanced security
 */

// Include core settings and user controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

// Validate CSRF token
validate_form_csrf();

// Rate limiting for forgot password attempts
check_action_rate_limit('forgot_password', 3, 600); // 3 attempts per 10 minutes

try {
    // Get and sanitize input
    $email = sanitize_input($_POST['email'] ?? '');
    
    // Basic validation
    if (empty($email)) {
        echo 'Email address is required.';
        exit;
    }
    
    // Validate email format
    if (!validate_email($email)) {
        echo 'Please enter a valid email address.';
        exit;
    }
    
    // Process forgot password request
    $result = forgot_password_ctr($email);
    
    // Return result
    echo $result;
    
} catch (Exception $e) {
    // Log error
    error_log("Forgot password action error: " . $e->getMessage());
    
    // Return generic error message
    echo 'An error occurred. Please try again.';
}
?>

