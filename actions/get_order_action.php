<?php
/**
 * Get Order Action
 * 
 * Fetches order details for admin view
 */

// Start output buffering to prevent any accidental output
ob_start();

// Suppress all output except JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Include core settings
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/order_controller.php';

// Clear any output that may have been generated during includes
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view order details']);
    ob_end_flush();
    exit;
}

// Check if user is admin
if (!is_admin()) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    ob_end_flush();
    exit;
}

try {
    // Get order ID
    $order_id = (int)($_POST['order_id'] ?? 0);
    
    if (empty($order_id)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID is required.']);
        ob_end_flush();
        exit;
    }
    
    // Get order details (admin can view any order)
    $order_result = get_order_ctr($order_id, null);
    
    if (!$order_result || !isset($order_result['success']) || !$order_result['success']) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $order_result['message'] ?? 'Order not found.'
        ]);
        ob_end_flush();
        exit;
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'order' => $order_result['order'] ?? null,
        'items' => $order_result['items'] ?? []
    ]);
    ob_end_flush();
    exit;
    
} catch (PDOException $e) {
    error_log("Get order action PDO error: " . $e->getMessage());
    error_log("Get order action PDO trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
    ob_end_flush();
    exit;
} catch (Exception $e) {
    error_log("Get order action error: " . $e->getMessage());
    error_log("Get order action trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching order details.']);
    ob_end_flush();
    exit;
} catch (Throwable $e) {
    error_log("Get order action throwable error: " . $e->getMessage());
    error_log("Get order action throwable trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
    ob_end_flush();
    exit;
}

