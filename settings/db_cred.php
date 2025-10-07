<?php
/**
 * Database Credentials Configuration
 * 
 * This file contains all database connection settings.
 * Keep this file secure and never commit sensitive credentials to version control.
 */

// Database Configuration
// Detect if we're on localhost or live server
$is_localhost = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);

if ($is_localhost) {
    // Local development (XAMPP)
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USERNAME')) define('DB_USERNAME', 'root');
    if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'ecommerce_authent');
} else {
    // Live server
    if (!defined('DB_HOST')) define('DB_HOST', '169.239.251.102');
    if (!defined('DB_USERNAME')) define('DB_USERNAME', 'naa.aryee');
    if (!defined('DB_PASSWORD')) define('DB_PASSWORD', 'Araba2004!');
    if (!defined('DB_NAME')) define('DB_NAME', 'ecommerce_2025A_naa_aryee');
}
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('DB_COLLATE')) define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Database Connection Options
if (!defined('DB_OPTIONS')) {
    define('DB_OPTIONS', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

// Connection timeout (seconds)
if (!defined('DB_TIMEOUT')) define('DB_TIMEOUT', 30);

// Enable/disable persistent connections
if (!defined('DB_PERSISTENT')) define('DB_PERSISTENT', false);

// SSL Configuration (if needed)
if (!defined('DB_SSL_KEY')) define('DB_SSL_KEY', '');
if (!defined('DB_SSL_CERT')) define('DB_SSL_CERT', '');
if (!defined('DB_SSL_CA')) define('DB_SSL_CA', '');
if (!defined('DB_SSL_CAPATH')) define('DB_SSL_CAPATH', '');
if (!defined('DB_SSL_CIPHER')) define('DB_SSL_CIPHER', '');

?>
