<?php
session_start();
require_once("producer_controller.php");

// Check if user is logged in as producer
if (!isset($_SESSION['producer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['designer_id']) && !isset($_SESSION['artisan_id'])) {
    header('Location: login.php');
    exit;
}

$producerController = new ProducerController();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$orders = $producerController->getProducerOrders($limit, $offset);
$stats = $producerController->getDashboardStats();
$profile = $stats['profile'];
$producerName = $profile['name'] ?? $profile['full_name'] ?? $profile['username'] ?? 'Producer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - KenteKart</title>
    <link rel="stylesheet" href="producer_dashboard.css">
    <link rel="stylesheet" href="producer_orders.css">
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
                <a href="producer_products.php">Products</a>
                <a href="producer_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout →</a>
            </nav>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" placeholder="Search orders...">
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
                    <a href="producer_products.php" class="nav-item">
                        <span class="nav-icon">📦</span>
                        <span>Products</span>
                    </a>
                    <a href="producer_orders.php" class="nav-item active">
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
            <main class="main-content orders-content">
                <h1>My Orders</h1>

                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <p>You don't have any orders yet.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Order Number</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id'] ?? 'N/A'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></strong></td>
                                        <td class="order-amount">$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($order['status'] ?? 'pending'); ?>">
                                                <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $date = $order['created_at'] ?? $order['order_date'] ?? 'N/A';
                                            if ($date !== 'N/A') {
                                                echo date('M d, Y H:i', strtotime($date));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        function viewOrder(orderId) {
            // Fetch and display order details
            alert('View order ' + orderId + ' - Implement order details modal');
        }
    </script>
</body>
</html>

