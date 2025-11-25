<?php
/**
 * Debug script to test image upload functionality
 * Run this to check database connection, table structure, and permissions
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/product_class.php';
require_once __DIR__ . '/../class/db_class.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$debug_info = [];

// 1. Check database connection
try {
    $db = new db_class();
    $conn = $db->getConnection();
    if ($conn) {
        $debug_info['database_connection'] = 'OK';
    } else {
        $debug_info['database_connection'] = 'FAILED';
    }
} catch (Exception $e) {
    $debug_info['database_connection'] = 'ERROR: ' . $e->getMessage();
}

// 2. Check if product_images table exists
try {
    $db = new db_class();
    $conn = $db->getConnection();
    if ($conn) {
        $stmt = $conn->query("SHOW TABLES LIKE 'product_images'");
        $table_exists = $stmt->rowCount() > 0;
        $debug_info['table_exists'] = $table_exists ? 'YES' : 'NO';
        
        if ($table_exists) {
            // Get table structure
            $stmt = $conn->query("DESCRIBE product_images");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $debug_info['table_structure'] = $columns;
        }
    }
} catch (Exception $e) {
    $debug_info['table_check'] = 'ERROR: ' . $e->getMessage();
}

// 3. Test a simple insert (without actually inserting)
try {
    $product = new product_class();
    $test_product_id = 1; // Use a test product ID
    
    // Check if test product exists
    $test_product = $product->get_product_by_id($test_product_id);
    $debug_info['test_product_exists'] = $test_product ? 'YES (ID: ' . $test_product_id . ')' : 'NO (ID: ' . $test_product_id . ' not found)';
    
    if ($test_product) {
        // Try to prepare the insert statement (don't execute)
        $conn = $product->getConnection();
        if ($conn) {
            $sql = "INSERT INTO product_images (product_id, image_url, image_alt, image_title, sort_order, is_primary)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $debug_info['sql_prepare'] = $stmt ? 'OK' : 'FAILED';
            
            if ($stmt) {
                // Check if we can bind parameters
                $test_params = [
                    $test_product_id,
                    'test/path/image.jpg',
                    'Test Alt',
                    'Test Title',
                    0,
                    0
                ];
                
                try {
                    $stmt->bindValue(1, $test_params[0], PDO::PARAM_INT);
                    $stmt->bindValue(2, $test_params[1], PDO::PARAM_STR);
                    $stmt->bindValue(3, $test_params[2], PDO::PARAM_STR);
                    $stmt->bindValue(4, $test_params[3], PDO::PARAM_STR);
                    $stmt->bindValue(5, $test_params[4], PDO::PARAM_INT);
                    $stmt->bindValue(6, $test_params[5], PDO::PARAM_INT);
                    $debug_info['parameter_binding'] = 'OK';
                } catch (Exception $e) {
                    $debug_info['parameter_binding'] = 'ERROR: ' . $e->getMessage();
                }
            }
        }
    }
} catch (Exception $e) {
    $debug_info['insert_test'] = 'ERROR: ' . $e->getMessage();
}

// 4. Check upload directory permissions
$upload_base = __DIR__ . '/../uploads';
$debug_info['upload_directory'] = [
    'path' => $upload_base,
    'exists' => is_dir($upload_base) ? 'YES' : 'NO',
    'writable' => is_writable($upload_base) ? 'YES' : 'NO',
    'readable' => is_readable($upload_base) ? 'YES' : 'NO'
];

// 5. Check PHP upload settings
$debug_info['php_upload_settings'] = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'ENABLED' : 'DISABLED'
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);

