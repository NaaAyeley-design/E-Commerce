<?php
/**
 * Register Customer Action
 * 
 * Handles user registration requests with comprehensive validation
 */

// Include core settings and user controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';

header('Content-Type: application/json');
// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
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
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Validate terms acceptance
    if (!$terms) {
        echo json_encode(['success' => false, 'message' => 'You must accept the terms and conditions.']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    // Validate password strength
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
        exit;
    }

    // Validate contact number (basic check)
    if (!preg_match('/^\d{10,}$/', $contact)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid contact number (at least 10 digits).']);
        exit;
    }

    // Attempt registration (do NOT log or echo passwords)
    $result = register_user_ctr($name, $email, $password, $country, $city, $contact);

    if ($result === 'success') {
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Redirecting to login page...',
            'redirect' => url('view/user/login.php')
        ]);
    } else {
        // Log the failure reason for debugging (non-sensitive)
        error_log("Registration failed for {$email}: " . $result);
        echo json_encode(['success' => false, 'message' => $result]);
    }

} catch (Exception $e) {
    // Log detailed exception for server-side debugging (stack trace only in logs)
    error_log("Registration exception for {$email}: " . $e->getMessage());
    error_log($e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An internal error occurred during registration. Please try again later.']);
}
?>