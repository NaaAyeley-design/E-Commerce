<?php
/**
 * Utility Functions
 * 
 * Contains general utility functions used throughout the application.
 */

/**
 * Redirect to a URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header("Location: $url");
    exit();
}

/**
 * Get current URL
 */
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Generate a secure random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format currency
 */
function format_currency($amount, $currency = 'USD', $symbol = '$') {
    return $symbol . number_format($amount, 2);
}

/**
 * Format date for display
 */
function format_date($date, $format = 'M j, Y') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function format_datetime($datetime, $format = 'M j, Y g:i A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    return date($format, strtotime($datetime));
}

/**
 * Time ago function
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31104000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31104000) . ' years ago';
}

/**
 * Truncate text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate slug from text
 */
function create_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check session validity
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            // Session expired, log out user
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    return false;
}

/**
 * Check if user is admin
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1;
}

/**
 * Get current customer data
 */
function get_current_customer_data() {
    if (!is_logged_in()) {
        return false;
    }
    
    return [
        'id' => $_SESSION['customer_id'] ?? null,
        'name' => $_SESSION['customer_name'] ?? null,
        'email' => $_SESSION['customer_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}

/**
 * Get current user ID
 */
function get_user_id() {
    if (!is_logged_in()) {
        return null;
    }
    
    return $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
}

/**
 * Flash message system
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear flash messages
 */
function get_flash_messages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Upload file
 */
function upload_file($file, $upload_dir = 'uploads/', $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    // Validate file
    $validation = validate_file_upload($file, $allowed_types);
    if ($validation !== true) {
        return ['success' => false, 'message' => $validation];
    }
    
    // Create upload directory if it doesn't exist
    $full_upload_dir = ASSETS_PATH . '/' . $upload_dir;
    if (!is_dir($full_upload_dir)) {
        mkdir($full_upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $full_upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $upload_dir . $filename,
            'url' => ASSETS_URL . '/' . $upload_dir . $filename
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file.'];
}

/**
 * Delete file
 */
function delete_file($filepath) {
    $full_path = ASSETS_PATH . '/' . $filepath;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}

/**
 * Send JSON response
 */
function json_response($data, $status_code = 200) {
    // Clean up any accidental output that may have been sent earlier
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Start fresh output buffer
    ob_start();
    
    // Set headers
    http_response_code($status_code);
    header('Content-Type: application/json');
    
    // Output JSON
    echo json_encode($data);
    
    // Flush and exit
    ob_end_flush();
    exit();
}

/**
 * Log activity
 */
function log_activity($action, $description = '', $customer_id = null) {
    if (!$customer_id && is_logged_in()) {
        $customer_id = $_SESSION['customer_id'];
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // This would typically log to database or file
    // For now, just log to PHP error log in development
    if (APP_ENV === 'development') {
        $log_message = "Activity: $action | User: $customer_id | IP: $ip_address | Description: $description";
        // Only log to file, don't output to browser
        $original_display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
        error_log($log_message);
        ini_set('display_errors', $original_display_errors);
    }
}

/**
 * Generate pagination links
 */
function generate_pagination($current_page, $total_pages, $base_url, $params = []) {
    $pagination = [];
    $range = 2; // Number of pages to show on each side of current page
    
    // Previous page
    if ($current_page > 1) {
        $prev_params = array_merge($params, ['page' => $current_page - 1]);
        $pagination['prev'] = $base_url . '?' . http_build_query($prev_params);
    }
    
    // Page numbers
    $start = max(1, $current_page - $range);
    $end = min($total_pages, $current_page + $range);
    
    for ($i = $start; $i <= $end; $i++) {
        $page_params = array_merge($params, ['page' => $i]);
        $pagination['pages'][$i] = [
            'url' => $base_url . '?' . http_build_query($page_params),
            'current' => $i == $current_page
        ];
    }
    
    // Next page
    if ($current_page < $total_pages) {
        $next_params = array_merge($params, ['page' => $current_page + 1]);
        $pagination['next'] = $base_url . '?' . http_build_query($next_params);
    }
    
    return $pagination;
}

/**
 * Escape output for HTML
 */
function escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Rate limiting check
 */
function check_rate_limit($action, $limit = 5, $window = 300) {
    $ip = get_client_ip();
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
    }
    
    $rate_data = $_SESSION[$key];
    
    // Reset if window has passed
    if (time() - $rate_data['start_time'] > $window) {
        $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
        return true;
    }
    
    // Check if limit exceeded
    if ($rate_data['count'] >= $limit) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

?>
