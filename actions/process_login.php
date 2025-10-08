<?php
/**
 * Process Login Action
 * 
 * Handles user login requests with comprehensive validation
 */

// Include core settings and user controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
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
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            echo $message;
        }
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            echo $message;
        }
        exit;
    }

    // Attempt login
    $result = login_user_ctr($email, $password, $remember);
    
    if ($result === "success") {
        // Determine redirect URL based on user role
        $redirect_url = url('view/user/dashboard.php');
        if (is_admin()) {
            $redirect_url = url('view/admin/dashboard.php');
        }
        
        if ($is_ajax) {
            $response = [
                'success' => true, 
                'message' => 'Login successful! Redirecting...',
                'redirect' => $redirect_url
            ];
            echo json_encode($response);
        } else {
            // Redirect to appropriate dashboard
            header('Location: ' . $redirect_url);
            exit;
        }
    } else {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $result]);
        } else {
            echo $result;
        }
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $message = "An error occurred during login. Please try again.";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        echo $message;
    }
}
?>