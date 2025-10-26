<?php
/**
 * Update Brand Action
 * 
 * Handles brand update requests
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
    echo json_encode(['success' => false, 'message' => 'Please log in to update brands']);
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
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $brand_name = trim($_POST['brand_name'] ?? '');
    $brand_description = trim($_POST['brand_description'] ?? '');
    $brand_logo = trim($_POST['brand_logo'] ?? '');
    $user_id = $_SESSION['user_id'];
    
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
    
    // Attempt to update brand
    $result = update_brand_ctr($brand_id, $brand_name, $user_id, $brand_description, $brand_logo);
    
    if ($result === "success") {
        if ($is_ajax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Brand updated successfully!',
                'data' => [
                    'brand_id' => $brand_id,
                    'brand_name' => $brand_name,
                    'brand_description' => $brand_description,
                    'brand_logo' => $brand_logo,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo 'Brand updated successfully!';
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
    error_log("Update brand action error: " . $e->getMessage());
    
    // Return generic error message
    $error_msg = 'An error occurred while updating the brand. Please try again.';
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>
