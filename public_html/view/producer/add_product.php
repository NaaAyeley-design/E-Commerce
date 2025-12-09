<?php
/**
 * Add New Product Page
 * 
 * Allows producers to add new products to their store
 */

// Suppress error reporting
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../class/category_class.php';
require_once __DIR__ . '/../../../class/brand_class.php';
require_once __DIR__ . '/../../../class/product_class.php';
require_once __DIR__ . '/../../../class/user_class.php';

// Set page variables
$page_title = 'Add New Product - Producer Dashboard';
$page_description = 'Add a new product to your KenteKart store';
$body_class = 'dashboard-page producer-dashboard';
$additional_css = ['dashboard-metrics.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=login_required');
    exit;
}

// Get current user data
$user = new user_class();
$producer = $user->get_customer_by_id($_SESSION['user_id']);
if (!$producer) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=session_expired');
    exit;
}

// Check user role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 3) {
    if ($_SESSION['user_role'] == 1) {
        header('Location: ' . BASE_URL . '/view/admin/dashboard.php');
        exit;
    } elseif ($_SESSION['user_role'] == 2) {
        header('Location: ' . BASE_URL . '/view/user/dashboard.php');
        exit;
    } else {
        header('Location: ' . BASE_URL . '/view/user/login.php?error=access_denied');
        exit;
    }
}

$producer_id = $_SESSION['user_id'];
$business_name = $producer['business_name'] ?? $producer['customer_name'] ?? 'My Brand';

// Get categories and brands for this producer
$category_class = new category_class();
$brand_class = new brand_class();

// First try to get categories created by this producer
$categories = $category_class->get_categories_by_user($producer_id, 1000, 0);

// If producer has no categories, get all available categories (so they can still create products)
if (empty($categories)) {
    $categories = $category_class->get_all_categories(1000, 0);
}

$all_brands = $brand_class->get_brands_by_user($producer_id, 1000, 0);

// Group brands by category for easier selection
$brands_by_category = [];
foreach ($all_brands as $brand) {
    $cat_id = $brand['cat_id'];
    if (!isset($brands_by_category[$cat_id])) {
        $brands_by_category[$cat_id] = [];
    }
    $brands_by_category[$cat_id][] = $brand;
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<style>
/* Producer Dashboard Sidebar Styles */
.producer-sidebar {
    width: 260px;
    background: var(--white);
    border-right: 1px solid var(--warm-beige);
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    z-index: 100;
}

.producer-sidebar-header {
    padding: 30px 20px;
    border-bottom: 1px solid var(--warm-beige);
}

.producer-sidebar-header h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 5px 0;
}

.producer-sidebar-header p {
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    color: var(--text-light);
    margin: 0;
}

.producer-sidebar-nav {
    padding: 20px 0;
}

.producer-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: var(--text-dark);
    text-decoration: none;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.producer-nav-item:hover {
    background: var(--warm-beige);
    color: var(--terracotta);
}

.producer-nav-item.active {
    background: var(--warm-beige);
    color: var(--terracotta);
    border-left-color: var(--terracotta);
    font-weight: 500;
}

.producer-nav-item i {
    width: 20px;
    text-align: center;
}

.producer-main-content {
    margin-left: 0;
    padding: 0;
    width: 100%;
    min-height: 100vh;
    background: #F9F7F4;
}

/* Form Styles */
.product-form-container {
    max-width: 1000px;
    margin: 0 auto;
    background: var(--white);
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--warm-beige);
}

.form-section:last-child {
    border-bottom: none;
}

.section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: var(--terracotta);
    font-size: 1.25rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.form-group label .required {
    color: #EF4444;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E8DDD0;
    border-radius: 6px;
    font-family: 'Spectral', serif;
    font-size: 0.95rem;
    color: var(--text-dark);
    background: var(--white);
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #D2691E;
    box-shadow: 0 0 0 3px rgba(210, 105, 30, 0.1);
}

.form-help {
    font-family: 'Spectral', serif;
    font-size: 0.75rem;
    color: var(--text-light);
    margin-top: 5px;
    display: block;
}

/* Image Upload */
.image-upload-area {
    border: 2px dashed #E8DDD0;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    background: #F9F7F4;
    transition: all 0.3s ease;
    cursor: pointer;
}

.image-upload-area:hover {
    border-color: #D2691E;
    background: #FFF9F0;
}

.image-upload-area.dragover {
    border-color: #D2691E;
    background: #FFF9F0;
    box-shadow: 0 0 0 4px rgba(210, 105, 30, 0.1);
}

.image-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.image-preview-item {
    position: relative;
    border: 2px solid #E8DDD0;
    border-radius: 6px;
    overflow: hidden;
    aspect-ratio: 1;
}

.image-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview-item .remove-image {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 24px;
    height: 24px;
    background: rgba(239, 68, 68, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.image-preview-item .primary-badge {
    position: absolute;
    bottom: 5px;
    left: 5px;
    background: #D2691E;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-family: 'Spectral', serif;
}

/* Variations */
.variations-section {
    background: #F9F7F4;
    padding: 20px;
    border-radius: 8px;
    margin-top: 15px;
}

.variation-item {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
    padding: 10px;
    background: white;
    border-radius: 4px;
}

/* Buttons */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid var(--warm-beige);
}

.btn-primary {
    padding: 14px 32px;
    background: linear-gradient(135deg, #D2691E 0%, #B8621E 100%);
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-family: 'Spectral', serif;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(210, 105, 30, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #B8621E 0%, #A0522D 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(210, 105, 30, 0.4);
}

.btn-secondary {
    padding: 14px 32px;
    background: #FFFFFF;
    color: #D2691E;
    border: 2px solid #D2691E;
    border-radius: 8px;
    font-family: 'Spectral', serif;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #FFF9F0;
}

/* Responsive */
@media (max-width: 1024px) {
    .producer-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .producer-sidebar.open {
        transform: translateX(0);
    }
    
    .producer-main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="kentekart-dashboard">
    
    <!-- SIDEBAR NAVIGATION -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>Producer Hub</h2>
            <p>Manage your business</p>
        </div>

        <nav class="sidebar-nav">
            <a href="<?php echo url('view/producer/dashboard.php'); ?>" class="nav-item">
                <i class="fas fa-th"></i>
                <span>Overview</span>
            </a>
            <a href="<?php echo url('view/producer/products.php'); ?>" class="nav-item active">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
            <a href="<?php echo url('view/producer/orders.php'); ?>" class="nav-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
            <a href="<?php echo url('view/producer/earnings.php'); ?>" class="nav-item">
                <i class="fas fa-dollar-sign"></i>
                <span>Earnings</span>
            </a>
            <a href="<?php echo url('view/producer/analytics.php'); ?>" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
            <a href="<?php echo url('view/producer/profile_settings.php'); ?>" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="<?php echo url('view/producer/academy.php'); ?>" class="nav-item">
                <i class="fas fa-graduation-cap"></i>
                <span>Academy</span>
            </a>
            <a href="<?php echo url('view/producer/support.php'); ?>" class="nav-item">
                <i class="fas fa-headset"></i>
                <span>Support</span>
            </a>
        </nav>

        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($producer['customer_name'] ?? 'P', 0, 1)); ?>
            </div>
            <div class="user-info">
                <p class="user-name"><?php echo escape_html($business_name); ?></p>
                <p class="user-since">Producer</p>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        <div class="producer-main-content">
            <div class="product-form-container">
                
                <!-- Page Header -->
                <div style="margin-bottom: 30px;">
                    <a href="<?php echo url('view/producer/products.php'); ?>" style="color: #D2691E; text-decoration: none; font-family: 'Spectral', serif; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                    <h1 style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 600; color: var(--text-dark); margin: 0;">
                        Add New Product
                    </h1>
                    <p style="font-family: 'Spectral', serif; font-size: 0.95rem; color: var(--text-light); margin: 10px 0 0 0;">
                        Create a new product listing for your store
                    </p>
                </div>

                <!-- Progress Steps -->
                <div class="progress-steps" style="display: flex; justify-content: center; gap: 15px; margin-bottom: 40px;">
                    <div class="progress-step active" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #D2691E; background: #D2691E; color: white; font-weight: 600;">1</div>
                    <div class="progress-step" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #E8DDD0; background: #FFF9F0; color: #D2691E; font-weight: 600;">2</div>
                    <div class="progress-step" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #E8DDD0; background: #FFF9F0; color: #D2691E; font-weight: 600;">3</div>
                </div>

                <form id="addProductForm" method="post" action="<?php echo url('actions/producer/add_product_action.php'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <!-- Section A: Basic Information -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="product_name">
                                    Product Name <span class="required">*</span>
                                </label>
                                <input type="text" id="product_name" name="product_name" class="form-input" placeholder="Enter product name" required maxlength="100">
                                <span class="form-help">Maximum 100 characters</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">
                                    Category <span class="required">*</span>
                                </label>
                                <select id="category" name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['cat_id']; ?>"><?php echo escape_html($cat['cat_name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($categories)): ?>
                                    <span class="form-help" style="color: #EF4444;">
                                        <i class="fas fa-exclamation-triangle"></i> No categories available in the system. 
                                        Please contact an administrator to create categories first.
                                    </span>
                                <?php else: ?>
                                    <span class="form-help"><?php echo count($categories); ?> categor<?php echo count($categories) == 1 ? 'y' : 'ies'; ?> available</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="brand">
                                    Brand <span class="required">*</span>
                                </label>
                                <select id="brand" name="brand" class="form-select" required disabled>
                                    <option value="">Select a category first</option>
                                </select>
                                <span class="form-help" id="brand-help">Select a category to see available brands</span>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="sku">
                                    SKU (Stock Keeping Unit)
                                </label>
                                <input type="text" id="sku" name="sku" class="form-input" placeholder="Auto-generated if left empty">
                                <span class="form-help">Leave empty to auto-generate</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section B: Product Details -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-align-left"></i>
                            Product Details
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="description">
                                    Product Description <span class="required">*</span>
                                </label>
                                <textarea id="description" name="description" class="form-textarea" rows="6" placeholder="Describe your product in detail..." required minlength="100" maxlength="1000"></textarea>
                                <span class="form-help">Minimum 100 characters, maximum 1000 characters</span>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="cultural_story">
                                    Cultural Story/Background
                                </label>
                                <textarea id="cultural_story" name="cultural_story" class="form-textarea" rows="4" placeholder="Share the cultural significance or story behind this product..."></textarea>
                                <span class="form-help">Optional: Explain the cultural heritage and significance</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="materials">
                                    Materials Used
                                </label>
                                <input type="text" id="materials" name="materials" class="form-input" placeholder="e.g., 100% Cotton, Kente Fabric" maxlength="500">
                            </div>
                            
                            <div class="form-group">
                                <label for="care_instructions">
                                    Care Instructions
                                </label>
                                <textarea id="care_instructions" name="care_instructions" class="form-textarea" rows="3" placeholder="How to care for this product..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section C: Pricing -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-tag"></i>
                            Pricing
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="base_price">
                                    Base Price (GHS) <span class="required">*</span>
                                </label>
                                <input type="number" id="base_price" name="base_price" class="form-input" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="compare_price">
                                    Compare at Price (GHS)
                                </label>
                                <input type="number" id="compare_price" name="compare_price" class="form-input" placeholder="0.00" step="0.01" min="0">
                                <span class="form-help">Show original price for discounts</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="cost_per_item">
                                    Cost per Item (GHS)
                                </label>
                                <input type="number" id="cost_per_item" name="cost_per_item" class="form-input" placeholder="0.00" step="0.01" min="0">
                                <span class="form-help">For your internal tracking</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section D: Inventory -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-warehouse"></i>
                            Inventory
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="stock_quantity">
                                    Stock Quantity <span class="required">*</span>
                                </label>
                                <input type="number" id="stock_quantity" name="stock_quantity" class="form-input" placeholder="0" min="0" required value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="low_stock_threshold">
                                    Low Stock Alert
                                </label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-input" placeholder="5" min="0" value="5">
                                <span class="form-help">Alert when stock falls below this number</span>
                            </div>
                            
                            <div class="form-group full-width">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" id="track_inventory" name="track_inventory" checked style="width: 18px; height: 18px; cursor: pointer;">
                                    <span>Track inventory for this product</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section E: Product Images -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-images"></i>
                            Product Images
                        </h2>
                        
                        <div class="form-group full-width">
                            <label>
                                Main Product Image <span class="required">*</span>
                            </label>
                            <div class="image-upload-area" id="mainImageUpload">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #D2691E; margin-bottom: 10px;"></i>
                                <p style="font-family: 'Spectral', serif; color: var(--text-dark); margin: 5px 0;">
                                    <strong>Click to upload</strong> or drag and drop
                                </p>
                                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); margin: 5px 0;">
                                    PNG, JPG, WEBP up to 5MB
                                </p>
                                <input type="file" id="main_image" name="main_image" accept="image/jpeg,image/png,image/webp" required style="display: none;">
                            </div>
                            <div id="mainImagePreview" style="margin-top: 15px;"></div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>
                                Additional Images (up to 5)
                            </label>
                            <div class="image-upload-area" id="additionalImagesUpload">
                                <i class="fas fa-images" style="font-size: 2rem; color: #D2691E; margin-bottom: 10px;"></i>
                                <p style="font-family: 'Spectral', serif; color: var(--text-dark); margin: 5px 0;">
                                    <strong>Click to upload</strong> or drag and drop
                                </p>
                                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); margin: 5px 0;">
                                    PNG, JPG, WEBP up to 5MB each
                                </p>
                                <input type="file" id="additional_images" name="additional_images[]" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                            </div>
                            <div id="additionalImagesPreview" class="image-preview-grid" style="margin-top: 15px;"></div>
                        </div>
                    </div>
                    
                    <!-- Section F: Shipping -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-truck"></i>
                            Shipping Information
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="product_weight">
                                    Weight (kg)
                                </label>
                                <input type="number" id="product_weight" name="product_weight" class="form-input" placeholder="0.00" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="processing_time">
                                    Processing Time
                                </label>
                                <select id="processing_time" name="processing_time" class="form-select">
                                    <option value="">Select processing time</option>
                                    <option value="1-2 days">1-2 days</option>
                                    <option value="3-5 days">3-5 days</option>
                                    <option value="1-2 weeks">1-2 weeks</option>
                                    <option value="2-4 weeks">2-4 weeks</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="ships_from">
                                    Ships From (City)
                                </label>
                                <input type="text" id="ships_from" name="ships_from" class="form-input" placeholder="e.g., Accra, Kumasi" value="<?php echo escape_html($producer['customer_city'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="product_length">Length (cm)</label>
                                <input type="number" id="product_length" name="product_length" class="form-input" placeholder="0" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="product_width">Width (cm)</label>
                                <input type="number" id="product_width" name="product_width" class="form-input" placeholder="0" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="product_height">Height (cm)</label>
                                <input type="number" id="product_height" name="product_height" class="form-input" placeholder="0" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section G: SEO & Tags -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-search"></i>
                            SEO & Tags
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="tags">
                                    Tags/Keywords
                                </label>
                                <input type="text" id="tags" name="tags" class="form-input" placeholder="kente, traditional, ghana, fashion (comma-separated)">
                                <span class="form-help">Separate tags with commas</span>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="meta_description">
                                    Meta Description
                                </label>
                                <textarea id="meta_description" name="meta_description" class="form-textarea" rows="2" placeholder="Brief description for search engines (optional)" maxlength="255"></textarea>
                                <span class="form-help">Maximum 255 characters</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section H: Status -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-toggle-on"></i>
                            Product Status
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="product_status">
                                    Product Status <span class="required">*</span>
                                </label>
                                <select id="product_status" name="product_status" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="visibility">
                                    Visibility <span class="required">*</span>
                                </label>
                                <select id="visibility" name="visibility" class="form-select" required>
                                    <option value="public">Public</option>
                                    <option value="hidden">Hidden</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" name="action" value="publish" class="btn-primary">
                            <i class="fas fa-check"></i> Publish Product
                        </button>
                        <button type="submit" name="action" value="draft" class="btn-secondary">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                        <a href="<?php echo url('view/producer/products.php'); ?>" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                            Cancel
                        </a>
                    </div>
                </form>
                
                <!-- Response Messages -->
                <div id="response" style="margin-top: 20px;"></div>
            </div>
        </div>
    </main>
</div>

<script>
// Brand dropdown based on category selection
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    const brandSelect = document.getElementById('brand');
    const brandHelp = document.getElementById('brand-help');
    const brandsByCategory = <?php echo json_encode($brands_by_category); ?>;
    
    categorySelect.addEventListener('change', function() {
        const catId = this.value;
        
        if (!catId) {
            brandSelect.innerHTML = '<option value="">Select a category first</option>';
            brandSelect.disabled = true;
            brandHelp.textContent = 'Select a category to see available brands';
            return;
        }
        
        const brands = brandsByCategory[catId] || [];
        
        if (brands.length === 0) {
            brandSelect.innerHTML = '<option value="">No brands in this category</option>';
            brandSelect.disabled = true;
            brandHelp.textContent = 'No brands available for this category. You may need to create a brand first.';
        } else {
            brandSelect.innerHTML = '<option value="">Select Brand</option>';
            brands.forEach(brand => {
                const option = document.createElement('option');
                option.value = brand.brand_id;
                option.textContent = brand.brand_name;
                brandSelect.appendChild(option);
            });
            brandSelect.disabled = false;
            brandHelp.textContent = brands.length + ' brand(s) available';
        }
    });
    
    // Image upload handlers
    const mainImageUpload = document.getElementById('mainImageUpload');
    const mainImageInput = document.getElementById('main_image');
    const mainImagePreview = document.getElementById('mainImagePreview');
    
    mainImageUpload.addEventListener('click', () => mainImageInput.click());
    mainImageUpload.addEventListener('dragover', (e) => {
        e.preventDefault();
        mainImageUpload.classList.add('dragover');
    });
    mainImageUpload.addEventListener('dragleave', () => {
        mainImageUpload.classList.remove('dragover');
    });
    mainImageUpload.addEventListener('drop', (e) => {
        e.preventDefault();
        mainImageUpload.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            mainImageInput.files = files;
            handleMainImagePreview(files[0]);
        }
    });
    
    mainImageInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleMainImagePreview(this.files[0]);
        }
    });
    
    function handleMainImagePreview(file) {
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('Image size must be less than 5MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            mainImagePreview.innerHTML = `
                <div class="image-preview-item" style="max-width: 200px;">
                    <img src="${e.target.result}" alt="Main product image">
                    <button type="button" class="remove-image" onclick="removeMainImage()">×</button>
                    <span class="primary-badge">Main Image</span>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
    
    window.removeMainImage = function() {
        mainImageInput.value = '';
        mainImagePreview.innerHTML = '';
    };
    
    // Additional images
    const additionalImagesUpload = document.getElementById('additionalImagesUpload');
    const additionalImagesInput = document.getElementById('additional_images');
    const additionalImagesPreview = document.getElementById('additionalImagesPreview');
    let additionalImages = [];
    
    additionalImagesUpload.addEventListener('click', () => additionalImagesInput.click());
    additionalImagesUpload.addEventListener('dragover', (e) => {
        e.preventDefault();
        additionalImagesUpload.classList.add('dragover');
    });
    additionalImagesUpload.addEventListener('dragleave', () => {
        additionalImagesUpload.classList.remove('dragover');
    });
    additionalImagesUpload.addEventListener('drop', (e) => {
        e.preventDefault();
        additionalImagesUpload.classList.remove('dragover');
        const files = Array.from(e.dataTransfer.files);
        handleAdditionalImages(files);
    });
    
    additionalImagesInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleAdditionalImages(Array.from(this.files));
        }
    });
    
    function handleAdditionalImages(files) {
        files.forEach(file => {
            if (!file.type.startsWith('image/')) {
                alert(file.name + ' is not an image file');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert(file.name + ' is larger than 5MB');
                return;
            }
            if (additionalImages.length >= 5) {
                alert('Maximum 5 additional images allowed');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageId = Date.now() + Math.random();
                additionalImages.push({id: imageId, file: file, preview: e.target.result});
                updateAdditionalImagesPreview();
            };
            reader.readAsDataURL(file);
        });
    }
    
    function updateAdditionalImagesPreview() {
        additionalImagesPreview.innerHTML = additionalImages.map((img, index) => `
            <div class="image-preview-item">
                <img src="${img.preview}" alt="Additional image ${index + 1}">
                <button type="button" class="remove-image" onclick="removeAdditionalImage(${index})">×</button>
            </div>
        `).join('');
    }
    
    window.removeAdditionalImage = function(index) {
        additionalImages.splice(index, 1);
        updateAdditionalImagesPreview();
        // Update file input
        const dt = new DataTransfer();
        additionalImages.forEach(img => dt.items.add(img.file));
        additionalImagesInput.files = dt.files;
    };
    
    // Form validation and submission
    const form = document.getElementById('addProductForm');
    const responseDiv = document.getElementById('response');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const description = document.getElementById('description').value;
        if (description.length < 100) {
            showResponse('Product description must be at least 100 characters', 'error');
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        // Create FormData
        const formData = new FormData(form);
        
        // Add additional images from preview
        if (additionalImages.length > 0) {
            additionalImages.forEach(img => {
                formData.append('additional_images[]', img.file);
            });
        }
        
        // Submit via AJAX
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResponse(data.message, 'success');
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '<?php echo url("view/producer/products.php"); ?>';
                    }
                }, 1500);
            } else {
                showResponse(data.message || 'Failed to save product', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showResponse('An error occurred. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    function showResponse(message, type) {
        responseDiv.innerHTML = `
            <div style="padding: 15px; border-radius: 6px; margin-bottom: 20px; 
                        background: ${type === 'success' ? '#D4EDDA' : '#F8D7DA'}; 
                        color: ${type === 'success' ? '#155724' : '#721C24'}; 
                        border: 1px solid ${type === 'success' ? '#C3E6CB' : '#F5C6CB'};">
                <strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}
            </div>
        `;
        responseDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
});
</script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

