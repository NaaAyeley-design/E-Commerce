<?php
/**
 * Order Class
 * 
 * Handles all order-related database operations including
 * order creation, management, and order items.
 */

class order_class extends db_class {
    
    /**
     * Generate unique invoice number
     * Format: INV-YYYYMMDD-HHMMSS-XXXXX (where XXXXX is a random 5-digit number)
     */
    private function generate_invoice_no() {
        $prefix = 'INV';
        $date_part = date('Ymd-His');
        $random_part = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $invoice_no = $prefix . '-' . $date_part . '-' . $random_part;
        
        // Ensure uniqueness by checking database
        $max_attempts = 10;
        $attempt = 0;
        while ($attempt < $max_attempts) {
            $existing = $this->fetchRow("SELECT order_id FROM orders WHERE invoice_no = ?", [$invoice_no]);
            if (!$existing) {
                return $invoice_no; // Unique invoice number found
            }
            // Regenerate if duplicate found
            $random_part = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $invoice_no = $prefix . '-' . $date_part . '-' . $random_part;
            $attempt++;
        }
        
        // Fallback: use timestamp with microseconds for guaranteed uniqueness
        $invoice_no = $prefix . '-' . date('Ymd-His') . '-' . substr(str_replace('.', '', microtime(true)), -5);
        return $invoice_no;
    }
    
    /**
     * Create new order
     * 
     * Saves the following fields to the database:
     * - order_id: Auto-generated unique identifier (AUTO_INCREMENT)
     * - customer_id: ID of the customer placing the order
     * - invoice_no: Unique invoice/receipt number (generated automatically)
     * - order_date: Timestamp when the order was placed (current timestamp)
     * - order_status: Current status of the order (default: 'pending')
     */
    public function create_order($customer_id, $total_amount, $shipping_address, $payment_method = 'pending') {
        try {
            error_log("=== ORDER CREATION START ===");
            error_log("Customer ID: " . var_export($customer_id, true));
            error_log("Total Amount: " . var_export($total_amount, true));
            error_log("Shipping Address: " . var_export($shipping_address, true));
            error_log("Payment Method: " . var_export($payment_method, true));
            
            // Validate inputs
            if (empty($customer_id) || !is_numeric($customer_id)) {
                error_log("ERROR: Invalid customer_id: " . var_export($customer_id, true));
                return false;
            }
            
            if (empty($total_amount) || !is_numeric($total_amount) || $total_amount <= 0) {
                error_log("ERROR: Invalid total_amount: " . var_export($total_amount, true));
                return false;
            }
            
            if (empty($shipping_address)) {
                error_log("ERROR: Empty shipping_address");
                return false;
            }
            
            // Check database connection
            $conn = $this->getConnection();
            if (!$conn) {
                error_log("ERROR: Database connection is null");
                return false;
            }
            error_log("✓ Database connection established");
            error_log("Connection type: " . get_class($conn));
            
            // Verify it's PDO
            if (!($conn instanceof PDO)) {
                error_log("ERROR: Connection is not PDO instance. Got: " . get_class($conn));
                return false;
            }
            
            // Check if customer exists BEFORE creating order
            error_log("Checking if customer exists...");
            $customer_check = $this->fetchRow("SELECT customer_id FROM customer WHERE customer_id = ?", [(int)$customer_id]);
            if (!$customer_check) {
                error_log("ERROR: Customer ID $customer_id does NOT exist in customer table");
                error_log("This will cause a foreign key constraint error");
                
                // List available customers for debugging
                $all_customers = $this->fetchAll("SELECT customer_id, customer_name, customer_email FROM customer LIMIT 5");
                if ($all_customers) {
                    error_log("Available customers in database:");
                    foreach ($all_customers as $cust) {
                        error_log("  - ID: {$cust['customer_id']}, Name: {$cust['customer_name']}, Email: {$cust['customer_email']}");
                    }
                } else {
                    error_log("WARNING: No customers found in database at all!");
                }
                return false;
            }
            error_log("✓ Customer exists: ID={$customer_check['customer_id']}");
            
            // Check if orders table exists
            error_log("Checking if orders table exists...");
            try {
                $table_check = $this->fetchRow("SHOW TABLES LIKE 'orders'");
                if (!$table_check) {
                    error_log("WARNING: Orders table does not exist - attempting to create");
                    // Try to create the table
                    require_once __DIR__ . '/../db/create_orders_tables.php';
                    // Re-check
                    $table_check = $this->fetchRow("SHOW TABLES LIKE 'orders'");
                    if (!$table_check) {
                        error_log("ERROR: Orders table still does not exist after creation attempt");
                        return false;
                    }
                    error_log("✓ Orders table created successfully");
                } else {
                    error_log("✓ Orders table exists");
                }
            } catch (Exception $e) {
                error_log("WARNING: Orders table check failed: " . $e->getMessage());
                // Continue anyway - table might exist
            }
            
            // Verify table structure
            error_log("Verifying orders table structure...");
            $table_structure = $this->fetchAll("DESCRIBE orders");
            if ($table_structure) {
                $columns = array_column($table_structure, 'Field');
                error_log("Orders table columns: " . implode(', ', $columns));
                
                $required_columns = ['order_id', 'customer_id', 'total_amount', 'shipping_address', 'payment_method', 'order_status'];
                $missing_columns = array_diff($required_columns, $columns);
                if (!empty($missing_columns)) {
                    error_log("ERROR: Missing required columns: " . implode(', ', $missing_columns));
                    return false;
                }
                error_log("✓ All required columns exist");
            } else {
                error_log("WARNING: Cannot describe orders table structure");
            }
            
            // Generate unique invoice number
            $invoice_no = $this->generate_invoice_no();
            error_log("Generated invoice_no: " . $invoice_no);
            
            // Set order_date to current timestamp
            $order_date = date('Y-m-d H:i:s');
            error_log("Setting order_date: " . $order_date);
            
            // Set initial order_status to 'pending'
            $order_status = 'pending';
            error_log("Setting order_status: " . $order_status);
            
            // INSERT statement with ALL required fields:
            // - customer_id: ID of the customer placing the order
            // - invoice_no: Unique invoice/receipt number
            // - total_amount: Total order amount
            // - shipping_address: Shipping address
            // - payment_method: Payment method used
            // - order_status: Initial status (pending)
            // - order_date: Timestamp when order was placed
            $sql = "INSERT INTO orders 
                    (customer_id, invoice_no, total_amount, shipping_address, payment_method, order_status, order_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                (int)$customer_id,           // customer_id: ID of the customer placing the order
                $invoice_no,                 // invoice_no: Unique invoice/receipt number
                (float)$total_amount,        // total_amount: Total order amount
                $shipping_address,           // shipping_address: Shipping address
                $payment_method,              // payment_method: Payment method used
                $order_status,                // order_status: Initial status (pending)
                $order_date                   // order_date: Timestamp when order was placed
            ];
            error_log("Executing SQL: " . $sql);
            error_log("Parameters: " . json_encode($params));
            
            $stmt = $this->execute($sql, $params);
            
            if ($stmt === false) {
                error_log("ERROR: Execute returned false for order creation");
                error_log("SQL: " . $sql);
                error_log("Params: " . json_encode($params));
                
                // Get detailed error information
                $conn = $this->getConnection();
                if ($conn) {
                    $error_info = $conn->errorInfo();
                    error_log("PDO Error Info: " . json_encode($error_info));
                    
                    // Check for specific error codes
                    if (isset($error_info[1])) {
                        $error_code = $error_info[1];
                        if ($error_code == 1452) {
                            error_log("FOREIGN KEY CONSTRAINT ERROR (1452): Customer ID $customer_id does not exist in customer table");
                        } elseif ($error_code == 1146) {
                            error_log("TABLE NOT FOUND ERROR (1146): Orders table does not exist");
                        } elseif ($error_code == 1054) {
                            error_log("COLUMN NOT FOUND ERROR (1054): One or more columns don't exist in orders table");
                        } elseif ($error_code == 1062) {
                            error_log("DUPLICATE ENTRY ERROR (1062): Duplicate key value");
                        } elseif ($error_code == 1048) {
                            error_log("NULL VALUE ERROR (1048): NULL value in NOT NULL column");
                        }
                    }
                    
                    if (isset($error_info[2])) {
                        error_log("PDO Error Message: " . $error_info[2]);
                    }
                } else {
                    error_log("ERROR: Database connection is null - cannot get error details");
                }
                
                error_log("=== ORDER CREATION FAILED ===");
                return false;
            }
            
            error_log("✓ SQL executed successfully");
            
            $row_count = $stmt->rowCount();
            error_log("Rows affected: " . $row_count);
            
            if ($row_count > 0) {
                $order_id = $this->lastInsertId();
                error_log("lastInsertId() returned: " . var_export($order_id, true));
                
                if ($order_id && $order_id > 0) {
                    error_log("✓ Order created successfully with ID: " . $order_id);
                    
                    // Verify order was actually inserted
                    $verify = $this->fetchRow("SELECT * FROM orders WHERE order_id = ?", [$order_id]);
                    if ($verify) {
                        error_log("✓ Order verified in database");
                        error_log("=== ORDER CREATION SUCCESS ===");
                        return (int)$order_id;
                    } else {
                        error_log("ERROR: Order ID $order_id returned but order not found in database");
                        error_log("=== ORDER CREATION FAILED ===");
                        return false;
                    }
                } else {
                    error_log("WARNING: Order created but lastInsertId returned: " . var_export($order_id, true));
                    // Even if lastInsertId fails, check if the order was actually inserted
                    // by querying for the most recent order for this customer
                    $check_sql = "SELECT order_id FROM orders WHERE customer_id = ? ORDER BY order_id DESC LIMIT 1";
                    $check_result = $this->fetchRow($check_sql, [$customer_id]);
                    if ($check_result && isset($check_result['order_id'])) {
                        error_log("✓ Order found via query: " . $check_result['order_id']);
                        error_log("=== ORDER CREATION SUCCESS (via query) ===");
                        return (int)$check_result['order_id'];
                    }
                    error_log("ERROR: Order not found even after query");
                    error_log("=== ORDER CREATION FAILED ===");
                    return false;
                }
            }
            
            error_log("ERROR: Failed to create order - rowCount: " . $row_count);
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            error_log("=== ORDER CREATION FAILED ===");
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
        try {
            $sql = "SELECT * FROM orders 
                WHERE customer_id = ? 
                ORDER BY order_id DESC 
                LIMIT ? OFFSET ?";

            // Use PDO directly to properly bind integer parameters
            $conn = $this->getConnection();
            if (!$conn) {
                error_log("get_customer_orders: No database connection");
                return [];
            }

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, (int)$customer_id, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("get_customer_orders: Found " . count($results) . " orders for customer $customer_id");

            return $results ? $results : [];

        } catch (PDOException $e) {
            error_log("get_customer_orders PDO error: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("get_customer_orders error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update order status
     */
    public function update_order_status($order_id, $status) {
        try {
            // Accept more status values including 'completed' and 'paid'
            $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'completed', 'paid'];
            
            if (!in_array($status, $valid_statuses)) {
                error_log("Invalid order status: $status");
                return false;
            }
            
            $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $stmt = $this->execute($sql, [$status, $order_id]);
            
            if ($stmt !== false && $stmt->rowCount() > 0) {
                error_log("Order status updated successfully: order_id=$order_id, status=$status");
                return true;
            } else {
                error_log("Failed to update order status: order_id=$order_id, status=$status, rowCount=" . ($stmt ? $stmt->rowCount() : 0));
                return false;
            }
        } catch (Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update order with invoice number and status
     * 
     * Updates the following fields:
     * - invoice_no: Payment reference/transaction ID (if provided)
     * - order_status: Order status (e.g., 'completed', 'processing', etc.)
     * 
     * Note: order_date is NOT updated here - it remains the original order creation timestamp
     */
    public function update_order_complete($order_id, $invoice_no = null, $status = 'completed') {
        try {
            error_log("Updating order to complete: order_id=$order_id, invoice_no=" . ($invoice_no ?? 'null') . ", status=$status");
            
            // Build UPDATE statement
            $sql = "UPDATE orders SET order_status = ?";
            $params = [$status];
            
            // Update invoice_no if provided (e.g., payment reference from Paystack)
            // This allows linking the order to the payment transaction
            if ($invoice_no !== null && !empty($invoice_no)) {
                $sql .= ", invoice_no = ?";
                $params[] = $invoice_no;
                error_log("Updating invoice_no to: $invoice_no");
            }
            
            $sql .= " WHERE order_id = ?";
            $params[] = $order_id;
            
            error_log("Update order SQL: $sql");
            error_log("Update order params: " . json_encode($params));
            
            $stmt = $this->execute($sql, $params);
            
            if ($stmt !== false && $stmt->rowCount() > 0) {
                error_log("✓ Order updated successfully: order_id=$order_id, invoice_no=" . ($invoice_no ?? 'unchanged') . ", status=$status");
                return true;
            } else {
                error_log("✗ Failed to update order: order_id=$order_id, rowCount=" . ($stmt ? $stmt->rowCount() : 0));
                // Try without invoice_no if that failed
                if ($invoice_no !== null && count($params) > 2) {
                    error_log("Retrying without invoice_no...");
                    $sql_retry = "UPDATE orders SET order_status = ? WHERE order_id = ?";
                    $stmt_retry = $this->execute($sql_retry, [$status, $order_id]);
                    if ($stmt_retry !== false && $stmt_retry->rowCount() > 0) {
                        error_log("✓ Order status updated (without invoice_no): order_id=$order_id, status=$status");
                        return true;
                    }
                }
                return false;
            }
        } catch (PDOException $e) {
            error_log("Update order complete PDO error: " . $e->getMessage());
            // If error is about invoice_no column, try without it
            if (strpos($e->getMessage(), 'invoice_no') !== false) {
                error_log("Retrying without invoice_no column...");
                $sql_retry = "UPDATE orders SET order_status = ? WHERE order_id = ?";
                $stmt_retry = $this->execute($sql_retry, [$status, $order_id]);
                return $stmt_retry !== false && $stmt_retry->rowCount() > 0;
            }
            return false;
        } catch (Exception $e) {
            error_log("Update order complete error: " . $e->getMessage());
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
        try {
            $sql = "SELECT o.*, c.customer_name, c.customer_email 
                    FROM orders o 
                    LEFT JOIN customer c ON o.customer_id = c.customer_id";
            
            $params = [];
            
            if ($status) {
                $sql .= " WHERE o.order_status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            
            // Use direct PDO connection to properly bind LIMIT and OFFSET as integers
            $conn = $this->getConnection();
            if (!$conn) {
                error_log("get_all_orders: No database connection available");
                return [];
            }
            
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            $param_index = 1;
            if ($status) {
                $stmt->bindValue($param_index++, $status, PDO::PARAM_STR);
            }
            $stmt->bindValue($param_index++, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue($param_index++, (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("get_all_orders: Found " . count($results) . " orders (limit: $limit, offset: $offset, status: " . ($status ?? 'all') . ")");
            
            return $results ? $results : [];
            
        } catch (PDOException $e) {
            error_log("get_all_orders PDO error: " . $e->getMessage());
            error_log("get_all_orders SQL: " . $sql);
            return [];
        } catch (Exception $e) {
            error_log("get_all_orders error: " . $e->getMessage());
            return [];
        }
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
            $conn = $this->getConnection();
            if (!$conn) {
                error_log("count_all_orders: No database connection available");
                return 0;
            }
            
            $sql = "SELECT COUNT(*) as total FROM orders";
            
            if ($status) {
                $sql .= " WHERE order_status = ?";
            }
            
            $stmt = $conn->prepare($sql);
            if ($status) {
                $stmt->bindValue(1, $status, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $count = $result ? (int)$result['total'] : 0;
            error_log("count_all_orders: Found $count orders (status: " . ($status ?? 'all') . ")");
            
            return $count;
        } catch (PDOException $e) {
            error_log("count_all_orders PDO error: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            error_log("count_all_orders error: " . $e->getMessage());
            return 0;
        }
    }
}

?>
