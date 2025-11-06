<?php
/**
 * Simple Admin Dashboard
 * 
 * Clean, simple admin dashboard
 */

// Suppress error reporting to prevent code from showing
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';

// Set page variables
$page_title = 'Admin Dashboard';
$page_description = 'Manage your KenteKart platform.';
$body_class = 'admin-dashboard-page';
$additional_css = ['admin_dashboard.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=login_required');
    exit;
}

// Check if user is admin
if (!is_admin()) {
    header('Location: ' . BASE_URL . '/view/user/dashboard.php');
    exit;
}

// Get current admin data
$user = new user_class();
$admin = $user->get_customer_by_id($_SESSION['user_id']);
if (!$admin) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=session_expired');
    exit;
}

// Get dashboard statistics
require_once __DIR__ . '/../../../class/category_class.php';
require_once __DIR__ . '/../../../class/product_class.php';
require_once __DIR__ . '/../../../class/order_class.php';

try {
    // Count total users
    $total_users = $user->count_customers();
    
    // Count total categories
    $category = new category_class();
    $total_categories = $category->count_all_categories();
    
    // Count total products
    $product = new product_class();
    $total_products = $product->count_all_products();
    
    // Count total orders
    $order = new order_class();
    $total_orders = $order->count_all_orders();
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    // Set defaults if error occurs
    $total_users = 0;
    $total_categories = 0;
    $total_products = 0;
    $total_orders = 0;
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="admin-dashboard-container">
    <!-- Header Section -->
    <div class="admin-header">
        <h2>Admin Dashboard</h2>
        <p>Welcome back, <?php echo escape_html($admin['customer_name']); ?>!</p>
        <div class="admin-badge">
            <i class="fas fa-crown"></i>
            Administrator
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <h3>Quick Overview</h3>
        <div class="quick-stats-grid">
            <div class="quick-stat">
                <div class="quick-stat-number"><?php echo number_format($total_users); ?></div>
                <div class="quick-stat-label">Total Users</div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-number"><?php echo number_format($total_categories); ?></div>
                <div class="quick-stat-label">Categories</div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-number"><?php echo number_format($total_products); ?></div>
                <div class="quick-stat-label">Products</div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-number"><?php echo number_format($total_orders); ?></div>
                <div class="quick-stat-label">Orders</div>
            </div>
        </div>
    </div>

    <!-- Admin Actions -->
    <div class="admin-actions">
        <h3>Management Actions</h3>
        <div class="actions-grid">
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="action-title">Category Management</div>
                <div class="action-description">Add, edit, and manage product categories for your store.</div>
                <a href="<?php echo BASE_URL; ?>/view/admin/categories.php" class="action-button">
                    <i class="fas fa-arrow-right"></i>
                    Manage Categories
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="action-title">Product Management</div>
                <div class="action-description">Add, edit, and manage products in your inventory.</div>
                <a href="<?php echo BASE_URL; ?>/view/admin/products.php" class="action-button">
                    <i class="fas fa-arrow-right"></i>
                    Manage Products
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-trademark"></i>
                </div>
                <div class="action-title">Brand Management</div>
                <div class="action-description">Add, edit, and manage product brands for your store.</div>
                <a href="<?php echo BASE_URL; ?>/view/admin/brands.php" class="action-button">
                    <i class="fas fa-arrow-right"></i>
                    Manage Brands
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="action-title">User Management</div>
                <div class="action-description">View and manage user accounts and permissions.</div>
                <a href="<?php echo BASE_URL; ?>/view/admin/users.php" class="action-button">
                    <i class="fas fa-arrow-right"></i>
                    Manage Users
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
// Include footer
include __DIR__ . '/../templates/footer.php';
?>