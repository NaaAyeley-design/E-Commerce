<?php
session_start();
require_once("product_controller.php");

// Check if user is logged in (adjust based on your auth system)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    // Uncomment if authentication is required
    // header('Location: login.php');
    // exit;
}

$productController = new ProductController();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$products = $productController->getAllProducts($limit, $offset);
$totalProducts = $productController->getTotalProducts();
$totalPages = ceil($totalProducts / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Ecommerce Admin</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="products.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Ecommerce Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Customers</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Orders</span>
                </a>
                <a href="products.php" class="nav-item active">
                    <span class="nav-icon">🛍️</span>
                    <span>Products</span>
                </a>
                <a href="brands.php" class="nav-item">
                    <span class="nav-icon">🏷️</span>
                    <span>Brands</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Revenue</span>
                </a>
                <a href="login.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <h1>Products Management</h1>
                <div class="header-actions">
                    <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
                    <button class="add-product-btn" onclick="showAddProductModal()">+ Add Product</button>
                </div>
            </header>

            <!-- Products Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>All Products (<?php echo $totalProducts; ?>)</h2>
                    <div class="search-box">
                        <input type="text" id="productSearch" placeholder="Search products..." onkeyup="filterProducts()">
                    </div>
                </div>
                
                <div class="products-table-container">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <p>No products found. Add your first product to get started.</p>
                        </div>
                    <?php else: ?>
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="product-image-cell">
                                            <?php 
                                            $imageUrl = $product['image_url'] ?? null;
                                            $imageColumn = $product['image_column'] ?? null;
                                            
                                            // Try multiple image column names if image_url is null
                                            if (!$imageUrl && $imageColumn) {
                                                $imageUrl = $product[$imageColumn] ?? null;
                                            }
                                            
                                            // Try common image column names
                                            if (!$imageUrl) {
                                                $imageFields = ['image', 'image_url', 'photo', 'picture', 'img', 'thumbnail'];
                                                foreach ($imageFields as $field) {
                                                    if (isset($product[$field]) && !empty($product[$field])) {
                                                        $imageUrl = $product[$field];
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            // Format the image URL
                                            if ($imageUrl) {
                                                // If it's a full URL, use it
                                                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                                    $finalImageUrl = $imageUrl;
                                                } 
                                                // If it starts with /, use as is
                                                elseif (substr($imageUrl, 0, 1) === '/') {
                                                    $finalImageUrl = $imageUrl;
                                                }
                                                // Otherwise, try to construct the path
                                                else {
                                                    // Try common upload directories
                                                    $uploadDirs = ['uploads/', 'uploads/products/', 'images/', 'img/', 'products/', 'assets/images/'];
                                                    $finalImageUrl = null;
                                                    
                                                    // First, check if the path already includes an upload directory
                                                    foreach ($uploadDirs as $dir) {
                                                        if (strpos($imageUrl, $dir) === 0) {
                                                            // Path already includes directory, just add leading slash
                                                            $finalImageUrl = '/' . ltrim($imageUrl, '/');
                                                            break;
                                                        }
                                                    }
                                                    
                                                    // If not found, try prepending upload directories
                                                    if (!$finalImageUrl) {
                                                        foreach ($uploadDirs as $dir) {
                                                            $testPath = '/' . ltrim($dir . ltrim($imageUrl, '/'), '/');
                                                            // Check if file exists (relative to current directory or parent)
                                                            if (file_exists(ltrim($testPath, '/')) || 
                                                                file_exists('..' . $testPath) || 
                                                                file_exists('../..' . $testPath)) {
                                                                $finalImageUrl = $testPath;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    
                                                    // If still no file found, use the original path with leading slash
                                                    // This allows the browser to try loading it
                                                    if (!$finalImageUrl) {
                                                        $finalImageUrl = '/' . ltrim($imageUrl, '/');
                                                    }
                                                }
                                            } else {
                                                $finalImageUrl = null;
                                            }
                                            ?>
                                            
                                            <?php if ($finalImageUrl): ?>
                                                <div class="product-image-wrapper">
                                                    <img src="<?php echo htmlspecialchars($finalImageUrl); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>"
                                                         class="product-image"
                                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' dy=\'10.5\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                                                </div>
                                            <?php else: ?>
                                                <div class="product-image-wrapper">
                                                    <div class="no-image-placeholder">
                                                        <span>No Image</span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="product-id"><?php echo htmlspecialchars($product['id'] ?? 'N/A'); ?></td>
                                        <td class="product-name">
                                            <strong><?php echo htmlspecialchars($product['name'] ?? $product['title'] ?? 'Unnamed Product'); ?></strong>
                                            <?php if (isset($product['description']) && !empty($product['description'])): ?>
                                                <br><small class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="product-price">$<?php echo number_format($product['price'] ?? $product['amount'] ?? 0, 2); ?></td>
                                        <td class="product-stock"><?php echo htmlspecialchars($product['stock'] ?? $product['quantity'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            $status = strtolower($product['status'] ?? 'active');
                                            $statusClass = in_array($status, ['active', 'available', 'in stock']) ? 'active' : 'inactive';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="product-actions">
                                            <button class="action-btn edit-btn" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                                            <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="page-btn">← Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="page-btn active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>" class="page-btn"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="page-btn">Next →</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showAddProductModal() {
            alert('Add Product functionality - to be implemented');
        }

        function editProduct(id) {
            alert('Edit Product ' + id + ' - to be implemented');
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                console.log('Deleting product:', id);
            }
        }

        function filterProducts() {
            const input = document.getElementById('productSearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('productsTableBody');
            if (!table) return;
            
            const tr = table.getElementsByTagName('tr');

            for (let i = 0; i < tr.length; i++) {
                const nameCell = tr[i].getElementsByClassName('product-name')[0];
                if (nameCell) {
                    const txtValue = nameCell.textContent || nameCell.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>

