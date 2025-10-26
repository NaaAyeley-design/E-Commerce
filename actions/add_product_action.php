<?php
/**
 * Add Product Action
 * 
 * Handles product creation requests
 */

// Include core settings and product controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/product_controller.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to add products']);
    exit;
}

// Check if user is admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

try {
    // Validate CSRF token if available
    if (isset($_POST['csrf_token'])) {
        validate_csrf_token($_POST['csrf_token']);
    }

    // Get and sanitize input
    $user_id = $_SESSION['user_id'];
    $cat_id = (int)($_POST['cat_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $price = $_POST['price'] ?? '';
    $desc = trim($_POST['desc'] ?? '');
    $keyword = trim($_POST['keyword'] ?? '');
    $image_path = trim($_POST['image_path'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $compare_price = $_POST['compare_price'] ?? '';
    $cost_price = $_POST['cost_price'] ?? '';
    $stock_quantity = $_POST['stock_quantity'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $dimensions = trim($_POST['dimensions'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    // Basic validation
    if (empty($cat_id)) {
        $error_msg = 'Category ID is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    if (empty($brand_id)) {
        $error_msg = 'Brand ID is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    if (empty($title)) {
        $error_msg = 'Product title is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    if (empty($price) || !is_numeric($price)) {
        $error_msg = 'Valid price is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    if (empty($desc)) {
        $error_msg = 'Product description is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    if (empty($keyword)) {
        $error_msg = 'Product keywords are required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Prepare data array
    $data = [
        'user_id' => $user_id,
        'cat_id' => $cat_id,
        'brand_id' => $brand_id,
        'title' => $title,
        'price' => (float)$price,
        'desc' => $desc,
        'keyword' => $keyword,
        'image_path' => $image_path,
        'sku' => $sku,
        'compare_price' => $compare_price !== '' ? (float)$compare_price : null,
        'cost_price' => $cost_price !== '' ? (float)$cost_price : null,
        'stock_quantity' => $stock_quantity !== '' ? (int)$stock_quantity : 0,
        'weight' => $weight !== '' ? (float)$weight : null,
        'dimensions' => $dimensions,
        'meta_title' => $meta_title,
        'meta_description' => $meta_description
    ];
    
    // Additional validation using controller function
    $validation = validate_product_data($data);
    if (!$validation['valid']) {
        $error_msg = implode(' ', $validation['errors']);
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Attempt to add product
    $result = add_product_ctr($data);
    
    if ($result === "success") {
        if ($is_ajax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Product added successfully!',
                'data' => [
                    'cat_id' => $cat_id,
                    'brand_id' => $brand_id,
                    'title' => $title,
                    'price' => $price,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo 'Product added successfully!';
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
    error_log("Add product action error: " . $e->getMessage());
    
    // Return generic error message
    $error_msg = 'An error occurred while adding the product. Please try again.';
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>
