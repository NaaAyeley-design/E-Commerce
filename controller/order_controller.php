<?php
/**
 * Order Controller
 * 
 * Handles all order-related operations including order creation,
 * management, payment processing, and order tracking.
 */

// Include core settings and general controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/general_controller.php';

/**
 * Create new order
 */
function create_order_ctr($customer_id, $items, $shipping_address, $payment_method = 'pending') {
    try {
        // Check authentication (don't redirect, just return error if not logged in)
        if (!is_logged_in()) {
            return [
                'success' => false,
                'message' => 'Please log in to create an order.'
            ];
        }
        
        // Validate input
        if (empty($items) || !is_array($items)) {
            return [
                'success' => false,
                'message' => 'Order must contain at least one item.'
            ];
        }
        
        if (empty($shipping_address)) {
            return [
                'success' => false,
                'message' => 'Shipping address is required.'
            ];
        }
        
        // Validate and calculate order total
        $order_total = 0;
        $validated_items = [];
        $product = new product_class();
        
        foreach ($items as $item) {
            if (!isset($item['product_id'], $item['quantity']) || 
                !is_numeric($item['product_id']) || 
                !is_numeric($item['quantity']) || 
                $item['quantity'] <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid item data.'
                ];
            }
            
            // Get product details
            $product_data = $product->get_product_by_id($item['product_id']);
            if (!$product_data) {
                return [
                    'success' => false,
                    'message' => "Product with ID {$item['product_id']} not found."
                ];
            }
            
            // Calculate item total
            $item_total = $product_data['product_price'] * $item['quantity'];
            $order_total += $item_total;
            
            $validated_items[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $product_data['product_price'],
                'total' => $item_total,
                'product_title' => $product_data['product_title']
            ];
        }
        
        // Create order
        $order = new order_class();
        $order_id = $order->create_order($customer_id, $order_total, $shipping_address, $payment_method);
        
        if (!$order_id) {
            return [
                'success' => false,
                'message' => 'Failed to create order.'
            ];
        }
        
        // Add order items
        foreach ($validated_items as $item) {
            $item_added = $order->add_order_item(
                $order_id, 
                $item['product_id'], 
                $item['quantity'], 
                $item['price']
            );
            
            if (!$item_added) {
                // Rollback: cancel the order if item addition fails
                $order->cancel_order($order_id);
                return [
                    'success' => false,
                    'message' => 'Failed to add order items.'
                ];
            }
        }
        
        // Log order creation
        log_activity('order_created', "Order created: #$order_id, Total: $order_total", $customer_id);
        
        return [
            'success' => true,
            'message' => 'Order created successfully.',
            'order_id' => $order_id,
            'total' => $order_total
        ];
        
    } catch (Exception $e) {
        error_log("Create order error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating order.'
        ];
    }
}

/**
 * Get order details
 */
function get_order_ctr($order_id, $customer_id = null) {
    try {
        if (!is_numeric($order_id) || $order_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid order ID.'
            ];
        }
        
        $order = new order_class();
        $order_data = $order->get_order_by_id($order_id);
        
        if (!$order_data) {
            return [
                'success' => false,
                'message' => 'Order not found.'
            ];
        }
        
        // Check if user has permission to view this order
        if ($customer_id && $order_data['customer_id'] != $customer_id && !is_admin()) {
            return [
                'success' => false,
                'message' => 'Access denied.'
            ];
        }
        
        // Get order items
        $order_items = $order->get_order_items($order_id);
        
        return [
            'success' => true,
            'order' => $order_data,
            'items' => $order_items
        ];
        
    } catch (Exception $e) {
        error_log("Get order error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving order.'
        ];
    }
}

/**
 * Get customer orders
 */
function get_customer_orders_ctr($customer_id, $page = 1, $limit = 10) {
    try {
        // Check authentication
        require_auth();
        
        // Ensure user can only see their own orders (unless admin)
        if ($_SESSION['customer_id'] != $customer_id && !is_admin()) {
            return [
                'success' => false,
                'message' => 'Access denied.'
            ];
        }
        
        $offset = ($page - 1) * $limit;
        
        $order = new order_class();
        $orders = $order->get_customer_orders($customer_id, $limit, $offset);
        $total_orders = $order->count_customer_orders($customer_id);
        
        $pagination = get_paginated_results($total_orders, $limit, $page);
        
        return [
            'success' => true,
            'orders' => $orders,
            'pagination' => $pagination
        ];
        
    } catch (Exception $e) {
        error_log("Get customer orders error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving orders.'
        ];
    }
}

/**
 * Update order status (Admin only)
 */
function update_order_status_ctr($order_id, $status) {
    try {
        // Check admin privileges
        require_admin();
        
        if (!is_numeric($order_id) || $order_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid order ID.'
            ];
        }
        
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            return [
                'success' => false,
                'message' => 'Invalid order status.'
            ];
        }
        
        $order = new order_class();
        
        // Get current order data
        $order_data = $order->get_order_by_id($order_id);
        if (!$order_data) {
            return [
                'success' => false,
                'message' => 'Order not found.'
            ];
        }
        
        $updated = $order->update_order_status($order_id, $status);
        
        if ($updated) {
            // Log status update
            log_activity('order_status_updated', 
                "Order #$order_id status changed from {$order_data['order_status']} to $status", 
                $_SESSION['customer_id']);
            
            // Send notification to customer (placeholder)
            send_email_notification(
                $order_data['customer_email'],
                "Order Status Update - Order #$order_id",
                "Your order status has been updated to: $status"
            );
            
            return [
                'success' => true,
                'message' => 'Order status updated successfully.',
                'new_status' => $status
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update order status.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Update order status error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating order status.'
        ];
    }
}

/**
 * Cancel order
 */
function cancel_order_ctr($order_id, $customer_id = null) {
    try {
        if (!is_numeric($order_id) || $order_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid order ID.'
            ];
        }
        
        $order = new order_class();
        
        // Get order data
        $order_data = $order->get_order_by_id($order_id);
        if (!$order_data) {
            return [
                'success' => false,
                'message' => 'Order not found.'
            ];
        }
        
        // Check permissions
        if ($customer_id && $order_data['customer_id'] != $customer_id && !is_admin()) {
            return [
                'success' => false,
                'message' => 'Access denied.'
            ];
        }
        
        // Check if order can be cancelled
        if (in_array($order_data['order_status'], ['shipped', 'delivered', 'cancelled'])) {
            return [
                'success' => false,
                'message' => 'Order cannot be cancelled at this stage.'
            ];
        }
        
        // Cancel the order
        $cancelled = $order->cancel_order($order_id, $customer_id);
        
        if ($cancelled) {
            // Restore product stock
            $order_items = $order->get_order_items($order_id);
            $product = new product_class();
            
            foreach ($order_items as $item) {
                $product_data = $product->get_product_by_id($item['product_id']);
                if ($product_data) {
                    $new_stock = $product_data['stock_quantity'] + $item['quantity'];
                    $product->update_stock($item['product_id'], $new_stock);
                }
            }
            
            // Log cancellation
            log_activity('order_cancelled', "Order #$order_id cancelled", $customer_id ?? $_SESSION['customer_id']);
            
            return [
                'success' => true,
                'message' => 'Order cancelled successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to cancel order.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Cancel order error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error cancelling order.'
        ];
    }
}

/**
 * Get all orders (Admin only)
 */
function get_all_orders_ctr($page = 1, $limit = 20, $status = null) {
    try {
        // Check admin privileges
        require_admin();
        
        $offset = ($page - 1) * $limit;
        
        $order = new order_class();
        $orders = $order->get_all_orders($limit, $offset, $status);
        
        // Get total count (with status filter if provided)
        $total_orders = $order->count_all_orders($status);
        
        $pagination = get_paginated_results($total_orders, $limit, $page);
        
        return [
            'success' => true,
            'orders' => $orders,
            'pagination' => $pagination,
            'filter_status' => $status
        ];
        
    } catch (Exception $e) {
        error_log("Get all orders error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving orders.'
        ];
    }
}

/**
 * Process payment (placeholder for future implementation)
 */
function process_payment_ctr($order_id, $payment_method, $payment_data) {
    try {
        if (!is_numeric($order_id) || $order_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid order ID.'
            ];
        }
        
        $order = new order_class();
        $order_data = $order->get_order_by_id($order_id);
        
        if (!$order_data) {
            return [
                'success' => false,
                'message' => 'Order not found.'
            ];
        }
        
        // Check if order belongs to current user
        if ($order_data['customer_id'] != $_SESSION['customer_id'] && !is_admin()) {
            return [
                'success' => false,
                'message' => 'Access denied.'
            ];
        }
        
        // Check if order is in correct status for payment
        if ($order_data['order_status'] !== 'pending') {
            return [
                'success' => false,
                'message' => 'Order is not available for payment.'
            ];
        }
        
        // TODO: Integrate with payment gateway (Stripe, PayPal, etc.)
        // For now, simulate successful payment
        
        // Update order status to processing
        $order->update_order_status($order_id, 'processing');
        
        // Log payment
        log_activity('payment_processed', 
            "Payment processed for order #$order_id using $payment_method", 
            $order_data['customer_id']);
        
        return [
            'success' => true,
            'message' => 'Payment processed successfully.',
            'order_id' => $order_id,
            'payment_method' => $payment_method
        ];
        
    } catch (Exception $e) {
        error_log("Process payment error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error processing payment.'
        ];
    }
}

/**
 * Get order statistics (Admin only)
 */
function get_order_statistics_ctr() {
    try {
        // Check admin privileges
        require_admin();
        
        // TODO: Implement actual statistics queries
        // For now, return placeholder data
        
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'processing_orders' => 0,
            'shipped_orders' => 0,
            'delivered_orders' => 0,
            'cancelled_orders' => 0,
            'total_revenue' => 0.00,
            'average_order_value' => 0.00
        ];
        
        return [
            'success' => true,
            'statistics' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("Get order statistics error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving order statistics.'
        ];
    }
}

/**
 * Generate order invoice (placeholder for future implementation)
 */
function generate_order_invoice_ctr($order_id, $customer_id = null) {
    try {
        $order_result = get_order_ctr($order_id, $customer_id);
        
        if (!$order_result['success']) {
            return $order_result;
        }
        
        // TODO: Generate PDF invoice using a library like TCPDF or FPDF
        // For now, return a placeholder response
        
        log_activity('invoice_generated', "Invoice generated for order #$order_id", $customer_id);
        
        return [
            'success' => true,
            'message' => 'Invoice generation not yet implemented.',
            'order' => $order_result['order']
        ];
        
    } catch (Exception $e) {
        error_log("Generate invoice error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error generating invoice.'
        ];
    }
}

/**
 * Track order (placeholder for future implementation)
 */
function track_order_ctr($order_id, $tracking_number = null) {
    try {
        if (!is_numeric($order_id) || $order_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid order ID.'
            ];
        }
        
        $order_result = get_order_ctr($order_id);
        
        if (!$order_result['success']) {
            return $order_result;
        }
        
        // TODO: Integrate with shipping provider APIs for real tracking
        // For now, return placeholder tracking data
        
        $tracking_data = [
            'order_id' => $order_id,
            'tracking_number' => $tracking_number ?: 'TRK' . str_pad($order_id, 10, '0', STR_PAD_LEFT),
            'status' => $order_result['order']['order_status'],
            'estimated_delivery' => date('Y-m-d', strtotime('+7 days')),
            'tracking_events' => [
                [
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'Order received',
                    'location' => 'Warehouse'
                ]
            ]
        ];
        
        return [
            'success' => true,
            'tracking' => $tracking_data
        ];
        
    } catch (Exception $e) {
        error_log("Track order error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error tracking order.'
        ];
    }
}

?>
