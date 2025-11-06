<?php
/**
 * Single Product Page
 * 
 * Display full details for a selected product.
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/product_controller.php';

// Set page variables
$page_title = 'Product Details';
$page_description = 'View product details';
$body_class = 'single-product-page';
$additional_css = ['products-display.css'];

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($product_id)) {
    header('Location: ' . BASE_URL . '/view/product/all_product.php');
    exit;
}

// Get product details
$product = view_single_product_ctr($product_id);

if (!$product) {
    header('Location: ' . BASE_URL . '/view/product/all_product.php');
    exit;
}

// Set page title to product name
$page_title = escape_html($product['product_title']) . ' - Product Details';

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
    
    <div class="product-detail-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="<?php echo url('index.php'); ?>">Home</a>
            <span>/</span>
            <a href="<?php echo url('view/product/all_product.php'); ?>">Products</a>
            <span>/</span>
            <span><?php echo escape_html($product['product_title']); ?></span>
        </nav>

        <!-- Product Details -->
        <div class="product-detail">
            <div class="product-image-section">
                <?php 
                $image_url = ASSETS_URL . '/images/placeholder-product.svg';
                if (!empty($product['product_image'])): 
                    $image_path = ltrim($product['product_image'], '/');
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
                endif;
                ?>
                    <img src="<?php echo $image_url; ?>" 
                         alt="<?php echo escape_html($product['product_title']); ?>"
                         class="product-main-image"
                         onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
            </div>

            <div class="product-info-section">
                <h1 class="product-title"><?php echo escape_html($product['product_title']); ?></h1>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <strong>Product ID:</strong> #<?php echo $product['product_id']; ?>
                    </div>
                    <div class="meta-item">
                        <strong>Category:</strong> <?php echo escape_html($product['cat_name'] ?? 'N/A'); ?>
                    </div>
                    <div class="meta-item">
                        <strong>Brand:</strong> <?php echo escape_html($product['brand_name'] ?? 'N/A'); ?>
                    </div>
                </div>

                <div class="product-price">
                    <span class="price-label">Price:</span>
                    <span class="price-value">$<?php echo number_format($product['product_price'], 2); ?></span>
                </div>

                <div class="product-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(escape_html($product['product_desc'])); ?></p>
                </div>

                <?php if (!empty($product['product_keywords'])): ?>
                    <div class="product-keywords">
                        <h3>Keywords</h3>
                        <p><?php echo escape_html($product['product_keywords']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="product-actions">
                    <button class="btn btn-primary btn-lg add-to-cart-btn" 
                            data-product-id="<?php echo $product['product_id']; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <a href="<?php echo url('view/product/all_product.php'); ?>" 
                       class="btn btn-outline btn-lg">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Define BASE_URL for JavaScript
    if (typeof BASE_URL === 'undefined') {
        var BASE_URL = '<?php echo BASE_URL; ?>';
    }
</script>
<script src="<?php echo ASSETS_URL; ?>/js/product-display.js?v=<?php echo time(); ?>"></script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>


