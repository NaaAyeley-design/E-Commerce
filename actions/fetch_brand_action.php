<?php
/**
 * Fetch Brand Action
 * 
 * Fetches all brands created by the logged-in user
 */

// Include core settings and brand controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/brand_controller.php';

// Only allow GET and POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to access brands']);
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
    $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
    
    // Validate pagination parameters
    $limit = max(1, min($limit, 100)); // Between 1 and 100
    $offset = max(0, $offset);
    
    // Fetch brands
    if (!empty($search)) {
        $brands = search_brands_ctr($search, $user_id, $limit);
    } elseif (!empty($cat_id)) {
        $brands = get_brands_by_category_ctr($user_id, $cat_id);
    } else {
        $brands = fetch_brands_ctr($user_id, $limit, $offset);
    }
    
    // Check if result is an error message
    if (is_string($brands)) {
        echo json_encode(['success' => false, 'message' => $brands]);
        exit;
    }
    
    // Get total count for pagination (only if not filtering by category or search)
    $total_count = 0;
    if (empty($search) && empty($cat_id)) {
        $total_count = get_brand_count_ctr($user_id);
        if (is_string($total_count)) {
            $total_count = 0; // Default to 0 if error
        }
    } else {
        $total_count = count($brands);
    }
    
    // Format the response
    $response = [
        'success' => true,
        'data' => $brands,
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
    error_log("Fetch brand action error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while fetching brands'
    ]);
}
?>
