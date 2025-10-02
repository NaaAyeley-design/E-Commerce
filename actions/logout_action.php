<?php
/**
 * Logout Action
 * 
 * Handles user logout requests and session cleanup
 */

// Include core settings and user controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';

try {
    // Perform logout
    $result = logout_customer_ctr();
    
    if ($result === "success") {
        // Redirect to login page with success message
        redirect_with_message(
            BASE_URL . '/view/user/login.php?message=logged_out',
            'You have been logged out successfully.',
            'success'
        );
    } else {
        // Redirect to login page with error message
        redirect_with_message(
            BASE_URL . '/view/user/login.php?error=logout_failed',
            'An error occurred during logout.',
            'error'
        );
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Logout action error: " . $e->getMessage());
    
    // Redirect to login page with error message
    redirect_with_message(
        BASE_URL . '/view/user/login.php?error=logout_failed',
        'An error occurred during logout.',
        'error'
    );
}
?>