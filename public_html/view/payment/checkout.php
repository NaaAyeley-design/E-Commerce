<?php
/**
 * Checkout Page
 * 
 * Displays order summary and initiates Paystack payment
 */

require_once __DIR__ . '/../../../settings/core.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include cart controller
require_once __DIR__ . '/../../../controller/cart_controller.php';

// Get customer ID
$customer_id = get_user_id();
if (empty($customer_id)) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

// Get cart items
$cart_items = get_cart_items_ctr($customer_id);
$cart_total = get_cart_total_ctr($customer_id);
$cart_count = get_cart_count_ctr($customer_id);

// Check if cart is empty
if (empty($cart_items) || !is_array($cart_items) || count($cart_items) === 0) {
    header('Location: ' . BASE_URL . '/view/cart/view_cart.php');
    exit;
}

// Get customer data for email
require_once __DIR__ . '/../../../class/user_class.php';
$user = new user_class();
$customer_data = $user->get_customer_by_id($customer_id);
$customer_email = $customer_data['customer_email'] ?? $_SESSION['customer_email'] ?? '';

// If still no email, try to get from session
if (empty($customer_email) && isset($_SESSION['customer_email'])) {
    $customer_email = $_SESSION['customer_email'];
}

// Final fallback - prompt user if email is still missing
if (empty($customer_email)) {
    error_log("Warning: Customer email not found for user ID: $customer_id");
}

// Set page variables
$page_title = 'Checkout';
$page_description = 'Complete your order with secure Paystack payment';
$body_class = 'checkout-page';
$additional_css = ['cart.css'];

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container" style="max-width: 900px; margin: 60px auto; padding: 0 20px;">
    <!-- Page Header -->
    <div style="text-align: center; margin-bottom: 60px;">
        <h1 style="font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; font-weight: 400; color: var(--text-dark); margin-bottom: 15px; letter-spacing: -0.01em;">
            Checkout
        </h1>
        <p style="font-family: 'Spectral', serif; font-size: 1rem; color: var(--text-light);">
            Review your order and complete payment securely
        </p>
    </div>

    <!-- Order Summary -->
    <div style="background: var(--white); padding: 50px 40px; border: var(--border-thin); border-radius: var(--radius-md); margin-bottom: 30px; box-shadow: none;">
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.75rem; font-weight: 400; color: var(--text-dark); margin-bottom: 30px;">
            Order Summary
        </h2>
        
        <div id="checkoutItemsContainer">
            <!-- Items will be loaded by JavaScript -->
            <div style="text-align: center; padding: 40px; color: var(--text-light);">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 15px;"></i>
                <p>Loading order summary...</p>
            </div>
        </div>
        
        <div style="border-top: var(--border-medium); padding-top: 30px; margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span style="font-family: 'Spectral', serif; font-size: 1.125rem; font-weight: 400; color: var(--text-dark);">
                    Total Amount
                </span>
                <span id="checkoutTotal" style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 600; color: var(--terracotta);">
                    ₵0.00
                </span>
            </div>
        </div>
        
        <button onclick="showPaymentModal()" class="btn btn-primary" style="width: 100%; margin-top: 30px; padding: 20px 60px; border: 1px solid var(--terracotta); background: var(--terracotta); color: var(--white); font-family: 'Spectral', serif; font-size: 0.875rem; font-weight: 400; letter-spacing: 0.2em; text-transform: uppercase; border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-base);">
            <i class="fas fa-lock" style="margin-right: 8px;"></i>
            Proceed to Secure Payment
        </button>
    </div>

    <!-- Back to Cart Link -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="<?php echo url('view/cart/view_cart.php'); ?>" style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); text-decoration: none; transition: color var(--transition-base);">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
            Back to Cart
        </a>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="payment-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(58, 47, 38, 0.7); z-index: 1000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
    <div class="modal-content" style="background: var(--white); max-width: 500px; width: 90%; padding: 50px 40px; border-radius: var(--radius-md); position: relative; transform: scale(0.9); transition: transform 0.3s ease; box-shadow: 0 20px 60px rgba(139, 111, 71, 0.12);">
        <span class="modal-close" onclick="closePaymentModal()" style="position: absolute; top: 20px; right: 20px; font-size: 28px; cursor: pointer; color: var(--text-light); transition: color var(--transition-base);">
            &times;
        </span>
        
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.75rem; font-weight: 400; color: var(--text-dark); margin-bottom: 30px; text-align: center;">
            Secure Payment via Paystack
        </h2>
        
        <div style="text-align: center; margin: 30px 0;">
            <div style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); margin-bottom: 10px;">
                Amount to Pay
            </div>
            <div id="paymentAmount" style="font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; font-weight: 600; color: var(--terracotta);">
                ₵0.00
            </div>
        </div>
        
        <div style="background: var(--warm-beige); padding: 25px; border-radius: var(--radius-md); margin: 30px 0; text-align: center;">
            <div style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light); margin-bottom: 10px; letter-spacing: 0.1em; text-transform: uppercase;">
                Secured Payment
            </div>
            <div style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--deep-brown); margin-bottom: 15px;">
                🔒 Powered by Paystack
            </div>
            <div style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light);">
                Your payment information is 100% secure and encrypted
            </div>
        </div>
        
        <p style="text-align: center; font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); margin-bottom: 30px; line-height: 1.8;">
            You will be redirected to Paystack's secure payment gateway to complete your transaction.
        </p>
        
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button onclick="closePaymentModal()" class="btn btn-outline" style="flex: 1; padding: 15px 30px; border: 1px solid var(--terracotta); background: transparent; color: var(--terracotta); font-family: 'Spectral', serif; font-size: 0.875rem; letter-spacing: 0.1em; text-transform: uppercase; border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-base);">
                Cancel
            </button>
            <button onclick="processCheckout()" id="confirmPaymentBtn" class="btn btn-primary" style="flex: 1; padding: 15px 30px; border: 1px solid var(--terracotta); background: var(--terracotta); color: var(--white); font-family: 'Spectral', serif; font-size: 0.875rem; letter-spacing: 0.1em; text-transform: uppercase; border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-base);">
                <i class="fas fa-credit-card" style="margin-right: 8px;"></i>
                Pay Now
            </button>
        </div>
    </div>
</div>

<!-- Paystack Configuration -->
<?php
// Get Paystack public key from config
require_once __DIR__ . '/../../../settings/paystack_config.php';
$paystack_public_key = defined('PAYSTACK_PUBLIC_KEY') ? PAYSTACK_PUBLIC_KEY : '';
?>
<script>
// Store checkout data globally
window.checkoutTotal = <?php echo json_encode($cart_total); ?>;
window.customerEmail = <?php echo json_encode($customer_email); ?>;
window.cartItems = <?php echo json_encode($cart_items); ?>;

// Store Paystack public key
window.PAYSTACK_PUBLIC_KEY = <?php echo json_encode($paystack_public_key); ?>;

// Function to check if PaystackPop is loaded
function checkPaystackLoaded() {
    if (typeof PaystackPop !== 'undefined') {
        console.log('PaystackPop is loaded and ready');
        return true;
    } else {
        console.warn('PaystackPop is not loaded yet');
        return false;
    }
}

// Initialize Paystack when script is ready
(function initializePaystack() {
    // Function to set up Paystack
    function setupPaystack() {
        if (typeof PaystackPop !== 'undefined') {
            // Set Paystack public key if using Inline method
            if (window.PAYSTACK_PUBLIC_KEY && 
                window.PAYSTACK_PUBLIC_KEY !== 'pk_test_YOUR_PUBLIC_KEY_HERE' && 
                window.PAYSTACK_PUBLIC_KEY !== '') {
                try {
                    PaystackPop.setPublicKey(window.PAYSTACK_PUBLIC_KEY);
                    console.log('✓ Paystack public key set successfully');
                } catch (e) {
                    console.error('✗ Error setting Paystack public key:', e);
                }
            } else {
                console.warn('⚠ Paystack public key not configured. Using Standard (redirect) method only.');
            }
        } else {
            console.warn('⚠ PaystackPop not available. Using Standard (redirect) method only.');
        }
    }
    
    // Try to set up immediately (script might already be loaded)
    if (document.readyState === 'loading') {
        // DOM is still loading, wait for it
        document.addEventListener('DOMContentLoaded', setupPaystack);
    } else {
        // DOM is already loaded
        setupPaystack();
    }
    
    // Also try after a short delay in case script loads asynchronously
    setTimeout(setupPaystack, 500);
})();
</script>
<script src="<?php echo ASSETS_URL; ?>/js/checkout.js?v=<?php echo time(); ?>"></script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

