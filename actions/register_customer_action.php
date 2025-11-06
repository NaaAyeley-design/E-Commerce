<?php
/**
 * Register Customer Action
 * 
 * Handles user registration requests with comprehensive validation
 */

// Start output buffering to prevent any accidental output
ob_start();

// Suppress all output except JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Include core settings and user controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';

// Clear any output that may have been generated during includes
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    ob_end_flush();
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
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        ob_end_flush();
        exit;
    }

    // Validate terms acceptance
    if (!$terms) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'You must accept the terms and conditions.']);
        ob_end_flush();
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        ob_end_flush();
        exit;
    }

    // Validate password strength
    if (strlen($password) < 6) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
        ob_end_flush();
        exit;
    }

    // Validate contact number (basic check)
    if (!preg_match('/^\d{10,}$/', $contact)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please enter a valid contact number (at least 10 digits).']);
        ob_end_flush();
        exit;
    }

    // Attempt registration (do NOT log or echo passwords)
    $result = register_user_ctr($name, $email, $password, $country, $city, $contact);

    if ($result === 'success') {
        // Make redirect absolute using BASE_URL to avoid incorrect relative paths
        $loginUrl = rtrim(BASE_URL, '/') . '/view/user/login.php';
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Redirecting to login page...',
            'redirect' => $loginUrl
        ]);
        ob_end_flush();
        exit;
    } else {
        // Log the failure reason for debugging (non-sensitive)
        error_log("Registration failed for {$email}: " . $result);
        ob_clean();
        echo json_encode(['success' => false, 'message' => $result]);
        ob_end_flush();
        exit;
    }

} catch (Exception $e) {
    // Log detailed exception for server-side debugging (stack trace only in logs)
    error_log("Registration exception for {$email}: " . $e->getMessage());
    error_log($e->getTraceAsString());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'An internal error occurred during registration. Please try again later.']);
    ob_end_flush();
    exit;
}
?>