<?php
require_once("db.php");

class ProducerController {
    private $db;
    private $producerId;
    
    public function __construct($producerId = null) {
        $db_conn = new db_connection();
        $this->db = $db_conn->db;
        $this->producerId = $producerId ?? $this->getSessionProducerId();
    }
    
    /**
     * Get producer ID from session
     */
    private function getSessionProducerId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Try different session variable names
        return $_SESSION['producer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['designer_id'] ?? $_SESSION['artisan_id'] ?? null;
    }
    
    /**
     * Get producer profile information
     */
    public function getProducerProfile() {
        if (!$this->producerId) {
            return null;
        }
        
        $tables = ['producers', 'producer', 'users', 'user', 'designers', 'designer', 'artisans', 'artisan'];
        foreach ($tables as $table) {
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Try to find ID column
            $idColumns = ['id', 'producer_id', 'user_id', 'designer_id', 'artisan_id'];
            $idColumn = 'id';
            
            foreach ($idColumns as $col) {
                $colCheck = mysqli_query($this->db, "SHOW COLUMNS FROM `$table` LIKE '$col'");
                if ($colCheck && mysqli_num_rows($colCheck) > 0) {
                    $idColumn = $col;
                    break;
                }
            }
            
            $query = "SELECT * FROM `$table` WHERE `$idColumn` = ? LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "i", $this->producerId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($profile = mysqli_fetch_assoc($result)) {
                return $profile;
            }
        }
        
        return null;
    }
    
    /**
     * Get total products for this producer
     */
    public function getTotalProducts() {
        if (!$this->producerId) {
            return 0;
        }
        
        $tables = ['products', 'product', 'items', 'item'];
        foreach ($tables as $table) {
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Try to find producer/user ID column
            $producerColumns = ['producer_id', 'user_id', 'designer_id', 'artisan_id', 'seller_id', 'created_by'];
            $producerColumn = null;
            
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    foreach ($producerColumns as $pc) {
                        if (stripos($col['Field'], $pc) !== false) {
                            $producerColumn = $col['Field'];
                            break 2;
                        }
                    }
                }
            }
            
            if ($producerColumn) {
                $query = "SELECT COUNT(*) as total FROM `$table` WHERE `$producerColumn` = ?";
                $stmt = mysqli_prepare($this->db, $query);
                mysqli_stmt_bind_param($stmt, "i", $this->producerId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    return (int)$row['total'];
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Get pending orders for this producer's products
     */
    public function getPendingOrders() {
        if (!$this->producerId) {
            return 0;
        }
        
        // Get orders that contain this producer's products
        $ordersTable = 'orders';
        $orderItemsTable = 'order_items';
        
        // Check if tables exist
        $ordersCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$ordersTable'");
        $itemsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$orderItemsTable'");
        
        if (!$ordersCheck || mysqli_num_rows($ordersCheck) == 0) {
            return 0;
        }
        
        // Find producer column in products table
        $productsTable = 'products';
        $producerColumn = null;
        $productsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$productsTable'");
        
        if ($productsCheck && mysqli_num_rows($productsCheck) > 0) {
            $columnsQuery = "SHOW COLUMNS FROM `$productsTable`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    if (stripos($col['Field'], 'producer') !== false || 
                        stripos($col['Field'], 'user_id') !== false ||
                        stripos($col['Field'], 'designer') !== false ||
                        stripos($col['Field'], 'artisan') !== false) {
                        $producerColumn = $col['Field'];
                        break;
                    }
                }
            }
        }
        
        if ($producerColumn && $itemsCheck && mysqli_num_rows($itemsCheck) > 0) {
            // Count orders with this producer's products that are pending
            $query = "SELECT COUNT(DISTINCT o.id) as total 
                     FROM `$ordersTable` o
                     INNER JOIN `$orderItemsTable` oi ON o.id = oi.order_id
                     INNER JOIN `$productsTable` p ON oi.product_id = p.id
                     WHERE p.$producerColumn = ? 
                     AND o.status IN ('pending', 'processing', 'Pending', 'Processing')";
            
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "i", $this->producerId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (int)$row['total'];
            }
        }
        
        return 0;
    }
    
    /**
     * Get this month's sales for this producer
     */
    public function getThisMonthSales() {
        if (!$this->producerId) {
            return 0;
        }
        
        $ordersTable = 'orders';
        $orderItemsTable = 'order_items';
        $productsTable = 'products';
        
        $ordersCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$ordersTable'");
        $itemsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$orderItemsTable'");
        $productsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$productsTable'");
        
        if (!$ordersCheck || mysqli_num_rows($ordersCheck) == 0) {
            return 0;
        }
        
        // Find producer column
        $producerColumn = null;
        if ($productsCheck && mysqli_num_rows($productsCheck) > 0) {
            $columnsQuery = "SHOW COLUMNS FROM `$productsTable`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    if (stripos($col['Field'], 'producer') !== false || 
                        stripos($col['Field'], 'user_id') !== false ||
                        stripos($col['Field'], 'designer') !== false ||
                        stripos($col['Field'], 'artisan') !== false) {
                        $producerColumn = $col['Field'];
                        break;
                    }
                }
            }
        }
        
        if ($producerColumn && $itemsCheck && mysqli_num_rows($itemsCheck) > 0) {
            // Find date column in orders
            $dateColumn = 'created_at';
            $dateCheck = mysqli_query($this->db, "SHOW COLUMNS FROM `$ordersTable` LIKE '$dateColumn'");
            if (!$dateCheck || mysqli_num_rows($dateCheck) == 0) {
                $dateColumn = 'order_date';
            }
            
            $query = "SELECT SUM(oi.subtotal) as total 
                     FROM `$ordersTable` o
                     INNER JOIN `$orderItemsTable` oi ON o.id = oi.order_id
                     INNER JOIN `$productsTable` p ON oi.product_id = p.id
                     WHERE p.$producerColumn = ? 
                     AND MONTH(o.$dateColumn) = MONTH(CURRENT_DATE())
                     AND YEAR(o.$dateColumn) = YEAR(CURRENT_DATE())";
            
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "i", $this->producerId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (float)($row['total'] ?? 0);
            }
        }
        
        return 0;
    }
    
    /**
     * Get total earnings for this producer
     */
    public function getTotalEarnings() {
        if (!$this->producerId) {
            return 0;
        }
        
        $ordersTable = 'orders';
        $orderItemsTable = 'order_items';
        $productsTable = 'products';
        
        $ordersCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$ordersTable'");
        $itemsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$orderItemsTable'");
        $productsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$productsTable'");
        
        if (!$ordersCheck || mysqli_num_rows($ordersCheck) == 0) {
            return 0;
        }
        
        // Find producer column
        $producerColumn = null;
        if ($productsCheck && mysqli_num_rows($productsCheck) > 0) {
            $columnsQuery = "SHOW COLUMNS FROM `$productsTable`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    if (stripos($col['Field'], 'producer') !== false || 
                        stripos($col['Field'], 'user_id') !== false ||
                        stripos($col['Field'], 'designer') !== false ||
                        stripos($col['Field'], 'artisan') !== false) {
                        $producerColumn = $col['Field'];
                        break;
                    }
                }
            }
        }
        
        if ($producerColumn && $itemsCheck && mysqli_num_rows($itemsCheck) > 0) {
            $query = "SELECT SUM(oi.subtotal) as total 
                     FROM `$ordersTable` o
                     INNER JOIN `$orderItemsTable` oi ON o.id = oi.order_id
                     INNER JOIN `$productsTable` p ON oi.product_id = p.id
                     WHERE p.$producerColumn = ?";
            
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "i", $this->producerId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (float)($row['total'] ?? 0);
            }
        }
        
        return 0;
    }
    
    /**
     * Get all producer dashboard stats
     */
    public function getDashboardStats() {
        $profile = $this->getProducerProfile();
        
        return [
            'profile' => $profile,
            'total_products' => $this->getTotalProducts(),
            'pending_orders' => $this->getPendingOrders(),
            'this_month_sales' => $this->getThisMonthSales(),
            'total_earnings' => $this->getTotalEarnings()
        ];
    }
    
    /**
     * Get producer's products - SECURE: Only returns products belonging to this producer
     */
    public function getProducerProducts($limit = 50, $offset = 0) {
        if (!$this->producerId) {
            return [];
        }
        
        $tables = ['products', 'product', 'items', 'item'];
        foreach ($tables as $table) {
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Find producer column
            $producerColumns = ['producer_id', 'user_id', 'designer_id', 'artisan_id', 'seller_id', 'created_by'];
            $producerColumn = null;
            
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    foreach ($producerColumns as $pc) {
                        if (stripos($col['Field'], $pc) !== false) {
                            $producerColumn = $col['Field'];
                            break 2;
                        }
                    }
                }
            }
            
            if ($producerColumn) {
                // SECURITY: Always filter by producer ID - prevents access to other designers' products
                $query = "SELECT * FROM `$table` WHERE `$producerColumn` = ? ORDER BY id DESC LIMIT ? OFFSET ?";
                $stmt = mysqli_prepare($this->db, $query);
                mysqli_stmt_bind_param($stmt, "iii", $this->producerId, $limit, $offset);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $products = [];
                while ($product = mysqli_fetch_assoc($result)) {
                    // Add performance metrics for each product
                    $product['sales_count'] = $this->getProductSalesCount($product['id']);
                    $product['total_revenue'] = $this->getProductRevenue($product['id']);
                    $products[] = $product;
                }
                
                return $products;
            }
        }
        
        return [];
    }
    
    /**
     * Get a single product by ID - SECURE: Only if it belongs to this producer
     */
    public function getProducerProduct($productId) {
        if (!$this->producerId || !$productId) {
            return null;
        }
        
        $tables = ['products', 'product', 'items', 'item'];
        foreach ($tables as $table) {
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Find producer column
            $producerColumns = ['producer_id', 'user_id', 'designer_id', 'artisan_id', 'seller_id', 'created_by'];
            $producerColumn = null;
            
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    foreach ($producerColumns as $pc) {
                        if (stripos($col['Field'], $pc) !== false) {
                            $producerColumn = $col['Field'];
                            break 2;
                        }
                    }
                }
            }
            
            if ($producerColumn) {
                // SECURITY: Verify product belongs to this producer
                $query = "SELECT * FROM `$table` WHERE id = ? AND `$producerColumn` = ? LIMIT 1";
                $stmt = mysqli_prepare($this->db, $query);
                mysqli_stmt_bind_param($stmt, "ii", $productId, $this->producerId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($product = mysqli_fetch_assoc($result)) {
                    // Add performance metrics
                    $product['sales_count'] = $this->getProductSalesCount($productId);
                    $product['total_revenue'] = $this->getProductRevenue($productId);
                    return $product;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get sales count for a specific product
     */
    private function getProductSalesCount($productId) {
        $orderItemsTable = 'order_items';
        $itemsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$orderItemsTable'");
        
        if ($itemsCheck && mysqli_num_rows($itemsCheck) > 0) {
            $query = "SELECT SUM(quantity) as total FROM `$orderItemsTable` WHERE product_id = ?";
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "i", $productId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (int)($row['total'] ?? 0);
            }
        }
        
        return 0;
    }
    
    /**
     * Get total revenue for a specific product
     */
    private function getProductRevenue($productId) {
        $orderItemsTable = 'order_items';
        $itemsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$orderItemsTable'");
        
        if ($itemsCheck && mysqli_num_rows($itemsCheck) > 0) {
            $query = "SELECT SUM(subtotal) as total FROM `$orderItemsTable` WHERE product_id = ?";
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "i", $productId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (float)($row['total'] ?? 0);
            }
        }
        
        return 0;
    }
    
    /**
     * Verify product ownership - SECURITY CHECK
     */
    public function verifyProductOwnership($productId) {
        if (!$this->producerId || !$productId) {
            return false;
        }
        
        $tables = ['products', 'product', 'items', 'item'];
        foreach ($tables as $table) {
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Find producer column
            $producerColumns = ['producer_id', 'user_id', 'designer_id', 'artisan_id', 'seller_id', 'created_by'];
            $producerColumn = null;
            
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    foreach ($producerColumns as $pc) {
                        if (stripos($col['Field'], $pc) !== false) {
                            $producerColumn = $col['Field'];
                            break 2;
                        }
                    }
                }
            }
            
            if ($producerColumn) {
                $query = "SELECT id FROM `$table` WHERE id = ? AND `$producerColumn` = ? LIMIT 1";
                $stmt = mysqli_prepare($this->db, $query);
                mysqli_stmt_bind_param($stmt, "ii", $productId, $this->producerId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_fetch_assoc($result)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get producer's orders
     */
    public function getProducerOrders($limit = 50, $offset = 0) {
        if (!$this->producerId) {
            return [];
        }
        
        $ordersTable = 'orders';
        $orderItemsTable = 'order_items';
        $productsTable = 'products';
        
        $ordersCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$ordersTable'");
        $itemsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$orderItemsTable'");
        $productsCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$productsTable'");
        
        if (!$ordersCheck || mysqli_num_rows($ordersCheck) == 0) {
            return [];
        }
        
        // Find producer column
        $producerColumn = null;
        if ($productsCheck && mysqli_num_rows($productsCheck) > 0) {
            $columnsQuery = "SHOW COLUMNS FROM `$productsTable`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            if ($columnsResult) {
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    if (stripos($col['Field'], 'producer') !== false || 
                        stripos($col['Field'], 'user_id') !== false ||
                        stripos($col['Field'], 'designer') !== false ||
                        stripos($col['Field'], 'artisan') !== false) {
                        $producerColumn = $col['Field'];
                        break;
                    }
                }
            }
        }
        
        if ($producerColumn && $itemsCheck && mysqli_num_rows($itemsCheck) > 0) {
            $query = "SELECT DISTINCT o.* 
                     FROM `$ordersTable` o
                     INNER JOIN `$orderItemsTable` oi ON o.id = oi.order_id
                     INNER JOIN `$productsTable` p ON oi.product_id = p.id
                     WHERE p.$producerColumn = ? 
                     ORDER BY o.created_at DESC
                     LIMIT ? OFFSET ?";
            
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "iii", $this->producerId, $limit, $offset);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $orders = [];
            while ($order = mysqli_fetch_assoc($result)) {
                $orders[] = $order;
            }
            
            return $orders;
        }
        
        return [];
    }
}
?>

