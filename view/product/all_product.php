<?php
/**
 * Designer Product Management Page
 * Complete rebuild - All Products Management
 */
session_start();
require_once("../../db.php");

// Security: Check if user is logged in
if (!isset($_SESSION['producer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['designer_id']) && !isset($_SESSION['artisan_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get producer/designer ID from session
$producer_id = $_SESSION['producer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['designer_id'] ?? $_SESSION['artisan_id'];

// Database connection
$db_conn = new db_connection();
$db = $db_conn->db;

// Find products table
$productsTable = 'products';
$tableCheck = mysqli_query($db, "SHOW TABLES LIKE 'products'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    // Create table if doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS `products` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `price` DECIMAL(10,2) NOT NULL,
        `stock` INT(11) NOT NULL DEFAULT 0,
        `image` VARCHAR(255),
        `status` VARCHAR(50) DEFAULT 'active',
        `producer_id` INT(11) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `producer_id` (`producer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($db, $createTable);
}

// ============================================
// HANDLE DELETE PRODUCT
// ============================================
if (isset($_GET['delete_id'])) {
    $product_id = (int)$_GET['delete_id'];
    
    // SECURITY: Verify product belongs to this producer
    $checkQuery = "SELECT id FROM `$productsTable` WHERE id = ? AND producer_id = ?";
    $checkStmt = mysqli_prepare($db, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "ii", $product_id, $producer_id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    
    if (mysqli_fetch_assoc($checkResult)) {
        // Product belongs to this producer - safe to delete
        $deleteQuery = "DELETE FROM `$productsTable` WHERE id = ? AND producer_id = ?";
        $deleteStmt = mysqli_prepare($db, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "ii", $product_id, $producer_id);
        
        if (mysqli_stmt_execute($deleteStmt)) {
            header('Location: all_product.php?success=deleted');
            exit;
        }
    }
    header('Location: all_product.php?error=delete_failed');
    exit;
}

// ============================================
// HANDLE ADD/UPDATE PRODUCT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('product_') . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'uploads/products/' . $new_filename;
        }
    }
    
    if (isset($_POST['add_product'])) {
        // Add new product
        if (empty($name) || $price <= 0) {
            $error_msg = 'Product name and valid price are required';
        } else {
            $query = "INSERT INTO `$productsTable` (name, description, price, stock, image, status, producer_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "ssdissi", $name, $description, $price, $stock, $image_path, $status, $producer_id);
            
            if (mysqli_stmt_execute($stmt)) {
                header('Location: all_product.php?success=added');
                exit;
            } else {
                $error_msg = 'Failed to add product: ' . mysqli_error($db);
            }
        }
    } elseif (isset($_POST['update_product']) && $product_id > 0) {
        // Update existing product - SECURITY: Verify ownership
        $checkQuery = "SELECT id FROM `$productsTable` WHERE id = ? AND producer_id = ?";
        $checkStmt = mysqli_prepare($db, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ii", $product_id, $producer_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_fetch_assoc($checkResult)) {
            // Product belongs to this producer - safe to update
            if ($image_path) {
                $query = "UPDATE `$productsTable` SET name = ?, description = ?, price = ?, stock = ?, image = ?, status = ? 
                         WHERE id = ? AND producer_id = ?";
                $stmt = mysqli_prepare($db, $query);
                mysqli_stmt_bind_param($stmt, "ssdissii", $name, $description, $price, $stock, $image_path, $status, $product_id, $producer_id);
            } else {
                $query = "UPDATE `$productsTable` SET name = ?, description = ?, price = ?, stock = ?, status = ? 
                         WHERE id = ? AND producer_id = ?";
                $stmt = mysqli_prepare($db, $query);
                mysqli_stmt_bind_param($stmt, "ssdisii", $name, $description, $price, $stock, $status, $product_id, $producer_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                header('Location: all_product.php?success=updated');
                exit;
            } else {
                $error_msg = 'Failed to update product: ' . mysqli_error($db);
            }
        } else {
            $error_msg = 'Product not found or you do not have permission to edit it';
        }
    }
}

// ============================================
// GET STATISTICS
// ============================================
// Total Products
$totalProductsQuery = "SELECT COUNT(*) as total FROM `$productsTable` WHERE producer_id = ?";
$stmt = mysqli_prepare($db, $totalProductsQuery);
mysqli_stmt_bind_param($stmt, "i", $producer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalProducts = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active Products
$activeProductsQuery = "SELECT COUNT(*) as active FROM `$productsTable` WHERE producer_id = ? AND status = 'active'";
$stmt = mysqli_prepare($db, $activeProductsQuery);
mysqli_stmt_bind_param($stmt, "i", $producer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$activeProducts = mysqli_fetch_assoc($result)['active'] ?? 0;

// Out of Stock
$outOfStockQuery = "SELECT COUNT(*) as out_of_stock FROM `$productsTable` WHERE producer_id = ? AND stock = 0";
$stmt = mysqli_prepare($db, $outOfStockQuery);
mysqli_stmt_bind_param($stmt, "i", $producer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$outOfStock = mysqli_fetch_assoc($result)['out_of_stock'] ?? 0;

// Total Items Sold
$totalSoldQuery = "SELECT COALESCE(SUM(oi.quantity), 0) as total_sold
                   FROM order_items oi
                   JOIN `$productsTable` p ON oi.product_id = p.id
                   WHERE p.producer_id = ?";
$stmt = mysqli_prepare($db, $totalSoldQuery);
mysqli_stmt_bind_param($stmt, "i", $producer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalSold = mysqli_fetch_assoc($result)['total_sold'] ?? 0;

// Total Revenue
$totalRevenueQuery = "SELECT COALESCE(SUM(oi.subtotal), 0) as total_revenue
                      FROM order_items oi
                      JOIN `$productsTable` p ON oi.product_id = p.id
                      WHERE p.producer_id = ?";
$stmt = mysqli_prepare($db, $totalRevenueQuery);
mysqli_stmt_bind_param($stmt, "i", $producer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalRevenue = mysqli_fetch_assoc($result)['total_revenue'] ?? 0;

// ============================================
// GET PRODUCTS WITH SALES DATA
// ============================================
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? 'all';
$stockFilter = $_GET['stock_filter'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'newest';

// Build WHERE clause
$whereConditions = ["p.producer_id = ?"];
$bindParams = [$producer_id];
$bindTypes = "i";

if (!empty($search)) {
    $whereConditions[] = "p.name LIKE ?";
    $bindParams[] = "%$search%";
    $bindTypes .= "s";
}

if ($statusFilter !== 'all') {
    $whereConditions[] = "p.status = ?";
    $bindParams[] = $statusFilter;
    $bindTypes .= "s";
}

if ($stockFilter === 'low') {
    $whereConditions[] = "p.stock > 0 AND p.stock < 5";
} elseif ($stockFilter === 'out') {
    $whereConditions[] = "p.stock = 0";
} elseif ($stockFilter === 'in') {
    $whereConditions[] = "p.stock > 0";
}

// Build ORDER BY clause
$orderBy = "p.created_at DESC";
switch ($sortBy) {
    case 'oldest':
        $orderBy = "p.created_at ASC";
        break;
    case 'price_high':
        $orderBy = "p.price DESC";
        break;
    case 'price_low':
        $orderBy = "p.price ASC";
        break;
    case 'stock':
        $orderBy = "p.stock DESC";
        break;
}

$productsQuery = "SELECT p.*, 
                  COUNT(DISTINCT oi.order_id) as times_ordered,
                  COALESCE(SUM(oi.quantity), 0) as total_sold,
                  COALESCE(SUM(oi.subtotal), 0) as revenue
                  FROM `$productsTable` p
                  LEFT JOIN order_items oi ON p.id = oi.product_id
                  WHERE " . implode(' AND ', $whereConditions) . "
                  GROUP BY p.id
                  ORDER BY $orderBy";

$stmt = mysqli_prepare($db, $productsQuery);
if (!empty($bindParams)) {
    mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
}
mysqli_stmt_execute($stmt);
$productsResult = mysqli_stmt_get_result($stmt);
$products = [];
while ($row = mysqli_fetch_assoc($productsResult)) {
    $products[] = $row;
}

// Get product for editing (if edit_id is set)
$editProduct = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $editQuery = "SELECT * FROM `$productsTable` WHERE id = ? AND producer_id = ?";
    $editStmt = mysqli_prepare($db, $editQuery);
    mysqli_stmt_bind_param($editStmt, "ii", $edit_id, $producer_id);
    mysqli_stmt_execute($editStmt);
    $editResult = mysqli_stmt_get_result($editStmt);
    $editProduct = mysqli_fetch_assoc($editResult);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Product Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f0;
            color: #333;
            font-weight: 500;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Page Header */
        .page-header {
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        
        .breadcrumb {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .btn-add {
            background: #8b4513;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-add:hover {
            background: #6b3410;
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: #8b4513;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Search and Filter Bar */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Products Table */
        .products-table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th {
            background: #f5f0e8;
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .products-table tr:hover {
            background: #f9f9f9;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #000;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-right: 5px;
            transition: opacity 0.3s;
        }
        
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-view {
            background: #28a745;
            color: white;
        }
        
        .btn-action:hover {
            opacity: 0.8;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        
        .close {
            font-size: 28px;
            font-weight: 700;
            color: #999;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group textarea {
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-submit {
            background: #8b4513;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">My Products</h1>
                <div class="breadcrumb">Dashboard > Products</div>
            </div>
            <button class="btn-add" onclick="showAddModal()">+ Add New Product</button>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                $success = $_GET['success'];
                if ($success === 'added') echo 'Product added successfully!';
                elseif ($success === 'updated') echo 'Product updated successfully!';
                elseif ($success === 'deleted') echo 'Product deleted successfully!';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo number_format($totalProducts); ?></h3>
                <p>Total Products</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($activeProducts); ?></h3>
                <p>Active Products</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($outOfStock); ?></h3>
                <p>Out of Stock</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($totalSold); ?></h3>
                <p>Total Items Sold</p>
            </div>
            <div class="stat-card">
                <h3>GH₵<?php echo number_format($totalRevenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
        
        <!-- Search and Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Search Products</label>
                <input type="text" id="searchInput" placeholder="Search by product name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Status Filter</label>
                <select id="statusFilter">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Stock Filter</label>
                <select id="stockFilter">
                    <option value="all" <?php echo $stockFilter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="in" <?php echo $stockFilter === 'in' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>Low Stock (&lt; 5)</option>
                    <option value="out" <?php echo $stockFilter === 'out' ? 'selected' : ''; ?>>Out of Stock (0)</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Sort By</label>
                <select id="sortBy">
                    <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price (High-Low)</option>
                    <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price (Low-High)</option>
                    <option value="stock" <?php echo $sortBy === 'stock' ? 'selected' : ''; ?>>Stock Level</option>
                </select>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="products-table-container">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <p>No products found. Add your first product to get started!</p>
                </div>
            <?php else: ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Sales</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['image']): ?>
                                        <img src="../../<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image"
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Crect fill=\'%23ddd\' width=\'50\' height=\'50\'/%3E%3Ctext fill=\'%23999\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                                    <?php else: ?>
                                        <div style="width:50px;height:50px;background:#ddd;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#999;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td>GH₵<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php
                                    $stock = (int)$product['stock'];
                                    if ($stock > 5) {
                                        echo '<span class="badge badge-success">' . $stock . '</span>';
                                    } elseif ($stock > 0) {
                                        echo '<span class="badge badge-warning">' . $stock . ' <small>(Low Stock)</small></span>';
                                    } else {
                                        echo '<span class="badge badge-danger">0 <small>(Out of Stock)</small></span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $product['status'];
                                    if ($status === 'active') {
                                        echo '<span class="badge badge-success">Active</span>';
                                    } elseif ($status === 'inactive') {
                                        echo '<span class="badge badge-danger">Inactive</span>';
                                    } else {
                                        echo '<span class="badge badge-warning">Draft</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                <td><?php echo number_format($product['total_sold']); ?></td>
                                <td>GH₵<?php echo number_format($product['revenue'], 2); ?></td>
                                <td>
                                    <button class="btn-action btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                                    <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Product</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="product_id" id="productId" value="">
                
                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="name" required maxlength="255" value="<?php echo $editProduct ? htmlspecialchars($editProduct['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" name="description" rows="5"><?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="productPrice">Price (GH₵) *</label>
                    <input type="number" id="productPrice" name="price" step="0.01" min="0" required value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="productStock">Stock Quantity *</label>
                    <input type="number" id="productStock" name="stock" min="0" required value="<?php echo $editProduct ? $editProduct['stock'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="productImage">Product Image</label>
                    <?php if ($editProduct && $editProduct['image']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../../<?php echo htmlspecialchars($editProduct['image']); ?>" style="width:100px;height:100px;object-fit:cover;border-radius:4px;">
                            <p style="font-size:12px;color:#666;margin-top:5px;">Current image. Leave empty to keep.</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="productImage" name="image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="productStatus">Status</label>
                    <select id="productStatus" name="status">
                        <option value="draft" <?php echo ($editProduct && $editProduct['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="active" <?php echo ($editProduct && $editProduct['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editProduct && $editProduct['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" name="<?php echo $editProduct ? 'update_product' : 'add_product'; ?>">
                        <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Show add modal
        function showAddModal() {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.querySelector('button[name="add_product"]').name = 'add_product';
            document.querySelector('button[name="update_product"]').style.display = 'none';
            if (document.querySelector('button[name="add_product"]')) {
                document.querySelector('button[name="add_product"]').style.display = 'block';
            }
        }
        
        // Edit product
        function editProduct(id) {
            window.location.href = 'all_product.php?edit_id=' + id;
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
            window.location.href = 'all_product.php';
        }
        
        // Confirm delete
        function confirmDelete(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
                window.location.href = 'all_product.php?delete_id=' + id;
            }
        }
        
        // Filter functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
        
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('stockFilter').addEventListener('change', applyFilters);
        document.getElementById('sortBy').addEventListener('change', applyFilters);
        
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const stock = document.getElementById('stockFilter').value;
            const sort = document.getElementById('sortBy').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status !== 'all') params.append('status_filter', status);
            if (stock !== 'all') params.append('stock_filter', stock);
            if (sort !== 'newest') params.append('sort', sort);
            
            window.location.href = 'all_product.php?' + params.toString();
        }
        
        // Show modal if editing
        <?php if ($editProduct): ?>
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Product';
        <?php endif; ?>
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

