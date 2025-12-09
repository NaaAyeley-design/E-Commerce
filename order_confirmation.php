<?php
session_start();
require_once("order_class.php");

if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: index.php');
    exit;
}

$order = new Order();
$orderData = $order->getOrder($orderId);

if (!$orderData['success']) {
    header('Location: index.php');
    exit;
}

$orderDetails = $orderData['order'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Ecommerce</title>
    <link rel="stylesheet" href="order_confirmation.css">
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-header">
            <h1>✓ Order Confirmed!</h1>
            <p>Thank you for your purchase</p>
        </div>
        
        <div class="order-details">
            <div class="detail-card">
                <h2>Order Information</h2>
                <div class="detail-row">
                    <span class="label">Order Number:</span>
                    <span class="value"><?php echo htmlspecialchars($orderDetails['order_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Order Date:</span>
                    <span class="value"><?php echo date('F d, Y H:i', strtotime($orderDetails['created_at'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-badge <?php echo strtolower($orderDetails['status']); ?>">
                        <?php echo ucfirst($orderDetails['status']); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Total Amount:</span>
                    <span class="value total-amount">$<?php echo number_format($orderDetails['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <?php if (!empty($orderDetails['items'])): ?>
                <div class="detail-card">
                    <h2>Order Items</h2>
                    <div class="order-items-list">
                        <?php foreach ($orderDetails['items'] as $item): ?>
                            <div class="order-item-row">
                                <div class="item-name">
                                    <?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?>
                                </div>
                                <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                <div class="item-price">$<?php echo number_format($item['subtotal'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($orderDetails['shipping_address']): ?>
                <div class="detail-card">
                    <h2>Shipping Address</h2>
                    <p><?php echo nl2br(htmlspecialchars($orderDetails['shipping_address'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="confirmation-actions">
            <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            <a href="orders.php" class="btn btn-secondary">View All Orders</a>
        </div>
    </div>
</body>
</html>

