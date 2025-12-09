<?php
/**
 * Logout Action
 * 
 * Handles user logout requests and session cleanup
 */

// Suppress error reporting to prevent code from showing
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings and controllers
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';
require_once __DIR__ . '/../controller/general_controller.php';

try {
    // Perform logout
    $result = logout_user_ctr();
    
    // Build absolute login URL using BASE_URL
    $loginUrl = rtrim(BASE_URL, '/') . '/view/user/login.php';

    if ($result === "success") {
        // Redirect to login page with success message
        redirect_with_message(
            $loginUrl . '?message=logged_out',
            'You have been logged out successfully.',
            'success'
        );
        exit;
    } else {
        // Redirect to login page with error message
        redirect_with_message(
            $loginUrl . '?error=logout_failed',
            'An error occurred during logout.',
            'error'
        );
        exit;
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Logout action error: " . $e->getMessage());
    
    // Redirect to login page with error message
    redirect_with_message(
        $loginUrl . '?error=logout_failed',
        'An error occurred during logout.',
        'error'
    );
    exit;
}