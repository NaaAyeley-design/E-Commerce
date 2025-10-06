<?php
/**
 * Database Credentials Configuration
 * 
 * This file contains all database connection settings.
 * Keep this file secure and never commit sensitive credentials to version control.
 */

// Database Configuration
define('DB_HOST', '169.239.251.102');
define('DB_USERNAME', 'naa.aryee');
define('DB_PASSWORD', 'Araba2004!');
define('DB_NAME', 'ecommerce_2025A_naa_aryee');
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
