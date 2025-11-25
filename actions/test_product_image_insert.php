<?php
/**
 * Test Product Image Insert
 * 
 * This script tests if we can insert an image into the product_images table
 * Access it via: /actions/test_product_image_insert.php?product_id=11
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/product_class.php';

header('Content-Type: application/json');

// Only allow logged-in admin users
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['error' => 'Please provide a valid product_id in URL: ?product_id=11']);
    exit;
}

$test_results = [
    'product_id' => $product_id,
    'tests' => []
];

// Test 1: Check if product exists
$product = new product_class();
$product_data = $product->get_product_by_id($product_id);
$test_results['tests']['product_exists'] = [
    'result' => $product_data ? 'YES' : 'NO',
    'product_data' => $product_data ? ['id' => $product_data['product_id'], 'title' => $product_data['product_title']] : null
];

if (!$product_data) {
    echo json_encode([
        'error' => "Product ID $product_id does not exist in database",
        'tests' => $test_results['tests']
    ], JSON_PRETTY_PRINT);
    exit;
}

// Test 2: Check database connection
$conn = $product->getConnection();
$test_results['tests']['database_connection'] = [
    'result' => $conn ? 'YES' : 'NO',
    'connection_type' => $conn ? get_class($conn) : 'N/A'
];

if (!$conn) {
    echo json_encode([
        'error' => 'Database connection failed',
        'tests' => $test_results['tests']
    ], JSON_PRETTY_PRINT);
    exit;
}

// Test 3: Check if product_images table exists
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

// Test 4: Try to insert a test image (will rollback)
try {
    $test_image_url = 'uploads/test/test_image_' . time() . '.jpg';
    $test_sql = "INSERT INTO product_images (product_id, image_url, image_alt, image_title, sort_order, is_primary)
                 VALUES (?, ?, ?, ?, ?, ?)";
    
    $test_params = [
        $product_id,
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
        $test_results['tests']['insert_test'] = [
            'result' => 'SUCCESS',
            'image_id' => $test_image_id,
            'row_count' => $test_stmt->rowCount()
        ];
        
        // Rollback the test insert
        $conn->rollBack();
    } else {
        $error_info = $test_stmt->errorInfo();
        $test_results['tests']['insert_test'] = [
            'result' => 'FAILED',
            'error' => $error_info[2] ?? 'Unknown error',
            'error_info' => $error_info
        ];
        $conn->rollBack();
    }
} catch (PDOException $e) {
    $test_results['tests']['insert_test'] = [
        'result' => 'EXCEPTION',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ];
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
}

// Test 5: Check existing images for this product
try {
    $existing_images = $product->get_product_images($product_id);
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

echo json_encode($test_results, JSON_PRETTY_PRINT);

