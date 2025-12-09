<?php
/**
 * Delete Product Action
 * 
 * Handles product deletion requests
 */

// Include core settings and product controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/product_controller.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST and DELETE requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to delete products']);
    exit;
}

// Check if user is admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) || isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

try {
    // Validate CSRF token if available
    if (isset($_POST['csrf_token'])) {
        validate_csrf_token($_POST['csrf_token']);
    }

    // Get product ID from POST or GET
    $product_id = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = (int)($_POST['product_id'] ?? 0);
    } else {
        $product_id = (int)($_GET['product_id'] ?? 0);
    }
    
    // Basic validation
    if (empty($product_id)) {
        $error_msg = 'Product ID is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Get product details before deletion (for response)
    $product_data = get_product_ctr($product_id);
    if (is_string($product_data)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $product_data]);
        } else {
            echo $product_data;
        }
        exit;
    }
    
    // Attempt to delete product
    $result = delete_product_ctr($product_id);
    
    if ($result === "success") {
        if ($is_ajax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Product deleted successfully!',
                'data' => [
                    'product_id' => $product_id,
                    'product_title' => $product_data['product_title'] ?? 'Unknown',
                    'cat_name' => $product_data['cat_name'] ?? 'Unknown',
                    'brand_name' => $product_data['brand_name'] ?? 'Unknown',
                    'deleted_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo 'Product deleted successfully!';
        }
    } else {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $result]);
        } else {
            echo $result;
        }
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Delete product action error: " . $e->getMessage());
    
    // Return generic error message
    $error_msg = 'An error occurred while deleting the product. Please try again.';
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>

