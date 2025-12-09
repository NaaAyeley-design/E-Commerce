<?php
/**
 * Cart API Endpoint
 * Handles all cart operations: add, update, remove, empty, get
 */
session_start();
header('Content-Type: application/json');
require_once("cart_class.php");

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $cart = new Cart();
    
    switch ($method) {
        case 'GET':
            if ($action === 'items' || $action === '') {
                // Get cart items
                $cartData = $cart->getCartItems();
                echo json_encode($cartData);
            } elseif ($action === 'count') {
                // Get cart item count
                $count = $cart->getItemCount();
                echo json_encode(['success' => true, 'count' => $count]);
            } elseif ($action === 'total') {
                // Get cart total
                $total = $cart->getCartTotal();
                echo json_encode(['success' => true, 'total' => $total]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            // Add item to cart
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['product_id'])) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                break;
            }
            
            $productId = (int)$data['product_id'];
            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
            
            $result = $cart->addItem($productId, $quantity);
            echo json_encode($result);
            break;
            
        case 'PUT':
            // Update cart item quantity
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['product_id']) || !isset($data['quantity'])) {
                echo json_encode(['success' => false, 'message' => 'Product ID and quantity are required']);
                break;
            }
            
            $productId = (int)$data['product_id'];
            $quantity = (int)$data['quantity'];
            
            $result = $cart->updateQuantity($productId, $quantity);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            if (isset($_GET['product_id'])) {
                // Remove specific item
                $productId = (int)$_GET['product_id'];
                $result = $cart->removeItem($productId);
                echo json_encode($result);
            } elseif ($action === 'empty' || $action === 'clear') {
                // Empty entire cart
                $result = $cart->emptyCart();
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product ID required or use action=empty']);
            }
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

