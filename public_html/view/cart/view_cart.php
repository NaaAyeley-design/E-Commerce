<?php
/**
 * Cart View Page
 * 
 * Display all items in the user's shopping cart
 */

require_once __DIR__ . '/../../../settings/core.php';

// Only require cart controller if needed
if (!function_exists('get_cart_items_ctr')) {
    require_once __DIR__ . '/../../../controller/cart_controller.php';
}

// Set page variables
$page_title = 'Shopping Cart';
$page_description = 'View and manage your shopping cart';
$body_class = 'cart-page';
$additional_css = ['cart.css'];

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get customer ID
$customer_id = get_user_id();
if (empty($customer_id)) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

// Get cart items (with comprehensive error handling)
$cart_items = [];
$cart_total = 0.00;
$cart_count = 0;

try {
    // Check if cart functions exist
    if (!function_exists('get_cart_items_ctr')) {
        throw new Exception('Cart controller functions not available');
    }
    
    // Get cart data
    $cart_items = get_cart_items_ctr($customer_id);
    $cart_total = get_cart_total_ctr($customer_id);
    $cart_count = get_cart_count_ctr($customer_id);
    
    // Ensure cart_items is an array
    if (!is_array($cart_items)) {
        $cart_items = [];
    }
    
    // Ensure numeric values
    $cart_total = (float)$cart_total;
    $cart_count = (int)$cart_count;
    
} catch (Exception $e) {
    error_log("Cart view error: " . $e->getMessage());
    error_log("Cart view error trace: " . $e->getTraceAsString());
    $cart_items = [];
    $cart_total = 0.00;
    $cart_count = 0;
} catch (Error $e) {
    error_log("Cart view fatal error: " . $e->getMessage());
    error_log("Cart view fatal error trace: " . $e->getTraceAsString());
    $cart_items = [];
    $cart_total = 0.00;
    $cart_count = 0;
} catch (Throwable $e) {
    error_log("Cart view throwable error: " . $e->getMessage());
    error_log("Cart view throwable error trace: " . $e->getTraceAsString());
    $cart_items = [];
    $cart_total = 0.00;
    $cart_count = 0;
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <!-- Back Button -->
    <?php 
    $back_url = url('index.php');
    $back_text = 'Back to Home';
    if (is_logged_in()) {
        if (is_admin()) {
            $back_url = url('view/admin/dashboard.php');
            $back_text = 'Back to Dashboard';
        } else {
            $back_url = url('view/user/dashboard.php');
            $back_text = 'Back to Dashboard';
        }
    }
    ?>
    <a href="<?php echo $back_url; ?>" class="back-home">
        <i class="fas fa-arrow-left"></i> <?php echo $back_text; ?>
    </a>
    
    <div class="cart-container">
        <h1 class="page-title">Shopping Cart</h1>
        
        <?php if (empty($cart_items) || count($cart_items) === 0): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="cart-items-body">
                            <?php foreach ($cart_items as $item): 
                                $item_total = $item['product_price'] * $item['quantity'];
                                $image_url = ASSETS_URL . '/images/placeholder-product.svg';
                                $image_path = !empty($item['product_image']) ? ltrim($item['product_image'], '/') : '';
                                if (!empty($image_path)) {
                                    // Check if path already includes BASE_URL
                                    if (strpos($image_path, 'http') === 0) {
                                        $image_url = $image_path;
                                    } else {
                                        // If uploads path, remove /public_html from BASE_URL
                                        if (strpos($image_path, 'uploads/') === 0) {
                                            $base_url = str_replace('/public_html', '', BASE_URL);
                                            $full_path = ROOT_PATH . '/' . $image_path;
                                            // Check if file exists before using it
                                            if (file_exists($full_path)) {
                                                $image_url = $base_url . '/' . $image_path;
                                            } else {
                                                $image_url = ASSETS_URL . '/images/placeholder-product.svg';
                                            }
                                        } else {
                                            $full_path = ROOT_PATH . '/' . $image_path;
                                            // Check if file exists before using it
                                            if (file_exists($full_path)) {
                                                $image_url = BASE_URL . '/' . $image_path;
                                            } else {
                                                $image_url = ASSETS_URL . '/images/placeholder-product.svg';
                                            }
                                        }
                                    }
                                }
                            ?>
                                <tr data-cart-id="<?php echo $item['cart_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                                    <td class="product-cell" data-label="Product">
                                        <div class="cart-product">
                                            <div class="cart-product-image">
                                                <img src="<?php echo $image_url; ?>" 
                                                     alt="<?php echo escape_html($item['product_title']); ?>"
                                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
                                            </div>
                                            <div class="cart-product-info">
                                                <h3 class="cart-product-title">
                                                    <a href="<?php echo url('view/product/single_product.php?id=' . $item['product_id']); ?>">
                                                        <?php echo escape_html($item['product_title']); ?>
                                                    </a>
                                                </h3>
                                                <div class="cart-product-meta">
                                                    <?php if (!empty($item['cat_name'])): ?>
                                                        <span class="category"><?php echo escape_html($item['cat_name']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['brand_name'])): ?>
                                                        <span class="brand"><?php echo escape_html($item['brand_name']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="price-cell" data-label="Price">
                                        <span class="product-price">₵<?php echo number_format($item['product_price'], 2); ?></span>
                                    </td>
                                    <td class="quantity-cell" data-label="Quantity">
                                        <div class="quantity-controls">
                                            <button class="quantity-btn decrease-qty" 
                                                    data-cart-id="<?php echo $item['cart_id']; ?>"
                                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   class="quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1"
                                                   data-cart-id="<?php echo $item['cart_id']; ?>"
                                                   readonly>
                                            <button class="quantity-btn increase-qty" 
                                                    data-cart-id="<?php echo $item['cart_id']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="subtotal-cell" data-label="Subtotal">
                                        <span class="item-subtotal">₵<?php echo number_format($item_total, 2); ?></span>
                                    </td>
                                    <td class="action-cell" data-label="Action">
                                        <button class="remove-cart-item" 
                                                data-cart-id="<?php echo $item['cart_id']; ?>"
                                                title="Remove from cart"
                                                aria-label="Remove item from cart">
                                            <i class="fas fa-trash"></i>
                                            <span class="remove-text">Remove</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h2>Order Summary</h2>
                        <div class="summary-row">
                            <span>Items (<?php echo $cart_count; ?>):</span>
                            <span id="cart-items-count"><?php echo $cart_count; ?></span>
                        </div>
                        <div class="summary-row total-row">
                            <strong>Total:</strong>
                            <strong id="cart-total">₵<?php echo number_format($cart_total, 2); ?></strong>
                        </div>
                        <div class="summary-actions">
                            <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-outline btn-block">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                            <?php if ($cart_count > 0): ?>
                                <a href="<?php echo url('view/payment/checkout.php'); ?>" class="btn btn-primary btn-block" style="text-align: center; text-decoration: none; display: block;">
                                    <i class="fas fa-lock"></i> Proceed to Secure Checkout
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary btn-block" disabled>
                                    <i class="fas fa-lock"></i> Proceed to Checkout
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Define BASE_URL for JavaScript
    if (typeof BASE_URL === 'undefined') {
        var BASE_URL = '<?php echo BASE_URL; ?>';
    }
</script>
<script src="<?php echo ASSETS_URL; ?>/js/cart.js?v=<?php echo time(); ?>"></script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

