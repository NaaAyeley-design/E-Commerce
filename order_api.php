<?php
/**
 * Order API Endpoint
 * Handles order creation and status updates
 */
session_start();
header('Content-Type: application/json');
require_once("order_class.php");

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $order = new Order();
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['order_id'])) {
                // Get specific order
                $orderId = (int)$_GET['order_id'];
                $result = $order->getOrder($orderId);
                echo json_encode($result);
            } elseif ($action === 'list' || $action === '') {
                // Get user orders
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $result = $order->getUserOrders($limit);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            if ($action === 'create' || $action === '') {
                // Create order from cart
                $data = json_decode(file_get_contents('php://input'), true);
                
                $shippingAddress = $data['shipping_address'] ?? null;
                $billingAddress = $data['billing_address'] ?? null;
                $paymentMethod = $data['payment_method'] ?? null;
                
                $result = $order->createOrderFromCart($shippingAddress, $billingAddress, $paymentMethod);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'PUT':
            // Update order status
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['order_id']) || !isset($data['status'])) {
                echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
                break;
            }
            
            $orderId = (int)$data['order_id'];
            $status = $data['status'];
            
            $result = $order->updateOrderStatus($orderId, $status);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

