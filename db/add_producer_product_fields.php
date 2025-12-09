<?php
/**
 * Add Producer Product Fields to Products Table
 * 
 * This script adds fields needed for producer product management:
 * - SKU, stock quantity, status, visibility, shipping info, etc.
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/db_class.php';

try {
    $db = new db_class();
    
    // Check and add fields one by one
    $fields_to_add = [
        'sku' => "ALTER TABLE products ADD COLUMN sku VARCHAR(100) DEFAULT NULL AFTER product_id",
        'stock_quantity' => "ALTER TABLE products ADD COLUMN stock_quantity INT(11) DEFAULT 0 AFTER product_price",
        'low_stock_threshold' => "ALTER TABLE products ADD COLUMN low_stock_threshold INT(11) DEFAULT 5 AFTER stock_quantity",
        'track_inventory' => "ALTER TABLE products ADD COLUMN track_inventory TINYINT(1) DEFAULT 1 AFTER low_stock_threshold",
        'product_status' => "ALTER TABLE products ADD COLUMN product_status VARCHAR(20) DEFAULT 'draft' AFTER product_keywords",
        'visibility' => "ALTER TABLE products ADD COLUMN visibility VARCHAR(20) DEFAULT 'public' AFTER product_status",
        'compare_at_price' => "ALTER TABLE products ADD COLUMN compare_at_price DECIMAL(10,2) DEFAULT NULL AFTER product_price",
        'cost_per_item' => "ALTER TABLE products ADD COLUMN cost_per_item DECIMAL(10,2) DEFAULT NULL AFTER compare_at_price",
        'cultural_story' => "ALTER TABLE products ADD COLUMN cultural_story TEXT DEFAULT NULL AFTER product_desc",
        'materials_used' => "ALTER TABLE products ADD COLUMN materials_used VARCHAR(500) DEFAULT NULL AFTER cultural_story",
        'care_instructions' => "ALTER TABLE products ADD COLUMN care_instructions TEXT DEFAULT NULL AFTER materials_used",
        'product_weight' => "ALTER TABLE products ADD COLUMN product_weight DECIMAL(8,2) DEFAULT NULL AFTER care_instructions",
        'product_length' => "ALTER TABLE products ADD COLUMN product_length DECIMAL(8,2) DEFAULT NULL AFTER product_weight",
        'product_width' => "ALTER TABLE products ADD COLUMN product_width DECIMAL(8,2) DEFAULT NULL AFTER product_length",
        'product_height' => "ALTER TABLE products ADD COLUMN product_height DECIMAL(8,2) DEFAULT NULL AFTER product_width",
        'ships_from' => "ALTER TABLE products ADD COLUMN ships_from VARCHAR(100) DEFAULT NULL AFTER product_height",
        'processing_time' => "ALTER TABLE products ADD COLUMN processing_time VARCHAR(50) DEFAULT NULL AFTER ships_from",
        'meta_description' => "ALTER TABLE products ADD COLUMN meta_description VARCHAR(255) DEFAULT NULL AFTER product_keywords",
        'producer_id' => "ALTER TABLE products ADD COLUMN producer_id INT(11) DEFAULT NULL AFTER product_brand",
        'created_at' => "ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER meta_description",
        'updated_at' => "ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];
    
    foreach ($fields_to_add as $field_name => $sql) {
        try {
            $check = $db->fetchRow("SHOW COLUMNS FROM products LIKE '$field_name'");
            if (!$check) {
                $result = $db->execute($sql);
                if ($result !== false) {
                    echo "✓ Added $field_name column to products table.\n";
                } else {
                    echo "✗ Failed to add $field_name column.\n";
                }
            } else {
                echo "→ $field_name column already exists.\n";
            }
        } catch (Exception $e) {
            echo "⚠ Error adding $field_name: " . $e->getMessage() . "\n";
        }
    }
    
    // Add indexes
    try {
        $indexes = [
            'idx_sku' => "ALTER TABLE products ADD INDEX idx_sku (sku)",
            'idx_product_status' => "ALTER TABLE products ADD INDEX idx_product_status (product_status)",
            'idx_visibility' => "ALTER TABLE products ADD INDEX idx_visibility (visibility)",
            'idx_producer_id' => "ALTER TABLE products ADD INDEX idx_producer_id (producer_id)",
            'idx_stock_quantity' => "ALTER TABLE products ADD INDEX idx_stock_quantity (stock_quantity)"
        ];
        
        foreach ($indexes as $index_name => $sql) {
            try {
                $check = $db->fetchRow("SHOW INDEX FROM products WHERE Key_name = '$index_name'");
                if (!$check) {
                    $db->execute($sql);
                    echo "✓ Added $index_name index.\n";
                }
            } catch (Exception $e) {
                // Index might already exist or error occurred
            }
        }
    } catch (Exception $e) {
        echo "Note: Some indexes may already exist.\n";
    }
    
    // Create product_variations table for sizes/colors
    $variations_sql = "CREATE TABLE IF NOT EXISTS product_variations (
        variation_id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        variation_type VARCHAR(50) NOT NULL,
        variation_value VARCHAR(100) NOT NULL,
        price_adjustment DECIMAL(10,2) DEFAULT 0,
        stock_quantity INT(11) DEFAULT 0,
        sku VARCHAR(100) DEFAULT NULL,
        image_url VARCHAR(500) DEFAULT NULL,
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (variation_id),
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        INDEX idx_product_id (product_id),
        INDEX idx_variation_type (variation_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    
    try {
        $db->execute($variations_sql);
        echo "✓ Created product_variations table.\n";
    } catch (Exception $e) {
        echo "→ product_variations table may already exist.\n";
    }
    
    echo "\nDone! Producer product fields are ready.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

