<?php
/**
 * Database Credentials Configuration
 * 
 * This file contains all database connection settings.
 * Keep this file secure and never commit sensitive credentials to version control.
 */

// Database Configuration
// Detect if we're on localhost or live server
$is_localhost = (
    (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) ||
    (isset($_SERVER['SERVER_NAME']) && (strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false)) ||
    (php_sapi_name() === 'cli') || // Command line interface
    (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['DOCUMENT_ROOT'], 'xampp') !== false) // XAMPP detection
);

if ($is_localhost) {
    // Local development (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'ecommerce_authent');
} else {
    // Live server - configure these for your production environment
    define('DB_HOST', 'your_production_host');
    define('DB_USERNAME', 'your_production_username');
    define('DB_PASSWORD', 'your_production_password');
    define('DB_NAME', 'your_production_database');
}
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Database Connection Options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Connection timeout (seconds)
define('DB_TIMEOUT', 30);

// Enable/disable persistent connections
define('DB_PERSISTENT', false);

// SSL Configuration (if needed)
define('DB_SSL_KEY', '');
define('DB_SSL_CERT', '');
define('DB_SSL_CA', '');
define('DB_SSL_CAPATH', '');
define('DB_SSL_CIPHER', '');

?>
