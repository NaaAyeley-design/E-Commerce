<?php
/**
 * Simple Admin Dashboard
 * 
 * Clean, simple admin dashboard
 */

// Suppress error reporting to prevent code from showing
error_reporting(0);
ini_set('display_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';

// Set page variables
$page_title = 'Admin Dashboard';
$page_description = 'Manage your e-commerce platform.';
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

// Set page variables
$page_title = 'Admin Dashboard';
$page_description = 'Manage your e-commerce platform.';
$body_class = 'admin-dashboard-page';
$additional_css = ['admin_dashboard.css'];

// Include header
include VIEW_PATH . '/templates/header.php';
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
                <div class="quick-stat-number">0</div>
                <div class="quick-stat-label">Total Users</div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-number">0</div>
                <div class="quick-stat-label">Categories</div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-number">0</div>
                <div class="quick-stat-label">Products</div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-number">0</div>
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
                <a href="#" class="action-button">
                    <i class="fas fa-arrow-right"></i>
                    Manage Products
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="action-title">User Management</div>
                <div class="action-description">View and manage user accounts and permissions.</div>
                <a href="#" class="action-button">
                    <i class="fas fa-arrow-right"></i>
                    Manage Users
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="action-title">Analytics</div>
                <div class="action-description">View sales reports and platform analytics.</div>
                <a href="#" class="action-button">
                    <i class="fas fa-arrow-right"></i>
                    View Analytics
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
// Include footer
include __DIR__ . '/../templates/footer.php';
?>