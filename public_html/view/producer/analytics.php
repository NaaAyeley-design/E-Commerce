<?php
/**
 * Analytics Page for Producers
 * 
 * Displays business analytics, sales trends, and performance metrics
 */

require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../class/order_class.php';
require_once __DIR__ . '/../../class/product_class.php';
require_once __DIR__ . '/../../class/user_class.php';
require_once __DIR__ . '/../../class/db_class.php';

// Set page variables
$page_title = 'Analytics - Producer Dashboard';
$page_description = 'View your business analytics and performance';
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

// Get filter parameters
$filter_period = $_GET['period'] ?? '30'; // 7, 30, 90, 365, all

// Get producer's brand IDs
$db = new db_class();
$brand_ids_sql = "SELECT brand_id FROM brands WHERE user_id = ?";
$producer_brands = $db->fetchAll($brand_ids_sql, [$producer_id]);
$brand_ids = array_column($producer_brands, 'brand_id');

// Check if producer_id column exists
$check_producer_id = $db->fetchRow("SHOW COLUMNS FROM products LIKE 'producer_id'");

// Build base where condition
$where_conditions = [];
$params = [];

if ($check_producer_id && !empty($brand_ids)) {
    $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
    $where_conditions[] = "(p.producer_id = ? OR p.product_brand IN ($placeholders))";
    $params = array_merge([$producer_id], $brand_ids);
} elseif ($check_producer_id) {
    $where_conditions[] = "p.producer_id = ?";
    $params = [$producer_id];
} elseif (!empty($brand_ids)) {
    $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
    $where_conditions[] = "p.product_brand IN ($placeholders)";
    $params = $brand_ids;
} else {
    $where_conditions[] = "1 = 0";
    $params = [];
}

$base_where = implode(' AND ', $where_conditions);

// Get sales data for charts
$sales_data = [];
$revenue_data = [];
$product_sales = [];

if (!empty($params) && $filter_period !== 'all') {
    $days = (int)$filter_period;
    $date_from = date('Y-m-d', strtotime("-$days days"));
    
    $sales_sql = "SELECT DATE(o.order_date) as sale_date, 
                         COUNT(DISTINCT o.order_id) as order_count,
                         SUM(oi.price * oi.quantity) as daily_revenue
                  FROM orders o
                  INNER JOIN order_items oi ON o.order_id = oi.order_id
                  INNER JOIN products p ON oi.product_id = p.product_id
                  WHERE $base_where
                  AND o.order_date >= ?
                  AND o.order_status IN ('completed', 'delivered')
                  GROUP BY DATE(o.order_date)
                  ORDER BY sale_date ASC";
    
    $sales_params = array_merge($params, [$date_from]);
    $sales_data = $db->fetchAll($sales_sql, $sales_params);
} elseif (!empty($params)) {
    $sales_sql = "SELECT DATE(o.order_date) as sale_date, 
                         COUNT(DISTINCT o.order_id) as order_count,
                         SUM(oi.price * oi.quantity) as daily_revenue
                  FROM orders o
                  INNER JOIN order_items oi ON o.order_id = oi.order_id
                  INNER JOIN products p ON oi.product_id = p.product_id
                  WHERE $base_where
                  AND o.order_status IN ('completed', 'delivered')
                  GROUP BY DATE(o.order_date)
                  ORDER BY sale_date ASC";
    
    $sales_data = $db->fetchAll($sales_sql, $params);
}

// Get top selling products
if (!empty($params)) {
    $top_products_sql = "SELECT p.product_id, p.product_title, p.product_image,
                                SUM(oi.quantity) as total_sold,
                                SUM(oi.price * oi.quantity) as total_revenue
                         FROM products p
                         INNER JOIN order_items oi ON p.product_id = oi.product_id
                         INNER JOIN orders o ON oi.order_id = o.order_id
                         WHERE $base_where
                         AND o.order_status IN ('completed', 'delivered')
                         GROUP BY p.product_id, p.product_title, p.product_image
                         ORDER BY total_sold DESC
                         LIMIT 10";
    
    $product_sales = $db->fetchAll($top_products_sql, $params);
}

// Calculate summary metrics
$total_orders = 0;
$total_revenue = 0;
$average_order_value = 0;
$total_products_sold = 0;

if (!empty($sales_data)) {
    foreach ($sales_data as $day) {
        $total_orders += (int)$day['order_count'];
        $total_revenue += (float)$day['daily_revenue'];
    }
    
    if ($total_orders > 0) {
        $average_order_value = $total_revenue / $total_orders;
    }
}

if (!empty($product_sales)) {
    foreach ($product_sales as $product) {
        $total_products_sold += (int)$product['total_sold'];
    }
}

// Prepare chart data
$chart_labels = [];
$chart_sales = [];
$chart_revenue = [];

foreach ($sales_data as $day) {
    $chart_labels[] = date('M j', strtotime($day['sale_date']));
    $chart_sales[] = (int)$day['order_count'];
    $chart_revenue[] = (float)$day['daily_revenue'];
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<style>
/* Reuse sidebar styles */
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

/* Analytics Header */
.analytics-header {
    background: var(--white);
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.analytics-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.analytics-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.filters-row {
    display: flex;
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    color: var(--text-dark);
    margin-bottom: 5px;
}

.filter-group select {
    padding: 10px 12px;
    border: 2px solid #E8DDD0;
    border-radius: 6px;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
}

.filter-group select:focus {
    outline: none;
    border-color: #D2691E;
}

.btn-filter {
    padding: 10px 20px;
    background: #D2691E;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Spectral', serif;
    font-weight: 500;
}

.btn-filter:hover {
    background: #B8621E;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--white);
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-card h3 {
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    color: var(--text-light);
    margin: 0 0 10px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card .stat-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.stat-card .stat-icon {
    float: right;
    font-size: 2rem;
    color: var(--terracotta);
    opacity: 0.3;
}

/* Chart Container */
.chart-container {
    background: var(--white);
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.chart-container h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 20px 0;
}

.chart-wrapper {
    position: relative;
    height: 300px;
    margin-top: 20px;
}

.chart-placeholder {
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F9F7F4;
    border-radius: 6px;
    color: var(--text-light);
    font-family: 'Spectral', serif;
}

/* Top Products */
.top-products {
    background: var(--white);
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.top-products h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 20px 0;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #E8DDD0;
}

.product-item:last-child {
    border-bottom: none;
}

.product-item-image {
    width: 60px;
    height: 60px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
}

.product-item-details {
    flex: 1;
}

.product-item-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 5px 0;
}

.product-item-stats {
    font-family: 'Spectral', serif;
    font-size: 0.85rem;
    color: var(--text-light);
}

.product-item-revenue {
    font-family: 'Spectral', serif;
    font-size: 1rem;
    font-weight: 600;
    color: #10B981;
    text-align: right;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.empty-state i {
    font-size: 4rem;
    color: #E8DDD0;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--text-dark);
}

.empty-state p {
    font-family: 'Spectral', serif;
    color: var(--text-light);
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
            <a href="<?php echo url('view/producer/analytics.php'); ?>" class="nav-item active">
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
            
            <!-- Analytics Header -->
            <div class="analytics-header">
                <div class="analytics-header-top">
                    <h1>Analytics</h1>
                </div>
                
                <form method="GET" action="" class="filters-row">
                    <div class="filter-group">
                        <label>Time Period</label>
                        <select name="period">
                            <option value="7" <?php echo $filter_period === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $filter_period === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $filter_period === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="365" <?php echo $filter_period === '365' ? 'selected' : ''; ?>>Last Year</option>
                            <option value="all" <?php echo $filter_period === 'all' ? 'selected' : ''; ?>>All Time</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart stat-icon"></i>
                    <h3>Total Orders</h3>
                    <p class="stat-value"><?php echo number_format($total_orders); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign stat-icon"></i>
                    <h3>Total Revenue</h3>
                    <p class="stat-value">GHS <?php echo number_format($total_revenue, 2); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calculator stat-icon"></i>
                    <h3>Avg Order Value</h3>
                    <p class="stat-value">GHS <?php echo number_format($average_order_value, 2); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-box stat-icon"></i>
                    <h3>Products Sold</h3>
                    <p class="stat-value"><?php echo number_format($total_products_sold); ?></p>
                </div>
            </div>

            <!-- Sales Chart -->
            <div class="chart-container">
                <h2>Sales & Revenue Trend</h2>
                <div class="chart-wrapper">
                    <?php if (empty($sales_data)): ?>
                        <div class="chart-placeholder">
                            <div>
                                <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 10px;"></i>
                                <p>No sales data available for the selected period</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <canvas id="salesChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Products -->
            <div class="top-products">
                <h2>Top Selling Products</h2>
                <?php if (empty($product_sales)): ?>
                    <div class="empty-state" style="padding: 40px 20px;">
                        <i class="fas fa-box-open"></i>
                        <p>No product sales data available</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($product_sales as $product): ?>
                        <div class="product-item">
                            <?php
                            $image_url = ASSETS_URL . '/images/placeholder-product.svg';
                            if (!empty($product['product_image'])) {
                                $img_path = ltrim($product['product_image'], '/');
                                if (strpos($img_path, 'http') === 0) {
                                    $image_url = $img_path;
                                } else {
                                    $image_url = BASE_URL . '/' . $img_path;
                                }
                            }
                            ?>
                            <img src="<?php echo $image_url; ?>" alt="<?php echo escape_html($product['product_title']); ?>" class="product-item-image">
                            <div class="product-item-details">
                                <h4 class="product-item-title"><?php echo escape_html($product['product_title']); ?></h4>
                                <p class="product-item-stats"><?php echo number_format($product['total_sold']); ?> units sold</p>
                            </div>
                            <div class="product-item-revenue">
                                GHS <?php echo number_format($product['total_revenue'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php if (!empty($sales_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [
            {
                label: 'Orders',
                data: <?php echo json_encode($chart_sales); ?>,
                borderColor: '#D2691E',
                backgroundColor: 'rgba(210, 105, 30, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Revenue (GHS)',
                data: <?php echo json_encode($chart_revenue); ?>,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Orders'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Revenue (GHS)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

