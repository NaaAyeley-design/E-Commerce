<?php
/**
 * Process Login Action
 * 
 * Handles user login requests with enhanced security and validation
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

// Rate limiting for login attempts
check_action_rate_limit('login', 5, 300); // 5 attempts per 5 minutes

try {
    // Get and sanitize input
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        echo 'Email and password are required.';
        exit;
    }
    
    // Additional validation
    if (!validate_email($email)) {
        echo 'Please enter a valid email address.';
        exit;
    }
    
    // Attempt login
    $result = login_customer_ctr($email, $password, $remember);
    
    // Return result
    echo $result;
    
} catch (Exception $e) {
    // Log error
    error_log("Login action error: " . $e->getMessage());
    
    // Return generic error message
    echo 'An error occurred during login. Please try again.';
}
?>
