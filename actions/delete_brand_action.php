<?php
/**
 * Delete Brand Action
 * 
 * Handles brand deletion requests
 */

// Include core settings and brand controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/brand_controller.php';

// Only allow POST and DELETE requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to delete brands']);
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

    // Get brand ID from POST or GET
    $brand_id = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $brand_id = (int)($_POST['brand_id'] ?? 0);
    } else {
        $brand_id = (int)($_GET['brand_id'] ?? 0);
    }
    
    $user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
    if (!$user_id) {
        $error_msg = 'User ID not found in session. Please log in again.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Basic validation
    if (empty($brand_id)) {
        $error_msg = 'Brand ID is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Get brand details before deletion (for response)
    $brand_data = get_brand_ctr($brand_id, $user_id);
    if (is_string($brand_data)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $brand_data]);
        } else {
            echo $brand_data;
        }
        exit;
    }
    
    // Attempt to delete brand
    $result = delete_brand_ctr($brand_id, $user_id);
    
    if ($result === "success") {
        if ($is_ajax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Brand deleted successfully!',
                'data' => [
                    'brand_id' => $brand_id,
                    'brand_name' => $brand_data['brand_name'] ?? 'Unknown',
                    'cat_name' => $brand_data['cat_name'] ?? 'Unknown',
                    'deleted_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo 'Brand deleted successfully!';
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
    error_log("Delete brand action error: " . $e->getMessage());
    
    // Return generic error message
    $error_msg = 'An error occurred while deleting the brand. Please try again.';
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>
