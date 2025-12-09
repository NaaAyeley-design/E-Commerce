<?php
/**
 * Get Product Action
 * 
 * Fetches a single product by ID
 */

// Include core settings and product controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/product_controller.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow GET and POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to access products']);
    exit;
}

// Check if user is admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

try {
    // Get product ID from GET or POST
    $product_id = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $product_id = (int)($_GET['product_id'] ?? 0);
    } else {
        $product_id = (int)($_POST['product_id'] ?? 0);
    }
    
    // Validate product ID
    if (empty($product_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }
    
    // Fetch product
    $product = get_product_ctr($product_id);
    
    // Check if result is an error message
    if (is_string($product)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => $product]);
        exit;
    }
    
    // Format the response
    $response = [
        'success' => true,
        'data' => $product
    ];
    
    // Return JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("Get product action error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while fetching the product'
    ]);
}
?>

