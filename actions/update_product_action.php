<?php
/**
 * Update Product Action
 * 
 * Handles product update requests
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
    echo json_encode(['success' => false, 'message' => 'Please log in to update products']);
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
    $product_id = (int)($_POST['product_id'] ?? 0);
    $cat_id = (int)($_POST['cat_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $price = $_POST['price'] ?? '';
    $desc = trim($_POST['desc'] ?? '');
    $keyword = trim($_POST['keyword'] ?? '');
    $image_path = trim($_POST['image_path'] ?? '');
    
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
        'product_id' => $product_id,
        'cat_id' => $cat_id,
        'brand_id' => $brand_id,
        'title' => $title,
        'price' => (float)$price,
        'desc' => $desc,
        'keyword' => $keyword,
        'image_path' => $image_path
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
    
    // Attempt to update product
    $result = update_product_ctr($data);
    
    if ($result === "success") {
        if ($is_ajax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Product updated successfully!',
                'data' => [
                    'product_id' => $product_id,
                    'title' => $title,
                    'price' => $price,
                    'image_path' => $image_path,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo 'Product updated successfully!';
        }
    } else {
        // Even if update fails, if image was uploaded, return partial success
        if (!empty($image_path) && $is_ajax) {
            echo json_encode([
                'success' => false, 
                'message' => $result . ' However, the image was uploaded successfully.',
                'data' => [
                    'product_id' => $product_id,
                    'image_path' => $image_path
                ]
            ]);
        } else if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $result]);
        } else {
            echo $result;
        }
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Update product action error: " . $e->getMessage());
    
    // Return generic error message
    $error_msg = 'An error occurred while updating the product. Please try again.';
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>
