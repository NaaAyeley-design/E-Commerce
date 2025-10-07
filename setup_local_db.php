<?php
/**
 * Local Database Setup Script
 * 
 * This script helps set up the local database for development
 */

// Database configuration for local setup
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ecommerce_authent';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "✅ Database '$database' created successfully!\n";
    
    // Connect to the new database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create customer table (singular - matches your application)
    $sql = "
    CREATE TABLE IF NOT EXISTS `customer` (
        `customer_id` int(11) NOT NULL AUTO_INCREMENT,
        `customer_name` varchar(100) NOT NULL,
        `customer_email` varchar(100) NOT NULL UNIQUE,
        `customer_pass` varchar(255) NOT NULL,
        `user_role` tinyint(1) NOT NULL DEFAULT 0,
        `reg_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ Customer table created successfully!\n";
    
    // Insert a test admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO `customer` (`customer_name`, `customer_email`, `customer_pass`, `user_role`) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['Admin User', 'admin@test.com', $admin_password, 1]);
    
    // Insert a test regular user
    $user_password = password_hash('user123', PASSWORD_DEFAULT);
    $stmt->execute(['Test User', 'user@test.com', $user_password, 0]);
    
    echo "✅ Test users created successfully!\n";
    echo "\n📋 Test Login Credentials:\n";
    echo "Admin: admin@test.com / admin123\n";
    echo "User: user@test.com / user123\n";
    echo "\n🌐 Access your site at: http://localhost/ecommerce-authent/public_html/\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nMake sure XAMPP is running and MySQL is started!\n";
}
?>
