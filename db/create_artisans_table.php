<?php
/**
 * Create Artisans Table
 * 
 * This script creates the artisans table and populates it with existing
 * users who have user_role = 3 (Designer/Producer/Artisan)
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/db_class.php';

try {
    $db = new db_class();
    
    echo "=== Creating Artisans Table ===\n\n";
    
    // Create artisans table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS artisans (
        artisan_id INT(11) NOT NULL AUTO_INCREMENT,
        customer_id INT(11) NOT NULL,
        business_name VARCHAR(200) DEFAULT NULL,
        artisan_name VARCHAR(100) NOT NULL,
        bio TEXT DEFAULT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        cover_image VARCHAR(255) DEFAULT NULL,
        email VARCHAR(50) NOT NULL,
        phone VARCHAR(15) DEFAULT NULL,
        city VARCHAR(30) DEFAULT NULL,
        country VARCHAR(30) DEFAULT NULL,
        website VARCHAR(255) DEFAULT NULL,
        instagram VARCHAR(100) DEFAULT NULL,
        facebook VARCHAR(100) DEFAULT NULL,
        twitter VARCHAR(100) DEFAULT NULL,
        status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
        featured TINYINT(1) DEFAULT 0,
        total_products INT(11) DEFAULT 0,
        total_sales INT(11) DEFAULT 0,
        rating DECIMAL(3,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (artisan_id),
        UNIQUE KEY unique_customer_id (customer_id),
        INDEX idx_status (status),
        INDEX idx_featured (featured),
        INDEX idx_city (city),
        INDEX idx_country (country),
        FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->execute($create_table_sql);
    echo "✓ Artisans table created successfully\n\n";
    
    // Populate artisans table with existing role 3 users
    echo "=== Populating Artisans Table ===\n\n";
    
    // Check if business_name and bio columns exist
    $has_business_name = $db->fetchRow("SHOW COLUMNS FROM customer LIKE 'business_name'");
    $has_bio = $db->fetchRow("SHOW COLUMNS FROM customer LIKE 'bio'");
    
    // Build SELECT statement based on available columns
    if ($has_business_name && $has_bio) {
        // Both columns exist
        $populate_sql = "
        INSERT INTO artisans (
            customer_id,
            artisan_name,
            business_name,
            bio,
            profile_image,
            email,
            phone,
            city,
            country,
            status,
            created_at
        )
        SELECT 
            c.customer_id,
            c.customer_name,
            COALESCE(c.business_name, c.customer_name) as business_name,
            c.bio,
            c.customer_image,
            c.customer_email,
            c.customer_contact,
            c.customer_city,
            c.customer_country,
            'active' as status,
            NOW() as created_at
        FROM customer c
        WHERE c.user_role = 3
        AND NOT EXISTS (
            SELECT 1 FROM artisans a WHERE a.customer_id = c.customer_id
        )
        ";
    } elseif ($has_business_name) {
        // Only business_name exists
        $populate_sql = "
        INSERT INTO artisans (
            customer_id,
            artisan_name,
            business_name,
            bio,
            profile_image,
            email,
            phone,
            city,
            country,
            status,
            created_at
        )
        SELECT 
            c.customer_id,
            c.customer_name,
            COALESCE(c.business_name, c.customer_name) as business_name,
            NULL as bio,
            c.customer_image,
            c.customer_email,
            c.customer_contact,
            c.customer_city,
            c.customer_country,
            'active' as status,
            NOW() as created_at
        FROM customer c
        WHERE c.user_role = 3
        AND NOT EXISTS (
            SELECT 1 FROM artisans a WHERE a.customer_id = c.customer_id
        )
        ";
    } else {
        // Neither column exists - use basic version
        $populate_sql = "
        INSERT INTO artisans (
            customer_id,
            artisan_name,
            business_name,
            bio,
            profile_image,
            email,
            phone,
            city,
            country,
            status,
            created_at
        )
        SELECT 
            c.customer_id,
            c.customer_name,
            c.customer_name as business_name,
            NULL as bio,
            c.customer_image,
            c.customer_email,
            c.customer_contact,
            c.customer_city,
            c.customer_country,
            'active' as status,
            NOW() as created_at
        FROM customer c
        WHERE c.user_role = 3
        AND NOT EXISTS (
            SELECT 1 FROM artisans a WHERE a.customer_id = c.customer_id
        )
        ";
    }
    
    $result = $db->execute($populate_sql);
    
    // Get count of inserted records
    $count_sql = "SELECT COUNT(*) as count FROM artisans";
    $count_result = $db->fetchRow($count_sql);
    $count = $count_result['count'] ?? 0;
    
    echo "✓ Populated artisans table with $count artisan(s)\n\n";
    
    // Update total_products count for each artisan
    echo "=== Updating Product Counts ===\n\n";
    
    $update_products_sql = "
    UPDATE artisans a
    SET total_products = (
        SELECT COUNT(*) 
        FROM products p
        WHERE (p.producer_id = a.customer_id 
               OR p.product_brand IN (
                   SELECT brand_id 
                   FROM brands 
                   WHERE user_id = a.customer_id
               ))
    )
    ";
    
    $db->execute($update_products_sql);
    echo "✓ Updated product counts for artisans\n\n";
    
    echo "=== Migration Complete ===\n";
    echo "Artisans table is ready to use!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

