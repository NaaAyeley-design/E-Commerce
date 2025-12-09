<?php
/**
 * Get Cart Action
 * 
 * Fetches cart items for the logged-in user
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/cart_controller.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view cart']);
    exit;
}

try {
    // Get customer ID
    $customer_id = get_user_id();
    if (empty($customer_id)) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // Get cart items
    $cart_items = get_cart_items_ctr($customer_id);
    $cart_count = get_cart_count_ctr($customer_id);
    $cart_total = get_cart_total_ctr($customer_id);

    if ($cart_items !== false) {
        echo json_encode([
            'success' => true,
            'data' => $cart_items,
            'count' => $cart_count,
            'total' => $cart_total
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch cart items.'
        ]);
    }

} catch (Exception $e) {
    error_log("Get cart action error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching cart.']);
}

