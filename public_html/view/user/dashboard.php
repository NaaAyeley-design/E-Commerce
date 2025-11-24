<?php
/**
 * KenteKart User Dashboard - Metrics-Focused Design
 * 
 * Clean dashboard showing actual user metrics with visual charts
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
$additional_css = ['dashboard-metrics.css']; // New CSS file for metrics dashboard

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
$order_result = get_customer_orders_ctr($_SESSION['user_id'], 1, 5);
$recent_orders = isset($order_result['orders']) ? $order_result['orders'] : [];

// Calculate metrics from actual orders
$total_orders = count($recent_orders);
$total_spent = 0;
$orders_this_month = 0;
$spending_this_month = 0;
$unique_artisans = [];

$current_month = date('Y-m');

foreach ($recent_orders as $order) {
    $total_spent += $order['order_total'] ?? 0;
    
    // Check if order is from this month
    if (date('Y-m', strtotime($order['created_at'])) === $current_month) {
        $orders_this_month++;
        $spending_this_month += $order['order_total'] ?? 0;
    }
    
    // TODO: When you have artisan/seller data in orders, extract unique artisan IDs
    // For now, we'll use a placeholder
    // if (isset($order['seller_id'])) {
    //     $unique_artisans[$order['seller_id']] = true;
    // }
}

// Placeholder for artisans count (TODO: Replace with actual query)
$artisans_supported = $total_orders > 0 ? min($total_orders, rand(3, 8)) : 0;

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="dashboard-container">
    
    <!-- WELCOME HEADER -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Welcome back, <span class="user-name"><?php echo escape_html($customer['customer_name']); ?></span>!</h1>
            <p>Here's your shopping overview</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn-primary">
                <i class="fas fa-shopping-bag"></i> Browse Products
            </a>
            <a href="<?php echo url('view/order/order_history.php'); ?>" class="btn-secondary">
                <i class="fas fa-history"></i> Order History
            </a>
        </div>
    </div>

    <!-- KEY METRICS CARDS -->
    <div class="metrics-grid">
        <!-- Total Orders -->
        <div class="metric-card">
            <div class="metric-icon" style="background: linear-gradient(135deg, #FF9A56, #E97451);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="metric-content">
                <div class="metric-label">Total Orders</div>
                <div class="metric-value"><?php echo $total_orders; ?></div>
                <div class="metric-change positive">
                    <i class="fas fa-arrow-up"></i> All time
                </div>
            </div>
        </div>

        <!-- Total Spent (Lifetime) -->
        <div class="metric-card">
            <div class="metric-icon" style="background: linear-gradient(135deg, #CC8B3C, #C1502F);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="metric-content">
                <div class="metric-label">Total Spent</div>
                <div class="metric-value">$<?php echo number_format($total_spent, 2); ?></div>
                <div class="metric-change">
                    <i class="fas fa-infinity"></i> Lifetime
                </div>
            </div>
        </div>

        <!-- Orders This Month -->
        <div class="metric-card">
            <div class="metric-icon" style="background: linear-gradient(135deg, #E3B778, #D4A574);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="metric-content">
                <div class="metric-label">Orders This Month</div>
                <div class="metric-value"><?php echo $orders_this_month; ?></div>
                <div class="metric-change">
                    <i class="fas fa-calendar"></i> <?php echo date('F'); ?>
                </div>
            </div>
        </div>

        <!-- Spending This Month -->
        <div class="metric-card">
            <div class="metric-icon" style="background: linear-gradient(135deg, #B7410E, #FF7F50);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="metric-content">
                <div class="metric-label">Spending This Month</div>
                <div class="metric-value">$<?php echo number_format($spending_this_month, 2); ?></div>
                <div class="metric-change">
                    <i class="fas fa-calendar"></i> <?php echo date('F'); ?>
                </div>
            </div>
        </div>

        <!-- Artisans Supported -->
        <div class="metric-card highlight">
            <div class="metric-icon" style="background: linear-gradient(135deg, #C1502F, #B7410E);">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-content">
                <div class="metric-label">Artisans Supported</div>
                <div class="metric-value"><?php echo $artisans_supported; ?></div>
                <div class="metric-change cultural">
                    <i class="fas fa-heart"></i> Ghanaian creators
                </div>
            </div>
        </div>
    </div>

    <!-- VISUAL CHARTS SECTION -->
    <div class="charts-section">
        <!-- Spending Over Time Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-area"></i> Spending Over Time</h3>
                <div class="chart-period">
                    <button class="period-btn active" data-period="6months">6 Months</button>
                    <button class="period-btn" data-period="year">1 Year</button>
                    <button class="period-btn" data-period="all">All Time</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="spendingChart"></canvas>
            </div>
        </div>

        <!-- Orders by Month Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-chart-bar"></i> Orders by Month</h3>
            </div>
            <div class="chart-container">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>
    </div>

    <!-- CURRENT CART -->
    <div class="cart-section">
        <div class="section-header">
            <h2><i class="fas fa-shopping-cart"></i> Current Cart</h2>
            <a href="<?php echo url('view/cart/view_cart.php'); ?>" class="view-all">View Full Cart →</a>
        </div>
        
        <div class="cart-card" id="cart-summary">
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn-primary">Start Shopping</a>
            </div>
        </div>
    </div>

    <!-- RECENT ORDERS -->
    <div class="orders-section">
        <div class="section-header">
            <h2><i class="fas fa-box"></i> Recent Orders (Last 5)</h2>
            <a href="<?php echo url('view/order/order_history.php'); ?>" class="view-all">View All Orders →</a>
        </div>
        
        <div class="orders-table-container">
            <?php if (empty($recent_orders)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No orders yet</p>
                <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn-primary">Browse Products</a>
            </div>
            <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recent_orders, 0, 5) as $order): ?>
                    <tr>
                        <td><strong>#<?php echo escape_html($order['order_id']); ?></strong></td>
                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                        <td><?php echo isset($order['item_count']) ? $order['item_count'] : '—'; ?> items</td>
                        <td class="amount">$<?php echo number_format($order['order_total'] ?? 0, 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower(escape_html($order['order_status'])); ?>">
                                <?php echo ucfirst(escape_html($order['order_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo url('view/order/order_details.php?id=' . $order['order_id']); ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="quick-actions">
        <div class="section-header">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div class="actions-grid">
            <a href="<?php echo url('view/product/all_product.php'); ?>" class="action-card">
                <i class="fas fa-search"></i>
                <span>Browse Products</span>
            </a>
            <a href="<?php echo url('view/order/order_history.php'); ?>" class="action-card">
                <i class="fas fa-history"></i>
                <span>Order History</span>
            </a>
            <a href="<?php echo url('view/user/profile.php'); ?>" class="action-card">
                <i class="fas fa-user-edit"></i>
                <span>Edit Profile</span>
            </a>
            <a href="<?php echo url('view/user/change_password.php'); ?>" class="action-card">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a>
        </div>
    </div>
    
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Dashboard Data & Charts Script -->
<script>
// Dashboard data from PHP
const dashboardData = {
    totalOrders: <?php echo $total_orders; ?>,
    totalSpent: <?php echo $total_spent; ?>,
    ordersThisMonth: <?php echo $orders_this_month; ?>,
    spendingThisMonth: <?php echo $spending_this_month; ?>,
    artisansSupported: <?php echo $artisans_supported; ?>
};

// TODO: Fetch historical data from API for charts
// This is placeholder data - replace with actual API calls
async function fetchChartData() {
    try {
        // TODO: Replace with actual API endpoint
        // const response = await fetch('/api/user/chart-data.php');
        // return await response.json();
        
        // Placeholder data
        const months = [];
        const spending = [];
        const orders = [];
        
        for (let i = 5; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            months.push(date.toLocaleDateString('en-US', { month: 'short' }));
            
            // Placeholder values
            spending.push(Math.random() * 300 + 50);
            orders.push(Math.floor(Math.random() * 5) + 1);
        }
        
        return { months, spending, orders };
    } catch (error) {
        console.error('Error fetching chart data:', error);
        return null;
    }
}

// Fetch cart data
async function fetchCartData() {
    try {
        // TODO: Replace with actual API endpoint
        // const response = await fetch('/api/cart/summary.php');
        // return await response.json();
        
        // Placeholder
        return {
            itemCount: 0,
            totalValue: 0,
            items: []
        };
    } catch (error) {
        console.error('Error fetching cart:', error);
        return null;
    }
}

// Update cart summary
function updateCart(cartData) {
    const cartSummary = document.getElementById('cart-summary');
    
    if (cartData.itemCount === 0) {
        cartSummary.innerHTML = `
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn-primary">Start Shopping</a>
            </div>
        `;
    } else {
        cartSummary.innerHTML = `
            <div class="cart-info">
                <div class="cart-items">
                    <i class="fas fa-shopping-bag"></i>
                    <span><strong>${cartData.itemCount}</strong> items in cart</span>
                </div>
                <div class="cart-total">
                    <span>Total:</span>
                    <strong>$${cartData.totalValue.toFixed(2)}</strong>
                </div>
            </div>
            <a href="<?php echo url('view/cart/view_cart.php'); ?>" class="btn-primary">
                <i class="fas fa-shopping-cart"></i> View Cart
            </a>
        `;
    }
}

// Chart colors (African Savanna theme)
const chartColors = {
    primary: '#FF9A56',      // Amber
    secondary: '#CC8B3C',    // Ochre
    tertiary: '#B7410E',     // Rust
    quaternary: '#E3B778',   // Sand
    grid: 'rgba(204, 139, 60, 0.1)',
    text: '#6B4423'          // Earth
};

// Create Spending Over Time Chart
async function createSpendingChart() {
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const data = await fetchChartData();
    
    if (!data) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.months,
            datasets: [{
                label: 'Spending ($)',
                data: data.spending,
                borderColor: chartColors.primary,
                backgroundColor: 'rgba(255, 154, 86, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: chartColors.primary,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 248, 231, 0.95)',
                    titleColor: chartColors.text,
                    bodyColor: chartColors.text,
                    borderColor: chartColors.secondary,
                    borderWidth: 2,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Spent: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: chartColors.grid
                    },
                    ticks: {
                        color: chartColors.text,
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                },
                x: {
                    grid: {
                        color: chartColors.grid
                    },
                    ticks: {
                        color: chartColors.text
                    }
                }
            }
        }
    });
}

// Create Orders by Month Chart
async function createOrdersChart() {
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const data = await fetchChartData();
    
    if (!data) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.months,
            datasets: [{
                label: 'Orders',
                data: data.orders,
                backgroundColor: [
                    chartColors.primary,
                    chartColors.secondary,
                    chartColors.tertiary,
                    chartColors.quaternary,
                    chartColors.primary,
                    chartColors.secondary
                ],
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 248, 231, 0.95)',
                    titleColor: chartColors.text,
                    bodyColor: chartColors.text,
                    borderColor: chartColors.secondary,
                    borderWidth: 2,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Orders: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: chartColors.grid
                    },
                    ticks: {
                        color: chartColors.text,
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: chartColors.text
                    }
                }
            }
        }
    });
}

// Initialize dashboard
async function initDashboard() {
    // Fetch and update cart
    const cart = await fetchCartData();
    if (cart) {
        updateCart(cart);
    }
    
    // Create charts
    await createSpendingChart();
    await createOrdersChart();
}

// Run on page load
document.addEventListener('DOMContentLoaded', initDashboard);
</script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>