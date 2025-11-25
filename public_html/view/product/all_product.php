<?php
/**
 * All Products Page
 * 
 * Display all products with pagination and filters.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

try {
    require_once __DIR__ . '/../../../settings/core.php';
    require_once __DIR__ . '/../../../controller/product_controller.php';
    require_once __DIR__ . '/../../../controller/category_controller.php';
    require_once __DIR__ . '/../../../controller/brand_controller.php';
} catch (Exception $e) {
    error_log("Error loading files in all_product.php: " . $e->getMessage());
    die("Error loading required files. Please check the error logs.");
}

// Set page variables
$page_title = 'All Products';
$page_description = 'Browse all available products';
$body_class = 'products-page';
$additional_css = ['products.css'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Products per page
$offset = ($page - 1) * $limit;

// Get search and filter parameters
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$filter_cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$filter_brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Determine page title
$is_search = !empty($query) || $filter_cat_id > 0 || $filter_brand_id > 0 || $max_price > 0;
$page_title = $is_search ? 'Search Results' : 'All Products';

// Get products
$products = [];
$total = 0;
$total_pages = 0;

try {
    // Use composite search if we have any filters or search query
    if ($is_search) {
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
        if (!is_array($products)) {
            $products = [];
        }
        $total = count_filtered_products_ctr($filters);
        if (!is_numeric($total)) {
            $total = 0;
        }
    } else {
        $products = view_all_products_ctr($limit, $offset);
        if (!is_array($products)) {
            $products = [];
        }
        $total = get_product_count_ctr();
        if (!is_numeric($total)) {
            $total = 0;
        }
    }

    $total_pages = $total > 0 ? ceil($total / $limit) : 0;
} catch (Exception $e) {
    error_log("Error getting products in all_product.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $products = [];
    $total = 0;
    $total_pages = 0;
}

// Get categories and brands for filters
try {
    $category_obj = new category_class();
    $categories = $category_obj->get_all_categories(1000, 0);
    if (!is_array($categories)) {
        $categories = [];
    }
} catch (Exception $e) {
    error_log("Error getting categories: " . $e->getMessage());
    $categories = [];
}

try {
    $brand_obj = new brand_class();
    $brands = $brand_obj->get_all_brands(1000, 0);
    if (!is_array($brands)) {
        $brands = [];
    }
} catch (Exception $e) {
    error_log("Error getting brands: " . $e->getMessage());
    $brands = [];
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
    
    <div class="page-header">
        <h1><?php echo $is_search ? 'Search Results' : 'All Products'; ?></h1>
        <?php if (!empty($query)): ?>
            <p class="subtitle">Searching for: "<strong><?php echo escape_html($query); ?></strong>"</p>
        <?php elseif ($is_search): ?>
            <p class="subtitle">Filtered products</p>
        <?php else: ?>
            <p class="subtitle">Browse our complete product catalog</p>
        <?php endif; ?>
        <?php if ($is_search && $total > 0): ?>
            <p class="result-count">Found <?php echo $total; ?> product(s)</p>
        <?php endif; ?>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <?php if ($is_search): ?>
            <h3>Refine Your Search</h3>
        <?php endif; ?>
        <form method="GET" action="" class="filter-form" id="search-filter-form">
            <?php if (!empty($query)): ?>
                <input type="hidden" name="query" value="<?php echo escape_html($query); ?>">
            <?php endif; ?>
            <div class="filters-container">
                <div class="filter-group">
                    <label for="filter-category"><?php echo $is_search ? 'Category:' : 'Filter by Category:'; ?></label>
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
                    <label for="filter-brand"><?php echo $is_search ? 'Brand:' : 'Filter by Brand:'; ?></label>
                    <select id="filter-brand" name="brand_id" class="form-select" <?php echo ($filter_cat_id > 0) ? '' : 'disabled'; ?>>
                        <option value="0"><?php echo ($filter_cat_id > 0) ? 'All Brands' : 'Select a category first'; ?></option>
                        <?php if ($filter_cat_id > 0): ?>
                            <?php 
                            // Get brands for selected category
                            $brands_for_category = [];
                            if ($filter_cat_id > 0) {
                                try {
                                    $user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
                                    if ($user_id) {
                                        $brands_result = get_brands_by_category_ctr($user_id, $filter_cat_id);
                                        if (is_array($brands_result)) {
                                            $brands_for_category = $brands_result;
                                        } else {
                                            $brands_for_category = [];
                                        }
                                    }
                                } catch (Exception $e) {
                                    error_log("Error getting brands by category: " . $e->getMessage());
                                    $brands_for_category = [];
                                }
                            }
                            ?>
                            <?php foreach ($brands_for_category as $brand): ?>
                                <option value="<?php echo $brand['brand_id']; ?>" <?php echo ($filter_brand_id == $brand['brand_id']) ? 'selected' : ''; ?>>
                                    <?php echo escape_html($brand['brand_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if ($is_search): ?>
                <div class="filter-group">
                    <label for="max-price">Max Price:</label>
                    <input type="number" id="max-price" name="max_price" 
                           value="<?php echo $max_price > 0 ? $max_price : ''; ?>" 
                           class="form-input" placeholder="e.g., 100" min="0" step="0.01">
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary"><?php echo $is_search ? 'Apply Filters' : 'Filter'; ?></button>
                <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-outline">Clear Filters</a>
            </div>
        </form>
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
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
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
                            <div class="product-price">₵<?php echo number_format($product['product_price'], 2); ?></div>
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
                    <?php
                    $pagination_params = [];
                    if (!empty($query)) $pagination_params[] = 'query=' . urlencode($query);
                    if ($filter_cat_id > 0) $pagination_params[] = 'cat_id=' . $filter_cat_id;
                    if ($filter_brand_id > 0) $pagination_params[] = 'brand_id=' . $filter_brand_id;
                    if ($max_price > 0) $pagination_params[] = 'max_price=' . $max_price;
                    $param_string = !empty($pagination_params) ? '&' . implode('&', $pagination_params) : '';
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $param_string; ?>" 
                           class="btn btn-outline">Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $param_string; ?>" 
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


