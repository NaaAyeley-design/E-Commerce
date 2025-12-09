<?php
/**
 * Brand Management Page
 */

require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../controller/brand_controller.php';
require_once __DIR__ . '/../../controller/category_controller.php';

// Set page variables
$page_title = 'Brand Management';
$page_description = 'Manage product brands for KenteKart.';
$body_class = 'brands-page';
$additional_css = ['brands.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

if (!is_admin()) {
    header('Location: ' . BASE_URL . '/view/user/dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
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

// Get brands grouped by category
$brands_by_category = [];
if (!empty($categories)) {
    foreach ($categories as $cat) {
        $brands = get_brands_by_category_ctr($user_id, $cat['cat_id']);
        // Check if result is an error message (string) or valid array
        if (is_array($brands)) {
            // Always add the category section, even if brands array is empty
            $brands_by_category[$cat['cat_id']] = [
                'category' => $cat,
                'brands' => $brands
            ];
            error_log("Brands for category {$cat['cat_id']} ({$cat['cat_name']}): " . count($brands));
        } else {
            error_log("Error getting brands for category {$cat['cat_id']}: " . $brands);
            // Still add the category with empty brands array
            $brands_by_category[$cat['cat_id']] = [
                'category' => $cat,
                'brands' => []
            ];
        }
    }
}

error_log("Total categories with brands: " . count($brands_by_category));

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container center-content">
        <h1>Brand Management</h1>
        
        <!-- Messages -->
        <div id="message-container">
            <?php if ($message): ?>
                <div class="message message-success" id="success-message"><?php echo escape_html($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message message-error" id="error-message"><?php echo escape_html($error); ?></div>
            <?php endif; ?>
            
        </div>

        <!-- Add Brand Form -->
        <div class="card">
            <h3>Add New Brand</h3>
            <form id="add-brand-form" method="post">
                <div class="form-group">
                    <label for="brand_name">Brand Name:</label>
                    <input type="text" id="brand_name" name="brand_name" placeholder="Enter brand name" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="cat_id">Category:</label>
                    <select id="cat_id" name="cat_id" required class="form-input">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['cat_id']; ?>">
                                <?php echo escape_html($cat['cat_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="brand_description">Description (Optional):</label>
                    <textarea id="brand_description" name="brand_description" placeholder="Enter brand description" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="brand_logo">Logo URL (Optional):</label>
                    <input type="url" id="brand_logo" name="brand_logo" placeholder="https://example.com/logo.png" class="form-input">
                </div>
                
                <button type="submit" class="btn btn-primary">Add Brand</button>
            </form>
        </div>

        <!-- Brands by Category -->
        <div class="card">
            <h3>Your Brands</h3>
            
            <?php 
            $total_brands = 0;
            foreach ($brands_by_category as $cat_data) {
                $total_brands += count($cat_data['brands']);
            }
            ?>
            
            <?php if (empty($brands_by_category) || $total_brands === 0): ?>
                <p>No brands found. Add your first brand above.</p>
            <?php else: ?>
                <?php foreach ($brands_by_category as $category_data): ?>
                    <div class="category-section">
                        <h4 class="category-title">
                            <?php echo escape_html($category_data['category']['cat_name']); ?>
                            <span class="brand-count">(<?php echo count($category_data['brands']); ?> brands)</span>
                        </h4>
                        
                        <?php if (empty($category_data['brands'])): ?>
                            <p class="no-brands">No brands in this category yet.</p>
                        <?php else: ?>
                            <div class="brands-grid">
                                <?php foreach ($category_data['brands'] as $brand): ?>
                                    <div class="brand-card" data-brand-id="<?php echo $brand['brand_id']; ?>">
                                        <div class="brand-header">
                                            <h5 class="brand-name" id="brand-name-<?php echo $brand['brand_id']; ?>">
                                                <?php echo escape_html($brand['brand_name']); ?>
                                            </h5>
                                            <div class="brand-actions">
                                                <button class="btn btn-sm btn-outline edit-brand-btn" data-brand-id="<?php echo $brand['brand_id']; ?>">
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-brand-btn" data-brand-id="<?php echo $brand['brand_id']; ?>" data-brand-name="<?php echo escape_html($brand['brand_name']); ?>">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($brand['brand_description'])): ?>
                                            <p class="brand-description"><?php echo escape_html($brand['brand_description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($brand['brand_logo'])): ?>
                                            <div class="brand-logo">
                                                <img src="<?php echo escape_html($brand['brand_logo']); ?>" alt="<?php echo escape_html($brand['brand_name']); ?> logo" onerror="this.style.display='none'">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="brand-meta">
                                            <small class="brand-status <?php echo $brand['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $brand['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </small>
                                            <small class="brand-date">
                                                Created: <?php echo date('M j, Y', strtotime($brand['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <!-- Edit Form (Hidden by default) -->
                                        <form class="edit-brand-form" id="edit-form-<?php echo $brand['brand_id']; ?>">
                                            <div class="form-group">
                                                <label>Brand Name:</label>
                                                <input type="text" name="brand_name" value="<?php echo escape_html($brand['brand_name']); ?>" required class="form-input">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Description:</label>
                                                <textarea name="brand_description" class="form-input" rows="2"><?php echo escape_html($brand['brand_description']); ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Logo URL:</label>
                                                <input type="url" name="brand_logo" value="<?php echo escape_html($brand['brand_logo']); ?>" class="form-input">
                                            </div>
                                            
                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                                <button type="button" class="btn btn-outline btn-sm cancel-edit-btn" data-brand-id="<?php echo $brand['brand_id']; ?>">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Define BASE_URL for JavaScript
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo ASSETS_URL; ?>/js/brands.js?v=<?php echo time(); ?>"></script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>
