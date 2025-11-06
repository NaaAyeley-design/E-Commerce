<?php
/**
 * Order Management Page - Admin
 * 
 * Displays all orders with customer information
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/order_controller.php';
require_once __DIR__ . '/../../../class/order_class.php';

// Set page variables
$page_title = 'Order Management';
$page_description = 'View and manage all customer orders.';
$body_class = 'orders-page';
$additional_css = ['admin_orders.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

if (!is_admin()) {
    header('Location: ' . BASE_URL . '/view/user/dashboard.php');
    exit;
}

// Get filter parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : null;
$limit = 20;

// Get orders
try {
    $order_result = get_all_orders_ctr($page, $limit, $status_filter);
    
    if ($order_result && isset($order_result['success']) && $order_result['success']) {
        $orders = $order_result['orders'] ?? [];
        $pagination = $order_result['pagination'] ?? [];
    } else {
        $orders = [];
        $pagination = [];
        $error = $order_result['message'] ?? 'Failed to load orders.';
    }
} catch (Exception $e) {
    error_log("Get orders error: " . $e->getMessage());
    $orders = [];
    $pagination = [];
    $error = 'An error occurred while loading orders.';
}

// Get order items for each order
$order_class = new order_class();
foreach ($orders as &$order) {
    try {
        $order['items'] = $order_class->get_order_items($order['order_id']);
    } catch (Exception $e) {
        error_log("Get order items error: " . $e->getMessage());
        $order['items'] = [];
    }
}
unset($order);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Order Management</h1>
        <p class="page-description">View and manage all customer orders</p>
    </div>

    <?php if (isset($error) && !empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo escape_html($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-controls">
            <form method="GET" action="" class="filter-form">
                <label for="status-filter">Filter by Status:</label>
                <select name="status" id="status-filter" class="form-input">
                    <option value="">All Orders</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if ($status_filter): ?>
                    <a href="<?php echo url('view/admin/orders.php'); ?>" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear Filter
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="orders-section">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h2>No Orders Found</h2>
                <p>There are no orders<?php echo $status_filter ? ' with status "' . escape_html($status_filter) . '"' : ''; ?> at this time.</p>
            </div>
        <?php else: ?>
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr data-order-id="<?php echo $order['order_id']; ?>" class="order-row">
                                <td class="order-id">#<?php echo $order['order_id']; ?></td>
                                <td class="customer-name">
                                    <strong><?php echo escape_html($order['customer_name'] ?? 'N/A'); ?></strong>
                                </td>
                                <td class="customer-email">
                                    <?php echo escape_html($order['customer_email'] ?? 'N/A'); ?>
                                </td>
                                <td class="order-items">
                                    <?php 
                                    $items = $order['items'] ?? [];
                                    $item_count = count($items);
                                    if ($item_count > 0) {
                                        echo '<div class="items-summary">';
                                        echo '<span class="item-count">' . $item_count . ' item' . ($item_count > 1 ? 's' : '') . '</span>';
                                        echo '<button class="btn-view-items btn-link" data-order-id="' . $order['order_id'] . '" title="View cart items">';
                                        echo '<i class="fas fa-shopping-cart"></i> View Cart';
                                        echo '</button>';
                                        echo '</div>';
                                    } else {
                                        echo 'No items';
                                    }
                                    ?>
                                </td>
                                <td class="order-total">
                                    <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </td>
                                <td class="order-status">
                                    <span class="status-badge status-<?php echo strtolower($order['order_status'] ?? 'pending'); ?>">
                                        <?php echo escape_html(ucfirst($order['order_status'] ?? 'Pending')); ?>
                                    </span>
                                </td>
                                <td class="order-date">
                                    <?php 
                                    $date = isset($order['created_at']) ? strtotime($order['created_at']) : time();
                                    echo date('M d, Y', $date);
                                    ?>
                                </td>
                                <td class="order-actions">
                                    <button class="btn btn-sm btn-primary view-order-details" 
                                            data-order-id="<?php echo $order['order_id']; ?>"
                                            title="View Details">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <!-- Cart Items Row (Expandable) -->
                            <?php if (!empty($items)): ?>
                                <tr class="cart-items-row" id="cart-items-<?php echo $order['order_id']; ?>" style="display: none;">
                                    <td colspan="8" class="cart-items-container">
                                        <div class="cart-items-header">
                                            <h4><i class="fas fa-shopping-cart"></i> Cart Items for <?php echo escape_html($order['customer_name'] ?? 'Customer'); ?></h4>
                                            <button class="btn-close-cart" data-order-id="<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="cart-items-list">
                                            <table class="cart-items-table">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Image</th>
                                                        <th>Quantity</th>
                                                        <th>Unit Price</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items as $item): ?>
                                                        <tr>
                                                            <td class="product-name">
                                                                <strong><?php echo escape_html($item['product_title'] ?? 'N/A'); ?></strong>
                                                            </td>
                                                            <td class="product-image">
                                                                <?php if (!empty($item['product_image'])): ?>
                                                                    <?php 
                                                                    $image_path = $item['product_image'];
                                                                    // Handle image path
                                                                    if (strpos($image_path, 'uploads/') === 0) {
                                                                        $image_url = str_replace('/public_html', '', BASE_URL) . '/' . $image_path;
                                                                    } else {
                                                                        $image_url = BASE_URL . '/' . ltrim($image_path, '/');
                                                                    }
                                                                    ?>
                                                                    <img src="<?php echo escape_html($image_url); ?>" 
                                                                         alt="<?php echo escape_html($item['product_title'] ?? ''); ?>"
                                                                         onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
                                                                <?php else: ?>
                                                                    <img src="<?php echo ASSETS_URL; ?>/images/placeholder-product.svg" alt="No image">
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="product-quantity">
                                                                <span class="quantity-badge"><?php echo $item['quantity'] ?? 0; ?></span>
                                                            </td>
                                                            <td class="product-price">
                                                                $<?php echo number_format($item['price'] ?? 0, 2); ?>
                                                            </td>
                                                            <td class="product-subtotal">
                                                                <strong>$<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 2); ?></strong>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4" class="cart-total-label">
                                                            <strong>Cart Total:</strong>
                                                        </td>
                                                        <td class="cart-total-amount">
                                                            <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (isset($pagination) && isset($pagination['total_pages']) && $pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($pagination['current_page'] > 1): ?>
                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['total_pages']; ?>
                    </span>
                    
                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Order Details</h2>
            <button class="modal-close" id="close-order-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="order-details-content">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading order details...
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

<script>
// Order details modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('order-details-modal');
    const closeBtn = document.getElementById('close-order-modal');
    const viewButtons = document.querySelectorAll('.view-order-details');
    
    // Close modal
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close on outside click
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // View order details
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            loadOrderDetails(orderId);
        });
    });
    
    // View cart items
    const viewCartButtons = document.querySelectorAll('.btn-view-items');
    viewCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const cartRow = document.getElementById('cart-items-' + orderId);
            if (cartRow) {
                // Close all other cart rows
                document.querySelectorAll('.cart-items-row').forEach(row => {
                    if (row.id !== 'cart-items-' + orderId) {
                        row.style.display = 'none';
                    }
                });
                // Toggle current cart row
                if (cartRow.style.display === 'none' || cartRow.style.display === '') {
                    cartRow.style.display = 'table-row';
                    cartRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    cartRow.style.display = 'none';
                }
            }
        });
    });
    
    // Close cart items
    const closeCartButtons = document.querySelectorAll('.btn-close-cart');
    closeCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const cartRow = document.getElementById('cart-items-' + orderId);
            if (cartRow) {
                cartRow.style.display = 'none';
            }
        });
    });
    
    function loadOrderDetails(orderId) {
        modal.style.display = 'block';
        const content = document.getElementById('order-details-content');
        content.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading order details...</div>';
        
        // Fetch order details
        const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/get_order_action.php';
        
        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                order_id: orderId
            }),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.order) {
                displayOrderDetails(data.order, data.items || []);
            } else {
                content.innerHTML = '<div class="error">Failed to load order details.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            content.innerHTML = '<div class="error">An error occurred while loading order details.</div>';
        });
    }
    
    function displayOrderDetails(order, items) {
        const content = document.getElementById('order-details-content');
        let html = `
            <div class="order-details">
                <div class="order-info-section">
                    <h3>Order Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Order ID:</label>
                            <span>#${order.order_id}</span>
                        </div>
                        <div class="info-item">
                            <label>Customer:</label>
                            <span>${order.customer_name || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span>${order.customer_email || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Status:</label>
                            <span class="status-badge status-${(order.order_status || 'pending').toLowerCase()}">
                                ${(order.order_status || 'Pending').charAt(0).toUpperCase() + (order.order_status || 'Pending').slice(1)}
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Total Amount:</label>
                            <span class="amount">$${parseFloat(order.total_amount || 0).toFixed(2)}</span>
                        </div>
                        <div class="info-item">
                            <label>Date:</label>
                            <span>${new Date(order.created_at).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
                
                <div class="shipping-section">
                    <h3>Shipping Address</h3>
                    <div class="shipping-address">
                        ${(order.shipping_address || 'N/A').replace(/\n/g, '<br>')}
                    </div>
                </div>
                
                <div class="order-items-section">
                    <h3>Order Items</h3>
                    <table class="items-table">
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
        
        items.forEach(item => {
            html += `
                <tr>
                    <td>${item.product_title || 'N/A'}</td>
                    <td>${item.quantity || 0}</td>
                    <td>$${parseFloat(item.price || 0).toFixed(2)}</td>
                    <td>$${parseFloat((item.price || 0) * (item.quantity || 0)).toFixed(2)}</td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
    }
});
</script>

