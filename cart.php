<?php
session_start();
require_once("cart_class.php");

$cart = new Cart();
$cartData = $cart->getCartItems();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Ecommerce</title>
    <link rel="stylesheet" href="cart.css">
</head>
<body>
    <div class="cart-container">
        <h1>Shopping Cart</h1>
        
        <div id="cart-items" class="cart-items">
            <?php if (empty($cartData['items'])): ?>
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <a href="index.php" class="continue-shopping">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-items-list">
                    <?php foreach ($cartData['items'] as $item): ?>
                        <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="cart-item-info">
                                <h4><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?></h4>
                                <p class="cart-item-price">$<?php echo number_format($item['product_price'] ?? 0, 2); ?> each</p>
                            </div>
                            <div class="cart-item-controls">
                                <label>Quantity:</label>
                                <input type="number" 
                                       class="cart-quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       data-product-id="<?php echo $item['product_id']; ?>"
                                       onchange="cartManager.updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                <button class="remove-from-cart" data-product-id="<?php echo $item['product_id']; ?>">Remove</button>
                            </div>
                            <div class="cart-item-total">
                                $<?php echo number_format($item['item_total'] ?? 0, 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="cart-total-section">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span id="cart-subtotal">$<?php echo number_format($cartData['total'], 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>$0.00</span>
                        </div>
                        <div class="total-row final-total">
                            <span>Total:</span>
                            <span id="cart-total">$<?php echo number_format($cartData['total'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="cart-actions">
                        <button id="empty-cart" class="empty-cart-btn">Empty Cart</button>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="cart.js"></script>
</body>
</html>

