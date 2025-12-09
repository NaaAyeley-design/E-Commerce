<?php
/**
 * General Controller
 * 
 * Handles general application functionality and common operations.
 */

// Include core settings
require_once __DIR__ . '/../settings/core.php';

/**
 * Render a view with data
 */
function render_view($view_path, $data = []) {
    // Extract data to variables
    extract($data);
    
    // Include the view file
    $full_path = VIEW_PATH . '/' . $view_path;
    
    if (file_exists($full_path)) {
        include $full_path;
    } else {
        // Show error page
        include VIEW_PATH . '/error/404.php';
    }
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message, $type = 'info') {
    set_flash_message($message, $type);
    redirect($url);
}

/**
 * Check authentication and redirect if not logged in
 */
function require_auth() {
    if (!is_logged_in()) {
        redirect_with_message(
            BASE_URL . '/view/user/login.php?error=login_required',
            'Please log in to access that page.',
            'error'
        );
    }
}

/**
 * Check admin privileges
 */
function require_admin() {
    require_auth();
    if (!is_admin()) {
        redirect_with_message(
            BASE_URL . '/view/user/dashboard.php?error=access_denied',
            'You do not have permission to access that page.',
            'error'
        );
    }
}

/**
 * Handle file upload
 */
function handle_file_upload($file, $upload_dir = 'uploads/', $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    return upload_file($file, $upload_dir, $allowed_types);
}

/**
 * Send JSON response and exit
 */
function send_json_response($data, $status_code = 200) {
    json_response($data, $status_code);
}

/**
 * Log user activity
 */
function log_user_activity($action, $description = '') {
    $customer_id = is_logged_in() ? $_SESSION['customer_id'] : null;
    log_activity($action, $description, $customer_id);
}

/**
 * Note: check_action_rate_limit() and validate_form_csrf() are now defined in:
 * - functions/utils.php (check_action_rate_limit)
 * - functions/validation.php (validate_form_csrf)
 * These functions are auto-loaded via core.php, so they're available here.
 */

/**
 * Get paginated results
 */
function get_paginated_results($total_items, $items_per_page = 20, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($total_pages, $current_page));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'items_per_page' => $items_per_page,
        'offset' => $offset,
        'total_items' => $total_items
    ];
}

/**
 * Handle search functionality
 */
function handle_search($search_term, $search_type = 'general') {
    $search_term = sanitize_input($search_term);
    
    if (strlen($search_term) < 2) {
        return [
            'success' => false,
            'message' => 'Search term must be at least 2 characters long.'
        ];
    }
    
    // Log search activity
    log_user_activity('search', "Searched for: $search_term (type: $search_type)");
    
    return [
        'success' => true,
        'search_term' => $search_term,
        'search_type' => $search_type
    ];
}

/**
 * Get application statistics
 */
function get_app_statistics() {
    try {
        $user = new user_class();
        
        $stats = [
            'total_users' => $user->count_customers(),
            'total_products' => 0, // Will be implemented when product system is ready
            'total_orders' => 0,   // Will be implemented when order system is ready
            'app_version' => APP_VERSION,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting app statistics: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired sessions and tokens
 */
function cleanup_expired_data() {
    try {
        $db = new db_class();
        
        // Clean up expired password reset tokens
        $db->execute("DELETE FROM password_resets WHERE expires_at < NOW()");
        
        // Clean up expired sessions (if using database sessions)
        $db->execute("DELETE FROM user_sessions WHERE expires_at < NOW()");
        
        log_activity('system_cleanup', 'Cleaned up expired data');
        
        return true;
    } catch (Exception $e) {
        error_log("Error during cleanup: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification (placeholder for future implementation)
 */
function send_email_notification($to, $subject, $message, $template = null) {
    // This would integrate with PHPMailer or another email service
    // For now, just log the email attempt
    
    log_activity('email_sent', "Email sent to: $to, Subject: $subject");
    
    // In development, just return success
    if (APP_ENV === 'development') {
        return true;
    }
    
    // TODO: Implement actual email sending
    return false;
}

/**
 * Handle contact form submissions
 */
function handle_contact_form($name, $email, $subject, $message) {
    // Validate input
    $validation_rules = [
        'name' => ['required' => true, 'name' => true, 'max_length' => 100],
        'email' => ['required' => true, 'email' => true],
        'subject' => ['required' => true, 'max_length' => 200],
        'message' => ['required' => true, 'max_length' => 1000]
    ];
    
    $data = compact('name', 'email', 'subject', 'message');
    $errors = validate_form($data, $validation_rules);
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        ];
    }
    
    // Rate limiting
    check_action_rate_limit('contact_form', 3, 600); // 3 attempts per 10 minutes
    
    // Send email notification (to admin)
    $admin_email = 'admin@ecommerce-platform.com';
    $email_subject = "Contact Form: $subject";
    $email_message = "Name: $name\nEmail: $email\n\nMessage:\n$message";
    
    send_email_notification($admin_email, $email_subject, $email_message);
    
    // Log the contact form submission
    log_activity('contact_form', "Contact form submitted by: $email");
    
    return [
        'success' => true,
        'message' => 'Thank you for your message. We will get back to you soon!'
    ];
}

/**
 * Get system health status
 */
function get_system_health() {
    $health = [
        'database' => false,
        'files' => false,
        'memory' => false,
        'disk_space' => false
    ];
    
    try {
        // Check database connection
        $db = new db_class();
        $db->fetchRow("SELECT 1");
        $health['database'] = true;
    } catch (Exception $e) {
        error_log("Database health check failed: " . $e->getMessage());
    }
    
    try {
        // Check file permissions
        $health['files'] = is_writable(ASSETS_PATH) && is_readable(VIEW_PATH);
        
        // Check memory usage
        $memory_limit = ini_get('memory_limit');
        $memory_usage = memory_get_usage(true);
        $health['memory'] = $memory_usage < (128 * 1024 * 1024); // Less than 128MB
        
        // Check disk space
        $free_space = disk_free_space(ROOT_PATH);
        $health['disk_space'] = $free_space > (100 * 1024 * 1024); // More than 100MB
        
    } catch (Exception $e) {
        error_log("System health check failed: " . $e->getMessage());
    }
    
    return $health;
}

?>
