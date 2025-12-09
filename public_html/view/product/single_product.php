<?php
/**
 * Single Product Page
 * 
 * Display full details for a selected product.
 */

require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../controller/product_controller.php';

// Set page variables
$page_title = 'Product Details';
$page_description = 'View product details';
$body_class = 'single-product-page';
$additional_css = ['products.css', 'wishlist.css'];

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

// Get similar products (same category, excluding current product)
$similar_products = [];
if (!empty($product['product_cat'])) {
    try {
        require_once __DIR__ . '/../../controller/product_controller.php';
        $all_similar = filter_products_by_category_ctr($product['product_cat'], 4, 0);
        // Filter out current product
        $similar_products = array_filter($all_similar, function($p) use ($product_id) {
            return $p['product_id'] != $product_id;
        });
        // Limit to 4 products
        $similar_products = array_slice($similar_products, 0, 4);
    } catch (Exception $e) {
        error_log("Error getting similar products: " . $e->getMessage());
        $similar_products = [];
    }
}

// Parse keywords into array
$keywords = [];
if (!empty($product['product_keywords'])) {
    $keywords = array_map('trim', explode(',', $product['product_keywords']));
}

// Set page title to product name
$page_title = escape_html($product['product_title']) . ' - Product Details';

// Include header
include __DIR__ . '/../templates/header.php';
?>

<!-- Back Link -->
<div class="product-page-back-link">
    <a href="<?php echo url('view/product/all_product.php'); ?>" class="back-to-dashboard">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<!-- Breadcrumb Navigation -->
<div class="product-page-breadcrumb">
    <nav class="breadcrumb-nav">
        <a href="<?php echo url('index.php'); ?>">Home</a>
        <span>/</span>
        <a href="<?php echo url('view/product/all_product.php'); ?>">Products</a>
        <span>/</span>
        <span class="breadcrumb-current"><?php echo escape_html($product['product_title']); ?></span>
    </nav>
</div>

<!-- Main Product Section -->
<div class="product-detail-page">
    <div class="product-detail-container">
        <!-- LEFT COLUMN - Product Images -->
        <div class="product-images-column">
            <div class="product-main-image-container">
                <?php 
                $image_url = get_image_url($product['product_image'] ?? '');
                ?>
                <!-- Product Badge (optional - can be dynamic based on product data) -->
                <div class="product-badge">NEW</div>
                
                <img src="<?php echo $image_url; ?>" 
                     alt="<?php echo escape_html($product['product_title']); ?>"
                     class="product-main-image"
                     id="product-main-image"
                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
            </div>
            
            <!-- Thumbnail Gallery -->
            <div class="product-thumbnails">
                <div class="thumbnail-item active" data-image="<?php echo $image_url; ?>">
                    <img src="<?php echo $image_url; ?>" alt="Thumbnail 1">
                </div>
                <!-- Additional thumbnails can be added here if product has multiple images -->
                <?php for ($i = 2; $i <= 4; $i++): ?>
                    <div class="thumbnail-item" data-image="<?php echo $image_url; ?>">
                        <img src="<?php echo $image_url; ?>" alt="Thumbnail <?php echo $i; ?>">
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN - Product Information -->
        <div class="product-info-column">
            <!-- Category Path -->
            <div class="product-category-path">
                Home / <?php echo escape_html($product['cat_name'] ?? 'Products'); ?>
            </div>

            <!-- Product Title -->
            <h1 class="product-detail-title"><?php echo escape_html($product['product_title']); ?></h1>

            <!-- Product ID -->
            <div class="product-detail-id">Product ID: #<?php echo $product['product_id']; ?></div>

            <!-- Product Meta Information -->
            <div class="product-detail-meta">
                <div class="meta-row">
                    <span class="meta-label">Category:</span>
                    <span class="meta-value"><?php echo escape_html($product['cat_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Brand:</span>
                    <span class="meta-value brand-value"><?php echo escape_html($product['brand_name'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <!-- Product Price -->
            <div class="product-detail-price">₵<?php echo number_format($product['product_price'], 2); ?></div>

            <!-- Product Description -->
            <div class="product-detail-description">
                <p><?php echo nl2br(escape_html($product['product_desc'])); ?></p>
            </div>

            <!-- Color Selection -->
            <div class="product-detail-colors">
                <h3 class="section-label">Color</h3>
                <div class="color-swatches">
                    <button type="button" class="color-swatch active" data-color="terracotta" style="background-color: #C67D5C;" aria-label="Terracotta"></button>
                    <button type="button" class="color-swatch" data-color="black" style="background-color: #000000;" aria-label="Black"></button>
                    <button type="button" class="color-swatch" data-color="gold" style="background-color: #FFD700;" aria-label="Gold"></button>
                    <button type="button" class="color-swatch" data-color="navy" style="background-color: #2C3E50;" aria-label="Navy"></button>
                    <button type="button" class="color-swatch" data-color="red" style="background-color: #E74C3C;" aria-label="Red"></button>
                    <button type="button" class="color-swatch" data-color="brown" style="background-color: #8B4513;" aria-label="Brown"></button>
                    <button type="button" class="color-swatch" data-color="white" style="background-color: #FFFFFF; border: 1px solid #ddd;" aria-label="White"></button>
                </div>
                <input type="hidden" id="selected-color" value="terracotta">
            </div>

            <!-- Size Selection -->
            <div class="product-detail-sizes">
                <h3 class="section-label">Size</h3>
                <div class="size-buttons">
                    <button type="button" class="size-btn" data-size="XS">XS</button>
                    <button type="button" class="size-btn" data-size="S">S</button>
                    <button type="button" class="size-btn active" data-size="M">M</button>
                    <button type="button" class="size-btn" data-size="L">L</button>
                    <button type="button" class="size-btn" data-size="XL">XL</button>
                    <button type="button" class="size-btn" data-size="XXL">XXL</button>
                </div>
                <input type="hidden" id="selected-size" value="M">
            </div>

            <!-- Quantity Selection -->
            <div class="product-detail-quantity">
                <h3 class="section-label">Quantity</h3>
                <div class="quantity-controls">
                    <button type="button" class="quantity-btn decrease" id="quantity-decrease">-</button>
                    <input type="number" class="quantity-input" id="quantity-input" value="1" min="1" max="99">
                    <button type="button" class="quantity-btn increase" id="quantity-increase">+</button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="product-detail-actions">
                <button type="button" class="btn-add-to-cart" id="add-to-cart-btn" data-product-id="<?php echo $product['product_id']; ?>">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
                <button type="button" class="btn-wishlist" id="add-to-wishlist-btn" data-product-id="<?php echo $product['product_id']; ?>" aria-label="Add to wishlist" aria-pressed="false">
                    <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span class="btn-text">Add to Wishlist</span>
                </button>
            </div>

            <!-- Keywords Section -->
            <?php if (!empty($keywords)): ?>
            <div class="product-detail-keywords">
                <h3 class="section-label">Keywords</h3>
                <div class="keyword-tags">
                    <?php foreach ($keywords as $keyword): ?>
                        <span class="keyword-tag"><?php echo escape_html(trim($keyword)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Similar Products Section -->
    <?php if (!empty($similar_products)): ?>
    <div class="similar-products-section">
        <div class="similar-products-header">
            <h2 class="similar-products-title">
                Similar <span class="title-accent">Products</span>
            </h2>
            <p class="similar-products-subtitle">You might also like these handcrafted items</p>
        </div>
        
        <div class="similar-products-grid">
            <?php foreach ($similar_products as $similar): ?>
                <?php 
                $similar_image_url = ASSETS_URL . '/images/placeholder-product.svg';
                $similar_image_url = get_image_url($similar['product_image'] ?? '');
                ?>
                <a href="<?php echo url('view/product/single_product.php?id=' . $similar['product_id']); ?>" class="similar-product-card">
                    <div class="similar-product-image">
                        <img src="<?php echo $similar_image_url; ?>" 
                             alt="<?php echo escape_html($similar['product_title']); ?>"
                             onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
                    </div>
                    <div class="similar-product-info">
                        <div class="similar-product-brand"><?php echo escape_html($similar['brand_name'] ?? 'Brand'); ?></div>
                        <h3 class="similar-product-name"><?php echo escape_html($similar['product_title']); ?></h3>
                        <div class="similar-product-price">₵<?php echo number_format($similar['product_price'], 2); ?></div>
                        <div class="similar-product-colors">
                            <span class="color-dot" style="background-color: #C67D5C;"></span>
                            <span class="color-dot" style="background-color: #000000;"></span>
                            <span class="color-dot" style="background-color: #FFD700;"></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer Section -->
    <div class="product-page-footer">
        <p>Est. 2024 — Connecting Africa with the World</p>
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


