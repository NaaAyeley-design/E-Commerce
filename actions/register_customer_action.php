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
    $user_role = isset($_POST['user_role']) ? (int)$_POST['user_role'] : 2;
    $business_name = sanitize_input($_POST['business_name'] ?? '');
    $bio = sanitize_input($_POST['bio'] ?? '');
    $terms = isset($_POST['terms']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($country) || empty($city) || empty($contact)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        ob_end_flush();
        exit;
    }
    
    // Validate and sanitize user role
    // Only allow role 2 (customer) or 3 (designer) - prevent role 1 (admin) from being set via registration
    if ($user_role !== 2 && $user_role !== 3) {
        $user_role = 2; // Default to customer if invalid role provided
    }
    
    // Validate role selection
    if (!isset($_POST['user_role']) || ($_POST['user_role'] != '2' && $_POST['user_role'] != '3')) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please select a registration type (Customer or Designer/Producer).']);
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
    // Pass role and designer-specific fields
    $result = register_user_ctr($name, $email, $password, $country, $city, $contact, $user_role, $business_name, $bio);

    if ($result === 'success') {
        // Determine redirect URL based on role
        // Role 2 (Customer) -> Customer dashboard after login
        // Role 3 (Designer) -> Designer dashboard after login
        // For now, redirect to login page - login will handle dashboard routing
        $loginUrl = rtrim(BASE_URL, '/') . '/view/user/login.php';
        
        $successMessage = $user_role == 3 
            ? 'Designer account created successfully! Redirecting to login...'
            : 'Registration successful! Redirecting to login page...';
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => $successMessage,
            'redirect' => $loginUrl,
            'user_role' => $user_role
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