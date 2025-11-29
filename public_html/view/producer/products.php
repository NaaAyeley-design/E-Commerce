<?php
/**
 * Products Management Page for Producers
 * 
 * Lists all products for the logged-in producer with filtering and actions
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../class/product_class.php';
require_once __DIR__ . '/../../../class/category_class.php';
require_once __DIR__ . '/../../../class/brand_class.php';
require_once __DIR__ . '/../../../class/user_class.php';

// Set page variables
$page_title = 'My Products - Producer Dashboard';
$page_description = 'Manage your products';
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
$filter_category = (int)($_GET['category'] ?? 0);
$filter_brand = (int)($_GET['brand'] ?? 0);
$search_query = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get producer's products
$product_class = new product_class();
$db = new db_class();

// Build query
$where_conditions = ["producer_id = ?"];
$params = [$producer_id];

if (!empty($filter_status)) {
    $where_conditions[] = "product_status = ?";
    $params[] = $filter_status;
}

if ($filter_category > 0) {
    $where_conditions[] = "product_cat = ?";
    $params[] = $filter_category;
}

if ($filter_brand > 0) {
    $where_conditions[] = "product_brand = ?";
    $params[] = $filter_brand;
}

if (!empty($search_query)) {
    $where_conditions[] = "(product_title LIKE ? OR sku LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM products WHERE $where_clause";
$total_products = $db->fetchColumn($count_sql, $params);

// Get products
$sql = "SELECT p.*, 
        c.cat_name, 
        b.brand_name,
        (SELECT COUNT(*) FROM product_images WHERE product_id = p.product_id) as image_count
        FROM products p
        LEFT JOIN categories c ON p.product_cat = c.cat_id
        LEFT JOIN brands b ON p.product_brand = b.brand_id
        WHERE $where_clause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$products = $db->fetchAll($sql, $params);

// Get stats
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN product_status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN product_status = 'draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN product_status = 'inactive' THEN 1 ELSE 0 END) as inactive,
    SUM(CASE WHEN track_inventory = 1 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock
    FROM products WHERE producer_id = ?";
$stats = $db->fetchRow($stats_sql, [$producer_id]);

// Get categories and brands for filters
$category_class = new category_class();
$brand_class = new brand_class();
$categories = $category_class->get_categories_by_user($producer_id, 1000, 0);
$brands = $brand_class->get_brands_by_user($producer_id, 1000, 0);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<style>
/* Reuse sidebar styles from add_product.php */
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

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--white);
    padding: 20px;
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

/* Filters and Actions */
.products-header {
    background: var(--white);
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.products-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.products-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.filters-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
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

/* Products Table */
.products-table-container {
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table thead {
    background: #F9F7F4;
}

.products-table th {
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

.products-table td {
    padding: 15px;
    border-bottom: 1px solid #E8DDD0;
    font-family: 'Spectral', serif;
    font-size: 0.9rem;
}

.products-table tbody tr:hover {
    background: #F9F7F4;
}

.product-image-cell {
    width: 80px;
}

.product-image-cell img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: #D4EDDA;
    color: #155724;
}

.status-badge.draft {
    background: #FFF3CD;
    color: #856404;
}

.status-badge.inactive {
    background: #F8D7DA;
    color: #721C24;
}

.stock-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.stock-badge.in-stock {
    background: #D4EDDA;
    color: #155724;
}

.stock-badge.low-stock {
    background: #FFF3CD;
    color: #856404;
}

.stock-badge.out-of-stock {
    background: #F8D7DA;
    color: #721C24;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-family: 'Spectral', serif;
    font-size: 0.8rem;
    transition: all 0.2s ease;
}

.btn-action.edit {
    background: #E8DDD0;
    color: #D2691E;
}

.btn-action.edit:hover {
    background: #D2691E;
    color: white;
}

.btn-action.delete {
    background: #F8D7DA;
    color: #721C24;
}

.btn-action.delete:hover {
    background: #721C24;
    color: white;
}

.btn-action.view {
    background: #D1ECF1;
    color: #0C5460;
}

.btn-action.view:hover {
    background: #0C5460;
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
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
}

.empty-state p {
    font-family: 'Spectral', serif;
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
            <a href="<?php echo url('view/producer/products.php'); ?>" class="nav-item active">
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
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-box stat-icon"></i>
                    <h3>Total Products</h3>
                    <p class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle stat-icon"></i>
                    <h3>Active</h3>
                    <p class="stat-value"><?php echo number_format($stats['active'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-alt stat-icon"></i>
                    <h3>Draft</h3>
                    <p class="stat-value"><?php echo number_format($stats['draft'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                    <h3>Low Stock</h3>
                    <p class="stat-value"><?php echo number_format($stats['low_stock'] ?? 0); ?></p>
                </div>
            </div>

            <!-- Products Header -->
            <div class="products-header">
                <div class="products-header-top">
                    <h1>My Products</h1>
                    <a href="<?php echo url('view/producer/add_product.php'); ?>" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
                
                <form method="GET" action="" class="filters-row">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo escape_html($search_query); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['cat_id']; ?>" <?php echo $filter_category == $cat['cat_id'] ? 'selected' : ''; ?>>
                                    <?php echo escape_html($cat['cat_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Brand</label>
                        <select name="brand">
                            <option value="0">All Brands</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['brand_id']; ?>" <?php echo $filter_brand == $brand['brand_id'] ? 'selected' : ''; ?>>
                                    <?php echo escape_html($brand['brand_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </form>
            </div>

            <!-- Products Table -->
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>Get started by adding your first product</p>
                    <a href="<?php echo url('view/producer/add_product.php'); ?>" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
            <?php else: ?>
                <div class="products-table-container">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
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
                                
                                $stock_class = 'in-stock';
                                $stock_text = $product['stock_quantity'] ?? 0;
                                if ($product['track_inventory'] ?? 0) {
                                    if ($stock_text == 0) {
                                        $stock_class = 'out-of-stock';
                                        $stock_text = 'Out of Stock';
                                    } elseif ($stock_text <= ($product['low_stock_threshold'] ?? 5)) {
                                        $stock_class = 'low-stock';
                                        $stock_text = $stock_text . ' (Low)';
                                    }
                                } else {
                                    $stock_text = 'Unlimited';
                                }
                                ?>
                                <tr>
                                    <td class="product-image-cell">
                                        <img src="<?php echo $image_url; ?>" alt="<?php echo escape_html($product['product_title']); ?>">
                                    </td>
                                    <td>
                                        <strong><?php echo escape_html($product['product_title']); ?></strong>
                                        <?php if (($product['image_count'] ?? 0) > 0): ?>
                                            <br><small style="color: var(--text-light);"><?php echo $product['image_count']; ?> additional images</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo escape_html($product['sku'] ?? 'N/A'); ?></td>
                                    <td><?php echo escape_html($product['cat_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo escape_html($product['brand_name'] ?? 'N/A'); ?></td>
                                    <td>GHS <?php echo number_format($product['product_price'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $stock_class; ?>">
                                            <?php echo $stock_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $product['product_status'] ?? 'draft'; ?>">
                                            <?php echo ucfirst($product['product_status'] ?? 'draft'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo url('view/product/single_product.php?id=' . $product['product_id']); ?>" 
                                               class="btn-action view" 
                                               target="_blank" 
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo url('view/producer/edit_product.php?id=' . $product['product_id']); ?>" 
                                               class="btn-action edit" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn-action delete" 
                                                    onclick="deleteProduct(<?php echo $product['product_id']; ?>)" 
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php
                $total_pages = ceil($total_products / $limit);
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

<script>
function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        return;
    }
    
    fetch('<?php echo url("actions/producer/delete_product_action.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            csrf_token: '<?php echo generate_csrf_token(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

