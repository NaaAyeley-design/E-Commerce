<?php
/**
 * Process Login Action
 * 
 * Handles user login requests with enhanced security and validation
 */

// Include core settings and user controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';
require_once __DIR__ . '/../controller/general_controller.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    } else {
        echo 'Method not allowed';
    }
    exit;
}

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

try {
    // Validate CSRF token if available
    if (isset($_POST['csrf_token'])) {
        validate_csrf_token($_POST['csrf_token']);
    }

    // Get and sanitize input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error_msg = 'Email and password are required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Additional validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Attempt login
    $result = login_user_ctr($email, $password, $remember);
    
    if ($result === "success") {
        // Determine redirect URL based on user role
        $redirect_url = BASE_URL . '/view/user/dashboard.php';
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
            $redirect_url = BASE_URL . '/view/admin/dashboard.php';
        }
        
        if ($is_ajax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful! Redirecting...',
                'redirect' => $redirect_url
            ]);
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
    // Log error
    error_log("Login action error: " . $e->getMessage());
    
    // Return generic error message
    $error_msg = 'An error occurred during login. Please try again.';
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>
