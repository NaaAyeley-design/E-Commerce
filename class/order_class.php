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
        try {
            // Validate inputs
            if (empty($customer_id) || !is_numeric($customer_id)) {
                error_log("Invalid customer_id: " . $customer_id);
                return false;
            }
            
            if (empty($total_amount) || !is_numeric($total_amount) || $total_amount <= 0) {
                error_log("Invalid total_amount: " . $total_amount);
                return false;
            }
            
            if (empty($shipping_address)) {
                error_log("Empty shipping_address");
                return false;
            }
            
            // Check if orders table exists
            try {
                $table_check = $this->fetchRow("SHOW TABLES LIKE 'orders'");
                if (!$table_check) {
                    error_log("Orders table does not exist - attempting to create");
                    // Try to create the table
                    require_once __DIR__ . '/../db/create_orders_tables.php';
                    // Re-check
                    $table_check = $this->fetchRow("SHOW TABLES LIKE 'orders'");
                    if (!$table_check) {
                        error_log("Orders table still does not exist after creation attempt");
                        return false;
                    }
                }
            } catch (Exception $e) {
                error_log("Orders table check failed: " . $e->getMessage());
                // Continue anyway - table might exist
            }
            
            $sql = "INSERT INTO orders 
                    (customer_id, total_amount, shipping_address, payment_method, order_status)
                    VALUES (?, ?, ?, ?, 'pending')";
            
            $params = [(int)$customer_id, (float)$total_amount, $shipping_address, $payment_method];
            error_log("Creating order with params: customer_id=" . $customer_id . ", total=" . $total_amount);
            
            $stmt = $this->execute($sql, $params);
            
            if ($stmt === false) {
                error_log("Execute returned false for order creation");
                error_log("SQL: " . $sql);
                error_log("Params: " . json_encode($params));
                // Check if connection exists
                if (!$this->conn) {
                    error_log("Database connection is null");
                }
                return false;
            }
            
            $row_count = $stmt->rowCount();
            if ($row_count > 0) {
                $order_id = $this->lastInsertId();
                if ($order_id && $order_id > 0) {
                    error_log("Order created successfully with ID: " . $order_id);
                    return (int)$order_id;
                } else {
                    error_log("Order created but lastInsertId returned: " . var_export($order_id, true));
                    // Even if lastInsertId fails, check if the order was actually inserted
                    // by querying for the most recent order for this customer
                    $check_sql = "SELECT order_id FROM orders WHERE customer_id = ? ORDER BY order_id DESC LIMIT 1";
                    $check_result = $this->fetchRow($check_sql, [$customer_id]);
                    if ($check_result && isset($check_result['order_id'])) {
                        error_log("Order found via query: " . $check_result['order_id']);
                        return (int)$check_result['order_id'];
                    }
                    return false;
                }
            }
            
            error_log("Failed to create order - rowCount: " . $row_count);
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            return false;
        } catch (PDOException $e) {
            error_log("Create order PDO error: " . $e->getMessage());
            error_log("Create order PDO code: " . $e->getCode());
            error_log("Create order PDO trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("Create order error: " . $e->getMessage());
            error_log("Create order trace: " . $e->getTraceAsString());
            return false;
        } catch (Throwable $e) {
            error_log("Create order throwable error: " . $e->getMessage());
            error_log("Create order throwable trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Add item to order
     */
    public function add_order_item($order_id, $product_id, $quantity, $price) {
        try {
            // Validate inputs
            if (empty($order_id) || !is_numeric($order_id) || $order_id <= 0) {
                error_log("Invalid order_id: " . $order_id);
                return false;
            }
            
            if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
                error_log("Invalid product_id: " . $product_id);
                return false;
            }
            
            if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                error_log("Invalid quantity: " . $quantity);
                return false;
            }
            
            if (empty($price) || !is_numeric($price) || $price <= 0) {
                error_log("Invalid price: " . $price);
                return false;
            }
            
            // Check if order_items table exists
            try {
                $table_check = $this->fetchRow("SHOW TABLES LIKE 'order_items'");
                if (!$table_check) {
                    error_log("Order_items table does not exist - attempting to create");
                    // Try to create the table
                    require_once __DIR__ . '/../db/create_orders_tables.php';
                    // Re-check
                    $table_check = $this->fetchRow("SHOW TABLES LIKE 'order_items'");
                    if (!$table_check) {
                        error_log("Order_items table still does not exist after creation attempt");
                        return false;
                    }
                }
            } catch (Exception $e) {
                error_log("Order_items table check failed: " . $e->getMessage());
                // Continue anyway - table might exist
            }
            
            $sql = "INSERT INTO order_items 
                    (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)";
            
            $params = [(int)$order_id, (int)$product_id, (int)$quantity, (float)$price];
            error_log("Adding order item: order_id=$order_id, product_id=$product_id, quantity=$quantity, price=$price");
            
            $stmt = $this->execute($sql, $params);
            
            if ($stmt === false) {
                error_log("Execute returned false for order item addition");
                error_log("SQL: " . $sql);
                error_log("Params: " . json_encode($params));
                return false;
            }
            
            $row_count = $stmt->rowCount();
            if ($row_count > 0) {
                error_log("Order item added successfully: order_id=$order_id, product_id=$product_id, quantity=$quantity");
                return true;
            }
            
            error_log("Failed to add order item - rowCount: " . $row_count);
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            return false;
        } catch (PDOException $e) {
            error_log("Add order item PDO error: " . $e->getMessage());
            error_log("Add order item PDO code: " . $e->getCode());
            error_log("Add order item PDO trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("Add order item error: " . $e->getMessage());
            error_log("Add order item trace: " . $e->getTraceAsString());
            return false;
        } catch (Throwable $e) {
            error_log("Add order item throwable error: " . $e->getMessage());
            error_log("Add order item throwable trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Record payment for an order
     * Supports Paystack payment fields
     */
    public function record_payment($amount, $customer_id, $order_id, $currency, $payment_date, $payment_method = 'direct', $transaction_ref = null, $authorization_code = null, $payment_channel = null) {
        try {
            // Validate inputs
            if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
                error_log("Invalid amount: " . $amount);
                return false;
            }
            
            if (empty($customer_id) || !is_numeric($customer_id)) {
                error_log("Invalid customer_id: " . $customer_id);
                return false;
            }
            
            if (empty($order_id) || !is_numeric($order_id)) {
                error_log("Invalid order_id: " . $order_id);
                return false;
            }
            
            // Build SQL with optional Paystack fields
            $columns = ["amt", "customer_id", "order_id", "currency", "payment_date"];
            $placeholders = ["?", "?", "?", "?", "?"];
            $params = [(float)$amount, (int)$customer_id, (int)$order_id, $currency, $payment_date];
            
            // Add optional fields if provided
            if ($payment_method !== null) {
                $columns[] = "payment_method";
                $placeholders[] = "?";
                $params[] = $payment_method;
            }
            
            if ($transaction_ref !== null) {
                $columns[] = "transaction_ref";
                $placeholders[] = "?";
                $params[] = $transaction_ref;
            }
            
            if ($authorization_code !== null) {
                $columns[] = "authorization_code";
                $placeholders[] = "?";
                $params[] = $authorization_code;
            }
            
            if ($payment_channel !== null) {
                $columns[] = "payment_channel";
                $placeholders[] = "?";
                $params[] = $payment_channel;
            }
            
            $sql = "INSERT INTO payment (" . implode(", ", $columns) . ") 
                    VALUES (" . implode(", ", $placeholders) . ")";
            
            error_log("Recording payment: amount=$amount, order_id=$order_id, method=$payment_method, ref=$transaction_ref");
            
            $stmt = $this->execute($sql, $params);
            
            if ($stmt === false) {
                error_log("Execute returned false for payment recording");
                return false;
            }
            
            $payment_id = $this->lastInsertId();
            if ($payment_id && $payment_id > 0) {
                error_log("Payment recorded successfully with ID: " . $payment_id);
                return (int)$payment_id;
            }
            
            error_log("Failed to record payment - lastInsertId: " . var_export($payment_id, true));
            return false;
            
        } catch (PDOException $e) {
            error_log("Record payment PDO error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Record payment error: " . $e->getMessage());
            return false;
        }
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
        $sql = "SELECT oi.*, p.product_title, p.product_image 
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
        try {
            $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($status, $valid_statuses)) {
                return false;
            }
            
            $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $stmt = $this->execute($sql, [$status, $order_id]);
            
            return $stmt !== false && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel order
     */
    public function cancel_order($order_id, $customer_id = null) {
        try {
            $sql = "UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?";
            $params = [$order_id];
            
            // If customer_id provided, ensure they own the order
            if ($customer_id !== null) {
                $sql .= " AND customer_id = ?";
                $params[] = $customer_id;
            }
            
            $stmt = $this->execute($sql, $params);
            return $stmt !== false && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Cancel order error: " . $e->getMessage());
            return false;
        }
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
    
    /**
     * Count all orders (for admin dashboard).
     *
     * @param string|null $status Optional status filter
     * @return int Total number of orders.
     */
    public function count_all_orders($status = null) {
        try {
            if (!isset($this->conn) || $this->conn === null) {
                return 0;
            }
            $sql = "SELECT COUNT(*) as total FROM orders";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE order_status = ?";
                $params[] = $status;
            }
            
            $result = $this->fetchRow($sql, $params);
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            error_log("count_all_orders error: " . $e->getMessage());
            return 0;
        }
    }
}

?>
