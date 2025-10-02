<?php
/**
 * Order Class
 * 
 * Handles all order-related database operations including
 * order creation, management, and order items.
 */

class order_class extends db_class {
    
    /**
     * Create new order
     */
    public function create_order($customer_id, $total_amount, $shipping_address, $payment_method = 'pending') {
        $sql = "INSERT INTO orders 
                (customer_id, total_amount, shipping_address, payment_method, order_status)
                VALUES (?, ?, ?, ?, 'pending')";
        
        $params = [$customer_id, $total_amount, $shipping_address, $payment_method];
        $stmt = $this->execute($sql, $params);
        
        if ($stmt !== false) {
            return $this->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Add item to order
     */
    public function add_order_item($order_id, $product_id, $quantity, $price) {
        $sql = "INSERT INTO order_items 
                (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)";
        
        $params = [$order_id, $product_id, $quantity, $price];
        $stmt = $this->execute($sql, $params);
        
        return $stmt !== false;
    }
    
    /**
     * Get order by ID
     */
    public function get_order_by_id($order_id) {
        $sql = "SELECT o.*, c.customer_name, c.customer_email 
                FROM orders o 
                JOIN customer c ON o.customer_id = c.customer_id 
                WHERE o.order_id = ?";
        
        return $this->fetchRow($sql, [$order_id]);
    }
    
    /**
     * Get order items
     */
    public function get_order_items($order_id) {
        $sql = "SELECT oi.*, p.product_name, p.product_image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";
        
        return $this->fetchAll($sql, [$order_id]);
    }
    
    /**
     * Get customer orders
     */
    public function get_customer_orders($customer_id, $limit = 20, $offset = 0) {
        $sql = "SELECT * FROM orders 
                WHERE customer_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->fetchAll($sql, [$customer_id, $limit, $offset]);
    }
    
    /**
     * Update order status
     */
    public function update_order_status($order_id, $status) {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
        $stmt = $this->execute($sql, [$status, $order_id]);
        
        return $stmt !== false;
    }
    
    /**
     * Cancel order
     */
    public function cancel_order($order_id, $customer_id = null) {
        $sql = "UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?";
        $params = [$order_id];
        
        // If customer_id provided, ensure they own the order
        if ($customer_id !== null) {
            $sql .= " AND customer_id = ?";
            $params[] = $customer_id;
        }
        
        $stmt = $this->execute($sql, $params);
        return $stmt !== false;
    }
    
    /**
     * Get all orders (admin function)
     */
    public function get_all_orders($limit = 50, $offset = 0, $status = null) {
        $sql = "SELECT o.*, c.customer_name, c.customer_email 
                FROM orders o 
                JOIN customer c ON o.customer_id = c.customer_id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE o.order_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Calculate order total
     */
    public function calculate_order_total($order_id) {
        $sql = "SELECT SUM(quantity * price) as total 
                FROM order_items 
                WHERE order_id = ?";
        
        $result = $this->fetchRow($sql, [$order_id]);
        return $result ? $result['total'] : 0;
    }
    
    /**
     * Count customer orders
     */
    public function count_customer_orders($customer_id) {
        $sql = "SELECT COUNT(*) as total FROM orders WHERE customer_id = ?";
        $result = $this->fetchRow($sql, [$customer_id]);
        return $result ? $result['total'] : 0;
    }
}

?>
