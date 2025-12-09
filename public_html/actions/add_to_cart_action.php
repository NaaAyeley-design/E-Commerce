<?php
/**
 * Add to Cart Action
 * 
 * Handles adding products to cart
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
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit;
}

try {
    // Get customer ID
    $customer_id = get_user_id();
    if (empty($customer_id)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not found. Please log in.']);
        exit;
    }

    // Get product ID and quantity
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    // Validate product ID
    if (empty($product_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
        exit;
    }

    // Add to cart
    $result = add_to_cart_ctr($customer_id, $product_id, $quantity);

    if (!is_array($result)) {
        $result = ['success' => false, 'message' => 'Unexpected response from cart controller.'];
    }

    echo json_encode($result);

} catch (PDOException $e) {
    error_log("Add to cart action PDO error: " . $e->getMessage());
    error_log("Add to cart action PDO trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
} catch (Exception $e) {
    error_log("Add to cart action error: " . $e->getMessage());
    error_log("Add to cart action trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding to cart.']);
} catch (Throwable $e) {
    error_log("Add to cart action throwable error: " . $e->getMessage());
    error_log("Add to cart action throwable trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}

