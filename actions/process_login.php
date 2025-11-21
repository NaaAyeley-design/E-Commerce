<?php
/**
 * Process Login Action
 * 
 * Handles user login requests with comprehensive validation
 */

// Start output buffering to prevent any accidental output
ob_start();

// Enable error reporting for debugging (can be disabled in production)
if (defined('APP_ENV') && APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Don't display, but log
    ini_set('log_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Set execution time limit for login (30 seconds max)
set_time_limit(30);

// Include core settings and user controller
try {
    require_once __DIR__ . '/../settings/core.php';
    require_once __DIR__ . '/../controller/user_controller.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Server configuration error. Please contact support.'
    ]);
    error_log("Login endpoint error: " . $e->getMessage());
    ob_end_flush();
    exit;
}

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

// Rate limiting for login attempts
check_action_rate_limit('login', 5, 300); // 5 attempts per 5 minutes

try {
    // Get and sanitize input
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $is_ajax = isset($_POST['ajax']);

    // Validate required fields
    if (empty($email) || empty($password)) {
        $message = "Email and password are required.";
        ob_clean();
        echo json_encode(['success' => false, 'message' => $message]);
        ob_end_flush();
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        ob_clean();
        echo json_encode(['success' => false, 'message' => $message]);
        ob_end_flush();
        exit;
    }

    // Attempt login with timeout protection
    $start_time = microtime(true);
    $result = login_user_ctr($email, $password, $remember);
    $duration = microtime(true) - $start_time;
    
    // Log if login takes too long
    if ($duration > 5) {
        error_log("Login took {$duration} seconds for {$email}");
    }

    if ($result === "success") {
        // Determine redirect URL based on user role
        $redirect_url = url('view/user/dashboard.php');
        if (is_admin()) {
            $redirect_url = url('view/admin/dashboard.php');
        }
        // Always return JSON with redirect URL, even for non-AJAX
        $response = [
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => $redirect_url
        ];
        // Clean any output and send JSON
        ob_clean();
        echo json_encode($response);
        ob_end_flush();
        exit;
    } else {
        // Log failed attempt for debugging (do not include password)
        error_log("Login failed for {$email}: " . $result);
        // Always return JSON error
        ob_clean();
        echo json_encode(['success' => false, 'message' => $result]);
        ob_end_flush();
        exit;
    }

} catch (Exception $e) {
    // Log exception details for server-side debugging
    error_log("Login exception for {$email}: " . $e->getMessage());
    error_log($e->getTraceAsString());
    $message = "An internal error occurred during login. Please try again later.";
    ob_clean();
    echo json_encode(['success' => false, 'message' => $message]);
    ob_end_flush();
    exit;
}