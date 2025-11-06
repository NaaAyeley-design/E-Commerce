<?php
/**
 * All Products Page
 * 
 * Display all products with pagination and filters.
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/product_controller.php';
require_once __DIR__ . '/../../../controller/category_controller.php';
require_once __DIR__ . '/../../../controller/brand_controller.php';

// Set page variables
$page_title = 'All Products';
$page_description = 'Browse all available products';
$body_class = 'products-page';
$additional_css = ['products-display.css'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Products per page
$offset = ($page - 1) * $limit;

// Get filter parameters
$filter_cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$filter_brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;

// Get products
$products = [];
$total = 0;
$total_pages = 0;

if ($filter_cat_id > 0) {
    $products = filter_products_by_category_ctr($filter_cat_id, $limit, $offset);
    $filters = ['cat_id' => $filter_cat_id];
    $total = count_filtered_products_ctr($filters);
} elseif ($filter_brand_id > 0) {
    $products = filter_products_by_brand_ctr($filter_brand_id, $limit, $offset);
    $filters = ['brand_id' => $filter_brand_id];
    $total = count_filtered_products_ctr($filters);
} else {
    $products = view_all_products_ctr($limit, $offset);
    $total = get_product_count_ctr();
}

$total_pages = ceil($total / $limit);

// Get categories and brands for filters
$category_obj = new category_class();
$categories = $category_obj->get_all_categories(1000, 0) ?: [];

$brand_obj = new brand_class();
$brands = $brand_obj->get_all_brands(1000, 0) ?: [];

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
    
    <div class="page-header">
        <h1>All Products</h1>
        <p class="subtitle">Browse our complete product catalog</p>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-container">
            <div class="filter-group">
                <label for="filter-category">Filter by Category:</label>
                <select id="filter-category" class="form-select">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($filter_cat_id == $cat['cat_id']) ? 'selected' : ''; ?>>
                            <?php echo escape_html($cat['cat_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter-brand">Filter by Brand:</label>
                <select id="filter-brand" class="form-select">
                    <option value="0">All Brands</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['brand_id']; ?>" <?php echo ($filter_brand_id == $brand['brand_id']) ? 'selected' : ''; ?>>
                            <?php echo escape_html($brand['brand_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button id="clear-filters" class="btn btn-outline">Clear Filters</button>
        </div>
    </div>

    <!-- Products Grid -->
    <div id="products-container">
        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>No products found.</p>
            </div>
        <?php else: ?>
            <div class="products-grid" id="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                        <div class="product-image">
                            <?php if (!empty($product['product_image'])): ?>
                                <img src="<?php echo BASE_URL . '/' . escape_html($product['product_image']); ?>" 
                                     alt="<?php echo escape_html($product['product_title']); ?>"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.png'">
                            <?php else: ?>
                                <img src="<?php echo ASSETS_URL; ?>/images/placeholder-product.png" 
                                     alt="Product Image">
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="<?php echo url('view/product/single_product.php?id=' . $product['product_id']); ?>">
                                    <?php echo escape_html($product['product_title']); ?>
                                </a>
                            </h3>
                            <div class="product-meta">
                                <span class="product-category"><?php echo escape_html($product['cat_name'] ?? 'N/A'); ?></span>
                                <span class="product-brand"><?php echo escape_html($product['brand_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="product-price">$<?php echo number_format($product['product_price'], 2); ?></div>
                            <div class="product-actions">
                                <a href="<?php echo url('view/product/single_product.php?id=' . $product['product_id']); ?>" 
                                   class="btn btn-primary btn-sm">View Details</a>
                                <button class="btn btn-outline btn-sm add-to-cart-btn" 
                                        data-product-id="<?php echo $product['product_id']; ?>">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $filter_cat_id ? '&cat_id=' . $filter_cat_id : ''; ?><?php echo $filter_brand_id ? '&brand_id=' . $filter_brand_id : ''; ?>" 
                           class="btn btn-outline">Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $filter_cat_id ? '&cat_id=' . $filter_cat_id : ''; ?><?php echo $filter_brand_id ? '&brand_id=' . $filter_brand_id : ''; ?>" 
                           class="btn btn-outline">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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


