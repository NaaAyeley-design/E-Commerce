<?php
/**
 * Update Cart Action
 * 
 * Handles updating cart item quantities
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/cart_controller.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to update cart']);
    exit;
}

try {
    // Get customer ID
    $customer_id = get_user_id();
    if (empty($customer_id)) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // Get product ID and quantity
    // cart_id format: "customer_id_product_id" (e.g., "5_11")
    $cart_id = $_POST['cart_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);

    // Parse cart_id to get product_id
    if (empty($cart_id)) {
        echo json_encode(['success' => false, 'message' => 'Cart ID is required.']);
        exit;
    }

    // Extract product_id from cart_id (format: "customer_id_product_id")
    $parts = explode('_', $cart_id);
    if (count($parts) < 2) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart ID format.']);
        exit;
    }
    $product_id = (int)end($parts); // Get last part (product_id)

    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0.']);
        exit;
    }

    // Update cart item
    $result = update_cart_item_ctr($customer_id, $product_id, $quantity);

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Update cart action error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating cart.']);
}

