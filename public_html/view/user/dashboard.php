<?php
/**
 * User Dashboard
 * 
 * Main dashboard for authenticated users
 */

// Suppress error reporting to prevent code from showing
error_reporting(0);
ini_set('display_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';

// Set page variables
$page_title = 'User Dashboard';
$page_description = 'Manage your account and view your orders.';
$body_class = 'dashboard-page';
$additional_css = ['dashboard.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=login_required');
    exit;
}

// Get current user data
$user = new user_class();
$customer = $user->get_customer_by_id($_SESSION['user_id']);
if (!$customer) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=session_expired');
    exit;
}

// Check if user is admin and redirect to admin dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
    header('Location: ' . BASE_URL . '/view/admin/dashboard.php');
    exit;
}

// Set page variables
$page_title = 'User Dashboard';
$page_description = 'Manage your account and view your orders.';
$body_class = 'dashboard-page';
$additional_css = ['dashboard.css'];

// Include header
include VIEW_PATH . '/templates/header.php';
?>

<div class="dashboard-container">
    <!-- Header Section -->
    <div class="dashboard-header">
        <h2>Welcome back, <?php echo escape_html($customer['customer_name']); ?>!</h2>
        <p>Here's what's happening with your account today.</p>
    </div>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h3>Account Overview</h3>
        <p>Manage your account, view orders, and access all platform features from this dashboard.</p>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-number">0</div>
            <div class="stat-label">Total Orders</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-number">$0.00</div>
            <div class="stat-label">Total Spent</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-number">0</div>
            <div class="stat-label">Wishlist Items</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-number">0</div>
            <div class="stat-label">Reviews Written</div>
        </div>
    </div>

    <!-- User Info Section -->
    <div class="user-info">
        <h3>Profile Information</h3>
        <div class="user-details">
            <div class="user-detail">
                <div class="user-detail-label">Name:</div>
                <div class="user-detail-value"><?php echo escape_html($customer['customer_name']); ?></div>
            </div>
            <div class="user-detail">
                <div class="user-detail-label">Email:</div>
                <div class="user-detail-value"><?php echo escape_html($customer['customer_email']); ?></div>
            </div>
            <div class="user-detail">
                <div class="user-detail-label">Location:</div>
                <div class="user-detail-value"><?php echo escape_html($customer['customer_city'] . ', ' . $customer['customer_country']); ?></div>
            </div>
            <div class="user-detail">
                <div class="user-detail-label">Phone:</div>
                <div class="user-detail-value"><?php echo escape_html($customer['customer_contact']); ?></div>
            </div>
            <div class="user-detail">
                <div class="user-detail-label">Role:</div>
                <div class="user-detail-value"><?php echo $customer['user_role'] == 1 ? 'Administrator' : 'Customer'; ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="actions-grid">
            <a href="<?php echo BASE_URL; ?>/view/user/profile.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="action-title">Edit Profile</div>
                <div class="action-description">Update your personal information</div>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/view/user/change_password.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="action-title">Change Password</div>
                <div class="action-description">Update your account security</div>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/view/order/order_history.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="action-title">Order History</div>
                <div class="action-description">View your past purchases</div>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/view/product/product_list.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="action-title">Browse Products</div>
                <div class="action-description">Discover new items</div>
            </a>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>