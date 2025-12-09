<?php
session_start();
require_once("producer_controller.php");
require_once("db.php");

// Check if user is logged in as producer
if (!isset($_SESSION['producer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['designer_id']) && !isset($_SESSION['artisan_id'])) {
    header('Location: login.php');
    exit;
}

$producerController = new ProducerController();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$products = $producerController->getProducerProducts($limit, $offset);
$stats = $producerController->getDashboardStats();
$profile = $stats['profile'];
$producerName = $profile['name'] ?? $profile['full_name'] ?? $profile['username'] ?? 'Producer';

// Handle product deletion
if (isset($_GET['delete']) && isset($_GET['product_id'])) {
    $productId = (int)$_GET['product_id'];
    $producerId = $_SESSION['producer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['designer_id'] ?? $_SESSION['artisan_id'];
    
    $db_conn = new db_connection();
    $db = $db_conn->db;
    
    // Find products table and producer column
    $tables = ['products', 'product'];
    foreach ($tables as $table) {
        $tableCheck = mysqli_query($db, "SHOW TABLES LIKE '$table'");
        if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
            // Find producer column
            $producerColumns = ['producer_id', 'user_id', 'designer_id', 'artisan_id'];
            $producerColumn = null;
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = mysqli_query($db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    foreach ($producerColumns as $pc) {
                        if (stripos($col['Field'], $pc) !== false) {
                            $producerColumn = $col['Field'];
                            break 2;
                        }
                    }
                }
            }
            
            if ($producerColumn) {
                // Verify product belongs to producer before deleting
                $checkQuery = "SELECT id FROM `$table` WHERE id = ? AND $producerColumn = ?";
                $checkStmt = mysqli_prepare($db, $checkQuery);
                mysqli_stmt_bind_param($checkStmt, "ii", $productId, $producerId);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                
                if (mysqli_fetch_assoc($checkResult)) {
                    $deleteQuery = "DELETE FROM `$table` WHERE id = ?";
                    $deleteStmt = mysqli_prepare($db, $deleteQuery);
                    mysqli_stmt_bind_param($deleteStmt, "i", $productId);
                    mysqli_stmt_execute($deleteStmt);
                    
                    header('Location: producer_products.php?deleted=1');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - KenteKart</title>
    <link rel="stylesheet" href="producer_dashboard.css">
    <link rel="stylesheet" href="producer_products.css">
</head>
<body>
    <div class="producer-dashboard">
        <!-- Top Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <h1 class="logo">KENTEKART</h1>
            </div>
            <nav class="header-nav">
                <a href="index.php">Home</a>
                <a href="producer_products.php" class="active">Products</a>
                <a href="producer_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout →</a>
            </nav>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" placeholder="Search products..." id="productSearch">
                    <button type="button">🔍</button>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Left Sidebar -->
            <aside class="sidebar">
                <h2 class="sidebar-title">Manage your business</h2>
                <nav class="sidebar-nav">
                    <a href="producer_dashboard.php" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span>Overview</span>
                    </a>
                    <a href="producer_products.php" class="nav-item active">
                        <span class="nav-icon">📦</span>
                        <span>Products</span>
                    </a>
                    <a href="producer_orders.php" class="nav-item">
                        <span class="nav-icon">🛒</span>
                        <span>Orders</span>
                    </a>
                    <a href="producer_earnings.php" class="nav-item">
                        <span class="nav-icon">💰</span>
                        <span>Earnings</span>
                    </a>
                    <a href="producer_analytics.php" class="nav-item">
                        <span class="nav-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                    <a href="producer_settings.php" class="nav-item">
                        <span class="nav-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </nav>
                
                <div class="user-profile">
                    <div class="profile-avatar"><?php echo strtoupper(substr($producerName, 0, 1)); ?></div>
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($producerName); ?></div>
                        <div class="profile-since">Producer</div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="main-content products-content">
                <div class="products-header">
                    <h1>My Products</h1>
                    <button class="add-product-btn" onclick="showAddProductModal()">+ Add Product</button>
                </div>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Product deleted successfully!</div>
                <?php endif; ?>

                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <p>You haven't added any products yet.</p>
                        <button class="add-product-btn" onclick="showAddProductModal()">Add Your First Product</button>
                    </div>
                <?php else: ?>
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" data-product-name="<?php echo strtolower(htmlspecialchars($product['name'] ?? '')); ?>">
                                <div class="product-image">
                                    <?php 
                                    $imageUrl = $product['image'] ?? $product['image_url'] ?? $product['photo'] ?? null;
                                    if ($imageUrl):
                                        if (substr($imageUrl, 0, 1) !== '/') {
                                            $imageUrl = '/' . ltrim($imageUrl, '/');
                                        }
                                    ?>
                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'200\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' dy=\'10.5\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E';">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name'] ?? $product['title'] ?? 'Unnamed Product'); ?></h3>
                                    <p class="product-price">$<?php echo number_format($product['price'] ?? $product['amount'] ?? 0, 2); ?></p>
                                    <p class="product-stock">Stock: <?php echo htmlspecialchars($product['stock'] ?? $product['quantity'] ?? 'N/A'); ?></p>
                                    
                                    <!-- Performance Metrics -->
                                    <div class="product-metrics">
                                        <span class="metric-item">
                                            <strong><?php echo number_format($product['sales_count'] ?? 0); ?></strong> sold
                                        </span>
                                        <span class="metric-item">
                                            <strong>$<?php echo number_format($product['total_revenue'] ?? 0, 2); ?></strong> revenue
                                        </span>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <button class="btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                                        <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProductModal()">&times;</span>
            <h2 id="modalTitle">Add New Product</h2>
            <form id="productForm" method="POST" action="producer_product_action.php" enctype="multipart/form-data">
                <input type="hidden" id="productId" name="product_id" value="">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="productPrice">Price ($) *</label>
                        <input type="number" id="productPrice" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productStock">Stock Quantity *</label>
                        <input type="number" id="productStock" name="stock" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="productImage">Product Image</label>
                    <input type="file" id="productImage" name="image" accept="image/*">
                    <small>Upload a product image (JPG, PNG, GIF)</small>
                </div>
                
                <div class="form-group">
                    <label for="productStatus">Status</label>
                    <select id="productStatus" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddProductModal() {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('formAction').value = 'add';
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function editProduct(id) {
            // Fetch product data securely via API
            fetch(`producer_product_api.php?product_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.product) {
                        const product = data.product;
                        
                        // Populate form with product data
                        document.getElementById('productId').value = product.id;
                        document.getElementById('productName').value = product.name || '';
                        document.getElementById('productDescription').value = product.description || '';
                        document.getElementById('productPrice').value = product.price || 0;
                        document.getElementById('productStock').value = product.stock || 0;
                        document.getElementById('productStatus').value = product.status || 'active';
                        
                        // Show modal
                        document.getElementById('productModal').style.display = 'block';
                        document.getElementById('modalTitle').textContent = 'Edit Product';
                        document.getElementById('formAction').value = 'edit';
                    } else {
                        alert('Failed to load product: ' + (data.message || 'Product not found or you do not have permission'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading product data');
                });
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                // Use API for secure deletion with ownership verification
                fetch(`producer_product_api.php?product_id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product deleted successfully!');
                        location.reload();
                    } else {
                        alert('Failed to delete product: ' + (data.message || 'You do not have permission to delete this product'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the product');
                });
            }
        }

        // Search functionality
        document.getElementById('productSearch').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                const productName = card.getAttribute('data-product-name');
                if (productName.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

