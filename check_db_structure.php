<?php
/**
 * Check Database Structure
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Current customer table structure:\n";
    $stmt = $pdo->query('DESCRIBE customer');
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\nMissing columns needed for registration:\n";
    $required_columns = ['customer_country', 'customer_city', 'customer_contact'];
    $existing_columns = array_column($columns, 'Field');
    
    foreach ($required_columns as $col) {
        if (!in_array($col, $existing_columns)) {
            echo "- MISSING: $col\n";
        } else {
            echo "- EXISTS: $col\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
