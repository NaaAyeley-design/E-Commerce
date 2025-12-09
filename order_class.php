<?php
require_once("db.php");
require_once("cart_class.php");
require_once("order_logger.php");

class Order {
    private $db;
    private $userId;
    
    public function __construct($userId = null) {
        $db_conn = new db_connection();
        $this->db = $db_conn->db;
        $this->userId = $userId ?? $this->getSessionUserId();
        
        // Log order class initialization
        OrderLogger::log("Order class initialized", [
            'user_id' => $this->userId,
            'session_id' => session_id()
        ]);
    }
    
    /**
     * Get user ID from session
     */
    private function getSessionUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
    }
    
    /**
     * Get or create orders table
     * FIXED: Ensures proper table structure with all required fields
     */
    private function ensureOrdersTable() {
        $tables = ['orders', 'order'];
        foreach ($tables as $table) {
            $check = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if ($check && mysqli_num_rows($check) > 0) {
                // Verify table has required columns, add if missing
                $this->verifyOrdersTableStructure($table);
                return $table;
            }
        }
        
        // Create orders table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `order_number` VARCHAR(50) UNIQUE,
            `order_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `order_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `shipping_address` TEXT,
            `billing_address` TEXT,
            `payment_method` VARCHAR(50),
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `user_id` (`user_id`),
            INDEX `status` (`status`),
            INDEX `order_status` (`order_status`),
            INDEX `order_number` (`order_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (mysqli_query($this->db, $createTable)) {
            OrderLogger::log("Orders table created successfully");
        } else {
            OrderLogger::logError("Failed to create orders table", new Exception(mysqli_error($this->db)));
        }
        return 'orders';
    }
    
    /**
     * Verify and update orders table structure if needed
     */
    private function verifyOrdersTableStructure($table) {
        $requiredColumns = [
            'order_total' => "ALTER TABLE `$table` ADD COLUMN `order_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`",
            'order_status' => "ALTER TABLE `$table` ADD COLUMN `order_status` VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER `status`"
        ];
        
        $columnsQuery = "SHOW COLUMNS FROM `$table`";
        $result = mysqli_query($this->db, $columnsQuery);
        $existingColumns = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $existingColumns[] = $row['Field'];
            }
        }
        
        foreach ($requiredColumns as $column => $alterQuery) {
            if (!in_array($column, $existingColumns)) {
                mysqli_query($this->db, $alterQuery);
            }
        }
    }
    
    /**
     * Get or create order_items table
     * FIXED: Ensures proper table structure with all required fields
     */
    private function ensureOrderItemsTable() {
        $tables = ['order_items', 'orderitem', 'order_item'];
        foreach ($tables as $table) {
            $check = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if ($check && mysqli_num_rows($check) > 0) {
                return $table;
            }
        }
        
        // Create order_items table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `order_item_id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `product_id` INT(11) NOT NULL,
            `quantity` INT(11) NOT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `subtotal` DECIMAL(10,2) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `order_id` (`order_id`),
            INDEX `product_id` (`product_id`),
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (mysqli_query($this->db, $createTable)) {
            OrderLogger::log("Order items table created successfully");
        } else {
            OrderLogger::logError("Failed to create order_items table", new Exception(mysqli_error($this->db)));
        }
        return 'order_items';
    }
    
    /**
     * Generate unique order number
     */
    private function generateOrderNumber() {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
    
    /**
     * Create order from cart
     */
    public function createOrderFromCart($shippingAddress = null, $billingAddress = null, $paymentMethod = null) {
        OrderLogger::log("createOrderFromCart called", [
            'user_id' => $this->userId,
            'has_shipping' => !empty($shippingAddress),
            'has_billing' => !empty($billingAddress),
            'payment_method' => $paymentMethod
        ]);
        
        if (!$this->userId) {
            OrderLogger::logError("Order creation failed: User not logged in");
            return ['success' => false, 'message' => 'User not logged in'];
        }
        
        // Get cart items
        $cart = new Cart($this->userId);
        $cartData = $cart->getCartItems();
        
        OrderLogger::log("Cart data retrieved", [
            'item_count' => count($cartData['items'] ?? []),
            'total' => $cartData['total'] ?? 0
        ]);
        
        if (empty($cartData['items'])) {
            OrderLogger::logError("Order creation failed: Cart is empty");
            return ['success' => false, 'message' => 'Cart is empty'];
        }
        
        $ordersTable = $this->ensureOrdersTable();
        $orderItemsTable = $this->ensureOrderItemsTable();
        
        OrderLogger::log("Tables ensured", [
            'orders_table' => $ordersTable,
            'order_items_table' => $orderItemsTable
        ]);
        
        // Start transaction
        if (!mysqli_begin_transaction($this->db)) {
            OrderLogger::logError("Failed to start transaction", new Exception(mysqli_error($this->db)));
            return ['success' => false, 'message' => 'Failed to start transaction: ' . mysqli_error($this->db)];
        }
        
        try {
            // STEP 1: Validate cart is not empty (already checked above)
            
            // STEP 2: Calculate order total from cart items
            $orderNumber = $this->generateOrderNumber();
            $totalAmount = (float)($cartData['total'] ?? 0);
            $orderTotal = $totalAmount; // Use both field names for compatibility
            $status = 'pending';
            
            // Ensure addresses are not null
            $shippingAddress = $shippingAddress ?? '';
            $billingAddress = $billingAddress ?? $shippingAddress;
            $paymentMethod = $paymentMethod ?? 'credit_card';
            
            if ($totalAmount <= 0) {
                throw new Exception('Order total must be greater than 0');
            }
            
            OrderLogger::log("Preparing to insert order", [
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'order_total' => $orderTotal,
                'user_id' => $this->userId,
                'cart_items_count' => count($cartData['items'])
            ]);
            
            // STEP 3: Insert order into orders table
            // Check which columns exist in the table
            $columnsQuery = "SHOW COLUMNS FROM `$ordersTable`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            $existingColumns = [];
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    $existingColumns[] = strtolower($col['Field']);
                }
            }
            
            // Build INSERT query with only existing columns
            $insertCols = [];
            $insertVals = [];
            $bindTypes = '';
            $bindVars = [];
            
            // Required: user_id
            $insertCols[] = 'user_id';
            $insertVals[] = '?';
            $bindTypes .= 'i';
            $bindVars[] = &$this->userId;
            
            // Optional: order_number
            if (in_array('order_number', $existingColumns)) {
                $insertCols[] = 'order_number';
                $insertVals[] = '?';
                $bindTypes .= 's';
                $bindVars[] = &$orderNumber;
            }
            
            // Try order_total first, then total_amount
            if (in_array('order_total', $existingColumns)) {
                $insertCols[] = 'order_total';
                $insertVals[] = '?';
                $bindTypes .= 'd';
                $bindVars[] = &$orderTotal;
            } elseif (in_array('total_amount', $existingColumns)) {
                $insertCols[] = 'total_amount';
                $insertVals[] = '?';
                $bindTypes .= 'd';
                $bindVars[] = &$totalAmount;
            }
            
            // Try order_status first, then status
            if (in_array('order_status', $existingColumns)) {
                $insertCols[] = 'order_status';
                $insertVals[] = '?';
                $bindTypes .= 's';
                $bindVars[] = &$status;
            } elseif (in_array('status', $existingColumns)) {
                $insertCols[] = 'status';
                $insertVals[] = '?';
                $bindTypes .= 's';
                $bindVars[] = &$status;
            }
            
            // Optional: shipping_address
            if (in_array('shipping_address', $existingColumns)) {
                $insertCols[] = 'shipping_address';
                $insertVals[] = '?';
                $bindTypes .= 's';
                $bindVars[] = &$shippingAddress;
            }
            
            // Optional: billing_address
            if (in_array('billing_address', $existingColumns)) {
                $insertCols[] = 'billing_address';
                $insertVals[] = '?';
                $bindTypes .= 's';
                $bindVars[] = &$billingAddress;
            }
            
            // Optional: payment_method
            if (in_array('payment_method', $existingColumns)) {
                $insertCols[] = 'payment_method';
                $insertVals[] = '?';
                $bindTypes .= 's';
                $bindVars[] = &$paymentMethod;
            }
            
            // created_at - use NOW() if column exists, or let DB default handle it
            if (in_array('created_at', $existingColumns)) {
                $insertCols[] = 'created_at';
                $insertVals[] = 'NOW()';
            }
            
            $insertOrder = "INSERT INTO `$ordersTable` (" . implode(', ', $insertCols) . ") 
                          VALUES (" . implode(', ', $insertVals) . ")";
            
            OrderLogger::log("Order INSERT query", [
                'query' => $insertOrder,
                'columns' => $insertCols,
                'bind_types' => $bindTypes
            ]);
            
            $stmt = mysqli_prepare($this->db, $insertOrder);
            if (!$stmt) {
                throw new Exception('Failed to prepare order statement: ' . mysqli_error($this->db));
            }
            
            // Bind parameters dynamically
            if (!empty($bindVars)) {
                call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bindTypes], $bindVars));
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to execute order insert: ' . mysqli_error($this->db) . ' | SQL State: ' . mysqli_sqlstate($this->db));
            }
            
            $orderId = mysqli_insert_id($this->db);
            
            if (!$orderId || $orderId <= 0) {
                throw new Exception('Failed to get order ID after insert. Insert ID: ' . mysqli_insert_id($this->db));
            }
            
            OrderLogger::log("Order created successfully", [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'insert_id' => mysqli_insert_id($this->db)
            ]);
            
            // STEP 4: Insert order items for each cart item
            $itemsCreated = 0;
            $failedItems = [];
            
            foreach ($cartData['items'] as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 1);
                $price = (float)(isset($item['product_price']) ? $item['product_price'] : 0);
                $subtotal = (float)(isset($item['item_total']) ? $item['item_total'] : ($price * $quantity));
                
                if ($productId <= 0) {
                    OrderLogger::logError("Invalid product ID in cart item", ['item' => $item]);
                    $failedItems[] = $item;
                    continue;
                }
                
                if ($quantity <= 0) {
                    OrderLogger::logError("Invalid quantity in cart item", ['item' => $item]);
                    $failedItems[] = $item;
                    continue;
                }
                
                if ($price <= 0) {
                    OrderLogger::logError("Invalid price in cart item", ['item' => $item]);
                    $failedItems[] = $item;
                    continue;
                }
                
                // Check which columns exist in order_items table
                $itemsColumnsQuery = "SHOW COLUMNS FROM `$orderItemsTable`";
                $itemsColumnsResult = mysqli_query($this->db, $itemsColumnsQuery);
                $itemsExistingColumns = [];
                if ($itemsColumnsResult) {
                    while ($col = mysqli_fetch_assoc($itemsColumnsResult)) {
                        $itemsExistingColumns[] = strtolower($col['Field']);
                    }
                }
                
                // Build INSERT for order_items
                $itemCols = ['order_id', 'product_id', 'quantity', 'price', 'subtotal'];
                $itemVals = ['?', '?', '?', '?', '?'];
                $itemBindTypes = 'iiidd';
                $itemBindVars = [&$orderId, &$productId, &$quantity, &$price, &$subtotal];
                
                // Add created_at if column exists
                if (in_array('created_at', $itemsExistingColumns)) {
                    $itemCols[] = 'created_at';
                    $itemVals[] = 'NOW()';
                }
                
                $insertItem = "INSERT INTO `$orderItemsTable` (" . implode(', ', $itemCols) . ") 
                              VALUES (" . implode(', ', $itemVals) . ")";
                
                $itemStmt = mysqli_prepare($this->db, $insertItem);
                if (!$itemStmt) {
                    throw new Exception('Failed to prepare order item statement: ' . mysqli_error($this->db) . ' | Product ID: ' . $productId);
                }
                
                // Bind parameters dynamically
                call_user_func_array('mysqli_stmt_bind_param', array_merge([$itemStmt, $itemBindTypes], $itemBindVars));
                
                if (!mysqli_stmt_execute($itemStmt)) {
                    throw new Exception('Failed to create order item: ' . mysqli_error($this->db) . ' | Product ID: ' . $productId . ' | SQL State: ' . mysqli_sqlstate($this->db));
                }
                
                $itemsCreated++;
                
                OrderLogger::log("Order item created", [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ]);
            }
            
            if ($itemsCreated === 0) {
                throw new Exception('No order items were created. Failed items: ' . count($failedItems));
            }
            
            OrderLogger::log("Order items created", [
                'count' => $itemsCreated,
                'failed_count' => count($failedItems)
            ]);
            
            // STEP 5: Delete all items from user's cart
            // Use direct database query within transaction to ensure it's part of the transaction
            $cartTable = $cart->getCartTableName();
            $directDelete = "DELETE FROM `$cartTable` WHERE user_id = ?";
            $deleteStmt = mysqli_prepare($this->db, $directDelete);
            
            if ($deleteStmt) {
                mysqli_stmt_bind_param($deleteStmt, "i", $this->userId);
                if (mysqli_stmt_execute($deleteStmt)) {
                    $deletedRows = mysqli_affected_rows($this->db);
                    OrderLogger::log("Cart emptied via direct query in transaction", [
                        'deleted_rows' => $deletedRows,
                        'order_id' => $orderId
                    ]);
                    
                    // Verify cart is empty
                    $verifyQuery = "SELECT COUNT(*) as count FROM `$cartTable` WHERE user_id = ?";
                    $verifyStmt = mysqli_prepare($this->db, $verifyQuery);
                    mysqli_stmt_bind_param($verifyStmt, "i", $this->userId);
                    mysqli_stmt_execute($verifyStmt);
                    $verifyResult = mysqli_stmt_get_result($verifyStmt);
                    $verifyRow = mysqli_fetch_assoc($verifyResult);
                    $remainingItems = (int)($verifyRow['count'] ?? 0);
                    
                    if ($remainingItems > 0) {
                        OrderLogger::logError("Cart verification failed - items still present after deletion", [
                            'remaining_items' => $remainingItems,
                            'order_id' => $orderId
                        ]);
                        // Try one more time
                        mysqli_stmt_execute($deleteStmt);
                    }
                } else {
                    OrderLogger::logError("Failed to empty cart in transaction", [
                        'error' => mysqli_error($this->db),
                        'order_id' => $orderId
                    ]);
                    // Don't fail the order - it was already created
                }
            } else {
                OrderLogger::logError("Failed to prepare cart delete statement", [
                    'error' => mysqli_error($this->db),
                    'order_id' => $orderId
                ]);
            }
            
            // STEP 6: COMMIT TRANSACTION
            if (!mysqli_commit($this->db)) {
                throw new Exception('Failed to commit transaction: ' . mysqli_error($this->db));
            }
            
            OrderLogger::logSuccess("Order created and cart emptied successfully", [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $totalAmount,
                'items_count' => $itemsCreated,
                'cart_emptied' => $cartEmptyResult['success'] ?? false
            ]);
            
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $totalAmount,
                'items_count' => $itemsCreated
            ];
            
        } catch (Exception $e) {
            // ROLLBACK TRANSACTION on any error
            mysqli_rollback($this->db);
            OrderLogger::logError("Order creation failed - transaction rolled back", $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'User not logged in'];
        }
        
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'completed'];
        if (!in_array(strtolower($status), $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $table = $this->ensureOrdersTable();
        
        // Check if order belongs to user (or allow admin to update any order)
        $checkQuery = "SELECT id FROM `$table` WHERE id = ?";
        $checkStmt = mysqli_prepare($this->db, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $orderId);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);
        
        if (!mysqli_fetch_assoc($result)) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        $updateQuery = "UPDATE `$table` SET status = ? WHERE id = ?";
        $updateStmt = mysqli_prepare($this->db, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "si", $status, $orderId);
        
        if (mysqli_stmt_execute($updateStmt)) {
            return ['success' => true, 'message' => 'Order status updated', 'status' => $status];
        } else {
            return ['success' => false, 'message' => 'Failed to update order status: ' . mysqli_error($this->db)];
        }
    }
    
    /**
     * Get order details
     */
    public function getOrder($orderId) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'User not logged in'];
        }
        
        $ordersTable = $this->ensureOrdersTable();
        $orderItemsTable = $this->ensureOrderItemsTable();
        
        // Get order
        $query = "SELECT * FROM `$ordersTable` WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $orderId, $this->userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $order = mysqli_fetch_assoc($result);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Get order items
        $itemsQuery = "SELECT oi.*, p.name as product_name 
                      FROM `$orderItemsTable` oi
                      LEFT JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        
        $itemsStmt = mysqli_prepare($this->db, $itemsQuery);
        mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
        mysqli_stmt_execute($itemsStmt);
        $itemsResult = mysqli_stmt_get_result($itemsStmt);
        
        $items = [];
        while ($item = mysqli_fetch_assoc($itemsResult)) {
            $items[] = $item;
        }
        
        $order['items'] = $items;
        
        return ['success' => true, 'order' => $order];
    }
    
    /**
     * Get all orders for user
     */
    public function getUserOrders($limit = 10) {
        if (!$this->userId) {
            return ['success' => false, 'orders' => []];
        }
        
        $table = $this->ensureOrdersTable();
        
        $query = "SELECT * FROM `$table` WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $this->userId, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $orders = [];
        while ($order = mysqli_fetch_assoc($result)) {
            $orders[] = $order;
        }
        
        return ['success' => true, 'orders' => $orders];
    }
}
?>

