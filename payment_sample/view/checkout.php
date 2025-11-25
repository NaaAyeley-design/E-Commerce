<?php
require_once '../settings/core.php';
require_login('../login/login.php');

// Check if cart is not empty
require_once '../controllers/cart_controller.php';
$customer_id = get_user_id();
$cart_items = get_user_cart_ctr($customer_id);

if (!$cart_items || count($cart_items) == 0) {
    header('Location: cart.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Aya Crafts</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #ffffff; }
        
        .navbar { background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%); padding: 20px 0; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; }
        .logo { font-family: 'Cormorant Garamond', serif; font-size: 28px; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        
        .page-header { background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%); padding: 50px 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06); margin-bottom: 30px; position: relative; overflow: hidden; }
        .page-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, #dc2626 0%, #ef4444 50%, #dc2626 100%); }
        .page-title { font-family: 'Cormorant Garamond', serif; font-size: 42px; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .checkout-section { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06); margin-bottom: 20px; }
        
        .summary-total { font-size: 32px; font-weight: 700; color: #dc2626; padding: 20px 0; text-align: center; border-top: 2px solid #f3f4f6; border-bottom: 2px solid #f3f4f6; margin: 20px 0; }
        
        .btn { padding: 16px 40px; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.4s ease; text-decoration: none; display: inline-block; width: 100%; }
        .btn-primary { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(220, 38, 38, 0.4); }
        .btn-secondary { background: white; color: #374151; border: 2px solid #e5e7eb; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal-content { background: white; max-width: 500px; width: 90%; padding: 40px; border-radius: 20px; position: relative; transform: scale(0.9); transition: transform 0.3s ease; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-content::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%); border-radius: 20px 20px 0 0; }
        .modal-close { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #6b7280; }
        .modal-close:hover { color: #dc2626; }
        .modal-title { font-family: 'Cormorant Garamond', serif; font-size: 28px; color: #1a1a1a; margin-bottom: 20px; text-align: center; }
        .modal-buttons { display: flex; gap: 12px; margin-top: 30px; }
        .modal-buttons button { flex: 1; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">Aya Crafts</a>
            <div style="display: flex; gap: 20px;">
                <a href="cart.php" style="color: #374151; text-decoration: none;">← Back to Cart</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Checkout</h1>
            <p style="color: #6b7280; font-size: 16px;">Review your order and complete payment</p>
        </div>

        <div class="checkout-section">
            <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; margin-bottom: 20px;">Order Summary</h2>
            <div id="checkoutItemsContainer"></div>
            
            <div class="summary-total">
                Total: <span id="checkoutTotal">GHS 0.00</span>
            </div>
            
            <button onclick="showPaymentModal()" class="btn btn-primary">💳 Proceed to Payment</button>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePaymentModal()">&times;</span>
            <h2 class="modal-title">Secure Payment via Paystack</h2>
            
            <div style="text-align: center; margin: 30px 0;">
                <div style="font-size: 14px; color: #6b7280; margin-bottom: 10px;">Amount to Pay</div>
                <div id="paymentAmount" style="font-size: 36px; font-weight: 700; color: #dc2626;"></div>
            </div>
            
            <div style="background: linear-gradient(135deg, #1f2937 0%, #374151 100%); color: white; padding: 20px; border-radius: 12px; margin: 20px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                <div style="font-size: 12px; margin-bottom: 10px; opacity: 0.8;">SECURED PAYMENT</div>
                <div style="font-size: 18px; letter-spacing: 2px; margin-bottom: 15px;">🔒 Powered by Paystack</div>
                <div style="font-size: 12px; opacity: 0.8;">Your payment information is 100% secure and encrypted</div>
            </div>
            
            <p style="text-align: center; color: #6b7280; font-size: 13px; margin-bottom: 20px;">
                You will be redirected to Paystack's secure payment gateway
            </p>
            
            <div class="modal-buttons">
                <button onclick="closePaymentModal()" class="btn btn-secondary">Cancel</button>
                <button onclick="processCheckout()" id="confirmPaymentBtn" class="btn btn-primary">💳 Pay Now</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">🎉 Order Successful!</h2>
            
            <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 20px; border-radius: 12px; margin: 20px 0; border: 2px solid #6ee7b7;">
                <div style="text-align: center; margin-bottom: 15px;">
                    <div style="font-size: 14px; color: #065f46; margin-bottom: 5px;">Invoice Number</div>
                    <div id="successInvoice" style="font-size: 20px; font-weight: 700; color: #047857;"></div>
                </div>
                <div style="border-top: 1px solid rgba(6, 95, 70, 0.2); padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #065f46;">
                        <span>Total Paid:</span>
                        <span style="font-weight: 600;" id="successAmount"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #065f46;">
                        <span>Date:</span>
                        <span id="successDate"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #065f46;">
                        <span>Items:</span>
                        <span id="successItems"></span>
                    </div>
                </div>
            </div>
            
            <p style="text-align: center; color: #6b7280; margin-bottom: 25px;">Thank you for your order! Your items are being processed.</p>
            
            <div class="modal-buttons">
                <button onclick="continueShopping()" class="btn btn-secondary">Continue Shopping</button>
                <button onclick="viewOrders()" class="btn btn-primary">View Orders</button>
            </div>
        </div>
    </div>

    <script src="../js/checkout.js"></script>
</body>
</html>