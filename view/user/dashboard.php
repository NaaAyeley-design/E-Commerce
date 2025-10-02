<?php
/**
 * User Dashboard
 * 
 * Main dashboard for authenticated users
 */

// Include core settings and user controller
require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../controller/user_controller.php';

// Check authentication
require_auth();

// Get current user data
$customer = get_current_customer();
if (!$customer) {
    redirect(BASE_URL . '/view/user/login.php?error=session_expired');
}

// Get dashboard data
$dashboard_data = get_user_dashboard_data($customer['customer_id']);

// Set page variables
$page_title = 'Dashboard';
$page_description = 'Manage your account, view orders, and access all platform features.';
$body_class = 'dashboard-page';

// Include header
include VIEW_PATH . '/templates/header.php';
?>

<div class="container">
    <!-- Welcome Section -->
    <div class="dashboard-welcome">
        <div class="welcome-content">
            <h1>Welcome back, <?php echo escape($customer['customer_name']); ?>!</h1>
            <p class="welcome-subtitle">Here's what's happening with your account today.</p>
        </div>
        <div class="welcome-actions">
            <a href="<?php echo BASE_URL; ?>/view/product/product_list.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Start Shopping
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $dashboard_data['stats']['orders'] ?? 0; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo format_currency($dashboard_data['stats']['total_spent'] ?? 0); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $dashboard_data['stats']['wishlist_items'] ?? 0; ?></div>
                <div class="stat-label">Wishlist Items</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $dashboard_data['stats']['reviews'] ?? 0; ?></div>
                <div class="stat-label">Reviews Written</div>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Profile Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Profile Information</h3>
                <a href="<?php echo BASE_URL; ?>/view/user/profile.php" class="btn btn-sm btn-outline">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php if (!empty($customer['customer_image'])): ?>
                        <img src="<?php echo ASSETS_URL . '/' . escape($customer['customer_image']); ?>" 
                             alt="Profile Picture" class="avatar-image">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-details">
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo escape($customer['customer_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo escape($customer['customer_email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Location:</span>
                        <span class="info-value">
                            <?php echo escape($customer['customer_city'] . ', ' . $customer['customer_country']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Contact:</span>
                        <span class="info-value"><?php echo escape($customer['customer_contact']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Type:</span>
                        <span class="info-value">
                            <span class="badge <?php echo $customer['user_role'] == 1 ? 'badge-admin' : 'badge-customer'; ?>">
                                <?php echo $customer['user_role'] == 1 ? 'Administrator' : 'Customer'; ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="action-grid">
                <a href="<?php echo BASE_URL; ?>/view/user/profile.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="action-content">
                        <h4>Edit Profile</h4>
                        <p>Update your personal information</p>
                    </div>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/view/user/change_password.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="action-content">
                        <h4>Change Password</h4>
                        <p>Update your account security</p>
                    </div>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/view/order/order_history.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="action-content">
                        <h4>Order History</h4>
                        <p>View your past purchases</p>
                    </div>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/view/product/product_list.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="action-content">
                        <h4>Browse Products</h4>
                        <p>Discover new items</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Recent Activity</h3>
            </div>
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="activity-content">
                        <p class="activity-text">You logged in to your account</p>
                        <span class="activity-time">Just now</span>
                    </div>
                </div>
                
                <!-- Placeholder for future activities -->
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No recent activity to display</p>
                </div>
            </div>
        </div>

        <!-- Account Security -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-shield-alt"></i> Account Security</h3>
            </div>
            <div class="security-info">
                <div class="security-item">
                    <div class="security-icon success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="security-content">
                        <h4>Email Verified</h4>
                        <p>Your email address is verified and secure</p>
                    </div>
                </div>
                
                <div class="security-item">
                    <div class="security-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="security-content">
                        <h4>Two-Factor Authentication</h4>
                        <p>Enable 2FA for enhanced security</p>
                        <a href="#" class="btn btn-sm btn-outline">Enable 2FA</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Additional CSS for dashboard
$additional_css = ['dashboard.css'];

// Additional JavaScript for dashboard
$additional_js = ['dashboard.js'];

// Include footer
include VIEW_PATH . '/templates/footer.php';
?>
