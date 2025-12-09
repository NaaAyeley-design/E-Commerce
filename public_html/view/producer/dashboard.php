<?php
/**
 * KenteKart Producer Dashboard
 * 
 * Dashboard for designers/producers (user_role = 3) to manage their products and business
 */

// Suppress error reporting to prevent code from showing
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../class/order_class.php';
require_once __DIR__ . '/../../class/product_class.php';
require_once __DIR__ . '/../../class/user_class.php';

// Set page variables
$page_title = 'Producer Dashboard - KenteKart';
$page_description = 'Manage your products and business on KenteKart';
$body_class = 'dashboard-page producer-dashboard';
$additional_css = ['dashboard-metrics.css']; // Dashboard metrics CSS file

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

// Check user role and redirect if not producer
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 3) {
    if ($_SESSION['user_role'] == 1) {
        // Admin -> Admin dashboard
        header('Location: ' . BASE_URL . '/view/admin/dashboard.php');
        exit;
    } elseif ($_SESSION['user_role'] == 2) {
        // Customer -> Customer dashboard
        header('Location: ' . BASE_URL . '/view/user/dashboard.php');
        exit;
    } else {
        // Invalid role
        header('Location: ' . BASE_URL . '/view/user/login.php?error=access_denied');
        exit;
    }
}

$producer_id = $_SESSION['user_id'];
$producer_name = $producer['customer_name'] ?? 'Producer';
$business_name = $producer['business_name'] ?? $producer_name;

// Get producer's products (through brands)
$product_class = new product_class();
$db = new db_class();

// Get all brands owned by this producer
$brands_sql = "SELECT brand_id FROM brands WHERE user_id = ?";
$producer_brands = $db->fetchAll($brands_sql, [$producer_id]);
$brand_ids = array_column($producer_brands, 'brand_id');

// Get total products count
$total_products = 0;
if (!empty($brand_ids)) {
    $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
    $products_sql = "SELECT COUNT(*) as total FROM products WHERE product_brand IN ($placeholders)";
    $products_result = $db->fetchRow($products_sql, $brand_ids);
    $total_products = $products_result['total'] ?? 0;
}

// Get producer's orders (orders containing products from this producer)
$order_class = new order_class();
$pending_orders = 0;
$total_earnings = 0;
$this_month_sales = 0;
$current_month = date('Y-m');

if (!empty($brand_ids)) {
    // Get order items for producer's products
    $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
    $orders_sql = "SELECT DISTINCT o.order_id, o.order_status, o.total_amount, o.order_date, o.created_at
                   FROM orders o
                   INNER JOIN order_items oi ON o.order_id = oi.order_id
                   INNER JOIN products p ON oi.product_id = p.product_id
                   WHERE p.product_brand IN ($placeholders)
                   ORDER BY o.created_at DESC";
    
    $producer_orders = $db->fetchAll($orders_sql, $brand_ids);
    
    // Calculate metrics
    foreach ($producer_orders as $order) {
        $order_status = strtolower($order['order_status'] ?? 'pending');
        
        // Count pending orders
        if (in_array($order_status, ['pending', 'processing'])) {
            $pending_orders++;
        }
        
        // Calculate earnings from completed/delivered orders
        if (in_array($order_status, ['completed', 'delivered'])) {
            // Get order items for this producer's products only
            $order_id = $order['order_id'];
            $order_items_sql = "SELECT oi.price, oi.quantity 
                               FROM order_items oi
                               INNER JOIN products p ON oi.product_id = p.product_id
                               WHERE oi.order_id = ? AND p.product_brand IN ($placeholders)";
            $order_items = $db->fetchAll($order_items_sql, array_merge([$order_id], $brand_ids));
            
            foreach ($order_items as $item) {
                $item_total = (float)$item['price'] * (int)$item['quantity'];
                $total_earnings += $item_total;
            }
        }
        
        // Count this month's sales
        $order_date = $order['order_date'] ?? $order['created_at'] ?? '';
        if ($order_date) {
            $order_month = date('Y-m', strtotime($order_date));
            if ($order_month === $current_month) {
                $this_month_sales++;
            }
        }
    }
} else {
    $producer_orders = [];
}

// Get recent activity (last 5 orders)
$recent_orders = array_slice($producer_orders, 0, 5);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="kentekart-dashboard">
    
    <!-- SIDEBAR NAVIGATION -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>Producer Hub</h2>
            <p>Manage your business</p>
        </div>

        <nav class="sidebar-nav">
            <a href="<?php echo url('view/producer/dashboard.php'); ?>" class="nav-item active">
                <i class="fas fa-th"></i>
                <span>Overview</span>
            </a>
            <a href="<?php echo url('view/producer/products.php'); ?>" class="nav-item">
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
                <?php echo strtoupper(substr($producer_name, 0, 1)); ?>
            </div>
            <div class="user-info">
                <p class="user-name"><?php echo escape_html($business_name); ?></p>
                <p class="user-since">Producer since <?php echo date('M Y', strtotime($producer['created_at'] ?? 'now')); ?></p>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
            
            <!-- Header -->
            <header class="section-header-minimal">
                <div>
                    <p class="section-subtitle">Welcome back</p>
                    <h1 class="section-title"><?php echo escape_html($producer_name); ?></h1>
                </div>
                <div class="header-meta">
                    <p class="meta-label">Business</p>
                    <p class="meta-value"><?php echo escape_html($business_name); ?></p>
                </div>
            </header>

            <!-- Stat Cards -->
            <div class="stats-row">
                <div class="stat-card stat-card-gradient-1">
                    <div class="stat-card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-number"><?php echo $total_products; ?></p>
                        <p class="stat-label">Total Products</p>
                    </div>
                </div>
                <div class="stat-card stat-card-gradient-2">
                    <div class="stat-card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-number"><?php echo $pending_orders; ?></p>
                        <p class="stat-label">Pending Orders</p>
                    </div>
                </div>
                <div class="stat-card stat-card-gradient-3">
                    <div class="stat-card-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-number">₵<?php echo number_format($total_earnings, 0); ?></p>
                        <p class="stat-label">Total Earnings</p>
                    </div>
                </div>
                <div class="stat-card stat-card-gradient-4">
                    <div class="stat-card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-number"><?php echo $this_month_sales; ?></p>
                        <p class="stat-label">This Month Sales</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions" style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <a href="<?php echo url('view/producer/add_product.php'); ?>" class="quick-action-btn" style="display: flex; align-items: center; gap: 15px; padding: 20px; background: linear-gradient(135deg, #D2691E 0%, #B8621E 100%); color: #FFFFFF; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(210, 105, 30, 0.3);">
                    <i class="fas fa-plus-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong style="font-family: 'Cormorant Garamond', serif; font-size: 1.125rem; display: block; margin-bottom: 5px;">Add New Product</strong>
                        <span style="font-family: 'Spectral', serif; font-size: 0.875rem; opacity: 0.9;">List a new product</span>
                    </div>
                </a>
                <a href="<?php echo url('view/producer/orders.php'); ?>" class="quick-action-btn" style="display: flex; align-items: center; gap: 15px; padding: 20px; background: #FFFFFF; border: 2px solid #D2691E; color: #D2691E; border-radius: 8px; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-shopping-cart" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong style="font-family: 'Cormorant Garamond', serif; font-size: 1.125rem; display: block; margin-bottom: 5px;">View Orders</strong>
                        <span style="font-family: 'Spectral', serif; font-size: 0.875rem; color: #6B5B4F;">Manage your orders</span>
                    </div>
                </a>
            </div>

            <!-- Recent Activity -->
            <div class="orders-list-section" style="margin-top: 40px;">
                <h3 class="subsection-title">Recent Activity</h3>
                
                <?php if (empty($recent_orders)): ?>
                <div class="empty-state-minimal">
                    <i class="fas fa-box-open"></i>
                    <p>No orders yet</p>
                    <p class="text-muted">Start by adding products to your store</p>
                    <a href="<?php echo url('view/producer/add_product.php'); ?>" class="btn-minimal">Add Your First Product</a>
                </div>
                <?php else: ?>
                <div class="orders-list-minimal">
                    <?php foreach ($recent_orders as $index => $order): 
                        $order_date = $order['order_date'] ?? $order['created_at'] ?? '';
                        $status = strtolower($order['order_status'] ?? 'pending');
                        $status_colors = [
                            'pending' => '#F59E0B',
                            'processing' => '#3B82F6',
                            'shipped' => '#8B5CF6',
                            'delivered' => '#10B981',
                            'completed' => '#10B981',
                            'cancelled' => '#EF4444'
                        ];
                        $status_color = $status_colors[$status] ?? '#6B7280';
                    ?>
                    <div class="order-item-minimal <?php echo $index < count($recent_orders) - 1 ? 'has-border' : ''; ?>">
                        <div class="order-item-content">
                            <div class="order-item-info">
                                <p class="order-item-title">Order #<?php echo escape_html($order['order_id']); ?></p>
                                <p class="order-item-meta"><?php echo $order_date ? date('M j, Y', strtotime($order_date)) : 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="order-item-actions">
                            <span class="order-status-badge" style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; background: <?php echo $status_color; ?>20; color: <?php echo $status_color; ?>; margin-right: 10px;">
                                <?php echo ucfirst($status); ?>
                            </span>
                            <a href="<?php echo url('view/producer/orders.php?order_id=' . $order['order_id']); ?>" class="btn-outline-minimal">
                                View
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>


    </main>
    
</div>

<!-- Dashboard Script -->
<script>
// Dashboard data from PHP
const dashboardData = {
    totalProducts: <?php echo $total_products; ?>,
    pendingOrders: <?php echo $pending_orders; ?>,
    totalEarnings: <?php echo $total_earnings; ?>,
    thisMonthSales: <?php echo $this_month_sales; ?>
};

// KenteKart colors
const colors = {
    terracotta: '#C67D5C',
    deepBrown: '#8B6F47',
    chocolate: '#D2691E',
    lightBrown: '#8B7F74',
    warmBeige: '#F4EDE4',
    gold: '#FFD700'
};

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Producer Dashboard loaded');
    console.log('Stats:', dashboardData);
});
</script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

