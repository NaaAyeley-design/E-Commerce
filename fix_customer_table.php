<?php
/**
 * Fix Customer Table Structure
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Adding missing columns to customer table...\n";
    
    // Add missing columns
    $alter_queries = [
        "ALTER TABLE customer ADD COLUMN customer_country VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE customer ADD COLUMN customer_city VARCHAR(100) DEFAULT NULL", 
        "ALTER TABLE customer ADD COLUMN customer_contact VARCHAR(20) DEFAULT NULL"
    ];
    
    foreach ($alter_queries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ " . $query . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️  Column already exists: " . $query . "\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nUpdated customer table structure:\n";
    $stmt = $pdo->query('DESCRIBE customer');
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✅ Customer table structure fixed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
