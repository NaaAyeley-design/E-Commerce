<?php
/**
 * Register Customer Action
 * 
 * Handles user registration requests with comprehensive validation
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

// Rate limiting for registration attempts
check_action_rate_limit('register', 3, 300); // 3 attempts per 5 minutes

try {
    // Get and sanitize input
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $country = sanitize_input($_POST['country'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $contact = sanitize_input($_POST['contact'] ?? '');
    $terms = isset($_POST['terms']) && $_POST['terms'] === 'on';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || 
        empty($country) || empty($city) || empty($contact)) {
        echo 'All fields are required.';
        exit;
    }
    
    // Terms acceptance validation
    if (!$terms) {
        echo 'You must accept the Terms of Service and Privacy Policy.';
        exit;
    }
    
    // Additional validation
    if (!validate_email($email)) {
        echo 'Please enter a valid email address.';
        exit;
    }
    
    if (!validate_name($name)) {
        echo 'Please enter a valid name.';
        exit;
    }
    
    if (!validate_phone($contact)) {
        echo 'Please enter a valid phone number.';
        exit;
    }
    
    // Password strength validation
    $password_validation = validate_password($password);
    if ($password_validation !== true) {
        echo $password_validation;
        exit;
    }
    
    // Attempt registration
    $result = register_user_ctr($name, $email, $password, $country, $city, $contact);
    
    // Return result
    echo $result;
    
} catch (Exception $e) {
    // Log error
    error_log("Registration action error: " . $e->getMessage());
    
    // Return generic error message
    echo 'An error occurred during registration. Please try again.';
}
?>