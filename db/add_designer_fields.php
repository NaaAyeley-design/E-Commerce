<?php
/**
 * Add Designer/Producer Fields to Customer Table
 * 
 * This script adds optional fields for designers/producers:
 * - business_name: Business or brand name
 * - bio: Brief description/bio
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/db_class.php';

try {
    $db = new db_class();
    
    // Check if business_name column exists
    $check_business = $db->fetchRow("SHOW COLUMNS FROM customer LIKE 'business_name'");
    if (!$check_business) {
        $db->execute("ALTER TABLE customer ADD COLUMN business_name VARCHAR(200) DEFAULT NULL AFTER customer_name");
        echo "Added business_name column to customer table.\n";
    } else {
        echo "business_name column already exists.\n";
    }
    
    // Check if bio column exists
    $check_bio = $db->fetchRow("SHOW COLUMNS FROM customer LIKE 'bio'");
    if (!$check_bio) {
        $db->execute("ALTER TABLE customer ADD COLUMN bio TEXT DEFAULT NULL AFTER business_name");
        echo "Added bio column to customer table.\n";
    } else {
        echo "bio column already exists.\n";
    }
    
    // Update user_roles table to include Designer role
    $check_role = $db->fetchRow("SELECT role_id FROM user_roles WHERE role_id = 3");
    if (!$check_role) {
        $db->execute("INSERT INTO user_roles (role_id, role_name, role_description) VALUES (3, 'Designer', 'Designer/Producer account for selling products')");
        echo "Added Designer role to user_roles table.\n";
    } else {
        echo "Designer role already exists.\n";
    }
    
    echo "\nDone! Designer fields are ready.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

