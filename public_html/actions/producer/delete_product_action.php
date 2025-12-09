<?php
/**
 * Delete Product Action for Producers
 */

require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../class/product_class.php';

header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

// Check user role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Only producers can delete products.']);
    exit;
}

$producer_id = $_SESSION['user_id'];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = (int)($input['product_id'] ?? 0);
    
    if (empty($product_id)) {
        throw new Exception('Product ID is required.');
    }
    
    // Verify product belongs to producer
    $product_class = new product_class();
    $product = $product_class->get_product_by_id($product_id);
    
    if (!$product) {
        throw new Exception('Product not found.');
    }
    
    // Check if product belongs to this producer
    $db = new db_class();
    $check_sql = "SELECT producer_id FROM products WHERE product_id = ?";
    $product_owner = $db->fetchColumn($check_sql, [$product_id]);
    
    if ($product_owner != $producer_id) {
        throw new Exception('You do not have permission to delete this product.');
    }
    
    // Delete product (cascade will handle related records)
    $delete_sql = "DELETE FROM products WHERE product_id = ? AND producer_id = ?";
    $result = $db->execute($delete_sql, [$product_id, $producer_id]);
    
    if ($result && $result->rowCount() > 0) {
        // Optionally delete product images directory
        $image_dir = __DIR__ . '/../../uploads/u' . $producer_id . '/p' . $product_id;
        if (is_dir($image_dir)) {
            // Recursive delete
            array_map('unlink', glob("$image_dir/*.*"));
            rmdir($image_dir);
        }
        
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
    } else {
        throw new Exception('Failed to delete product.');
    }
    
} catch (Exception $e) {
    error_log("Delete product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

