<?php
/**
 * Core Configuration File
 * 
 * This file contains core application settings and initializes the application.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database credentials
require_once __DIR__ . '/db_cred.php';

// Application Configuration
define('APP_NAME', 'E-Commerce Platform');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, staging, production

// Error reporting configuration
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Don't display errors on frontend
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 0); // Don't log errors to prevent output
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 0);
}

// Path Configuration
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('CLASS_PATH', ROOT_PATH . '/class');
define('CONTROLLER_PATH', ROOT_PATH . '/controller');
define('VIEW_PATH', PUBLIC_PATH . '/view');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');
define('FUNCTIONS_PATH', ROOT_PATH . '/functions');

// URL Configuration
// Build BASE_URL and ASSETS_URL reliably from the public path and server document root.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Normalize paths to forward slashes
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$public_path_norm = str_replace('\\', '/', realpath(PUBLIC_PATH));

// Compute the web-accessible path to PUBLIC_PATH by removing the document root
$web_path = '/';
if ($doc_root && $public_path_norm && strpos($public_path_norm, $doc_root) === 0) {
    $web_path = '/' . ltrim(substr($public_path_norm, strlen($doc_root)), '/');
}

// Fallback: if we couldn't compute via document root, try a sensible default
// Fallback: if we couldn't compute via document root, try to infer PUBLIC_PATH from the
// current script path (useful on shared hosts with ~username in the URL).
if (empty($web_path) || $web_path === '/') {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
    // Prefer to extract up to /public_html if present in the script path
    $needle = '/public_html';
    $pos = strpos($script_name, $needle);
    if ($pos !== false) {
        $web_path = substr($script_name, 0, $pos + strlen($needle));
    } else {
        // Fallback to the script directory
        $script_dir = rtrim(dirname($script_name), '/\\');
        $web_path = $script_dir ?: '/';
    }
}

// (Keep web_path as-is. Do not strip /public_html — public path should reflect the actual document root location.)

define('BASE_URL', rtrim($protocol . $host . $web_path, '/'));
define('ASSETS_URL', BASE_URL . '/assets');
define('PUBLIC_URL', BASE_URL);

// Security Configuration
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('REMEMBER_TOKEN_LIFETIME', 2592000); // 30 days in seconds

// Email Configuration (for future use)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'noreply@ecommerce-platform.com');
define('FROM_NAME', APP_NAME);

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    // Only show errors if not already suppressed by individual files
    if (!isset($suppress_errors) || !$suppress_errors) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    }
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Auto-load core functions
require_once FUNCTIONS_PATH . '/validation.php';
require_once FUNCTIONS_PATH . '/utils.php';

/**
 * URL Helper Function
 * Creates absolute URLs using BASE_URL
 */
function url($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Auto-load core classes
require_once CLASS_PATH . '/db_class.php';
require_once CLASS_PATH . '/user_class.php';
require_once CLASS_PATH . '/category_class.php';

// Initialize error handler
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');

/**
 * Custom error handler
 */
function custom_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error_msg = "Error: [$severity] $message in $file on line $line";
    
    if (APP_ENV === 'development') {
        // If the client expects JSON, return JSON error to avoid breaking API responses
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $is_json = (strpos($accept, 'application/json') !== false) ||
                   (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                   (isset($_POST['ajax']) || isset($_REQUEST['ajax']));

        if ($is_json) {
            // Try to send JSON safely
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['success' => false, 'error' => $error_msg]);
        } else {
            echo "<div style='color: red; font-family: monospace;'>$error_msg</div>";
        }
    } else {
        error_log($error_msg);
    }
    
    return true;
}

/**
 * Custom exception handler
 */
function custom_exception_handler($exception) {
    $error_msg = "Uncaught Exception: " . $exception->getMessage() . 
                 " in " . $exception->getFile() . 
                 " on line " . $exception->getLine();
    
    if (APP_ENV === 'development') {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $is_json = (strpos($accept, 'application/json') !== false) ||
                   (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                   (isset($_POST['ajax']) || isset($_REQUEST['ajax']));

        if ($is_json) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['success' => false, 'exception' => $error_msg, 'trace' => $exception->getTraceAsString()]);
        } else {
            echo "<div style='color: red; font-family: monospace;'>$error_msg</div>";
            echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        }
    } else {
        error_log($error_msg);
        // Redirect to error page in production
        header('Location: ' . BASE_URL . '/error/500.php');
        exit;
    }
}

/**
 * Autoloader for classes
 */
spl_autoload_register(function ($class_name) {
    $class_file = CLASS_PATH . '/' . strtolower($class_name) . '.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// No closing PHP tag to avoid accidental output
