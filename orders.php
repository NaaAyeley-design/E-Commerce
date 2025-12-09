<?php
session_start();
require_once("order_class.php");
require_once("db.php");

// Check if user is logged in (adjust based on your auth system)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    // Uncomment if authentication is required
    // header('Location: login.php');
    // exit;
}

$order = new Order();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get all orders (for admin) or user orders
$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
$ordersData = $isAdmin ? getAllOrders($limit, $offset) : $order->getUserOrders($limit);
$orders = $ordersData['orders'] ?? [];
$totalOrders = count($orders);

// Function to get all orders (admin view)
function getAllOrders($limit = 20, $offset = 0) {
    $db_conn = new db_connection();
    $db = $db_conn->db;
    
    $tables = ['orders', 'order'];
    foreach ($tables as $table) {
        $check = mysqli_query($db, "SHOW TABLES LIKE '$table'");
        if ($check && mysqli_num_rows($check) > 0) {
            $query = "SELECT * FROM `$table` ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $orders = [];
            while ($order = mysqli_fetch_assoc($result)) {
                $orders[] = $order;
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM `$table`";
            $countResult = mysqli_query($db, $countQuery);
            $totalCount = 0;
            if ($countResult) {
                $countRow = mysqli_fetch_assoc($countResult);
                $totalCount = (int)$countRow['total'];
            }
            
            return ['success' => true, 'orders' => $orders, 'total' => $totalCount];
        }
    }
    
    return ['success' => false, 'orders' => [], 'total' => 0];
}

$allOrdersData = getAllOrders(1000, 0); // Get all for pagination
$totalOrders = $allOrdersData['total'] ?? 0;
$totalPages = ceil($totalOrders / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Ecommerce Admin</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="orders.css">
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
                <a href="orders.php" class="nav-item active">
                    <span class="nav-icon">📦</span>
                    <span>Orders</span>
                </a>
                <a href="products.php" class="nav-item">
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
                <h1>Orders Management</h1>
                <div class="header-actions">
                    <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
                    <span class="order-count">Total Orders: <?php echo $totalOrders; ?></span>
                </div>
            </header>

            <!-- Orders Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>All Orders</h2>
                    <div class="filter-box">
                        <select id="statusFilter" onchange="filterOrders()">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                
                <div class="orders-table-container">
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <p>No orders found. Orders will appear here once customers place orders.</p>
                            <p><small>Check the <a href="order_errors.log" target="_blank">error log</a> if orders aren't being created.</small></p>
                        </div>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer ID</th>
                                    <th>Order Number</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
                                <?php foreach ($orders as $orderItem): ?>
                                    <tr data-status="<?php echo strtolower($orderItem['status'] ?? 'pending'); ?>">
                                        <td class="order-id"><?php echo htmlspecialchars($orderItem['id'] ?? 'N/A'); ?></td>
                                        <td class="customer-id"><?php echo htmlspecialchars($orderItem['user_id'] ?? 'N/A'); ?></td>
                                        <td class="order-number">
                                            <strong><?php echo htmlspecialchars($orderItem['order_number'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td class="order-amount">$<?php echo number_format($orderItem['total_amount'] ?? 0, 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($orderItem['status'] ?? 'pending'); ?>">
                                                <?php echo ucfirst($orderItem['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td class="payment-method"><?php echo ucfirst(str_replace('_', ' ', $orderItem['payment_method'] ?? 'N/A')); ?></td>
                                        <td class="order-date">
                                            <?php 
                                            $date = $orderItem['created_at'] ?? $orderItem['order_date'] ?? 'N/A';
                                            if ($date !== 'N/A') {
                                                echo date('M d, Y H:i', strtotime($date));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td class="order-actions">
                                            <button class="action-btn view-btn" onclick="viewOrder(<?php echo $orderItem['id']; ?>)">View</button>
                                            <button class="action-btn edit-btn" onclick="editOrderStatus(<?php echo $orderItem['id']; ?>)">Edit</button>
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

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeOrderModal()">&times;</span>
            <h2>Order Details</h2>
            <div id="orderDetails"></div>
        </div>
    </div>

    <script>
        function viewOrder(orderId) {
            // Fetch order details via API
            fetch(`order_api.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                        document.getElementById('orderModal').style.display = 'block';
                    } else {
                        alert('Failed to load order details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading order details');
                });
        }

        function displayOrderDetails(order) {
            const detailsDiv = document.getElementById('orderDetails');
            let html = `
                <div class="order-detail-section">
                    <h3>Order Information</h3>
                    <p><strong>Order Number:</strong> ${order.order_number || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-badge ${order.status}">${order.status || 'Pending'}</span></p>
                    <p><strong>Total Amount:</strong> $${parseFloat(order.total_amount || 0).toFixed(2)}</p>
                    <p><strong>Payment Method:</strong> ${order.payment_method || 'N/A'}</p>
                    <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                </div>
            `;
            
            if (order.shipping_address) {
                html += `
                    <div class="order-detail-section">
                        <h3>Shipping Address</h3>
                        <p>${order.shipping_address.replace(/\n/g, '<br>')}</p>
                    </div>
                `;
            }
            
            if (order.items && order.items.length > 0) {
                html += `
                    <div class="order-detail-section">
                        <h3>Order Items</h3>
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                order.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.product_name || 'Product #' + item.product_id}</td>
                            <td>${item.quantity}</td>
                            <td>$${parseFloat(item.price || 0).toFixed(2)}</td>
                            <td>$${parseFloat(item.subtotal || 0).toFixed(2)}</td>
                        </tr>
                    `;
                });
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            detailsDiv.innerHTML = html;
        }

        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        function editOrderStatus(orderId) {
            const newStatus = prompt('Enter new status (pending, processing, shipped, delivered, cancelled, completed):');
            if (newStatus) {
                fetch('order_api.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order status updated successfully');
                        location.reload();
                    } else {
                        alert('Failed to update order status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating order status');
                });
            }
        }

        function filterOrders() {
            const filter = document.getElementById('statusFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTableBody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (!filter || status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

