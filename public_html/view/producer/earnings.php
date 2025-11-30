<?php
/**
 * Earnings Management Page for Producers
 * 
 * Displays earnings, revenue, and payout information for the logged-in producer
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../class/order_class.php';
require_once __DIR__ . '/../../../class/product_class.php';
require_once __DIR__ . '/../../../class/user_class.php';
require_once __DIR__ . '/../../../class/db_class.php';

// Set page variables
$page_title = 'My Earnings - Producer Dashboard';
$page_description = 'View your earnings and revenue';
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
$filter_period = $_GET['period'] ?? 'all'; // all, this_month, last_month, this_year
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get producer's brand IDs
$db = new db_class();
$brand_ids_sql = "SELECT brand_id FROM brands WHERE user_id = ?";
$producer_brands = $db->fetchAll($brand_ids_sql, [$producer_id]);
$brand_ids = array_column($producer_brands, 'brand_id');

// Check if producer_id column exists
$check_producer_id = $db->fetchRow("SHOW COLUMNS FROM products LIKE 'producer_id'");

// Build base where condition for producer's products
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

// Calculate earnings metrics
$total_earnings = 0;
$pending_earnings = 0;
$this_month_earnings = 0;
$last_month_earnings = 0;
$this_year_earnings = 0;

if (!empty($params)) {
    // Get all orders with producer's products
    $orders_sql = "SELECT DISTINCT o.order_id, o.order_status, o.order_date, o.created_at
                   FROM orders o
                   INNER JOIN order_items oi ON o.order_id = oi.order_id
                   INNER JOIN products p ON oi.product_id = p.product_id
                   WHERE $base_where
                   ORDER BY o.created_at DESC";
    
    $all_orders = $db->fetchAll($orders_sql, $params);
    
    $current_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    $current_year = date('Y');
    
    foreach ($all_orders as $order) {
        $order_id = $order['order_id'];
        $order_status = strtolower($order['order_status'] ?? 'pending');
        $order_date = $order['order_date'] ?? $order['created_at'] ?? '';
        
        // Get order items for this producer's products
        if ($check_producer_id && !empty($brand_ids)) {
            $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
            $items_sql = "SELECT oi.price, oi.quantity 
                         FROM order_items oi
                         INNER JOIN products p ON oi.product_id = p.product_id
                         WHERE oi.order_id = ? AND (p.producer_id = ? OR p.product_brand IN ($placeholders))";
            $items_params = array_merge([$order_id, $producer_id], $brand_ids);
        } elseif ($check_producer_id) {
            $items_sql = "SELECT oi.price, oi.quantity 
                         FROM order_items oi
                         INNER JOIN products p ON oi.product_id = p.product_id
                         WHERE oi.order_id = ? AND p.producer_id = ?";
            $items_params = [$order_id, $producer_id];
        } elseif (!empty($brand_ids)) {
            $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
            $items_sql = "SELECT oi.price, oi.quantity 
                         FROM order_items oi
                         INNER JOIN products p ON oi.product_id = p.product_id
                         WHERE oi.order_id = ? AND p.product_brand IN ($placeholders)";
            $items_params = array_merge([$order_id], $brand_ids);
        } else {
            continue;
        }
        
        $order_items = $db->fetchAll($items_sql, $items_params);
        
        $order_total = 0;
        foreach ($order_items as $item) {
            $order_total += (float)$item['price'] * (int)$item['quantity'];
        }
        
        // Total earnings (from completed/delivered orders)
        if (in_array($order_status, ['completed', 'delivered'])) {
            $total_earnings += $order_total;
        }
        
        // Pending earnings (from pending/processing/shipped orders)
        if (in_array($order_status, ['pending', 'processing', 'shipped'])) {
            $pending_earnings += $order_total;
        }
        
        // Monthly breakdown
        if ($order_date) {
            $order_month = date('Y-m', strtotime($order_date));
            $order_year = date('Y', strtotime($order_date));
            
            if ($order_month === $current_month && in_array($order_status, ['completed', 'delivered'])) {
                $this_month_earnings += $order_total;
            }
            
            if ($order_month === $last_month && in_array($order_status, ['completed', 'delivered'])) {
                $last_month_earnings += $order_total;
            }
            
            if ($order_year === $current_year && in_array($order_status, ['completed', 'delivered'])) {
                $this_year_earnings += $order_total;
            }
        }
    }
}

// Get earnings history (transactions)
$history_where = $base_where;
$history_params = $params;

// Apply period filter
if ($filter_period === 'this_month') {
    $history_where .= " AND DATE_FORMAT(o.order_date, '%Y-%m') = ?";
    $history_params[] = date('Y-m');
} elseif ($filter_period === 'last_month') {
    $history_where .= " AND DATE_FORMAT(o.order_date, '%Y-%m') = ?";
    $history_params[] = date('Y-m', strtotime('-1 month'));
} elseif ($filter_period === 'this_year') {
    $history_where .= " AND YEAR(o.order_date) = ?";
    $history_params[] = date('Y');
}

$history_sql = "SELECT DISTINCT o.order_id, o.invoice_no, o.order_status, o.order_date, o.created_at,
                       c.customer_name
                FROM orders o
                INNER JOIN order_items oi ON o.order_id = oi.order_id
                INNER JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN customer c ON o.customer_id = c.customer_id
                WHERE $history_where
                AND o.order_status IN ('completed', 'delivered')
                ORDER BY o.order_date DESC
                LIMIT ? OFFSET ?";
$history_params[] = $limit;
$history_params[] = $offset;

$earnings_history = $db->fetchAll($history_sql, $history_params);

// Calculate earnings for each transaction
foreach ($earnings_history as &$transaction) {
    $order_id = $transaction['order_id'];
    
    if ($check_producer_id && !empty($brand_ids)) {
        $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
        $items_sql = "SELECT oi.price, oi.quantity 
                     FROM order_items oi
                     INNER JOIN products p ON oi.product_id = p.product_id
                     WHERE oi.order_id = ? AND (p.producer_id = ? OR p.product_brand IN ($placeholders))";
        $items_params = array_merge([$order_id, $producer_id], $brand_ids);
    } elseif ($check_producer_id) {
        $items_sql = "SELECT oi.price, oi.quantity 
                     FROM order_items oi
                     INNER JOIN products p ON oi.product_id = p.product_id
                     WHERE oi.order_id = ? AND p.producer_id = ?";
        $items_params = [$order_id, $producer_id];
    } elseif (!empty($brand_ids)) {
        $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
        $items_sql = "SELECT oi.price, oi.quantity 
                     FROM order_items oi
                     INNER JOIN products p ON oi.product_id = p.product_id
                     WHERE oi.order_id = ? AND p.product_brand IN ($placeholders)";
        $items_params = array_merge([$order_id], $brand_ids);
    } else {
        $transaction['earnings'] = 0;
        continue;
    }
    
    $items = $db->fetchAll($items_sql, $items_params);
    $transaction['earnings'] = 0;
    foreach ($items as $item) {
        $transaction['earnings'] += (float)$item['price'] * (int)$item['quantity'];
    }
}
unset($transaction);

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT o.order_id) as total
              FROM orders o
              INNER JOIN order_items oi ON o.order_id = oi.order_id
              INNER JOIN products p ON oi.product_id = p.product_id
              WHERE $history_where
              AND o.order_status IN ('completed', 'delivered')";
$count_result = $db->fetchRow($count_sql, array_slice($history_params, 0, -2));
$total_transactions = $count_result['total'] ?? 0;

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
    margin-left: 260px;
    padding: 40px;
    min-height: 100vh;
    background: #F9F7F4;
}

/* Earnings Header */
.earnings-header {
    background: var(--white);
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.earnings-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.earnings-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    font-size: 2.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.stat-card .stat-icon {
    float: right;
    font-size: 2.5rem;
    color: var(--terracotta);
    opacity: 0.3;
}

.stat-card.primary {
    background: linear-gradient(135deg, #D2691E 0%, #B8621E 100%);
    color: white;
}

.stat-card.primary h3,
.stat-card.primary .stat-value {
    color: white;
}

.stat-card.primary .stat-icon {
    color: white;
    opacity: 0.5;
}

/* Filters */
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

/* Earnings History */
.earnings-history {
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.earnings-history-header {
    padding: 25px;
    border-bottom: 1px solid #E8DDD0;
}

.earnings-history-header h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.transactions-table {
    width: 100%;
    border-collapse: collapse;
}

.transactions-table thead {
    background: #F9F7F4;
}

.transactions-table th {
    padding: 15px;
    text-align: left;
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #E8DDD0;
}

.transactions-table td {
    padding: 15px;
    border-bottom: 1px solid #E8DDD0;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
}

.transactions-table tbody tr:hover {
    background: #F9F7F4;
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

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
    padding: 20px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #E8DDD0;
    border-radius: 4px;
    text-decoration: none;
    color: var(--text-dark);
    font-family: 'Spectral', serif;
}

.pagination a:hover {
    background: #D2691E;
    color: white;
    border-color: #D2691E;
}

.pagination .current {
    background: #D2691E;
    color: white;
    border-color: #D2691E;
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
            <a href="<?php echo url('view/producer/earnings.php'); ?>" class="nav-item active">
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
            
            <!-- Earnings Header -->
            <div class="earnings-header">
                <div class="earnings-header-top">
                    <h1>My Earnings</h1>
                </div>
                
                <form method="GET" action="" class="filters-row">
                    <div class="filter-group">
                        <label>Period</label>
                        <select name="period">
                            <option value="all" <?php echo $filter_period === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="this_month" <?php echo $filter_period === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="last_month" <?php echo $filter_period === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                            <option value="this_year" <?php echo $filter_period === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <i class="fas fa-dollar-sign stat-icon"></i>
                    <h3>Total Earnings</h3>
                    <p class="stat-value">GHS <?php echo number_format($total_earnings, 2); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock stat-icon"></i>
                    <h3>Pending Earnings</h3>
                    <p class="stat-value">GHS <?php echo number_format($pending_earnings, 2); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt stat-icon"></i>
                    <h3>This Month</h3>
                    <p class="stat-value">GHS <?php echo number_format($this_month_earnings, 2); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line stat-icon"></i>
                    <h3>This Year</h3>
                    <p class="stat-value">GHS <?php echo number_format($this_year_earnings, 2); ?></p>
                </div>
            </div>

            <!-- Earnings History -->
            <div class="earnings-history">
                <div class="earnings-history-header">
                    <h2>Earnings History</h2>
                </div>
                
                <?php if (empty($earnings_history)): ?>
                    <div class="empty-state">
                        <i class="fas fa-wallet"></i>
                        <h3>No Earnings Yet</h3>
                        <p>Your earnings from completed orders will appear here</p>
                    </div>
                <?php else: ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($earnings_history as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($transaction['order_date'] ?? $transaction['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo url('view/producer/orders.php?search=' . $transaction['order_id']); ?>" style="color: #D2691E; text-decoration: none;">
                                            #<?php echo escape_html($transaction['order_id']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo escape_html($transaction['customer_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge delivered" style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; background: #D4EDDA; color: #155724;">
                                            <?php echo ucfirst($transaction['order_status']); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 600; color: #10B981;">GHS <?php echo number_format($transaction['earnings'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php
                    $total_pages = ceil($total_transactions / $limit);
                    if ($total_pages > 1):
                    ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

