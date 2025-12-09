<?php
/**
 * Image Upload Diagnostic Test
 * 
 * This script tests the image upload system to identify issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/class/db_class.php';
require_once __DIR__ . '/class/product_class.php';

echo "<h2>Image Upload Diagnostic Test</h2>";
echo "<pre>";

// Test 1: Database Connection
echo "=== TEST 1: Database Connection ===\n";
$db = new db_class();
$conn = $db->getConnection();
if ($conn) {
    echo "✓ Database connection successful\n";
} else {
    echo "✗ Database connection FAILED\n";
    exit;
}

// Test 2: Check if product_images table exists
echo "\n=== TEST 2: Table Existence ===\n";
$table_check = $db->fetchRow("SHOW TABLES LIKE 'product_images'");
if ($table_check) {
    echo "✓ product_images table exists\n";
} else {
    echo "✗ product_images table DOES NOT EXIST\n";
    echo "   Attempting to create...\n";
    
    $create_sql = "CREATE TABLE IF NOT EXISTS product_images (
        image_id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        image_alt VARCHAR(200) DEFAULT NULL,
        image_title VARCHAR(200) DEFAULT NULL,
        sort_order INT(11) DEFAULT 0,
        is_primary TINYINT(1) DEFAULT 0,
        file_size INT(11) DEFAULT NULL,
        mime_type VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (image_id),
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        INDEX idx_product_id (product_id),
        INDEX idx_sort_order (sort_order),
        INDEX idx_is_primary (is_primary),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    
    $result = $db->execute($create_sql);
    if ($result !== false) {
        echo "✓ product_images table created\n";
    } else {
        echo "✗ Failed to create product_images table\n";
        $error_info = $conn->errorInfo();
        echo "   Error: " . json_encode($error_info) . "\n";
    }
}

// Test 3: Check table structure
echo "\n=== TEST 3: Table Structure ===\n";
$structure = $db->fetchAll("DESCRIBE product_images");
if ($structure) {
    echo "product_images table columns:\n";
    foreach ($structure as $col) {
        $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col['Default'] !== null ? " DEFAULT '{$col['Default']}'" : '';
        echo "  - {$col['Field']} ({$col['Type']}) $null$default\n";
    }
    
    // Check required columns
    $columns = array_column($structure, 'Field');
    $required = ['image_id', 'product_id', 'image_url'];
    $missing = array_diff($required, $columns);
    if (empty($missing)) {
        echo "✓ All required columns exist\n";
    } else {
        echo "✗ Missing required columns: " . implode(', ', $missing) . "\n";
    }
} else {
    echo "✗ Cannot describe product_images table\n";
}

// Test 4: Check if products table exists (for foreign key)
echo "\n=== TEST 4: Products Table (Foreign Key) ===\n";
$products_check = $db->fetchRow("SHOW TABLES LIKE 'products'");
if ($products_check) {
    echo "✓ products table exists\n";
    
    // Get a sample product ID
    $sample_product = $db->fetchRow("SELECT product_id FROM products LIMIT 1");
    if ($sample_product) {
        $test_product_id = $sample_product['product_id'];
        echo "   Sample product ID: $test_product_id\n";
    } else {
        echo "   ⚠ No products in database\n";
        $test_product_id = null;
    }
} else {
    echo "✗ products table DOES NOT EXIST\n";
    echo "   Foreign key constraint will fail\n";
    $test_product_id = null;
}

// Test 5: Test directory permissions
echo "\n=== TEST 5: Upload Directory Permissions ===\n";
$uploads_base = __DIR__ . '/uploads';
echo "Uploads base directory: $uploads_base\n";

if (!file_exists($uploads_base)) {
    echo "⚠ Directory does not exist, attempting to create...\n";
    if (@mkdir($uploads_base, 0755, true)) {
        echo "✓ Directory created\n";
    } else {
        echo "✗ Failed to create directory\n";
    }
} else {
    echo "✓ Directory exists\n";
}

if (is_writable($uploads_base)) {
    echo "✓ Directory is writable\n";
} else {
    echo "✗ Directory is NOT writable\n";
    echo "   Attempting to change permissions...\n";
    if (@chmod($uploads_base, 0755)) {
        echo "✓ Permissions changed\n";
    } else {
        echo "✗ Failed to change permissions\n";
    }
}

// Test 6: Test database insert (if we have a product)
if ($test_product_id) {
    echo "\n=== TEST 6: Database Insert Test ===\n";
    $test_image_url = 'uploads/test/test_image.jpg';
    $test_result = $db->execute(
        "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)",
        [$test_product_id, $test_image_url, 0]
    );
    
    if ($test_result !== false) {
        $test_image_id = $conn->lastInsertId();
        if ($test_image_id > 0) {
            echo "✓ Test insert successful - Image ID: $test_image_id\n";
            
            // Clean up
            $db->execute("DELETE FROM product_images WHERE image_id = ?", [$test_image_id]);
            echo "✓ Test record cleaned up\n";
        } else {
            echo "✗ Insert executed but no ID returned\n";
            $error_info = $conn->errorInfo();
            echo "   Error: " . json_encode($error_info) . "\n";
        }
    } else {
        echo "✗ Insert failed\n";
        $error_info = $conn->errorInfo();
        echo "   Error: " . json_encode($error_info) . "\n";
    }
} else {
    echo "\n=== TEST 6: Database Insert Test ===\n";
    echo "⚠ Skipped - No products in database\n";
}

// Test 7: Test product_class method
if ($test_product_id) {
    echo "\n=== TEST 7: product_class::add_product_image() Test ===\n";
    $product = new product_class();
    $test_result = $product->add_product_image($test_product_id, 'uploads/test/test_image_2.jpg', false, 'Test Alt', 'Test Title', 0);
    
    if (isset($test_result['success']) && $test_result['success']) {
        echo "✓ add_product_image() method works\n";
        echo "   Image ID: " . ($test_result['image_id'] ?? 'N/A') . "\n";
        
        // Clean up
        if (isset($test_result['image_id'])) {
            $db->execute("DELETE FROM product_images WHERE image_id = ?", [$test_result['image_id']]);
            echo "✓ Test record cleaned up\n";
        }
    } else {
        echo "✗ add_product_image() method failed\n";
        echo "   Message: " . ($test_result['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "\n=== TEST 7: product_class::add_product_image() Test ===\n";
    echo "⚠ Skipped - No products in database\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "</pre>";

