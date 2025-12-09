<?php
/**
 * Orders Management Page for Producers
 * 
 * Displays orders containing products created by the logged-in producer
 */

require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../class/order_class.php';
require_once __DIR__ . '/../../class/product_class.php';
require_once __DIR__ . '/../../class/user_class.php';
require_once __DIR__ . '/../../class/db_class.php';

// Set page variables
$page_title = 'My Orders - Producer Dashboard';
$page_description = 'View and manage orders for your products';
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
$filter_status = $_GET['status'] ?? '';
$search_query = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get producer's brand IDs
$db = new db_class();
$brand_ids_sql = "SELECT brand_id FROM brands WHERE user_id = ?";
$producer_brands = $db->fetchAll($brand_ids_sql, [$producer_id]);
$brand_ids = array_column($producer_brands, 'brand_id');

// Build query to get orders containing products from this producer
$where_conditions = [];
$params = [];

// Check if producer_id column exists
$check_producer_id = $db->fetchRow("SHOW COLUMNS FROM products LIKE 'producer_id'");

if ($check_producer_id && !empty($brand_ids)) {
    // Use both producer_id and brand-based filtering
    $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
    $where_conditions[] = "(p.producer_id = ? OR p.product_brand IN ($placeholders))";
    $params = array_merge([$producer_id], $brand_ids);
} elseif ($check_producer_id) {
    // Only producer_id exists
    $where_conditions[] = "p.producer_id = ?";
    $params = [$producer_id];
} elseif (!empty($brand_ids)) {
    // No producer_id column, use brand-based filtering
    $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
    $where_conditions[] = "p.product_brand IN ($placeholders)";
    $params = $brand_ids;
} else {
    // No brands and no producer_id - return empty result
    $where_conditions[] = "1 = 0";
    $params = [];
}

// Add status filter if provided
if (!empty($filter_status)) {
    $where_conditions[] = "o.order_status = ?";
    $params[] = $filter_status;
}

// Add search filter if provided
if (!empty($search_query)) {
    $where_conditions[] = "(o.order_id LIKE ? OR c.customer_name LIKE ? OR o.invoice_no LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count of orders
$count_sql = "SELECT COUNT(DISTINCT o.order_id) as total
              FROM orders o
              INNER JOIN order_items oi ON o.order_id = oi.order_id
              INNER JOIN products p ON oi.product_id = p.product_id
              LEFT JOIN customer c ON o.customer_id = c.customer_id
              $where_clause";
$total_result = $db->fetchRow($count_sql, $params);
$total_orders = $total_result['total'] ?? 0;

// Get orders with customer info
$sql = "SELECT DISTINCT o.order_id, o.invoice_no, o.total_amount, o.order_status, 
               o.payment_method, o.order_date, o.created_at, o.shipping_address,
               c.customer_id, c.customer_name, c.customer_email, c.customer_contact
        FROM orders o
        INNER JOIN order_items oi ON o.order_id = oi.order_id
        INNER JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN customer c ON o.customer_id = c.customer_id
        $where_clause
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$orders = $db->fetchAll($sql, $params);

// Get order items for each order (only products from this producer)
$order_class = new order_class();
foreach ($orders as &$order) {
    $order_id = $order['order_id'];
    
    // Get order items for this producer's products only
    if ($check_producer_id && !empty($brand_ids)) {
        $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
        $items_sql = "SELECT oi.*, p.product_title, p.product_image, p.product_price,
                             b.brand_name, c.cat_name
                      FROM order_items oi
                      INNER JOIN products p ON oi.product_id = p.product_id
                      LEFT JOIN brands b ON p.product_brand = b.brand_id
                      LEFT JOIN categories c ON p.product_cat = c.cat_id
                      WHERE oi.order_id = ? 
                      AND (p.producer_id = ? OR p.product_brand IN ($placeholders))";
        $items_params = array_merge([$order_id, $producer_id], $brand_ids);
    } elseif ($check_producer_id) {
        $items_sql = "SELECT oi.*, p.product_title, p.product_image, p.product_price,
                             b.brand_name, c.cat_name
                      FROM order_items oi
                      INNER JOIN products p ON oi.product_id = p.product_id
                      LEFT JOIN brands b ON p.product_brand = b.brand_id
                      LEFT JOIN categories c ON p.product_cat = c.cat_id
                      WHERE oi.order_id = ? AND p.producer_id = ?";
        $items_params = [$order_id, $producer_id];
    } elseif (!empty($brand_ids)) {
        $placeholders = str_repeat('?,', count($brand_ids) - 1) . '?';
        $items_sql = "SELECT oi.*, p.product_title, p.product_image, p.product_price,
                             b.brand_name, c.cat_name
                      FROM order_items oi
                      INNER JOIN products p ON oi.product_id = p.product_id
                      LEFT JOIN brands b ON p.product_brand = b.brand_id
                      LEFT JOIN categories c ON p.product_cat = c.cat_id
                      WHERE oi.order_id = ? AND p.product_brand IN ($placeholders)";
        $items_params = array_merge([$order_id], $brand_ids);
    } else {
        $items_sql = "SELECT oi.*, p.product_title, p.product_image, p.product_price,
                             b.brand_name, c.cat_name
                      FROM order_items oi
                      INNER JOIN products p ON oi.product_id = p.product_id
                      LEFT JOIN brands b ON p.product_brand = b.brand_id
                      LEFT JOIN categories c ON p.product_cat = c.cat_id
                      WHERE oi.order_id = ? AND 1 = 0";
        $items_params = [$order_id];
    }
    
    $order['items'] = $db->fetchAll($items_sql, $items_params);
    
    // Calculate total for this producer's products only
    $order['producer_total'] = 0;
    foreach ($order['items'] as $item) {
        $order['producer_total'] += (float)$item['price'] * (int)$item['quantity'];
    }
}
unset($order);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<style>
/* Reuse sidebar styles from products.php */
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

/* Orders Header */
.orders-header {
    background: var(--white);
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.orders-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.orders-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.filters-row {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
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

.filter-group input,
.filter-group select {
    padding: 10px 12px;
    border: 2px solid #E8DDD0;
    border-radius: 6px;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
}

.filter-group input:focus,
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

/* Orders List */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.order-card-header {
    padding: 20px 25px;
    background: #F9F7F4;
    border-bottom: 1px solid #E8DDD0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-card-header-left {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.order-id {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
}

.order-date {
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    color: var(--text-light);
}

.order-card-header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.pending {
    background: #FFF3CD;
    color: #856404;
}

.status-badge.processing {
    background: #D1ECF1;
    color: #0C5460;
}

.status-badge.shipped {
    background: #E2D9F3;
    color: #6F42C1;
}

.status-badge.delivered {
    background: #D4EDDA;
    color: #155724;
}

.status-badge.cancelled {
    background: #F8D7DA;
    color: #721C24;
}

.payment-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 500;
}

.payment-badge.paid {
    background: #D4EDDA;
    color: #155724;
}

.payment-badge.pending {
    background: #FFF3CD;
    color: #856404;
}

.order-card-body {
    padding: 25px;
}

.customer-info {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #E8DDD0;
}

.customer-info h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 10px 0;
}

.customer-info p {
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
    color: var(--text-light);
    margin: 5px 0;
}

.order-items {
    margin-bottom: 20px;
}

.order-items h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 15px 0;
}

.order-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #F9F7F4;
    border-radius: 6px;
    margin-bottom: 10px;
}

.order-item-image {
    width: 80px;
    height: 80px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
}

.order-item-details {
    flex: 1;
}

.order-item-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 5px 0;
}

.order-item-meta {
    font-family: 'Spectral', serif;
    font-size: 0.85rem;
    color: var(--text-light);
    margin: 3px 0;
}

.order-item-price {
    font-family: 'Spectral', serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-top: 5px;
}

.order-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 2px solid #E8DDD0;
}

.order-total {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
}

.order-actions {
    display: flex;
    gap: 10px;
}

.btn-view {
    padding: 8px 16px;
    background: #D2691E;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Spectral', serif;
    font-size: 0.875rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.btn-view:hover {
    background: #B8621E;
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
    margin-bottom: 30px;
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
            <a href="<?php echo url('view/producer/orders.php'); ?>" class="nav-item active">
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
            
            <!-- Orders Header -->
            <div class="orders-header">
                <div class="orders-header-top">
                    <h1>My Orders</h1>
                </div>
                
                <form method="GET" action="" class="filters-row">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search by order ID, customer name..." value="<?php echo escape_html($search_query); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $filter_status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $filter_status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $filter_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>

            <!-- Orders List -->
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>You don't have any orders yet</h3>
                    <p>Orders will appear here when customers purchase your products</p>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <div class="order-card-header-left">
                                    <span class="order-id">Order #<?php echo escape_html($order['order_id']); ?></span>
                                    <?php if (!empty($order['invoice_no'])): ?>
                                        <span class="order-date">Invoice: <?php echo escape_html($order['invoice_no']); ?></span>
                                    <?php endif; ?>
                                    <span class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['order_date'] ?? $order['created_at'])); ?></span>
                                </div>
                                <div class="order-card-header-right">
                                    <span class="status-badge <?php echo strtolower($order['order_status'] ?? 'pending'); ?>">
                                        <?php echo ucfirst($order['order_status'] ?? 'Pending'); ?>
                                    </span>
                                    <span class="payment-badge <?php echo strtolower($order['payment_method'] ?? 'pending'); ?>">
                                        <?php echo ucfirst($order['payment_method'] ?? 'Pending'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-card-body">
                                <!-- Customer Info -->
                                <div class="customer-info">
                                    <h3>Customer Information</h3>
                                    <p><strong>Name:</strong> <?php echo escape_html($order['customer_name'] ?? 'N/A'); ?></p>
                                    <p><strong>Email:</strong> <?php echo escape_html($order['customer_email'] ?? 'N/A'); ?></p>
                                    <?php if (!empty($order['customer_contact'])): ?>
                                        <p><strong>Contact:</strong> <?php echo escape_html($order['customer_contact']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Order Items -->
                                <div class="order-items">
                                    <h3>Products Ordered</h3>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="order-item">
                                            <?php
                                            $image_url = get_image_url($item['product_image'] ?? '');
                                            ?>
                                            <img src="<?php echo $image_url; ?>" alt="<?php echo escape_html($item['product_title']); ?>" class="order-item-image">
                                            <div class="order-item-details">
                                                <h4 class="order-item-title"><?php echo escape_html($item['product_title']); ?></h4>
                                                <?php if (!empty($item['brand_name'])): ?>
                                                    <p class="order-item-meta">Brand: <?php echo escape_html($item['brand_name']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($item['cat_name'])): ?>
                                                    <p class="order-item-meta">Category: <?php echo escape_html($item['cat_name']); ?></p>
                                                <?php endif; ?>
                                                <p class="order-item-meta">Quantity: <?php echo (int)$item['quantity']; ?></p>
                                                <p class="order-item-price">GHS <?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Order Summary -->
                                <div class="order-summary">
                                    <div>
                                        <p class="order-total">Total: GHS <?php echo number_format($order['producer_total'], 2); ?></p>
                                        <p style="font-family: 'Spectral', serif; font-size: 0.85rem; color: var(--text-light); margin-top: 5px;">
                                            (Your products only)
                                        </p>
                                    </div>
                                    <div class="order-actions">
                                        <a href="<?php echo url('view/producer/order_details.php?id=' . $order['order_id']); ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php
                $total_pages = ceil($total_orders / $limit);
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
    </main>
</div>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

