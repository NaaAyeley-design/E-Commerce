<?php
/**
 * KenteKart User Dashboard - Sidebar Navigation Design
 * 
 * Clean dashboard with sidebar navigation showing different metric views
 */

// Suppress error reporting to prevent code from showing
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/order_controller.php';
require_once __DIR__ . '/../../../class/artisan_class.php';

// Set page variables
$page_title = 'My Dashboard - KenteKart';
$page_description = 'View your shopping metrics and order history';
$body_class = 'dashboard-page';
$additional_css = ['dashboard-metrics.css', 'wishlist.css', 'products.css']; // Dashboard metrics CSS file + wishlist styles

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

// Check user role and redirect to appropriate dashboard
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] == 1) {
        // Role 1: Admin -> Admin dashboard
        header('Location: ' . BASE_URL . '/view/admin/dashboard.php');
        exit;
    } elseif ($_SESSION['user_role'] == 3) {
        // Role 3: Designer/Producer -> Producer dashboard
        $producer_dashboard = __DIR__ . '/../producer/dashboard.php';
        if (file_exists($producer_dashboard)) {
            header('Location: ' . BASE_URL . '/view/producer/dashboard.php');
            exit;
        }
        // If producer dashboard doesn't exist, allow access to user dashboard for now
    }
    // Role 2: Customer -> Continue to user dashboard (default)
}

// Get user orders with enhanced data
require_once __DIR__ . '/../../../class/order_class.php';
require_once __DIR__ . '/../../../class/db_class.php';
$order_class = new order_class();
$db = new db_class();
$customer_id = $_SESSION['user_id'];

// Get all orders for this customer
$all_orders = $order_class->get_customer_orders($customer_id, 100, 0);

// Calculate metrics from actual orders
$total_orders = count($all_orders);
$total_spent = 0;
$orders_this_month = 0;
$spending_this_month = 0;
$unique_artisans = [];
$orders_delivered = 0;
$orders_in_transit = 0;

// Spending by month (last 6 months)
$monthly_spending = [];
$monthly_orders = [];

$current_month = date('Y-m');
$current_year = date('Y');

// Initialize last 6 months
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $monthly_spending[$date] = 0;
    $monthly_orders[$date] = 0;
}

// Fetch order items for each order to enhance display
$orders_with_items = [];
foreach ($all_orders as $order) {
    // Use correct column name: total_amount (not order_total)
    $order_amount = isset($order['total_amount']) ? (float)$order['total_amount'] : 0;
    $total_spent += $order_amount;
    
    // Get order month from order_date or created_at
    $order_date = $order['order_date'] ?? $order['created_at'] ?? date('Y-m-d H:i:s');
    $order_month = date('Y-m', strtotime($order_date));
    
    // Add to monthly totals if in last 6 months
    if (isset($monthly_spending[$order_month])) {
        $monthly_spending[$order_month] += $order_amount;
        $monthly_orders[$order_month]++;
    }
    
    // Check if order is from this month
    if ($order_month === $current_month) {
        $orders_this_month++;
        $spending_this_month += $order_amount;
    }
    
    // Count by status - more accurate status checking
    $status = strtolower($order['order_status'] ?? 'pending');
    if ($status === 'delivered' || $status === 'completed') {
        $orders_delivered++;
    } elseif (in_array($status, ['shipped', 'processing', 'in_transit'])) {
        $orders_in_transit++;
    }
    
    // Get order items for enhanced display
    $order_items = $order_class->get_order_items($order['order_id']);
    $order['items'] = $order_items;
    $orders_with_items[] = $order;
}

// Replace all_orders with enhanced version
$all_orders = $orders_with_items;

// Calculate average order value
$average_order_value = $total_orders > 0 ? $total_spent / $total_orders : 0;

// Get artisans from database
$artisan_class = new artisan_class();
$all_artisans = $artisan_class->get_all_artisans(100, 0);
$total_artisans = $artisan_class->get_total_artisans_count();

// Calculate unique artisans supported by this customer
// (artisans whose products the customer has ordered)
$artisans_supported = 0;
$supported_artisan_ids = [];
if (!empty($all_orders)) {
    foreach ($all_orders as $order) {
        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                // Get product's producer/artisan
                $product_sql = "SELECT producer_id, product_brand FROM products WHERE product_id = ?";
                $product = $db->fetchRow($product_sql, [$item['product_id']]);
                
                if ($product) {
                    $artisan_id = null;
                    // Check if product has producer_id
                    if (!empty($product['producer_id'])) {
                        $artisan_id = $product['producer_id'];
                    } elseif (!empty($product['product_brand'])) {
                        // Get brand owner
                        $brand_sql = "SELECT user_id FROM brands WHERE brand_id = ?";
                        $brand = $db->fetchRow($brand_sql, [$product['product_brand']]);
                        if ($brand) {
                            $artisan_id = $brand['user_id'];
                        }
                    }
                    
                    if ($artisan_id && !in_array($artisan_id, $supported_artisan_ids)) {
                        $supported_artisan_ids[] = $artisan_id;
                        $artisans_supported++;
                    }
                }
            }
        }
    }
}

// If no orders, show total available artisans
if ($artisans_supported == 0) {
    $artisans_supported = $total_artisans;
}

// Format chart data
$chart_months = [];
$chart_spending = [];
$chart_orders = [];

foreach ($monthly_spending as $month => $spending) {
    $chart_months[] = date('M', strtotime($month . '-01'));
    $chart_spending[] = $spending;
    $chart_orders[] = $monthly_orders[$month];
}

// Get recent 5 orders for display
$recent_orders = array_slice($all_orders, 0, 5);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="kentekart-dashboard">
    
    <!-- SIDEBAR NAVIGATION -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>Dashboard</h2>
            <p>Your artisan journey</p>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-item active" data-section="overview">
                <i class="fas fa-th"></i>
                <span>Overview</span>
            </button>
            <button class="nav-item" data-section="orders">
                <i class="fas fa-box"></i>
                <span>Orders</span>
            </button>
            <button class="nav-item" data-section="spending">
                <i class="fas fa-dollar-sign"></i>
                <span>Spending</span>
            </button>
            <button class="nav-item" data-section="artisans">
                <i class="fas fa-users"></i>
                <span>Artisans</span>
            </button>
            <button class="nav-item" data-section="insights">
                <i class="fas fa-chart-line"></i>
                <span>Insights</span>
            </button>
            <button class="nav-item" data-section="wishlist">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
            </button>
        </nav>

        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($customer['customer_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <p class="user-name"><?php echo escape_html($customer['customer_name']); ?></p>
                <p class="user-since">Member since <?php echo date('M Y', strtotime($customer['created_at'] ?? 'now')); ?></p>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        
        <!-- OVERVIEW SECTION -->
        <section id="section-overview" class="dashboard-section active">
            
            <!-- Header -->
            <header class="section-header-minimal">
                <div>
                    <p class="section-subtitle">Dashboard</p>
                    <h1 class="section-title">Your Journey</h1>
                </div>
                <div class="header-meta">
                    <p class="meta-label">Member since</p>
                    <p class="meta-value"><?php echo date('F Y', strtotime($customer['created_at'] ?? 'now')); ?></p>
                </div>
            </header>

            <!-- Key Stats -->
            <div class="stats-row">
                <div class="stat-column">
                    <p class="stat-number"><?php echo $total_orders; ?></p>
                    <p class="stat-label">Orders</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number">₵<?php echo number_format($total_spent, 0); ?></p>
                    <p class="stat-label">Total Spent</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number"><?php echo $artisans_supported; ?></p>
                    <p class="stat-label">Artisans</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number"><?php echo $orders_this_month; ?></p>
                    <p class="stat-label">This Month</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number">₵<?php echo number_format($average_order_value, 0); ?></p>
                    <p class="stat-label">Average</p>
                </div>
            </div>

            <!-- Insight Banner -->
            <div class="insight-banner">
                <i class="fas fa-award"></i>
                <div>
                    <p class="insight-main">You're <?php echo (10 - $artisans_supported); ?> orders away from supporting 10 unique artisans</p>
                    <p class="insight-sub">Your spending has increased this year as you discover new favorites</p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid-minimal">
                <!-- Spending Chart -->
                <div class="chart-box">
                    <div class="chart-box-header">
                        <h3>Spending Pattern</h3>
                        <p>Your purchasing activity over the last 6 months</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="spendingChartOverview"></canvas>
                    </div>
                </div>

                <!-- Categories Chart -->
                <div class="chart-box">
                    <div class="chart-box-header">
                        <h3>Category Breakdown</h3>
                        <p>Where you've invested in artisan crafts</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>

        </section>

        <!-- ORDERS SECTION -->
        <section id="section-orders" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Your Orders</h1>
                    <p class="section-subtitle">Complete history of your artisan purchases</p>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <select id="order-filter-status" class="btn-outline-minimal" style="padding: 8px 15px; border-radius: 4px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="order-sort" class="btn-outline-minimal" style="padding: 8px 15px; border-radius: 4px;">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="amount-high">Amount: High to Low</option>
                        <option value="amount-low">Amount: Low to High</option>
                    </select>
                </div>
            </header>

            <!-- Order Stats -->
            <div class="mini-stats-grid">
                <div class="mini-stat-card">
                    <i class="fas fa-box"></i>
                    <p class="mini-stat-label">Total Orders</p>
                    <p class="mini-stat-value"><?php echo $total_orders; ?></p>
                </div>
                <div class="mini-stat-card">
                    <i class="fas fa-clock"></i>
                    <p class="mini-stat-label">In Transit</p>
                    <p class="mini-stat-value"><?php echo $orders_in_transit; ?></p>
                </div>
                <div class="mini-stat-card">
                    <i class="fas fa-check-circle"></i>
                    <p class="mini-stat-label">Delivered</p>
                    <p class="mini-stat-value"><?php echo $orders_delivered; ?></p>
                </div>
                <div class="mini-stat-card">
                    <i class="fas fa-chart-line"></i>
                    <p class="mini-stat-label">This Month</p>
                    <p class="mini-stat-value"><?php echo $orders_this_month; ?></p>
                </div>
            </div>

            <!-- Order History Table -->
            <div class="orders-list-section" style="margin-top: 30px;">
                <h3 class="subsection-title">Order History</h3>
                
                <?php if (empty($all_orders)): ?>
                <div class="empty-state-minimal">
                    <i class="fas fa-box-open"></i>
                    <p>No orders yet</p>
                    <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn-minimal">Browse Products</a>
                </div>
                <?php else: ?>
                <div class="orders-table-container" style="overflow-x: auto;">
                    <table class="orders-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--warm-beige);">
                                <th style="padding: 15px; text-align: left; font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Order #</th>
                                <th style="padding: 15px; text-align: left; font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Date</th>
                                <th style="padding: 15px; text-align: left; font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Items</th>
                                <th style="padding: 15px; text-align: left; font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Amount</th>
                                <th style="padding: 15px; text-align: left; font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                                <th style="padding: 15px; text-align: right; font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-body">
                            <?php foreach ($all_orders as $order): 
                                $order_date = $order['order_date'] ?? $order['created_at'] ?? '';
                                $items_count = count($order['items'] ?? []);
                                $status = strtolower($order['order_status'] ?? 'pending');
                                $status_class = 'status-' . $status;
                                $status_label = ucfirst($status);
                                
                                // Status badge colors
                                $status_colors = [
                                    'pending' => '#F59E0B',
                                    'processing' => '#3B82F6',
                                    'shipped' => '#8B5CF6',
                                    'in_transit' => '#8B5CF6',
                                    'delivered' => '#10B981',
                                    'completed' => '#10B981',
                                    'cancelled' => '#EF4444'
                                ];
                                $status_color = $status_colors[$status] ?? '#6B7280';
                            ?>
                            <tr class="order-row" data-order-id="<?php echo $order['order_id']; ?>" data-status="<?php echo $status; ?>" data-amount="<?php echo $order['total_amount']; ?>" data-date="<?php echo strtotime($order_date); ?>" style="border-bottom: 1px solid var(--warm-beige); transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='var(--warm-beige)';" onmouseout="this.style.background='transparent';">
                                <td style="padding: 20px 15px;">
                                    <div>
                                        <p style="font-family: 'Cormorant Garamond', serif; font-size: 1.125rem; font-weight: 600; color: var(--text-dark); margin: 0;">
                                            #<?php echo $order['order_id']; ?>
                                        </p>
                                        <?php if (!empty($order['invoice_no'])): ?>
                                        <p style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light); margin: 5px 0 0 0;">
                                            Invoice: <?php echo escape_html($order['invoice_no']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 20px 15px;">
                                    <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 0;">
                                        <?php echo $order_date ? date('M j, Y', strtotime($order_date)) : 'N/A'; ?>
                                    </p>
                                    <p style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light); margin: 5px 0 0 0;">
                                        <?php echo $order_date ? date('g:i A', strtotime($order_date)) : ''; ?>
                                    </p>
                                </td>
                                <td style="padding: 20px 15px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if (!empty($order['items']) && count($order['items']) > 0): 
                                            $first_item = $order['items'][0];
                                        ?>
                                        <div style="display: flex; gap: -10px;">
                                            <?php foreach (array_slice($order['items'], 0, 3) as $item): ?>
                                            <img src="<?php echo !empty($item['product_image']) ? url($item['product_image']) : url('assets/images/placeholder-product.svg'); ?>" 
                                                 alt="<?php echo escape_html($item['product_title'] ?? 'Product'); ?>"
                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 2px solid var(--white); margin-left: -8px; background: var(--warm-beige);"
                                                 onerror="this.src='<?php echo url('assets/images/placeholder-product.svg'); ?>'">
                                            <?php endforeach; ?>
                                        </div>
                                        <div>
                                            <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 0; font-weight: 500;">
                                                <?php echo $items_count; ?> item<?php echo $items_count != 1 ? 's' : ''; ?>
                                            </p>
                                            <p style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light); margin: 3px 0 0 0;">
                                                <?php echo escape_html($first_item['product_title'] ?? 'Product'); ?>
                                                <?php if ($items_count > 1): ?>
                                                <span style="color: var(--text-light);">+<?php echo $items_count - 1; ?> more</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <?php else: ?>
                                        <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); margin: 0;">No items</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 20px 15px;">
                                    <p style="font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; font-weight: 600; color: var(--terracotta); margin: 0;">
                                        ₵<?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                                    </p>
                                </td>
                                <td style="padding: 20px 15px;">
                                    <span class="order-status-badge" style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-family: 'Spectral', serif; font-size: 0.75rem; font-weight: 500; background: <?php echo $status_color; ?>20; color: <?php echo $status_color; ?>; border: 1px solid <?php echo $status_color; ?>40;">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td style="padding: 20px 15px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)" class="btn-outline-minimal" style="padding: 8px 16px; font-size: 0.875rem;">
                                            View Details
                                        </button>
                                        <button onclick="trackOrder(<?php echo $order['order_id']; ?>)" class="btn-outline-minimal" style="padding: 8px 16px; font-size: 0.875rem;">
                                            Track
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="orders-list-section" style="margin-top: 40px;">
                <h3 class="subsection-title">Recent Activity</h3>
                
                <?php if (empty($all_orders)): ?>
                <div class="empty-state-minimal">
                    <i class="fas fa-box-open"></i>
                    <p>No recent activity</p>
                </div>
                <?php else: 
                    $recent_activities = [];
                    foreach (array_slice($all_orders, 0, 5) as $order) {
                        $order_date = $order['order_date'] ?? $order['created_at'] ?? '';
                        $recent_activities[] = [
                            'type' => 'order_placed',
                            'message' => 'Order #' . $order['order_id'] . ' placed',
                            'date' => $order_date,
                            'status' => $order['order_status'],
                            'amount' => $order['total_amount']
                        ];
                    }
                ?>
                <div class="activity-timeline" style="margin-top: 20px;">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item" style="display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid var(--warm-beige);">
                        <div class="activity-icon" style="width: 40px; height: 40px; border-radius: 50%; background: var(--warm-beige); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-box" style="color: var(--terracotta);"></i>
                        </div>
                        <div class="activity-content" style="flex: 1;">
                            <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 0; font-weight: 500;">
                                <?php echo escape_html($activity['message']); ?>
                            </p>
                            <p style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light); margin: 5px 0 0 0;">
                                <?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?> • 
                                <span style="color: var(--terracotta);">₵<?php echo number_format($activity['amount'], 2); ?></span> • 
                                <span style="text-transform: capitalize;"><?php echo escape_html($activity['status']); ?></span>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </section>

        <!-- SPENDING SECTION -->
        <section id="section-spending" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Spending Analysis</h1>
                    <p class="section-subtitle">Your investment in artisan craftsmanship</p>
                </div>
            </header>

            <!-- Spending Stats -->
            <div class="stats-row centered">
                <div class="stat-column">
                    <p class="stat-number large">₵<?php echo number_format($total_spent, 2); ?></p>
                    <p class="stat-label">Total Spent</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large">₵<?php echo number_format($spending_this_month, 2); ?></p>
                    <p class="stat-label">This Month</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large">₵<?php echo number_format($average_order_value, 2); ?></p>
                    <p class="stat-label">Average Order</p>
                </div>
            </div>

            <!-- Full Width Chart -->
            <div class="chart-box-full">
                <div class="chart-box-header">
                    <h3>Monthly Trends</h3>
                    <p>Your spending pattern over the last 6 months</p>
                </div>
                <div class="chart-wrapper-large">
                    <canvas id="spendingChartFull"></canvas>
                </div>
            </div>

        </section>

        <!-- ARTISANS SECTION -->
        <section id="section-artisans" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Artisan Community</h1>
                    <p class="section-subtitle">The makers you've supported on your journey</p>
                </div>
            </header>

            <!-- Artisan Stats -->
            <div class="stats-row centered">
                <div class="stat-column">
                    <p class="stat-number large"><?php echo $artisans_supported; ?></p>
                    <p class="stat-label">Artisans Supported</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large"><?php echo min($artisans_supported, 3); ?></p>
                    <p class="stat-label">Favorite Makers</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large">5</p>
                    <p class="stat-label">Countries</p>
                </div>
            </div>

            <!-- Artisans Grid -->
            <?php if (empty($all_artisans)): ?>
            <div class="empty-state-minimal">
                <i class="fas fa-users"></i>
                <p>No artisans available yet</p>
                <p class="text-muted">Check back soon for amazing artisans to support</p>
            </div>
            <?php else: ?>
            <div class="artisans-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <?php foreach ($all_artisans as $artisan): 
                    $profile_image = !empty($artisan['profile_image']) 
                        ? url('uploads/' . $artisan['profile_image']) 
                        : url('assets/images/placeholder-artisan.svg');
                    $business_name = !empty($artisan['business_name']) ? $artisan['business_name'] : $artisan['artisan_name'];
                ?>
                <div class="artisan-card" style="background: var(--color-white); border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(198, 125, 92, 0.1); transition: all 0.3s ease; border: 1px solid rgba(198, 125, 92, 0.1);">
                    <div class="artisan-header" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div class="artisan-avatar" style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; background: var(--color-warm-beige); flex-shrink: 0;">
                            <img src="<?php echo escape_html($profile_image); ?>" 
                                 alt="<?php echo escape_html($artisan['artisan_name']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='<?php echo url('assets/images/placeholder-artisan.svg'); ?>'">
                        </div>
                        <div class="artisan-info" style="flex: 1; min-width: 0;">
                            <h3 class="artisan-name" style="font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; font-weight: 600; color: var(--color-dark-brown); margin: 0 0 0.25rem 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo escape_html($business_name); ?>
                            </h3>
                            <p class="artisan-location" style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--color-light-brown); margin: 0;">
                                <?php echo escape_html($artisan['city'] ?? 'N/A'); ?>, <?php echo escape_html($artisan['country'] ?? 'Ghana'); ?>
                            </p>
                        </div>
                        <?php if ($artisan['featured']): ?>
                        <span class="featured-badge" style="display: inline-block; padding: 4px 8px; background: linear-gradient(135deg, #FFD700 0%, #D2691E 100%); color: white; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                            <i class="fas fa-star"></i> Featured
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($artisan['bio'])): ?>
                    <p class="artisan-bio" style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--color-dark-brown); margin: 0 0 1rem 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo escape_html($artisan['bio']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="artisan-stats" style="display: flex; gap: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(198, 125, 92, 0.1);">
                        <div class="stat-item">
                            <p class="stat-value" style="font-family: 'Cormorant Garamond', serif; font-size: 1.125rem; font-weight: 600; color: var(--color-terracotta); margin: 0;">
                                <?php echo $artisan['total_products'] ?? 0; ?>
                            </p>
                            <p class="stat-label" style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--color-light-brown); margin: 0;">
                                Products
                            </p>
                        </div>
                        <?php if ($artisan['rating'] > 0): ?>
                        <div class="stat-item">
                            <p class="stat-value" style="font-family: 'Cormorant Garamond', serif; font-size: 1.125rem; font-weight: 600; color: var(--color-terracotta); margin: 0;">
                                <?php echo number_format($artisan['rating'], 1); ?>
                            </p>
                            <p class="stat-label" style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--color-light-brown); margin: 0;">
                                Rating
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="artisan-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <a href="<?php echo url('view/product/all_product.php?artisan=' . $artisan['customer_id']); ?>" 
                           class="btn-view-products" 
                           style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #D2691E 0%, #B8621E 100%); color: white; border-radius: 6px; text-decoration: none; text-align: center; font-family: 'Spectral', serif; font-size: 0.875rem; font-weight: 500; transition: all 0.3s ease;">
                            View Products
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </section>

        <!-- INSIGHTS SECTION -->
        <section id="section-insights" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Personal Insights</h1>
                    <p class="section-subtitle">Understanding your shopping patterns</p>
                </div>
            </header>

            <!-- Insights Grid -->
            <div class="insights-grid">
                <div class="insight-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Growth Trajectory</h3>
                    <p>You've placed <?php echo $total_orders; ?> orders, spending ₵<?php echo number_format($total_spent, 2); ?>. Your average order value is ₵<?php echo number_format($average_order_value, 2); ?>.</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Average per order</p>
                        <p class="footer-value">₵<?php echo number_format($average_order_value, 2); ?></p>
                    </div>
                </div>

                <div class="insight-card">
                    <i class="fas fa-calendar"></i>
                    <h3>This Month's Activity</h3>
                    <p>You've made <?php echo $orders_this_month; ?> orders in <?php echo date('F'); ?>, spending ₵<?php echo number_format($spending_this_month, 2); ?>. Keep discovering amazing artisan crafts!</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Most active month</p>
                        <p class="footer-value"><?php echo date('F'); ?></p>
                    </div>
                </div>

                <div class="insight-card">
                    <i class="fas fa-heart"></i>
                    <h3>Artisan Support</h3>
                    <p>You've supported <?php echo $artisans_supported; ?> unique artisan<?php echo $artisans_supported != 1 ? 's' : ''; ?>. Each purchase directly supports Ghanaian craftspeople and their families.</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Artisans supported</p>
                        <p class="footer-value"><?php echo $artisans_supported; ?></p>
                    </div>
                </div>

                <div class="insight-card">
                    <i class="fas fa-award"></i>
                    <h3>Milestone Progress</h3>
                    <p>You're <?php echo (10 - $artisans_supported); ?> orders away from supporting 10 unique artisans. This milestone unlocks special recognition in our community.</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Next milestone</p>
                        <p class="footer-value">10 Artisans</p>
                    </div>
                </div>
            </div>

        </section>

        <!-- WISHLIST SECTION -->
        <section id="section-wishlist" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">My Wishlist</h1>
                    <p class="section-subtitle">Your curated collection of favorites</p>
                    <p class="wishlist-count-text" style="margin-top: 10px; color: var(--text-light); font-size: 0.875rem;">
                        <span id="dashboard-item-count">0</span> items saved
                    </p>
                </div>
            </header>

            <!-- Actions Bar -->
            <div class="wishlist-actions" style="margin-bottom: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" id="dashboard-clear-wishlist">
                    <i class="fas fa-trash"></i> Clear All
                </button>
                <button type="button" class="btn btn-primary" id="dashboard-add-all-to-cart">
                    <i class="fas fa-shopping-cart"></i> Add All to Cart
                </button>
            </div>

            <!-- Wishlist Grid -->
            <div class="wishlist-grid" id="dashboard-wishlist-grid" style="display: none;">
                <!-- Wishlist items will be loaded here by JavaScript -->
            </div>

            <!-- Empty Wishlist State -->
            <div class="empty-wishlist" id="dashboard-empty-wishlist">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </div>
                <h3>Your Wishlist is Empty</h3>
                <p>Save items you love to shop later</p>
                <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-primary">
                    Browse Products
                </a>
            </div>

        </section>

    </main>
    
</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(58, 47, 38, 0.7); z-index: 10000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
    <div class="modal-content" style="background: var(--white); max-width: 900px; width: 100%; max-height: 90vh; overflow-y: auto; border-radius: var(--radius-md); position: relative; box-shadow: 0 20px 60px rgba(139, 111, 71, 0.2);">
        <button onclick="closeOrderModal()" class="modal-close" style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; border: none; background: transparent; cursor: pointer; font-size: 28px; color: var(--text-light); z-index: 10;">
            &times;
        </button>
        <div id="order-details-content" style="padding: 40px;">
            <!-- Content will be loaded via AJAX -->
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--terracotta);"></i>
                <p style="margin-top: 15px; color: var(--text-light);">Loading order details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Order Tracking Modal -->
<div id="order-tracking-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(58, 47, 38, 0.7); z-index: 10000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
    <div class="modal-content" style="background: var(--white); max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; border-radius: var(--radius-md); position: relative; box-shadow: 0 20px 60px rgba(139, 111, 71, 0.2);">
        <button onclick="closeTrackingModal()" class="modal-close" style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; border: none; background: transparent; cursor: pointer; font-size: 28px; color: var(--text-light); z-index: 10;">
            &times;
        </button>
        <div id="order-tracking-content" style="padding: 40px;">
            <!-- Content will be loaded via AJAX -->
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--terracotta);"></i>
                <p style="margin-top: 15px; color: var(--text-light);">Loading tracking information...</p>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* Order table styles */
.orders-table-container {
    border: 1px solid var(--warm-beige);
    border-radius: 4px;
    overflow: hidden;
}

.orders-table {
    background: var(--white);
}

.orders-table thead {
    background: var(--warm-beige);
}

.orders-table tbody tr:hover {
    background: var(--warm-beige) !important;
}

.order-status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-family: 'Spectral', serif;
    font-size: 0.75rem;
    font-weight: 500;
}

.modal-overlay {
    animation: fadeIn 0.3s ease;
}

.modal-content {
    animation: slideUp 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .orders-table {
        font-size: 0.875rem;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 10px 8px !important;
    }
    
    .orders-table th:nth-child(3),
    .orders-table td:nth-child(3) {
        display: none;
    }
}
</style>

<!-- Wishlist JavaScript -->
<script>
    // Define BASE_URL and ASSETS_URL for wishlist.js if not already defined
    if (typeof BASE_URL === 'undefined') {
        var BASE_URL = '<?php echo BASE_URL; ?>';
    }
    if (typeof ASSETS_URL === 'undefined') {
        var ASSETS_URL = '<?php echo ASSETS_URL; ?>';
    }
</script>
<script src="<?php echo ASSETS_URL; ?>/js/wishlist.js?v=<?php echo time(); ?>"></script>

<!-- Dashboard Script -->
<script>
// Dashboard data from PHP
const dashboardData = {
    chartMonths: <?php echo json_encode($chart_months); ?>,
    chartSpending: <?php echo json_encode($chart_spending); ?>,
    chartOrders: <?php echo json_encode($chart_orders); ?>,
    totalOrders: <?php echo $total_orders; ?>,
    totalSpent: <?php echo $total_spent; ?>
};

// KenteKart colors
const colors = {
    terracotta: '#C67D5C',
    deepBrown: '#8B6F47',
    lightBrown: '#8B7F74',
    warmBeige: '#F4EDE4'
};

// Navigation functionality
document.querySelectorAll('.nav-item').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all buttons and sections
        document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.dashboard-section').forEach(section => section.classList.remove('active'));
        
        // Add active class to clicked button
        this.classList.add('active');
        
        // Show corresponding section
        const sectionId = 'section-' + this.dataset.section;
        const section = document.getElementById(sectionId);
        if (section) {
            section.classList.add('active');
            
            // If wishlist section, load wishlist content
            if (this.dataset.section === 'wishlist') {
                setTimeout(() => {
                    if (typeof loadDashboardWishlist === 'function') {
                        loadDashboardWishlist();
                    } else if (typeof loadWishlistPage === 'function') {
                        loadWishlistPage();
                    }
                }, 100);
            }
        }
    });
});

// Check URL hash or query parameter for section navigation
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const sectionParam = urlParams.get('section');
    const hash = window.location.hash.replace('#', '');
    
    let targetSection = sectionParam || hash || null;
    
    if (targetSection) {
        // Remove 'section-' prefix if present
        targetSection = targetSection.replace('section-', '');
        
        // Find and click the corresponding nav button
        const navButton = document.querySelector(`.nav-item[data-section="${targetSection}"]`);
        if (navButton) {
            navButton.click();
        }
    }
});

// Create Spending Chart (Overview)
function createSpendingChartOverview() {
    const ctx = document.getElementById('spendingChartOverview');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: dashboardData.chartMonths,
            datasets: [{
                label: 'Spending',
                data: dashboardData.chartSpending,
                borderColor: colors.terracotta,
                backgroundColor: 'rgba(198, 125, 92, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.terracotta,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#3A2F26',
                    bodyColor: '#3A2F26',
                    borderColor: colors.terracotta,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => '₵' + context.parsed.y.toFixed(2)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 },
                        callback: (value) => '₵' + value
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 }
                    }
                }
            }
        }
    });
}

// Create Categories Chart
function createCategoriesChart() {
    const ctx = document.getElementById('categoriesChart');
    if (!ctx) return;
    
    // Placeholder category data
    const categories = ['Jewelry', 'Pottery', 'Textiles', 'Art', 'Home'];
    const values = [420, 340, 280, 180, 120];
    
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Spent',
                data: values,
                backgroundColor: colors.deepBrown,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#3A2F26',
                    bodyColor: '#3A2F26',
                    borderColor: colors.terracotta,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => '₵' + context.parsed.y
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 }
                    }
                }
            }
        }
    });
}

// Create Full Spending Chart
function createSpendingChartFull() {
    const ctx = document.getElementById('spendingChartFull');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: dashboardData.chartMonths,
            datasets: [{
                label: 'Spending',
                data: dashboardData.chartSpending,
                borderColor: colors.terracotta,
                backgroundColor: 'rgba(198, 125, 92, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.terracotta,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#3A2F26',
                    bodyColor: '#3A2F26',
                    borderColor: colors.terracotta,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => 'Spent: ₵' + context.parsed.y.toFixed(2)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 12 },
                        callback: (value) => '₵' + value
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 12 }
                    }
                }
            }
        }
    });
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    createSpendingChartOverview();
    createCategoriesChart();
    createSpendingChartFull();
    
    // Initialize wishlist functionality for dashboard
    initializeDashboardWishlist();
    
    // Initialize orders functionality
    initializeOrdersSection();
});

/**
 * Initialize orders section functionality
 */
function initializeOrdersSection() {
    // Filter by status
    const statusFilter = document.getElementById('order-filter-status');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterAndSortOrders();
        });
    }
    
    // Sort orders
    const sortSelect = document.getElementById('order-sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            filterAndSortOrders();
        });
    }
    
    // Close modals on outside click
    const orderModal = document.getElementById('order-details-modal');
    const trackingModal = document.getElementById('order-tracking-modal');
    
    if (orderModal) {
        orderModal.addEventListener('click', function(e) {
            if (e.target === orderModal) {
                closeOrderModal();
            }
        });
    }
    
    if (trackingModal) {
        trackingModal.addEventListener('click', function(e) {
            if (e.target === trackingModal) {
                closeTrackingModal();
            }
        });
    }
}

/**
 * Filter and sort orders
 */
function filterAndSortOrders() {
    const statusFilter = document.getElementById('order-filter-status')?.value || '';
    const sortSelect = document.getElementById('order-sort')?.value || 'newest';
    const rows = document.querySelectorAll('.order-row');
    const tbody = document.getElementById('orders-table-body');
    
    if (!tbody) return;
    
    let filteredRows = Array.from(rows);
    
    // Filter by status
    if (statusFilter) {
        filteredRows = filteredRows.filter(row => {
            return row.getAttribute('data-status') === statusFilter;
        });
    }
    
    // Sort orders
    filteredRows.sort((a, b) => {
        switch(sortSelect) {
            case 'oldest':
                return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
            case 'amount-high':
                return parseFloat(b.getAttribute('data-amount')) - parseFloat(a.getAttribute('data-amount'));
            case 'amount-low':
                return parseFloat(a.getAttribute('data-amount')) - parseFloat(b.getAttribute('data-amount'));
            case 'newest':
            default:
                return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
        }
    });
    
    // Clear and re-append sorted rows
    tbody.innerHTML = '';
    filteredRows.forEach(row => tbody.appendChild(row));
    
    // Show empty message if no results
    if (filteredRows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">No orders found matching your criteria.</td></tr>';
    }
}

/**
 * View order details
 */
function viewOrderDetails(orderId) {
    const modal = document.getElementById('order-details-modal');
    const content = document.getElementById('order-details-content');
    
    if (!modal || !content) return;
    
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--terracotta);"></i><p style="margin-top: 15px; color: var(--text-light);">Loading order details...</p></div>';
    
    // Fetch order details via AJAX
    fetch('<?php echo url('actions/get_order_details.php'); ?>?order_id=' + orderId, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = generateOrderDetailsHTML(data.order, data.items);
        } else {
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><p style="color: var(--terracotta);">' + (data.message || 'Error loading order details') + '</p></div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = '<div style="text-align: center; padding: 40px;"><p style="color: var(--terracotta);">Error loading order details. Please try again.</p></div>';
    });
}

/**
 * Track order
 */
function trackOrder(orderId) {
    const modal = document.getElementById('order-tracking-modal');
    const content = document.getElementById('order-tracking-content');
    
    if (!modal || !content) return;
    
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--terracotta);"></i><p style="margin-top: 15px; color: var(--text-light);">Loading tracking information...</p></div>';
    
    // Fetch order tracking info via AJAX
    fetch('<?php echo url('actions/get_order_tracking.php'); ?>?order_id=' + orderId, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = generateOrderTrackingHTML(data.order);
        } else {
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><p style="color: var(--terracotta);">' + (data.message || 'Error loading tracking information') + '</p></div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = '<div style="text-align: center; padding: 40px;"><p style="color: var(--terracotta);">Error loading tracking information. Please try again.</p></div>';
    });
}

/**
 * Generate order details HTML
 */
function generateOrderDetailsHTML(order, items) {
    const statusColors = {
        'pending': '#F59E0B',
        'processing': '#3B82F6',
        'shipped': '#8B5CF6',
        'in_transit': '#8B5CF6',
        'delivered': '#10B981',
        'completed': '#10B981',
        'cancelled': '#EF4444'
    };
    const status = (order.order_status || 'pending').toLowerCase();
    const statusColor = statusColors[status] || '#6B7280';
    
    let itemsHTML = '';
    if (items && items.length > 0) {
        itemsHTML = items.map(item => `
            <div style="display: flex; gap: 15px; padding: 15px; border-bottom: 1px solid var(--warm-beige);">
                <img src="${item.product_image || '<?php echo url('assets/images/placeholder-product.svg'); ?>'}" 
                     alt="${item.product_title || 'Product'}"
                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; background: var(--warm-beige);"
                     onerror="this.src='<?php echo url('assets/images/placeholder-product.svg'); ?>'">
                <div style="flex: 1;">
                    <h4 style="font-family: 'Cormorant Garamond', serif; font-size: 1.125rem; margin: 0 0 5px 0; color: var(--text-dark);">${item.product_title || 'Product'}</h4>
                    <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); margin: 0;">Quantity: ${item.quantity}</p>
                    <p style="font-family: 'Cormorant Garamond', serif; font-size: 1rem; color: var(--terracotta); margin: 5px 0 0 0; font-weight: 600;">₵${parseFloat(item.price).toFixed(2)} each</p>
                </div>
                <div style="text-align: right;">
                    <p style="font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; color: var(--text-dark); margin: 0; font-weight: 600;">₵${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</p>
                </div>
            </div>
        `).join('');
    }
    
    return `
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; margin: 0 0 30px 0; color: var(--text-dark);">Order Details</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <div>
                <h3 style="font-family: 'Spectral', serif; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-light); margin: 0 0 10px 0;">Order Information</h3>
                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 5px 0;"><strong>Order #:</strong> ${order.order_id}</p>
                ${order.invoice_no ? `<p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 5px 0;"><strong>Invoice #:</strong> ${order.invoice_no}</p>` : ''}
                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 5px 0;"><strong>Date:</strong> ${new Date(order.order_date || order.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>
                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 5px 0;"><strong>Status:</strong> 
                    <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; background: ${statusColor}20; color: ${statusColor}; border: 1px solid ${statusColor}40;">
                        ${(order.order_status || 'pending').charAt(0).toUpperCase() + (order.order_status || 'pending').slice(1)}
                    </span>
                </p>
            </div>
            <div>
                <h3 style="font-family: 'Spectral', serif; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-light); margin: 0 0 10px 0;">Payment & Shipping</h3>
                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 5px 0;"><strong>Payment Method:</strong> ${(order.payment_method || 'N/A').charAt(0).toUpperCase() + (order.payment_method || 'N/A').slice(1)}</p>
                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 5px 0;"><strong>Shipping Address:</strong></p>
                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); margin: 5px 0; white-space: pre-line;">${order.shipping_address || 'N/A'}</p>
            </div>
        </div>
        
        <div style="margin-bottom: 30px;">
            <h3 style="font-family: 'Spectral', serif; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-light); margin: 0 0 15px 0;">Order Items</h3>
            <div style="border: 1px solid var(--warm-beige); border-radius: 4px; overflow: hidden;">
                ${itemsHTML || '<p style="padding: 20px; text-align: center; color: var(--text-light);">No items found</p>'}
            </div>
        </div>
        
        <div style="border-top: 2px solid var(--warm-beige); padding-top: 20px; text-align: right;">
            <div style="margin-bottom: 10px;">
                <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 5px 0; display: flex; justify-content: space-between;">
                    <span>Subtotal:</span>
                    <span>₵${parseFloat(order.total_amount || 0).toFixed(2)}</span>
                </p>
            </div>
            <div style="border-top: 1px solid var(--warm-beige); padding-top: 10px; margin-top: 10px;">
                <p style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--terracotta); margin: 0; font-weight: 600; display: flex; justify-content: space-between;">
                    <span>Total:</span>
                    <span>₵${parseFloat(order.total_amount || 0).toFixed(2)}</span>
                </p>
            </div>
        </div>
    `;
}

/**
 * Generate order tracking HTML
 */
function generateOrderTrackingHTML(order) {
    const status = (order.order_status || 'pending').toLowerCase();
    const statusSteps = [
        { key: 'pending', label: 'Order Placed', icon: 'fa-shopping-cart' },
        { key: 'processing', label: 'Processing', icon: 'fa-cog' },
        { key: 'shipped', label: 'Shipped', icon: 'fa-shipping-fast' },
        { key: 'delivered', label: 'Delivered', icon: 'fa-check-circle' }
    ];
    
    const currentStepIndex = statusSteps.findIndex(step => step.key === status);
    const completedSteps = currentStepIndex >= 0 ? currentStepIndex + 1 : 0;
    
    let timelineHTML = statusSteps.map((step, index) => {
        const isCompleted = index <= currentStepIndex;
        const isCurrent = index === currentStepIndex;
        const stepColor = isCompleted ? 'var(--terracotta)' : 'var(--text-light)';
        
        return `
            <div style="display: flex; gap: 20px; position: relative; padding-bottom: 30px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: ${isCompleted ? 'var(--terracotta)' : 'var(--warm-beige)'}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; z-index: 2;">
                    <i class="fas ${step.icon}" style="color: ${isCompleted ? 'var(--white)' : 'var(--text-light)'};"></i>
                </div>
                <div style="flex: 1; padding-top: 8px;">
                    <p style="font-family: 'Cormorant Garamond', serif; font-size: 1.125rem; color: ${stepColor}; margin: 0 0 5px 0; font-weight: ${isCompleted ? '600' : '400'};">
                        ${step.label}
                    </p>
                    ${isCurrent ? `<p style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light); margin: 0;">Current status</p>` : ''}
                </div>
            </div>
        `;
    }).join('');
    
    return `
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; margin: 0 0 30px 0; color: var(--text-dark);">Order Tracking</h2>
        
        <div style="margin-bottom: 30px; padding: 20px; background: var(--warm-beige); border-radius: 4px;">
            <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 0 0 10px 0;"><strong>Order #:</strong> ${order.order_id}</p>
            ${order.invoice_no ? `<p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 0 0 10px 0;"><strong>Invoice #:</strong> ${order.invoice_no}</p>` : ''}
            <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 0;"><strong>Status:</strong> 
                <span style="text-transform: capitalize; color: var(--terracotta); font-weight: 600;">${order.order_status || 'pending'}</span>
            </p>
        </div>
        
        <div style="position: relative; padding-left: 20px;">
            <div style="position: absolute; left: 39px; top: 40px; bottom: 0; width: 2px; background: var(--warm-beige);"></div>
            ${timelineHTML}
        </div>
        
        ${order.shipping_address ? `
        <div style="margin-top: 30px; padding: 20px; background: var(--warm-beige); border-radius: 4px;">
            <h3 style="font-family: 'Spectral', serif; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-light); margin: 0 0 10px 0;">Shipping Address</h3>
            <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-dark); margin: 0; white-space: pre-line;">${order.shipping_address}</p>
        </div>
        ` : ''}
    `;
}

/**
 * Close order modal
 */
function closeOrderModal() {
    const modal = document.getElementById('order-details-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Close tracking modal
 */
function closeTrackingModal() {
    const modal = document.getElementById('order-tracking-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Initialize wishlist functionality in dashboard
 */
function initializeDashboardWishlist() {
    // Load wishlist when wishlist section is shown
    const wishlistNavBtn = document.querySelector('.nav-item[data-section="wishlist"]');
    if (wishlistNavBtn) {
        wishlistNavBtn.addEventListener('click', function() {
            // Small delay to ensure section is visible
            setTimeout(() => {
                loadDashboardWishlist();
            }, 100);
        });
    }
    
    // Also load if wishlist section is already active (e.g., direct link)
    const wishlistSection = document.getElementById('section-wishlist');
    if (wishlistSection && wishlistSection.classList.contains('active')) {
        loadDashboardWishlist();
    }
    
    // Setup dashboard-specific wishlist buttons
    const clearBtn = document.getElementById('dashboard-clear-wishlist');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (typeof handleClearWishlist === 'function') {
                handleClearWishlist();
                setTimeout(() => loadDashboardWishlist(), 100);
            }
        });
    }
    
    const addAllBtn = document.getElementById('dashboard-add-all-to-cart');
    if (addAllBtn) {
        addAllBtn.addEventListener('click', function() {
            if (typeof handleAddAllToCart === 'function') {
                handleAddAllToCart();
            }
        });
    }
}

/**
 * Load wishlist content in dashboard
 */
function loadDashboardWishlist() {
    // Check if wishlist functions are available
    if (typeof getWishlistItems !== 'function' || typeof createWishlistItemHTML !== 'function') {
        console.warn('Wishlist functions not loaded. Make sure wishlist.js is included.');
        return;
    }
    
    const wishlist = getWishlistItems();
    const container = document.getElementById('dashboard-wishlist-grid');
    const emptyState = document.getElementById('dashboard-empty-wishlist');
    const itemCount = document.getElementById('dashboard-item-count');
    
    if (itemCount) {
        itemCount.textContent = wishlist.length;
    }
    
    if (wishlist.length === 0) {
        if (container) container.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        return;
    }
    
    if (container) container.style.display = 'grid';
    if (emptyState) emptyState.style.display = 'none';
    
    if (container) {
        container.innerHTML = wishlist.map(item => createWishlistItemHTML(item)).join('');
        
        // Re-attach event listeners
        container.querySelectorAll('.remove-wishlist-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = btn.getAttribute('data-product-id');
                if (productId && typeof removeFromWishlist === 'function') {
                    removeFromWishlist(productId);
                    setTimeout(() => loadDashboardWishlist(), 100);
                }
            });
        });
        
        container.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = btn.getAttribute('data-product-id');
                if (productId && typeof handleAddToCartFromWishlist === 'function') {
                    handleAddToCartFromWishlist(e);
                }
            });
        });
    }
}
</script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>