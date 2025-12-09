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
    require_once __DIR__ . '/../../settings/core.php';
    require_once __DIR__ . '/../../controller/product_controller.php';
    require_once __DIR__ . '/../../controller/category_controller.php';
    require_once __DIR__ . '/../../controller/brand_controller.php';
} catch (Exception $e) {
    error_log("Error loading files in all_product.php: " . $e->getMessage());
    die("Error loading required files. Please check the error logs.");
}

// Set page variables
$page_title = 'All Products';
$page_description = 'Browse all available products';
$body_class = 'products-page';
$additional_css = ['products.css', 'wishlist.css'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Products per page
$offset = ($page - 1) * $limit;

// Get search and filter parameters
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$filter_cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
// Handle both brand_id (single) and brand_ids[] (array)
$filter_brand_id = 0;
$filter_brand_ids = [];
if (isset($_GET['brand_id']) && $_GET['brand_id'] > 0) {
    $filter_brand_id = (int)$_GET['brand_id'];
    $filter_brand_ids = [$filter_brand_id];
} elseif (isset($_GET['brand_ids']) && is_array($_GET['brand_ids'])) {
    $filter_brand_ids = array_map('intval', $_GET['brand_ids']);
    $filter_brand_ids = array_filter($filter_brand_ids, function($id) { return $id > 0; });
    if (!empty($filter_brand_ids)) {
        $filter_brand_id = $filter_brand_ids[0]; // For backward compatibility
    }
}
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Determine page title
$is_search = !empty($query) || $filter_cat_id > 0 || !empty($filter_brand_ids) || $max_price > 0;
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
        if (!empty($filter_brand_ids)) {
            $filters['brand_ids'] = $filter_brand_ids;
        } elseif ($filter_brand_id > 0) {
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

    <!-- Top Filter Dropdowns -->
    <div class="top-filters-row">
        <form method="GET" action="" class="top-filters-form" id="top-filters-form">
            <?php if (!empty($query)): ?>
                <input type="hidden" name="query" value="<?php echo escape_html($query); ?>">
            <?php endif; ?>
            <div class="top-filter-group">
                <label for="top-filter-category" class="top-filter-label">Filter by Category:</label>
                <select id="top-filter-category" name="cat_id" class="top-filter-select">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($filter_cat_id == $cat['cat_id']) ? 'selected' : ''; ?>>
                            <?php echo escape_html($cat['cat_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="top-filter-group">
                <label for="top-filter-brand" class="top-filter-label">Filter by Brand:</label>
                <select id="top-filter-brand" name="brand_id" class="top-filter-select" <?php echo ($filter_cat_id > 0) ? '' : 'disabled'; ?>>
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
        </form>
    </div>

    <!-- Filter Sidebar Overlay (Mobile) -->
    <div class="filter-sidebar-overlay" id="filter-sidebar-overlay"></div>

    <!-- Products Layout with Sidebar -->
    <div class="products-layout">
        <!-- Filter Sidebar -->
        <aside class="filter-sidebar" id="filter-sidebar">
            <div class="filter-sidebar-header">
                <h2 class="filter-sidebar-title">Filters</h2>
                <button type="button" class="filter-sidebar-close" id="filter-sidebar-close" aria-label="Close filters">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="GET" action="" class="filter-sidebar-form" id="filter-sidebar-form">
                <?php if (!empty($query)): ?>
                    <input type="hidden" name="query" value="<?php echo escape_html($query); ?>">
                <?php endif; ?>

                <!-- Categories Section -->
                <div class="filter-section">
                    <h3 class="filter-section-title">
                        <i class="fas fa-tags"></i>
                        Categories
                    </h3>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="cat_id" value="0" class="filter-radio" <?php echo ($filter_cat_id == 0) ? 'checked' : ''; ?>>
                            <span class="filter-option-label">All Products</span>
                        </label>
                        <?php foreach ($categories as $cat): ?>
                            <label class="filter-option">
                                <input type="radio" name="cat_id" value="<?php echo $cat['cat_id']; ?>" class="filter-radio" <?php echo ($filter_cat_id == $cat['cat_id']) ? 'checked' : ''; ?>>
                                <span class="filter-option-label"><?php echo escape_html($cat['cat_name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Artisan/Brand Section -->
                <div class="filter-section">
                    <h3 class="filter-section-title">
                        <i class="fas fa-user-tie"></i>
                        Artisan
                    </h3>
                    <div class="filter-options" id="artisan-options">
                        <?php foreach ($brands as $brand): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="brand_ids[]" value="<?php echo $brand['brand_id']; ?>" class="filter-checkbox" 
                                       <?php echo (is_array($filter_brand_id) && in_array($brand['brand_id'], $filter_brand_id)) || $filter_brand_id == $brand['brand_id'] ? 'checked' : ''; ?>>
                                <span class="filter-option-label"><?php echo escape_html($brand['brand_name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Range Section -->
                <div class="filter-section">
                    <h3 class="filter-section-title">
                        <i class="fas fa-dollar-sign"></i>
                        Price Range
                    </h3>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="price_range" value="all" class="filter-radio" <?php echo ($max_price == 0) ? 'checked' : ''; ?>>
                            <span class="filter-option-label">All Prices</span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="price_range" value="50" class="filter-radio" <?php echo ($max_price == 50) ? 'checked' : ''; ?>>
                            <span class="filter-option-label">Under ₵50</span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="price_range" value="100" class="filter-radio" <?php echo ($max_price == 100) ? 'checked' : ''; ?>>
                            <span class="filter-option-label">₵50 - ₵100</span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="price_range" value="200" class="filter-radio" <?php echo ($max_price == 200) ? 'checked' : ''; ?>>
                            <span class="filter-option-label">₵100 - ₵200</span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="price_range" value="200+" class="filter-radio" <?php echo ($max_price > 200) ? 'checked' : ''; ?>>
                            <span class="filter-option-label">Over ₵200</span>
                        </label>
                    </div>
                    <input type="hidden" name="max_price" id="max-price-input" value="<?php echo $max_price; ?>">
                </div>

                <!-- Size Section -->
                <div class="filter-section">
                    <h3 class="filter-section-title">
                        <i class="fas fa-ruler"></i>
                        Size
                    </h3>
                    <div class="size-buttons-grid">
                        <button type="button" class="size-button" data-size="XS">XS</button>
                        <button type="button" class="size-button" data-size="S">S</button>
                        <button type="button" class="size-button" data-size="M">M</button>
                        <button type="button" class="size-button" data-size="L">L</button>
                        <button type="button" class="size-button" data-size="XL">XL</button>
                        <button type="button" class="size-button" data-size="XXL">XXL</button>
                    </div>
                    <input type="hidden" name="sizes[]" id="selected-sizes" value="">
                </div>

                <!-- Colors Section -->
                <div class="filter-section">
                    <h3 class="filter-section-title">
                        <i class="fas fa-palette"></i>
                        Colors
                    </h3>
                    <div class="color-swatches-grid">
                        <button type="button" class="color-swatch" data-color="brown" style="background-color: #8B4513;" aria-label="Brown"></button>
                        <button type="button" class="color-swatch" data-color="terracotta" style="background-color: #C67D5C;" aria-label="Terracotta"></button>
                        <button type="button" class="color-swatch" data-color="beige" style="background-color: #F4EDE4;" aria-label="Beige"></button>
                        <button type="button" class="color-swatch" data-color="black" style="background-color: #000000;" aria-label="Black"></button>
                        <button type="button" class="color-swatch" data-color="white" style="background-color: #FFFFFF; border: 1px solid #ddd;" aria-label="White"></button>
                        <button type="button" class="color-swatch" data-color="gold" style="background-color: #FFD700;" aria-label="Gold"></button>
                        <button type="button" class="color-swatch" data-color="navy" style="background-color: #2C3E50;" aria-label="Navy"></button>
                        <button type="button" class="color-swatch" data-color="red" style="background-color: #E74C3C;" aria-label="Red"></button>
                        <button type="button" class="color-swatch" data-color="olive" style="background-color: #808000;" aria-label="Olive"></button>
                        <button type="button" class="color-swatch" data-color="orange" style="background-color: #FF6347;" aria-label="Orange"></button>
                    </div>
                    <input type="hidden" name="colors[]" id="selected-colors" value="">
                </div>

                <!-- Clear All Filters Button -->
                <div class="filter-section filter-section-last">
                    <button type="button" class="btn-clear-filters" id="clear-all-filters">
                        <i class="fas fa-times-circle"></i>
                        Clear All Filters
                    </button>
                </div>
            </form>
        </aside>

        <!-- Products Section -->
        <div class="products-section">
            <div class="products-header">
                <h2 class="products-title">Products</h2>
                <span class="products-count" id="products-count"><?php echo $total; ?> products</span>
            </div>

            <!-- Mobile Filter Toggle -->
            <button type="button" class="mobile-filter-toggle" id="mobile-filter-toggle" aria-label="Toggle filters">
                <i class="fas fa-filter"></i>
                <span>Filters</span>
            </button>

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
                            $image_url = get_image_url($product['product_image'] ?? '');
                            ?>
                                <img src="<?php echo $image_url; ?>" 
                                     alt="<?php echo escape_html($product['product_title']); ?>"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
                            
                            <!-- Wishlist Heart Icon -->
                            <button class="wishlist-btn" 
                                    data-product-id="<?php echo $product['product_id']; ?>" 
                                    aria-label="Add to wishlist"
                                    aria-pressed="false"
                                    title="Add to wishlist">
                                <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>
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
                    if (!empty($filter_brand_ids)) {
                        foreach ($filter_brand_ids as $bid) {
                            $pagination_params[] = 'brand_ids[]=' . $bid;
                        }
                    } elseif ($filter_brand_id > 0) {
                        $pagination_params[] = 'brand_id=' . $filter_brand_id;
                    }
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
    </div>
</div>

<script>
    // Define BASE_URL for JavaScript
    if (typeof BASE_URL === 'undefined') {
        var BASE_URL = '<?php echo BASE_URL; ?>';
    }
</script>
<script src="<?php echo ASSETS_URL; ?>/js/wishlist.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo ASSETS_URL; ?>/js/product-display.js?v=<?php echo time(); ?>"></script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>


