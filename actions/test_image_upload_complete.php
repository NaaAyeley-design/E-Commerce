<?php
/**
 * Complete Image Upload Test
 * Tests the entire image upload flow including file handling and database insertion
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/product_class.php';
require_once __DIR__ . '/../class/db_class.php';
require_once __DIR__ . '/../controller/product_controller.php';

header('Content-Type: application/json');

// Only allow logged-in admin users
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$user_id = get_user_id();
$test_results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'user_id' => $user_id,
    'tests' => []
];

$product = new product_class();
$db = new db_class();
$conn = $db->getConnection();

// Test 1: Check if product_images table exists
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'product_images'");
    $table_exists = $stmt->rowCount() > 0;
    $test_results['tests']['table_exists'] = [
        'result' => $table_exists ? 'YES' : 'NO'
    ];
    
    if ($table_exists) {
        // Get table structure
        $stmt = $conn->query("DESCRIBE product_images");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $test_results['tests']['table_structure'] = $columns;
    }
} catch (Exception $e) {
    $test_results['tests']['table_exists'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 2: Get a real product ID to test with
try {
    $stmt = $conn->query("SELECT product_id, product_title FROM products ORDER BY product_id DESC LIMIT 1");
    $test_product = $stmt->fetch(PDO::FETCH_ASSOC);
    $test_results['tests']['test_product'] = $test_product;
    
    if (!$test_product) {
        echo json_encode([
            'error' => 'No products found in database. Please create a product first.',
            'tests' => $test_results['tests']
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    $test_product_id = $test_product['product_id'];
    
} catch (Exception $e) {
    $test_results['tests']['test_product'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
    echo json_encode($test_results, JSON_PRETTY_PRINT);
    exit;
}

// Test 3: Check if product exists using get_product_by_id
try {
    $product_data = $product->get_product_by_id($test_product_id);
    $test_results['tests']['product_exists_check'] = [
        'result' => $product_data ? 'YES' : 'NO',
        'product_data' => $product_data ? ['id' => $product_data['product_id'], 'title' => $product_data['product_title']] : null
    ];
} catch (Exception $e) {
    $test_results['tests']['product_exists_check'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 4: Test database connection
$test_results['tests']['database_connection'] = [
    'result' => $conn ? 'YES' : 'NO',
    'connection_type' => $conn ? get_class($conn) : 'N/A'
];

// Test 5: Try to insert a test image record (will rollback)
try {
    $test_image_url = 'uploads/test/test_image_' . time() . '.jpg';
    $test_sql = "INSERT INTO product_images (product_id, image_url, image_alt, image_title, sort_order, is_primary)
                 VALUES (?, ?, ?, ?, ?, ?)";
    
    $test_params = [
        $test_product_id,
        $test_image_url,
        'Test Alt',
        'Test Title',
        0,
        0
    ];
    
    $conn->beginTransaction();
    $test_stmt = $conn->prepare($test_sql);
    $execute_result = $test_stmt->execute($test_params);
    
    if ($execute_result) {
        $test_image_id = $conn->lastInsertId();
        $row_count = $test_stmt->rowCount();
        $test_results['tests']['direct_insert_test'] = [
            'result' => 'SUCCESS',
            'image_id' => $test_image_id,
            'row_count' => $row_count,
            'last_insert_id' => $test_image_id
        ];
        
        // Rollback the test insert
        $conn->rollBack();
    } else {
        $error_info = $test_stmt->errorInfo();
        $test_results['tests']['direct_insert_test'] = [
            'result' => 'FAILED',
            'error' => $error_info[2] ?? 'Unknown error',
            'error_info' => $error_info
        ];
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
    }
} catch (PDOException $e) {
    $test_results['tests']['direct_insert_test'] = [
        'result' => 'EXCEPTION',
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'sql_state' => $e->getCode()
    ];
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
}

// Test 6: Test add_product_image method
try {
    $test_image_url = 'uploads/test/method_test_' . time() . '.jpg';
    $result = $product->add_product_image($test_product_id, $test_image_url, false, 'Method Test Alt', 'Method Test Title', 0);
    
    $test_results['tests']['add_product_image_method'] = [
        'result' => $result['success'] ? 'SUCCESS' : 'FAILED',
        'response' => $result
    ];
    
    // If successful, clean up the test record
    if ($result['success'] && isset($result['image_id'])) {
        try {
            $delete_sql = "DELETE FROM product_images WHERE image_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->execute([$result['image_id']]);
            $test_results['tests']['add_product_image_method']['cleanup'] = 'SUCCESS';
        } catch (Exception $e) {
            $test_results['tests']['add_product_image_method']['cleanup'] = 'FAILED: ' . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $test_results['tests']['add_product_image_method'] = [
        'result' => 'EXCEPTION',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
}

// Test 7: Check existing images for the test product
try {
    $existing_images = $product->get_product_images($test_product_id);
    $test_results['tests']['existing_images'] = [
        'count' => is_array($existing_images) ? count($existing_images) : 0,
        'images' => $existing_images
    ];
} catch (Exception $e) {
    $test_results['tests']['existing_images'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 8: Check upload directory permissions
$upload_base = __DIR__ . '/../uploads';
$test_results['tests']['upload_directory'] = [
    'base_path' => $upload_base,
    'exists' => is_dir($upload_base),
    'writable' => is_dir($upload_base) ? is_writable($upload_base) : false,
    'permissions' => is_dir($upload_base) ? substr(sprintf('%o', fileperms($upload_base)), -4) : 'N/A'
];

// Test 9: Check PHP upload settings
$test_results['tests']['php_upload_settings'] = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'YES' : 'NO'
];

echo json_encode($test_results, JSON_PRETTY_PRINT);

