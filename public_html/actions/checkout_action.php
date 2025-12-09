<?php
/**
 * Checkout Action
 * 
 * Handles checkout process - creates order from cart items
 */

// Start output buffering to prevent any accidental output
ob_start();

// Suppress all output except JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Include core settings
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/order_class.php';
require_once __DIR__ . '/../class/product_class.php';
require_once __DIR__ . '/../controller/cart_controller.php';
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
    echo json_encode(['success' => false, 'message' => 'Please log in to checkout']);
    ob_end_flush();
    exit;
}

try {
    // Get customer ID
    $customer_id = get_user_id();
    if (empty($customer_id)) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not found. Please log in.']);
        ob_end_flush();
        exit;
    }

    // Get cart items
    $cart_items = get_cart_items_ctr($customer_id);
    
    if (empty($cart_items) || !is_array($cart_items) || count($cart_items) === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
        ob_end_flush();
        exit;
    }

    // Calculate total
    $cart_total = get_cart_total_ctr($customer_id);
    
    if ($cart_total <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid cart total.']);
        ob_end_flush();
        exit;
    }

    // Prepare order items from cart
    $order_items = [];
    foreach ($cart_items as $item) {
        // Validate required fields
        if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['product_price'])) {
            error_log("Invalid cart item structure: " . json_encode($item));
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Invalid cart item data. Please refresh your cart and try again.',
                'debug' => APP_ENV === 'development' ? ['item' => $item] : null
            ]);
            ob_end_flush();
            exit;
        }
        
        $order_items[] = [
            'product_id' => (int)$item['product_id'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['product_price']
        ];
    }

    // Get customer data for shipping address
    require_once __DIR__ . '/../class/user_class.php';
    $user = new user_class();
    $customer_data = $user->get_customer_by_id($customer_id);
    
    if (!$customer_data) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Customer data not found.']);
        ob_end_flush();
        exit;
    }

    // Create shipping address from customer data
    $name = $customer_data['customer_name'] ?? 'N/A';
    $city = $customer_data['customer_city'] ?? '';
    $country = $customer_data['customer_country'] ?? '';
    $contact = $customer_data['customer_contact'] ?? '';
    
    $shipping_address = $name . "\n" .
                       ($city ? $city : '') . ($city && $country ? ", " : '') . ($country ? $country : '') . "\n" .
                       ($contact ? "Contact: " . $contact : '');
    
    // Ensure shipping address is not empty
    if (trim($shipping_address) === '' || $shipping_address === "\n\n") {
        $shipping_address = $name . "\nAddress not provided";
    }

    // Log order creation attempt for debugging
    error_log("Checkout attempt - Customer ID: $customer_id, Items: " . count($order_items) . ", Total: $cart_total");
    error_log("Order items: " . json_encode($order_items));
    error_log("Shipping address: " . $shipping_address);
    
    // Create order
    $order_result = create_order_ctr($customer_id, $order_items, $shipping_address, 'pending');
    
    if (!$order_result || !isset($order_result['success']) || !$order_result['success']) {
        // Log the error for debugging
        error_log("Checkout order creation failed: " . json_encode($order_result));
        error_log("Customer ID: $customer_id");
        error_log("Order items count: " . count($order_items));
        error_log("Cart total: $cart_total");
        
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $order_result['message'] ?? 'Failed to create order. Please try again.',
            'debug' => APP_ENV === 'development' ? [
                'order_result' => $order_result,
                'customer_id' => $customer_id,
                'items_count' => count($order_items),
                'cart_total' => $cart_total
            ] : null
        ]);
        ob_end_flush();
        exit;
    }

    // Clear cart after successful order
    require_once __DIR__ . '/../class/cart_class.php';
    $cart = new cart_class();
    $cart_cleared = $cart->clear_cart($customer_id);

    // Log order creation
    log_activity('order_created', "Order #{$order_result['order_id']} created from cart", $customer_id);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_result['order_id'] ?? null,
        'redirect' => url('view/user/dashboard.php')
    ]);
    ob_end_flush();
    exit;

} catch (PDOException $e) {
    error_log("Checkout action PDO error: " . $e->getMessage());
    error_log("Checkout action PDO trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
    ob_end_flush();
    exit;
} catch (Exception $e) {
    error_log("Checkout action error: " . $e->getMessage());
    error_log("Checkout action trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred during checkout.']);
    ob_end_flush();
    exit;
} catch (Throwable $e) {
    error_log("Checkout action throwable error: " . $e->getMessage());
    error_log("Checkout action throwable trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
    ob_end_flush();
    exit;
}

