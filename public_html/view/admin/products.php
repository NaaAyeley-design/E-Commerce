<?php
/**
 * Product Management Page
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/product_controller.php';
require_once __DIR__ . '/../../../controller/category_controller.php';
require_once __DIR__ . '/../../../controller/brand_controller.php';

// Set page variables
$page_title = 'Product Management';
$page_description = 'Manage products for your e-commerce platform.';
$body_class = 'products-page';
$additional_css = ['products.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

if (!is_admin()) {
    header('Location: ' . BASE_URL . '/view/user/dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get categories for dropdown
$categories = get_categories_ctr($user_id);
if (is_string($categories)) {
    $error = "Error loading categories: " . $categories;
    $categories = [];
}

// Get brands for dropdown
$brands = fetch_brands_ctr($user_id);
if (is_string($brands)) {
    $error = "Error loading brands: " . $brands;
    $brands = [];
}

// Get products grouped by category and brand
$products_by_category = [];
if (!empty($categories)) {
    foreach ($categories as $category) {
        $category_products = get_products_by_category_ctr($user_id, $category['cat_id']);
        if (is_array($category_products)) {
            // Group products by brand within each category
            $products_by_brand = [];
            foreach ($category_products as $product) {
                $brand_id = $product['brand_id'];
                if (!isset($products_by_brand[$brand_id])) {
                    $products_by_brand[$brand_id] = [
                        'brand_name' => $product['brand_name'],
                        'products' => []
                    ];
                }
                $products_by_brand[$brand_id]['products'][] = $product;
            }
            
            $products_by_category[$category['cat_id']] = [
                'category' => $category,
                'brands' => $products_by_brand
            ];
        }
    }
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container center-content">
        <h1>Product Management</h1>
        
        <!-- Messages -->
        <div id="message-container">
            <?php if ($message): ?>
                <div class="message message-success" id="success-message"><?php echo escape_html($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message message-error" id="error-message"><?php echo escape_html($error); ?></div>
            <?php endif; ?>
        </div>

        <!-- Add/Edit Product Form -->
        <div class="card">
            <h3 id="form-title">Add New Product</h3>
            <form id="product-form" method="post" enctype="multipart/form-data">
                <input type="hidden" id="product_id" name="product_id" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cat_id">Category:</label>
                        <select id="cat_id" name="cat_id" required class="form-input">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['cat_id']; ?>">
                                    <?php echo escape_html($category['cat_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="brand_id">Brand:</label>
                        <select id="brand_id" name="brand_id" required class="form-input">
                            <option value="">Select a brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['brand_id']; ?>" data-cat-id="<?php echo $brand['cat_id']; ?>">
                                    <?php echo escape_html($brand['brand_name']); ?> (<?php echo escape_html($brand['cat_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">Product Title:</label>
                    <input type="text" id="title" name="title" placeholder="Enter product title" required class="form-input">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="compare_price">Compare Price ($):</label>
                        <input type="number" id="compare_price" name="compare_price" step="0.01" min="0" placeholder="0.00" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="cost_price">Cost Price ($):</label>
                        <input type="number" id="cost_price" name="cost_price" step="0.01" min="0" placeholder="0.00" class="form-input">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sku">SKU:</label>
                        <input type="text" id="sku" name="sku" placeholder="Product SKU" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity:</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" placeholder="0" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">Weight (lbs):</label>
                        <input type="number" id="weight" name="weight" step="0.01" min="0" placeholder="0.00" class="form-input">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="desc">Product Description:</label>
                    <textarea id="desc" name="desc" placeholder="Enter detailed product description" required class="form-input" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="keyword">Keywords (comma-separated):</label>
                    <input type="text" id="keyword" name="keyword" placeholder="keyword1, keyword2, keyword3" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="dimensions">Dimensions:</label>
                    <input type="text" id="dimensions" name="dimensions" placeholder="e.g., 12x8x4 inches" class="form-input">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="meta_title">Meta Title:</label>
                        <input type="text" id="meta_title" name="meta_title" placeholder="SEO meta title" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description:</label>
                        <input type="text" id="meta_description" name="meta_description" placeholder="SEO meta description" class="form-input">
                    </div>
                </div>
                
                <!-- Image Upload Section -->
                <div class="form-group">
                    <label for="product_image">Product Images:</label>
                    <input type="file" id="product_image" name="product_image" accept="image/*" multiple class="form-input">
                    <small class="form-help">Upload multiple JPEG, PNG, GIF, or WebP images (max 5MB each). Hold Ctrl/Cmd to select multiple files.</small>
                </div>
                
                <div id="image-preview" class="image-preview" style="display: none;">
                    <img id="preview-img" src="" alt="Image preview">
                    <div id="preview-info" class="preview-info"></div>
                    <button type="button" id="remove-image" class="btn btn-sm btn-danger">Remove Images</button>
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="submit-btn" class="btn btn-primary">Add Product</button>
                    <button type="button" id="cancel-btn" class="btn btn-outline" style="display: none;">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Products by Category and Brand -->
        <div class="card">
            <h3>Your Products</h3>
            
            <?php if (empty($products_by_category)): ?>
                <p>No products found. Add your first product above.</p>
            <?php else: ?>
                <?php foreach ($products_by_category as $category_data): ?>
                    <div class="category-section">
                        <h4 class="category-title">
                            <?php echo escape_html($category_data['category']['cat_name']); ?>
                            <span class="product-count">(<?php echo array_sum(array_map(function($brand) { return count($brand['products']); }, $category_data['brands'])); ?> products)</span>
                        </h4>
                        
                        <?php if (empty($category_data['brands'])): ?>
                            <p class="no-products">No products in this category yet.</p>
                        <?php else: ?>
                            <?php foreach ($category_data['brands'] as $brand_id => $brand_data): ?>
                                <div class="brand-section">
                                    <h5 class="brand-title">
                                        <?php echo escape_html($brand_data['brand_name']); ?>
                                        <span class="brand-product-count">(<?php echo count($brand_data['products']); ?> products)</span>
                                    </h5>
                                    
                                    <?php if (empty($brand_data['products'])): ?>
                                        <p class="no-products">No products for this brand yet.</p>
                                    <?php else: ?>
                                        <div class="products-grid">
                                            <?php foreach ($brand_data['products'] as $product): ?>
                                                <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                                                    <div class="product-header">
                                                        <h6 class="product-title" id="product-title-<?php echo $product['product_id']; ?>">
                                                            <?php echo escape_html($product['product_name']); ?>
                                                        </h6>
                                                        <div class="product-actions">
                                                            <button class="btn btn-sm btn-outline edit-product-btn" data-product-id="<?php echo $product['product_id']; ?>">
                                                                Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-product-btn" data-product-id="<?php echo $product['product_id']; ?>" data-product-title="<?php echo escape_html($product['product_name']); ?>">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="product-details">
                                                        <div class="product-price">
                                                            <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                                                            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                                                <span class="compare-price">$<?php echo number_format($product['compare_price'], 2); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="product-meta">
                                                            <small class="product-sku">SKU: <?php echo escape_html($product['sku'] ?: 'N/A'); ?></small>
                                                            <small class="product-stock">Stock: <?php echo $product['stock_quantity']; ?></small>
                                                        </div>
                                                        
                                                        <?php if (!empty($product['product_description'])): ?>
                                                            <p class="product-description"><?php echo escape_html(substr($product['product_description'], 0, 100)) . (strlen($product['product_description']) > 100 ? '...' : ''); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <div class="product-status">
                                                            <span class="status-badge <?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                            <?php if ($product['is_featured']): ?>
                                                                <span class="status-badge featured">Featured</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="product-date">
                                                            <small>Created: <?php echo date('M j, Y', strtotime($product['created_at'])); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>/js/products.js"></script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>
