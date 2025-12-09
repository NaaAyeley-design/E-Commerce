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

// Get producer name
$producerName = 'Producer';
if ($profile) {
    $producerName = $profile['name'] ?? $profile['full_name'] ?? $profile['username'] ?? 'Producer';
}

// Get producer since date (if available)
$producerSince = 'N/A';
if ($profile) {
    // Try different date columns
    $dateFields = ['created_at', 'registered_at', 'joined_at', 'created', 'registration_date'];
    foreach ($dateFields as $field) {
        if (isset($profile[$field]) && !empty($profile[$field])) {
            $producerSince = date('M Y', strtotime($profile[$field]));
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producer Dashboard - KenteKart</title>
    <link rel="stylesheet" href="producer_dashboard.css">
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
                <a href="producer_dashboard.php" class="active">Dashboard</a>
                <a href="logout.php">Logout →</a>
            </nav>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" placeholder="Search products...">
                    <button type="button">🔍</button>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Left Sidebar -->
            <aside class="sidebar">
                <h2 class="sidebar-title">Manage your business</h2>
                <nav class="sidebar-nav">
                    <a href="producer_dashboard.php" class="nav-item active">
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
                        <div class="profile-since">Producer since <?php echo htmlspecialchars($producerSince); ?></div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <div class="welcome-section">
                    <h2 class="welcome-text">Welcome back</h2>
                    <h1 class="producer-name"><?php echo htmlspecialchars($producerName); ?></h1>
                </div>

                <div class="business-info">
                    <div class="business-label">Business</div>
                    <div class="business-name"><?php echo htmlspecialchars($producerName); ?></div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card products-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                            <div class="stat-label">TOTAL PRODUCTS</div>
                        </div>
                    </div>

                    <div class="stat-card orders-card">
                        <div class="stat-icon">⏰</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                            <div class="stat-label">PENDING ORDERS</div>
                        </div>
                    </div>

                    <div class="stat-card sales-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-content">
                            <div class="stat-value">$<?php echo number_format($stats['this_month_sales'], 2); ?></div>
                            <div class="stat-label">THIS MONTH SALES</div>
                        </div>
                    </div>

                    <div class="stat-card earnings-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-value">$<?php echo number_format($stats['total_earnings'], 2); ?></div>
                            <div class="stat-label">TOTAL EARNINGS</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

