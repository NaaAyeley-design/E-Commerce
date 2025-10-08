<?php
/**
 * Register Customer Action
 * 
 * Handles user registration requests with comprehensive validation
 */

// Include core settings and user controller
// require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// Validate CSRF token
validate_form_csrf();

// Rate limiting for registration attempts (more lenient for development)
check_action_rate_limit('register', 10, 60); // 10 attempts per minute

try {
    // Get and sanitize input
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $country = sanitize_input($_POST['country'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $contact = sanitize_input($_POST['contact'] ?? '');
    $terms = isset($_POST['terms']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($country) || empty($city) || empty($contact)) {
        echo "All fields are required.";
        exit;
    }

    // Validate terms acceptance
    if (!$terms) {
        echo "You must accept the terms and conditions.";
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Please enter a valid email address.";
        exit;
    }

    // Validate password strength
    if (strlen($password) < 6) {
        echo "Password must be at least 6 characters long.";
        exit;
    }

    // Validate contact number (basic check)
    if (!preg_match('/^\d{10,}$/', $contact)) {
        echo "Please enter a valid contact number (at least 10 digits).";
        exit;
    }

    // Attempt registration
    $result = register_user_ctr($name, $email, $password, $country, $city, $contact);
    
    // Return result
    echo $result;

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo "An error occurred during registration. Please try again.";
}
?>