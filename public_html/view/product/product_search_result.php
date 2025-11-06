<?php
/**
 * Product Search Results Page
 * 
 * Display search results with pagination and filter refinement.
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/product_controller.php';
require_once __DIR__ . '/../../../controller/category_controller.php';
require_once __DIR__ . '/../../../controller/brand_controller.php';

// Set page variables
$page_title = 'Search Results';
$page_description = 'Product search results';
$body_class = 'search-results-page';
$additional_css = ['products-display.css'];

// Get search parameters
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Products per page
$offset = ($page - 1) * $limit;

// Get filter parameters
$filter_cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$filter_brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Get products
$products = [];
$total = 0;
$total_pages = 0;

if (!empty($query) || $filter_cat_id > 0 || $filter_brand_id > 0 || $max_price > 0) {
    $filters = [];
    
    if (!empty($query)) {
        $filters['query'] = $query;
    }
    
    if ($filter_cat_id > 0) {
        $filters['cat_id'] = $filter_cat_id;
    }
    
    if ($filter_brand_id > 0) {
        $filters['brand_id'] = $filter_brand_id;
    }
    
    if ($max_price > 0) {
        $filters['max_price'] = $max_price;
    }
    
    $products = composite_search_ctr($filters, $limit, $offset);
    $total = count_filtered_products_ctr($filters);
    $total_pages = ceil($total / $limit);
} else {
    // If no search query, redirect to all products
    header('Location: ' . BASE_URL . '/view/product/all_product.php');
    exit;
}

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
        <h1>Search Results</h1>
        <?php if (!empty($query)): ?>
            <p class="subtitle">Searching for: "<strong><?php echo escape_html($query); ?></strong>"</p>
        <?php endif; ?>
        <?php if ($total > 0): ?>
            <p class="result-count">Found <?php echo $total; ?> product(s)</p>
        <?php endif; ?>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <h3>Refine Your Search</h3>
        <form method="GET" action="" class="filter-form" id="search-filter-form">
            <input type="hidden" name="query" value="<?php echo escape_html($query); ?>">
            
            <div class="filters-container">
                <div class="filter-group">
                    <label for="filter-category">Category:</label>
                    <select id="filter-category" name="cat_id" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($filter_cat_id == $cat['cat_id']) ? 'selected' : ''; ?>>
                                <?php echo escape_html($cat['cat_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter-brand">Brand:</label>
                    <select id="filter-brand" name="brand_id" class="form-select">
                        <option value="0">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>" <?php echo ($filter_brand_id == $brand['brand_id']) ? 'selected' : ''; ?>>
                                <?php echo escape_html($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="max-price">Max Price:</label>
                    <input type="number" id="max-price" name="max_price" 
                           value="<?php echo $max_price > 0 ? $max_price : ''; ?>" 
                           class="form-input" placeholder="e.g., 100" min="0" step="0.01">
                </div>

                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="?query=<?php echo urlencode($query); ?>" class="btn btn-outline">Clear Filters</a>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <div id="products-container">
        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>No products found matching your search criteria.</p>
                <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-primary">Browse All Products</a>
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
                        <a href="?query=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?><?php echo $filter_cat_id ? '&cat_id=' . $filter_cat_id : ''; ?><?php echo $filter_brand_id ? '&brand_id=' . $filter_brand_id : ''; ?><?php echo $max_price > 0 ? '&max_price=' . $max_price : ''; ?>" 
                           class="btn btn-outline">Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?query=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?><?php echo $filter_cat_id ? '&cat_id=' . $filter_cat_id : ''; ?><?php echo $filter_brand_id ? '&brand_id=' . $filter_brand_id : ''; ?><?php echo $max_price > 0 ? '&max_price=' . $max_price : ''; ?>" 
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


