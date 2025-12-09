<?php
/**
 * Get Order Details Action
 * Returns order details with items via AJAX
 */

// Start output buffering
ob_start();

// Include core settings
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/order_class.php';
require_once __DIR__ . '/../controller/order_controller.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view order details.']);
    ob_end_flush();
    exit;
}

// Get order ID
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    ob_end_flush();
    exit;
}

try {
    // Get order details
    $customer_id = get_user_id();
    $order_result = get_order_ctr($order_id, $customer_id);
    
    if ($order_result['success']) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'order' => $order_result['order'],
            'items' => $order_result['items']
        ]);
        ob_end_flush();
    } else {
        ob_clean();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => $order_result['message'] ?? 'Order not found.'
        ]);
        ob_end_flush();
    }
} catch (Exception $e) {
    error_log("Get order details error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving order details.'
    ]);
    ob_end_flush();
}

