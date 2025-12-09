<?php
session_start();
require_once("cart_class.php");
require_once("order_class.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$cart = new Cart();
$cartData = $cart->getCartItems();

// If cart is empty, redirect to cart page
if (empty($cartData['items'])) {
    header('Location: cart.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once("order_logger.php");
    
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? $shippingAddress);
    $paymentMethod = $_POST['payment_method'] ?? 'credit_card';
    
    OrderLogger::log("Checkout form submitted", [
        'has_shipping' => !empty($shippingAddress),
        'has_billing' => !empty($billingAddress),
        'payment_method' => $paymentMethod
    ]);
    
    if (empty($shippingAddress)) {
        $error = 'Shipping address is required';
        OrderLogger::logError("Checkout failed: Shipping address is empty");
    } else {
        try {
            $order = new Order();
            $result = $order->createOrderFromCart($shippingAddress, $billingAddress, $paymentMethod);
            
            if ($result['success']) {
                $success = 'Order placed successfully! Order #' . $result['order_number'];
                OrderLogger::logSuccess("Order created from checkout", [
                    'order_id' => $result['order_id'],
                    'order_number' => $result['order_number']
                ]);
                // Redirect to order confirmation page
                header('Location: order_confirmation.php?order_id=' . $result['order_id']);
                exit;
            } else {
                $error = $result['message'] ?? 'Failed to create order. Please try again.';
                OrderLogger::logError("Order creation failed in checkout", ['result' => $result]);
            }
        } catch (Exception $e) {
            $error = 'An error occurred while processing your order. Please try again.';
            OrderLogger::logError("Exception in checkout", $e);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Ecommerce</title>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>
    <div class="checkout-container">
        <h1>Checkout</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="checkout-content">
            <div class="checkout-form-section">
                <form method="POST" action="checkout.php" class="checkout-form">
                    <h2>Shipping Information</h2>
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address *</label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="billing_address">Billing Address</label>
                        <textarea id="billing_address" name="billing_address" rows="4"><?php echo htmlspecialchars($_POST['billing_address'] ?? ''); ?></textarea>
                        <small>Leave blank to use shipping address</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method">
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="checkout-button">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary-section">
                <h2>Order Summary</h2>
                <div class="order-items">
                    <?php foreach ($cartData['items'] as $item): ?>
                        <div class="order-item">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?></h4>
                                <p>Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="item-price">
                                $<?php echo number_format($item['item_total'] ?? 0, 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($cartData['total'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span>$0.00</span>
                    </div>
                    <div class="total-row final-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($cartData['total'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

