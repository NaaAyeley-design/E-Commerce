<?php
/**
 * Fetch Category Action
 * 
 * Fetches all categories created by the logged-in user
 */

// Include core settings and category controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/category_controller.php';

// Only allow GET and POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to access categories']);
    exit;
}

// Check if user is admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

try {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get pagination parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Validate pagination parameters
    $limit = max(1, min($limit, 100)); // Between 1 and 100
    $offset = max(0, $offset);
    
    // Fetch categories
    if (!empty($search)) {
        $categories = search_categories_ctr($search, $user_id, $limit);
    } else {
        $categories = get_categories_ctr($user_id, $limit, $offset);
    }
    
    // Check if result is an error message
    if (is_string($categories)) {
        echo json_encode(['success' => false, 'message' => $categories]);
        exit;
    }
    
    // Get total count for pagination
    $total_count = get_category_count_ctr($user_id);
    if (is_string($total_count)) {
        $total_count = 0; // Default to 0 if error
    }
    
    // Format the response
    $response = [
        'success' => true,
        'data' => $categories,
        'pagination' => [
            'total' => $total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ]
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("Fetch category action error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while fetching categories'
    ]);
}
?>
