<?php
/**
 * Product Management Page
 */

require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../controller/product_controller.php';
require_once __DIR__ . '/../../controller/category_controller.php';
require_once __DIR__ . '/../../controller/brand_controller.php';

// Set page variables
$page_title = 'Product Management';
$page_description = 'Manage products for KenteKart.';
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

// Get categories for dropdown - for admin users, show ALL categories
try {
    $category_obj = new category_class();
    $categories = $category_obj->get_all_categories(1000, 0);
    
    if ($categories === false) {
        $categories = [];
    }
} catch (Exception $e) {
    error_log("Get categories error: " . $e->getMessage());
    $categories = [];
}

// Get brands for dropdown - for admin users, show ALL brands
try {
    $brand_obj = new brand_class();
    $brands = $brand_obj->get_all_brands(1000, 0);
    
    if ($brands === false) {
        $brands = [];
    }
    
} catch (Exception $e) {
    error_log("Get brands error: " . $e->getMessage());
    error_log("Get brands error trace: " . $e->getTraceAsString());
    $brands = [];
}

// Get all products
$all_products = [];
try {
    $product_obj = new product_class();
    $all_products = $product_obj->get_all_products(1000, 0);
    if ($all_products === false) {
        $all_products = [];
    }
} catch (Exception $e) {
    error_log("Get products error: " . $e->getMessage());
    $all_products = [];
}

// Group products by category and brand
$products_by_category = [];
if (!empty($categories)) {
    foreach ($categories as $cat) {
        $category_products = array_filter($all_products, function($product) use ($cat) {
            return isset($product['product_cat']) && $product['product_cat'] == $cat['cat_id'];
        });
        
        if (!empty($category_products)) {
            // Group products by brand within each category
            $products_by_brand = [];
            foreach ($category_products as $product) {
                $brand_id = $product['product_brand'] ?? null;
                if ($brand_id) {
                    if (!isset($products_by_brand[$brand_id])) {
                        $products_by_brand[$brand_id] = [
                            'brand_name' => $product['brand_name'] ?? 'Unknown Brand',
                            'products' => []
                        ];
                    }
                    $products_by_brand[$brand_id]['products'][] = $product;
                }
            }
            
            $products_by_category[$cat['cat_id']] = [
                'category' => $cat,
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
                        <label for="cat_id">Category: <span class="required">*</span></label>
                        <select id="cat_id" name="cat_id" required class="form-input">
                            <option value="">Select a category first</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['cat_id']; ?>">
                                        <?php echo escape_html($cat['cat_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No categories available</option>
                            <?php endif; ?>
                        </select>
                        <small class="form-help">Select a category first to filter available brands</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="brand_id">Brand: <span class="required">*</span></label>
                        <select id="brand_id" name="brand_id" required class="form-input" disabled>
                            <option value="">Select a category first</option>
                            <?php if (!empty($brands)): ?>
                                <?php 
                                $brands_rendered = 0;
                                foreach ($brands as $brand): 
                                    // Ensure cat_id exists and is not null
                                    $cat_id = isset($brand['cat_id']) && $brand['cat_id'] !== null && $brand['cat_id'] !== '' ? (string)$brand['cat_id'] : '';
                                    
                                    if (!empty($cat_id)): 
                                        $brands_rendered++;
                                ?>
                                        <option value="<?php echo $brand['brand_id']; ?>" data-cat-id="<?php echo htmlspecialchars($cat_id, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo escape_html($brand['brand_name']); ?> (<?php echo escape_html($brand['cat_name'] ?? ''); ?>)
                                        </option>
                                <?php 
                                    endif;
                                endforeach; 
                                
                                if ($brands_rendered === 0): ?>
                                    <option value="" disabled>No brands with categories available</option>
                                <?php endif; ?>
                            <?php else: ?>
                                <option value="" disabled>No brands available</option>
                            <?php endif; ?>
                        </select>
                        <small class="form-help" id="brand-help">Brands will be filtered based on the selected category</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">Product Title:</label>
                    <input type="text" id="title" name="title" placeholder="Enter product title" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="price">Product Price ($): <span class="required">*</span></label>
                    <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="desc">Product Description: <span class="required">*</span></label>
                    <textarea id="desc" name="desc" placeholder="Enter detailed product description" required class="form-input" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="keyword">Product Keywords: <span class="required">*</span></label>
                    <input type="text" id="keyword" name="keyword" placeholder="keyword1, keyword2, keyword3" required class="form-input">
                    <small class="form-help">Enter keywords separated by commas</small>
                </div>
                
                <!-- Image Upload Section -->
                <div class="form-group">
                    <label for="product_image">Product Images:</label>
                    <div class="file-upload-container">
                        <input type="file" id="product_image" name="product_image" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif,image/svg+xml" multiple class="file-input-hidden">
                        <div class="file-upload-area" id="file-upload-area">
                            <div class="file-upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p class="file-upload-text">Choose Files or Drag & Drop</p>
                                <p class="file-upload-hint">Select multiple images (JPG, PNG, WEBP, SVG) - Max 5MB each, up to 10 files</p>
                            </div>
                        </div>
                        <div class="file-count-display" id="file-count-display" style="display: none;">
                            <span id="file-count-text">0 files selected</span>
                        </div>
                    </div>
                    <small class="form-help">Upload multiple product images. Hold Ctrl/Cmd to select multiple files at once.</small>
                </div>
                
                <!-- File Preview Gallery -->
                <div id="file-preview-gallery" class="file-preview-gallery" style="display: none;">
                    <div class="preview-gallery-header">
                        <h4 class="preview-gallery-title">Selected Files</h4>
                        <button type="button" id="clear-all-files" class="btn-clear-all">Clear All</button>
                    </div>
                    <div class="preview-gallery-grid" id="preview-gallery-grid">
                        <!-- File previews will be inserted here -->
                    </div>
                </div>
                
                <!-- Legacy single image preview (hidden, kept for compatibility) -->
                <div id="image-preview" class="image-preview" style="display: none;">
                    <img id="preview-img" src="" alt="Image preview">
                    <div id="preview-info" class="preview-info"></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" id="submit-btn" class="btn btn-primary">Add Product</button>
                    <button type="button" id="cancel-btn" class="btn btn-outline">Cancel</button>
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
                                                            <?php echo escape_html($product['product_title'] ?? 'N/A'); ?>
                                                        </h6>
                                                        <div class="product-actions">
                                                            <button class="btn btn-sm btn-outline edit-product-btn" data-product-id="<?php echo $product['product_id']; ?>">
                                                                Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-product-btn" data-product-id="<?php echo $product['product_id']; ?>" data-product-title="<?php echo escape_html($product['product_title'] ?? 'N/A'); ?>">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (!empty($product['product_image'])): ?>
                                                        <div class="product-image">
                                                            <img src="<?php echo escape_html($product['product_image']); ?>" alt="<?php echo escape_html($product['product_title'] ?? ''); ?>" onerror="this.style.display='none'">
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="product-details">
                                                        <div class="product-price">
                                                            <span class="current-price">₵<?php echo number_format($product['product_price'] ?? 0, 2); ?></span>
                                                        </div>
                                                        
                                                        <?php if (!empty($product['product_desc'])): ?>
                                                            <p class="product-description"><?php echo escape_html(substr($product['product_desc'], 0, 100)) . (strlen($product['product_desc']) > 100 ? '...' : ''); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($product['product_keywords'])): ?>
                                                            <div class="product-keywords">
                                                                <small><strong>Keywords:</strong> <?php echo escape_html($product['product_keywords']); ?></small>
                                                            </div>
                                                        <?php endif; ?>
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
    <script>
        // Define BASE_URL for JavaScript if not already defined
        if (typeof BASE_URL === 'undefined') {
            var BASE_URL = '<?php echo BASE_URL; ?>';
        }
        
        // Immediate test to ensure dropdown works
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const catSelect = document.getElementById('cat_id');
                if (catSelect) {
                    // Force enable the dropdown
                    catSelect.disabled = false;
                    catSelect.style.pointerEvents = 'auto';
                    catSelect.style.zIndex = '9999';
                    catSelect.style.position = 'relative';
                    
                    // Remove any overlays that might be blocking
                    const overlays = document.querySelectorAll('.modal-overlay, #loading-overlay, .overlay');
                    overlays.forEach(function(overlay) {
                        if (overlay && overlay.style) {
                            overlay.style.display = 'none';
                        }
                    });
                    
                } else {
                    // Category dropdown not found
                }
                
                // Also force enable brand dropdown
                const brandSelect = document.getElementById('brand_id');
                if (brandSelect) {
                    brandSelect.disabled = false;
                    brandSelect.removeAttribute('readonly');
                    brandSelect.style.pointerEvents = 'auto';
                    brandSelect.style.zIndex = '9999';
                    brandSelect.style.position = 'relative';
                    brandSelect.style.cursor = 'pointer';
                }
            }, 50);
        });
    </script>
    <script src="<?php echo ASSETS_URL; ?>/js/products.js?v=<?php echo time(); ?>"></script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>
