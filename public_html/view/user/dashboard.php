<?php
/**
 * KenteKart User Dashboard - Sidebar Navigation Design
 * 
 * Clean dashboard with sidebar navigation showing different metric views
 */

// Suppress error reporting to prevent code from showing
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/order_controller.php';

// Set page variables
$page_title = 'My Dashboard - KenteKart';
$page_description = 'View your shopping metrics and order history';
$body_class = 'dashboard-page';
$additional_css = ['dashboard-metrics.css']; // Dashboard metrics CSS file

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=login_required');
    exit;
}

// Get current user data
$user = new user_class();
$customer = $user->get_customer_by_id($_SESSION['user_id']);
if (!$customer) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=session_expired');
    exit;
}

// Check if user is admin and redirect to admin dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
    header('Location: ' . BASE_URL . '/view/admin/dashboard.php');
    exit;
}

// Get user orders
$order_result = get_customer_orders_ctr($_SESSION['user_id'], 1, 100); // Get more orders for better analysis
$all_orders = isset($order_result['orders']) ? $order_result['orders'] : [];

// Calculate metrics from actual orders
$total_orders = count($all_orders);
$total_spent = 0;
$orders_this_month = 0;
$spending_this_month = 0;
$unique_artisans = [];
$orders_delivered = 0;
$orders_in_transit = 0;

// Spending by month (last 6 months)
$monthly_spending = [];
$monthly_orders = [];

$current_month = date('Y-m');
$current_year = date('Y');

// Initialize last 6 months
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $monthly_spending[$date] = 0;
    $monthly_orders[$date] = 0;
}

foreach ($all_orders as $order) {
    $order_amount = $order['order_total'] ?? 0;
    $total_spent += $order_amount;
    
    // Get order month
    $order_month = date('Y-m', strtotime($order['created_at']));
    
    // Add to monthly totals if in last 6 months
    if (isset($monthly_spending[$order_month])) {
        $monthly_spending[$order_month] += $order_amount;
        $monthly_orders[$order_month]++;
    }
    
    // Check if order is from this month
    if ($order_month === $current_month) {
        $orders_this_month++;
        $spending_this_month += $order_amount;
    }
    
    // Count by status
    $status = strtolower($order['order_status'] ?? '');
    if ($status === 'delivered' || $status === 'completed') {
        $orders_delivered++;
    } elseif ($status === 'shipped' || $status === 'processing') {
        $orders_in_transit++;
    }
    
    // TODO: When you have artisan/seller data in orders, extract unique artisan IDs
    // For now, we'll use a placeholder
    // if (isset($order['seller_id'])) {
    //     $unique_artisans[$order['seller_id']] = true;
    // }
}

// Calculate average order value
$average_order_value = $total_orders > 0 ? $total_spent / $total_orders : 0;

// Placeholder for artisans count (TODO: Replace with actual query)
$artisans_supported = $total_orders > 0 ? min($total_orders, rand(3, 8)) : 0;

// Format chart data
$chart_months = [];
$chart_spending = [];
$chart_orders = [];

foreach ($monthly_spending as $month => $spending) {
    $chart_months[] = date('M', strtotime($month . '-01'));
    $chart_spending[] = $spending;
    $chart_orders[] = $monthly_orders[$month];
}

// Get recent 5 orders for display
$recent_orders = array_slice($all_orders, 0, 5);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="kentekart-dashboard">
    
    <!-- SIDEBAR NAVIGATION -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>Dashboard</h2>
            <p>Your artisan journey</p>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-item active" data-section="overview">
                <i class="fas fa-th"></i>
                <span>Overview</span>
            </button>
            <button class="nav-item" data-section="orders">
                <i class="fas fa-box"></i>
                <span>Orders</span>
            </button>
            <button class="nav-item" data-section="spending">
                <i class="fas fa-dollar-sign"></i>
                <span>Spending</span>
            </button>
            <button class="nav-item" data-section="artisans">
                <i class="fas fa-users"></i>
                <span>Artisans</span>
            </button>
            <button class="nav-item" data-section="insights">
                <i class="fas fa-chart-line"></i>
                <span>Insights</span>
            </button>
            <button class="nav-item" data-section="wishlist">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
            </button>
        </nav>

        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($customer['customer_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <p class="user-name"><?php echo escape_html($customer['customer_name']); ?></p>
                <p class="user-since">Member since <?php echo date('M Y', strtotime($customer['created_at'] ?? 'now')); ?></p>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        
        <!-- OVERVIEW SECTION -->
        <section id="section-overview" class="dashboard-section active">
            
            <!-- Header -->
            <header class="section-header-minimal">
                <div>
                    <p class="section-subtitle">Dashboard</p>
                    <h1 class="section-title">Your Journey</h1>
                </div>
                <div class="header-meta">
                    <p class="meta-label">Member since</p>
                    <p class="meta-value"><?php echo date('F Y', strtotime($customer['created_at'] ?? 'now')); ?></p>
                </div>
            </header>

            <!-- Key Stats -->
            <div class="stats-row">
                <div class="stat-column">
                    <p class="stat-number"><?php echo $total_orders; ?></p>
                    <p class="stat-label">Orders</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number">₵<?php echo number_format($total_spent, 0); ?></p>
                    <p class="stat-label">Total Spent</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number"><?php echo $artisans_supported; ?></p>
                    <p class="stat-label">Artisans</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number"><?php echo $orders_this_month; ?></p>
                    <p class="stat-label">This Month</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number">₵<?php echo number_format($average_order_value, 0); ?></p>
                    <p class="stat-label">Average</p>
                </div>
            </div>

            <!-- Insight Banner -->
            <div class="insight-banner">
                <i class="fas fa-award"></i>
                <div>
                    <p class="insight-main">You're <?php echo (10 - $artisans_supported); ?> orders away from supporting 10 unique artisans</p>
                    <p class="insight-sub">Your spending has increased this year as you discover new favorites</p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid-minimal">
                <!-- Spending Chart -->
                <div class="chart-box">
                    <div class="chart-box-header">
                        <h3>Spending Pattern</h3>
                        <p>Your purchasing activity over the last 6 months</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="spendingChartOverview"></canvas>
                    </div>
                </div>

                <!-- Categories Chart -->
                <div class="chart-box">
                    <div class="chart-box-header">
                        <h3>Category Breakdown</h3>
                        <p>Where you've invested in artisan crafts</p>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>

        </section>

        <!-- ORDERS SECTION -->
        <section id="section-orders" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Your Orders</h1>
                    <p class="section-subtitle">Complete history of your artisan purchases</p>
                </div>
            </header>

            <!-- Order Stats -->
            <div class="mini-stats-grid">
                <div class="mini-stat-card">
                    <i class="fas fa-box"></i>
                    <p class="mini-stat-label">Total Orders</p>
                    <p class="mini-stat-value"><?php echo $total_orders; ?></p>
                </div>
                <div class="mini-stat-card">
                    <i class="fas fa-clock"></i>
                    <p class="mini-stat-label">In Transit</p>
                    <p class="mini-stat-value"><?php echo $orders_in_transit; ?></p>
                </div>
                <div class="mini-stat-card">
                    <i class="fas fa-check-circle"></i>
                    <p class="mini-stat-label">Delivered</p>
                    <p class="mini-stat-value"><?php echo $orders_delivered; ?></p>
                </div>
                <div class="mini-stat-card">
                    <i class="fas fa-chart-line"></i>
                    <p class="mini-stat-label">This Month</p>
                    <p class="mini-stat-value"><?php echo $orders_this_month; ?></p>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="orders-list-section">
                <h3 class="subsection-title">Recent Activity</h3>
                
                <?php if (empty($recent_orders)): ?>
                <div class="empty-state-minimal">
                    <i class="fas fa-box-open"></i>
                    <p>No orders yet</p>
                    <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn-minimal">Browse Products</a>
                </div>
                <?php else: ?>
                <div class="orders-list-minimal">
                    <?php foreach ($recent_orders as $index => $order): ?>
                    <div class="order-item-minimal <?php echo $index < count($recent_orders) - 1 ? 'has-border' : ''; ?>">
                        <div class="order-item-content">
                            <div class="order-item-info">
                                <p class="order-item-title">Order #<?php echo escape_html($order['order_id']); ?></p>
                                <p class="order-item-meta"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="order-item-actions">
                            <p class="order-item-amount">₵<?php echo number_format($order['order_total'] ?? 0, 2); ?></p>
                            <a href="<?php echo url('view/order/order_details.php?id=' . $order['order_id']); ?>" class="btn-outline-minimal">
                                View
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </section>

        <!-- SPENDING SECTION -->
        <section id="section-spending" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Spending Analysis</h1>
                    <p class="section-subtitle">Your investment in artisan craftsmanship</p>
                </div>
            </header>

            <!-- Spending Stats -->
            <div class="stats-row centered">
                <div class="stat-column">
                    <p class="stat-number large">₵<?php echo number_format($total_spent, 2); ?></p>
                    <p class="stat-label">Total Spent</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large">₵<?php echo number_format($spending_this_month, 2); ?></p>
                    <p class="stat-label">This Month</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large">₵<?php echo number_format($average_order_value, 2); ?></p>
                    <p class="stat-label">Average Order</p>
                </div>
            </div>

            <!-- Full Width Chart -->
            <div class="chart-box-full">
                <div class="chart-box-header">
                    <h3>Monthly Trends</h3>
                    <p>Your spending pattern over the last 6 months</p>
                </div>
                <div class="chart-wrapper-large">
                    <canvas id="spendingChartFull"></canvas>
                </div>
            </div>

        </section>

        <!-- ARTISANS SECTION -->
        <section id="section-artisans" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Artisan Community</h1>
                    <p class="section-subtitle">The makers you've supported on your journey</p>
                </div>
            </header>

            <!-- Artisan Stats -->
            <div class="stats-row centered">
                <div class="stat-column">
                    <p class="stat-number large"><?php echo $artisans_supported; ?></p>
                    <p class="stat-label">Artisans Supported</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large"><?php echo min($artisans_supported, 3); ?></p>
                    <p class="stat-label">Favorite Makers</p>
                </div>
                <div class="stat-column">
                    <p class="stat-number large">5</p>
                    <p class="stat-label">Countries</p>
                </div>
            </div>

            <!-- Placeholder for Artisans -->
            <div class="empty-state-minimal">
                <i class="fas fa-users"></i>
                <p>Artisan details coming soon</p>
                <p class="text-muted">We're working on bringing you detailed artisan profiles</p>
            </div>

        </section>

        <!-- INSIGHTS SECTION -->
        <section id="section-insights" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Personal Insights</h1>
                    <p class="section-subtitle">Understanding your shopping patterns</p>
                </div>
            </header>

            <!-- Insights Grid -->
            <div class="insights-grid">
                <div class="insight-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Growth Trajectory</h3>
                    <p>You've placed <?php echo $total_orders; ?> orders, spending ₵<?php echo number_format($total_spent, 2); ?>. Your average order value is ₵<?php echo number_format($average_order_value, 2); ?>.</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Average per order</p>
                        <p class="footer-value">₵<?php echo number_format($average_order_value, 2); ?></p>
                    </div>
                </div>

                <div class="insight-card">
                    <i class="fas fa-calendar"></i>
                    <h3>This Month's Activity</h3>
                    <p>You've made <?php echo $orders_this_month; ?> orders in <?php echo date('F'); ?>, spending ₵<?php echo number_format($spending_this_month, 2); ?>. Keep discovering amazing artisan crafts!</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Most active month</p>
                        <p class="footer-value"><?php echo date('F'); ?></p>
                    </div>
                </div>

                <div class="insight-card">
                    <i class="fas fa-heart"></i>
                    <h3>Artisan Support</h3>
                    <p>You've supported <?php echo $artisans_supported; ?> unique artisan<?php echo $artisans_supported != 1 ? 's' : ''; ?>. Each purchase directly supports Ghanaian craftspeople and their families.</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Artisans supported</p>
                        <p class="footer-value"><?php echo $artisans_supported; ?></p>
                    </div>
                </div>

                <div class="insight-card">
                    <i class="fas fa-award"></i>
                    <h3>Milestone Progress</h3>
                    <p>You're <?php echo (10 - $artisans_supported); ?> orders away from supporting 10 unique artisans. This milestone unlocks special recognition in our community.</p>
                    <div class="insight-card-footer">
                        <p class="footer-label">Next milestone</p>
                        <p class="footer-value">10 Artisans</p>
                    </div>
                </div>
            </div>

        </section>

        <!-- WISHLIST SECTION -->
        <section id="section-wishlist" class="dashboard-section">
            
            <header class="section-header-minimal">
                <div>
                    <h1 class="section-title">Saved Items</h1>
                    <p class="section-subtitle">Your curated collection of favorites</p>
                </div>
            </header>

            <!-- Placeholder for Wishlist -->
            <div class="empty-state-minimal">
                <i class="fas fa-heart"></i>
                <p>Wishlist feature coming soon</p>
                <p class="text-muted">Save your favorite items and we'll notify you of price drops</p>
            </div>

        </section>

    </main>
    
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Dashboard Script -->
<script>
// Dashboard data from PHP
const dashboardData = {
    chartMonths: <?php echo json_encode($chart_months); ?>,
    chartSpending: <?php echo json_encode($chart_spending); ?>,
    chartOrders: <?php echo json_encode($chart_orders); ?>,
    totalOrders: <?php echo $total_orders; ?>,
    totalSpent: <?php echo $total_spent; ?>
};

// KenteKart colors
const colors = {
    terracotta: '#C67D5C',
    deepBrown: '#8B6F47',
    lightBrown: '#8B7F74',
    warmBeige: '#F4EDE4'
};

// Navigation functionality
document.querySelectorAll('.nav-item').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all buttons and sections
        document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.dashboard-section').forEach(section => section.classList.remove('active'));
        
        // Add active class to clicked button
        this.classList.add('active');
        
        // Show corresponding section
        const sectionId = 'section-' + this.dataset.section;
        document.getElementById(sectionId).classList.add('active');
    });
});

// Create Spending Chart (Overview)
function createSpendingChartOverview() {
    const ctx = document.getElementById('spendingChartOverview');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: dashboardData.chartMonths,
            datasets: [{
                label: 'Spending',
                data: dashboardData.chartSpending,
                borderColor: colors.terracotta,
                backgroundColor: 'rgba(198, 125, 92, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.terracotta,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#3A2F26',
                    bodyColor: '#3A2F26',
                    borderColor: colors.terracotta,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => '₵' + context.parsed.y.toFixed(2)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 },
                        callback: (value) => '₵' + value
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 }
                    }
                }
            }
        }
    });
}

// Create Categories Chart
function createCategoriesChart() {
    const ctx = document.getElementById('categoriesChart');
    if (!ctx) return;
    
    // Placeholder category data
    const categories = ['Jewelry', 'Pottery', 'Textiles', 'Art', 'Home'];
    const values = [420, 340, 280, 180, 120];
    
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Spent',
                data: values,
                backgroundColor: colors.deepBrown,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#3A2F26',
                    bodyColor: '#3A2F26',
                    borderColor: colors.terracotta,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => '₵' + context.parsed.y
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 11 }
                    }
                }
            }
        }
    });
}

// Create Full Spending Chart
function createSpendingChartFull() {
    const ctx = document.getElementById('spendingChartFull');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: dashboardData.chartMonths,
            datasets: [{
                label: 'Spending',
                data: dashboardData.chartSpending,
                borderColor: colors.terracotta,
                backgroundColor: 'rgba(198, 125, 92, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.terracotta,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#3A2F26',
                    bodyColor: '#3A2F26',
                    borderColor: colors.terracotta,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => 'Spent: ₵' + context.parsed.y.toFixed(2)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 12 },
                        callback: (value) => '₵' + value
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(198, 125, 92, 0.1)',
                        drawBorder: false
                    },
                    border: { display: false },
                    ticks: {
                        color: colors.lightBrown,
                        font: { family: "'Spectral', serif", size: 12 }
                    }
                }
            }
        }
    });
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    createSpendingChartOverview();
    createCategoriesChart();
    createSpendingChartFull();
});
</script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>