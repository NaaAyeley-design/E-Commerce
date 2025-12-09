<?php
session_start();
require_once("producer_controller.php");

// Check if user is logged in as producer
if (!isset($_SESSION['producer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['designer_id']) && !isset($_SESSION['artisan_id'])) {
    header('Location: login.php');
    exit;
}

$producerController = new ProducerController();
$stats = $producerController->getDashboardStats();
$profile = $stats['profile'];
$producerName = $profile['name'] ?? $profile['full_name'] ?? $profile['username'] ?? 'Producer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings - KenteKart</title>
    <link rel="stylesheet" href="producer_dashboard.css">
    <link rel="stylesheet" href="producer_earnings.css">
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
                    <input type="text" placeholder="Search...">
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
                    <a href="producer_orders.php" class="nav-item">
                        <span class="nav-icon">🛒</span>
                        <span>Orders</span>
                    </a>
                    <a href="producer_earnings.php" class="nav-item active">
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
            <main class="main-content earnings-content">
                <h1>My Earnings</h1>

                <div class="earnings-summary">
                    <div class="earnings-card total">
                        <h3>Total Earnings</h3>
                        <div class="earnings-amount">$<?php echo number_format($stats['total_earnings'], 2); ?></div>
                    </div>
                    <div class="earnings-card monthly">
                        <h3>This Month</h3>
                        <div class="earnings-amount">$<?php echo number_format($stats['this_month_sales'], 2); ?></div>
                    </div>
                    <div class="earnings-card pending">
                        <h3>Pending Orders</h3>
                        <div class="earnings-amount"><?php echo number_format($stats['pending_orders']); ?></div>
                    </div>
                </div>

                <div class="earnings-info">
                    <p>Your earnings are calculated based on completed orders. Payments are processed according to your payment schedule.</p>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

