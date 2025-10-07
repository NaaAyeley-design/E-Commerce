<?php
/**
 * Debug Login Processor
 * This will log everything that happens during login
 */

// Log the request
error_log("=== LOGIN DEBUG START ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));
error_log("Headers: " . print_r(getallheaders(), true));

// Suppress error reporting to prevent code from showing
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings and user controller
require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../controller/user_controller.php';
require_once __DIR__ . '/../../controller/general_controller.php';

error_log("Core settings and controllers loaded");

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERROR: Not a POST request");
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
error_log("Is AJAX: " . ($is_ajax ? 'YES' : 'NO'));

try {
    // Validate CSRF token if available
    if (isset($_POST['csrf_token'])) {
        error_log("CSRF token provided: " . $_POST['csrf_token']);
        validate_csrf_token($_POST['csrf_token']);
        error_log("CSRF token validated");
    } else {
        error_log("No CSRF token provided");
    }

    // Get and sanitize input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    error_log("Email: " . $email);
    error_log("Password provided: " . (empty($password) ? 'NO' : 'YES'));
    error_log("Remember: " . ($remember ? 'YES' : 'NO'));
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error_msg = 'Email and password are required.';
        error_log("ERROR: " . $error_msg);
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
        error_log("ERROR: " . $error_msg);
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    error_log("Input validation passed, calling login_user_ctr()");
    
    // Attempt login
    $result = login_user_ctr($email, $password, $remember);
    
    error_log("Login result: " . $result);
    
    if ($result === "success") {
        error_log("Login successful, setting up redirect");
        
        // Determine redirect URL based on user role
        $redirect_url = BASE_URL . '/view/user/dashboard.php';
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
            $redirect_url = BASE_URL . '/view/admin/dashboard.php';
        }
        
        error_log("Redirect URL: " . $redirect_url);
        
        if ($is_ajax) {
            $response = [
                'success' => true, 
                'message' => 'Login successful! Redirecting...',
                'redirect' => $redirect_url
            ];
            error_log("AJAX response: " . json_encode($response));
            echo json_encode($response);
        } else {
            error_log("Redirecting to: " . $redirect_url);
            // Redirect to appropriate dashboard
            header('Location: ' . $redirect_url);
            exit;
        }
    } else {
        error_log("Login failed: " . $result);
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $result]);
        } else {
            echo $result;
        }
    }
    
} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
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

error_log("=== LOGIN DEBUG END ===");
?>
