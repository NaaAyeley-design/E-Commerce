<?php
/**
 * Wishlist Dashboard Page
 * 
 * Display and manage user's wishlist items
 */

require_once __DIR__ . '/../../../settings/core.php';

// Set page variables
$page_title = 'My Wishlist';
$page_description = 'View and manage your wishlist';
$body_class = 'wishlist-page';
$additional_css = ['wishlist.css', 'products.css'];

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="wishlist-page" id="wishlist-page">
    <!-- Page Header -->
    <div class="wishlist-page-header">
        <a href="<?php echo url('view/user/dashboard.php'); ?>" class="wishlist-back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1 class="wishlist-page-title">
            My <span class="highlight">Wishlist</span>
        </h1>
        <p class="wishlist-count-text">
            <span id="item-count">0</span> items saved
        </p>
    </div>

    <!-- Actions Bar -->
    <div class="wishlist-actions">
        <button type="button" class="btn btn-outline" id="clear-wishlist">
            <i class="fas fa-trash"></i> Clear All
        </button>
        <button type="button" class="btn btn-primary" id="add-all-to-cart">
            <i class="fas fa-shopping-cart"></i> Add All to Cart
        </button>
    </div>

    <!-- Wishlist Grid -->
    <div class="wishlist-grid" id="wishlist-grid" style="display: none;">
        <!-- Wishlist items will be loaded here by JavaScript -->
    </div>

    <!-- Empty Wishlist State -->
    <div class="empty-wishlist" id="empty-wishlist">
        <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </div>
        <h3>Your Wishlist is Empty</h3>
        <p>Save items you love to shop later</p>
        <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-primary">
            Browse Products
        </a>
    </div>
</div>

<script>
    // Define BASE_URL for JavaScript
    if (typeof BASE_URL === 'undefined') {
        var BASE_URL = '<?php echo BASE_URL; ?>';
    }
    if (typeof ASSETS_URL === 'undefined') {
        var ASSETS_URL = '<?php echo ASSETS_URL; ?>';
    }
</script>
<script src="<?php echo ASSETS_URL; ?>/js/wishlist.js?v=<?php echo time(); ?>"></script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

