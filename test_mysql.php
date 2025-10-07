<?php
/**
 * Test MySQL Connection
 */

echo "<h2>MySQL Connection Test</h2>";

// Test different connection methods
$hosts = ['localhost', '127.0.0.1'];
$ports = [3306, 3307];

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        echo "<h3>Testing $host:$port</h3>";
        
        try {
            $pdo = new PDO("mysql:host=$host;port=$port", 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p style='color: green;'>✅ Connection successful to $host:$port</p>";
            
            // Try to create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS ecommerce_authent");
            echo "<p style='color: green;'>✅ Database 'ecommerce_authent' created/accessed</p>";
            
            // Connect to the database
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=ecommerce_authent", 'root', '');
            
            // Create customer table
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
            echo "<p style='color: green;'>✅ Customer table created</p>";
            
            // Insert test user
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $sql = "INSERT IGNORE INTO `customer` (`customer_name`, `customer_email`, `customer_pass`, `user_role`) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['Admin User', 'admin@test.com', $admin_password, 1]);
            echo "<p style='color: green;'>✅ Test admin user created</p>";
            
            echo "<p><strong>SUCCESS! Use these settings in your db_cred.php:</strong></p>";
            echo "<p>Host: $host</p>";
            echo "<p>Port: $port</p>";
            echo "<p>Username: root</p>";
            echo "<p>Password: (empty)</p>";
            echo "<p>Database: ecommerce_authent</p>";
            
            break 2; // Exit both loops
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

echo "<hr>";
echo "<p><strong>If all tests failed, XAMPP MySQL is not running!</strong></p>";
echo "<p>Please start XAMPP Control Panel and start MySQL service.</p>";
?>
