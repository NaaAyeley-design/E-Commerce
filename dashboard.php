<?php
session_start();
require_once("dashboard_controller.php");

// Initialize dashboard controller
$dashboard = new DashboardController();
$stats = $dashboard->getAllStats();
$chartData = $dashboard->getRevenueChartData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ecommerce Admin</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Ecommerce Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Customers</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Orders</span>
                </a>
                <a href="products.php" class="nav-item">
                    <span class="nav-icon">🛍️</span>
                    <span>Products</span>
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
                <h1>Dashboard Overview</h1>
                <div class="header-actions">
                    <button class="refresh-btn" onclick="refreshDashboard()">🔄 Refresh</button>
                    <span class="last-updated">Last updated: <span id="lastUpdateTime"><?php echo date('H:i:s'); ?></span></span>
                </div>
            </header>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon customers">👥</div>
                    <div class="stat-content">
                        <h3>Total Customers</h3>
                        <p class="stat-value" id="totalCustomers"><?php echo number_format($stats['total_customers']); ?></p>
                        <span class="stat-change positive">
                            +<?php echo $stats['new_customers_this_month']; ?> this month
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orders">📦</div>
                    <div class="stat-content">
                        <h3>Total Orders</h3>
                        <p class="stat-value" id="totalOrders"><?php echo number_format($stats['total_orders']); ?></p>
                        <span class="stat-change">
                            <?php echo $stats['pending_orders']; ?> pending
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon revenue">💰</div>
                    <div class="stat-content">
                        <h3>Total Revenue</h3>
                        <p class="stat-value" id="totalRevenue">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                        <span class="stat-change positive">
                            $<?php echo number_format($stats['current_month_revenue'], 2); ?> this month
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon products">🛍️</div>
                    <div class="stat-content">
                        <h3>Total Products</h3>
                        <p class="stat-value" id="totalProducts"><?php echo number_format($stats['total_products']); ?></p>
                        <span class="stat-change">Active products</span>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables Section -->
            <div class="dashboard-grid">
                <!-- Revenue Chart -->
                <div class="dashboard-card chart-card">
                    <div class="card-header">
                        <h2>Revenue Overview</h2>
                        <select id="chartPeriod" onchange="updateChart()">
                            <option value="6">Last 6 Months</option>
                            <option value="12">Last 12 Months</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="dashboard-card orders-card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="#" class="view-all">View All →</a>
                    </div>
                    <div class="orders-list" id="recentOrders">
                        <?php if (!empty($stats['recent_orders'])): ?>
                            <?php foreach ($stats['recent_orders'] as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <h4>Order #<?php echo htmlspecialchars($order['id'] ?? 'N/A'); ?></h4>
                                        <p class="order-date">
                                            <?php 
                                            $date = $order['created_at'] ?? $order['order_date'] ?? 'N/A';
                                            echo date('M d, Y', strtotime($date));
                                            ?>
                                        </p>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-badge <?php echo strtolower($order['status'] ?? 'pending'); ?>">
                                            <?php echo htmlspecialchars($order['status'] ?? 'Pending'); ?>
                                        </span>
                                        <p class="order-amount">
                                            $<?php 
                                            $amount = $order['total'] ?? $order['amount'] ?? $order['price'] ?? 0;
                                            echo number_format($amount, 2); 
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No recent orders found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="additional-stats">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Quick Stats</h2>
                    </div>
                    <div class="quick-stats-grid">
                        <div class="quick-stat">
                            <span class="quick-stat-label">Pending Orders</span>
                            <span class="quick-stat-value"><?php echo $stats['pending_orders']; ?></span>
                        </div>
                        <div class="quick-stat">
                            <span class="quick-stat-label">New Customers (This Month)</span>
                            <span class="quick-stat-value"><?php echo $stats['new_customers_this_month']; ?></span>
                        </div>
                        <div class="quick-stat">
                            <span class="quick-stat-label">Monthly Revenue</span>
                            <span class="quick-stat-value">$<?php echo number_format($stats['current_month_revenue'], 2); ?></span>
                        </div>
                        <div class="quick-stat">
                            <span class="quick-stat-label">Average Order Value</span>
                            <span class="quick-stat-value">
                                $<?php 
                                $avg = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;
                                echo number_format($avg, 2); 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="dashboard.js"></script>
    <script>
        // Initialize Revenue Chart
        const chartData = <?php echo json_encode($chartData); ?>;
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(item => item.month),
                datasets: [{
                    label: 'Revenue',
                    data: chartData.map(item => item.revenue),
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        function refreshDashboard() {
            location.reload();
        }

        function updateChart() {
            // This would fetch new chart data based on period
            // For now, just reload the page
            refreshDashboard();
        }
    </script>
</body>
</html>

