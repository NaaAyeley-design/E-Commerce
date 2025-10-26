<?php
/**
 * Add Brand Action
 * 
 * Handles brand creation requests
 */

// Include core settings and brand controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/brand_controller.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to add brands']);
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
    $brand_name = trim($_POST['brand_name'] ?? '');
    $brand_description = trim($_POST['brand_description'] ?? '');
    $brand_logo = trim($_POST['brand_logo'] ?? '');
    
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
    
    if (empty($brand_name)) {
        $error_msg = 'Brand name is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Additional validation using controller function
    $validation = validate_brand_data($brand_name, $brand_description);
    if (!$validation['valid']) {
        $error_msg = implode(' ', $validation['errors']);
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Attempt to add brand
    $result = add_brand_ctr($user_id, $cat_id, $brand_name, $brand_description, $brand_logo);
    
    if ($result === "success") {
        if ($is_ajax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Brand added successfully!',
                'data' => [
                    'cat_id' => $cat_id,
                    'brand_name' => $brand_name,
                    'brand_description' => $brand_description,
                    'brand_logo' => $brand_logo,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo 'Brand added successfully!';
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
    error_log("Add brand action error: " . $e->getMessage());
    
    // Return generic error message
    $error_msg = 'An error occurred while adding the brand. Please try again.';
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>
