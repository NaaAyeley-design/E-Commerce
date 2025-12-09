<?php
require_once("db.php");

class Cart {
    private $db;
    private $userId;
    
    public function __construct($userId = null) {
        $db_conn = new db_connection();
        $this->db = $db_conn->db;
        $this->userId = $userId ?? $this->getSessionUserId();
    }
    
    /**
     * Get user ID from session or return null for guest
     */
    private function getSessionUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
    }
    
    /**
     * Get or create cart table
     */
    private function ensureCartTable() {
        $tables = ['cart', 'carts'];
        foreach ($tables as $table) {
            $check = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if ($check && mysqli_num_rows($check) > 0) {
                return $table;
            }
        }
        
        // Create cart table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS `cart` (
            `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) NOT NULL,
            `product_id` INT(11) NOT NULL,
            `quantity` INT(11) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `user_product` (`user_id`, `product_id`),
            INDEX `user_id` (`user_id`),
            INDEX `product_id` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        mysqli_query($this->db, $createTable);
        return 'cart';
    }
    
    /**
     * Add item to cart
     */
    public function addItem($productId, $quantity = 1) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'User not logged in'];
        }
        
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be greater than 0'];
        }
        
        $table = $this->ensureCartTable();
        
        // Check if item already exists in cart
        $checkQuery = "SELECT id, quantity FROM `$table` WHERE user_id = ? AND product_id = ?";
        $stmt = mysqli_prepare($this->db, $checkQuery);
        mysqli_stmt_bind_param($stmt, "ii", $this->userId, $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($existing = mysqli_fetch_assoc($result)) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            $updateQuery = "UPDATE `$table` SET quantity = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($this->db, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "ii", $newQuantity, $existing['id']);
            
            if (mysqli_stmt_execute($updateStmt)) {
                return ['success' => true, 'message' => 'Cart updated', 'quantity' => $newQuantity];
            } else {
                return ['success' => false, 'message' => 'Failed to update cart'];
            }
        } else {
            // Insert new item
            $insertQuery = "INSERT INTO `$table` (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insertStmt = mysqli_prepare($this->db, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, "iii", $this->userId, $productId, $quantity);
            
            if (mysqli_stmt_execute($insertStmt)) {
                return ['success' => true, 'message' => 'Item added to cart', 'cart_id' => mysqli_insert_id($this->db)];
            } else {
                return ['success' => false, 'message' => 'Failed to add item to cart: ' . mysqli_error($this->db)];
            }
        }
    }
    
    /**
     * Update cart item quantity
     */
    public function updateQuantity($productId, $quantity) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'User not logged in'];
        }
        
        if ($quantity <= 0) {
            // If quantity is 0 or less, remove the item
            return $this->removeItem($productId);
        }
        
        $table = $this->ensureCartTable();
        
        $query = "UPDATE `$table` SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $this->userId, $productId);
        
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_affected_rows($this->db) > 0) {
                return ['success' => true, 'message' => 'Cart updated', 'quantity' => $quantity];
            } else {
                return ['success' => false, 'message' => 'Item not found in cart'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to update cart: ' . mysqli_error($this->db)];
        }
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($productId) {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'User not logged in'];
        }
        
        $table = $this->ensureCartTable();
        
        $query = "DELETE FROM `$table` WHERE user_id = ? AND product_id = ?";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $this->userId, $productId);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'Item removed from cart'];
        } else {
            return ['success' => false, 'message' => 'Failed to remove item: ' . mysqli_error($this->db)];
        }
    }
    
    /**
     * Empty entire cart
     * FIXED: Enhanced with verification and logging
     */
    public function emptyCart() {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'User not logged in'];
        }
        
        $table = $this->ensureCartTable();
        
        // Get count before deletion for logging
        $countQuery = "SELECT COUNT(*) as count FROM `$table` WHERE user_id = ?";
        $countStmt = mysqli_prepare($this->db, $countQuery);
        mysqli_stmt_bind_param($countStmt, "i", $this->userId);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);
        $itemsBeforeDelete = (int)($countRow['count'] ?? 0);
        
        $query = "DELETE FROM `$table` WHERE user_id = ?";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->userId);
        
        if (mysqli_stmt_execute($stmt)) {
            $deletedRows = mysqli_affected_rows($this->db);
            
            // Verify cart is actually empty
            $verifyQuery = "SELECT COUNT(*) as count FROM `$table` WHERE user_id = ?";
            $verifyStmt = mysqli_prepare($this->db, $verifyQuery);
            mysqli_stmt_bind_param($verifyStmt, "i", $this->userId);
            mysqli_stmt_execute($verifyStmt);
            $verifyResult = mysqli_stmt_get_result($verifyStmt);
            $verifyRow = mysqli_fetch_assoc($verifyResult);
            $itemsAfterDelete = (int)($verifyRow['count'] ?? 0);
            
            if ($itemsAfterDelete === 0) {
                return [
                    'success' => true, 
                    'message' => 'Cart emptied',
                    'deleted_count' => $deletedRows,
                    'verified_empty' => true
                ];
            } else {
                return [
                    'success' => false, 
                    'message' => 'Cart deletion incomplete. Remaining items: ' . $itemsAfterDelete,
                    'deleted_count' => $deletedRows,
                    'remaining_count' => $itemsAfterDelete
                ];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to empty cart: ' . mysqli_error($this->db)];
        }
    }
    
    /**
     * Get cart table name (public method for order class)
     */
    public function getCartTableName() {
        return $this->ensureCartTable();
    }
    
    /**
     * Get all cart items with product details
     */
    public function getCartItems() {
        if (!$this->userId) {
            return ['success' => false, 'items' => [], 'total' => 0];
        }
        
        $table = $this->ensureCartTable();
        
        // Try to join with products table
        $productTables = ['products', 'product', 'items', 'item'];
        $productTable = null;
        
        foreach ($productTables as $pt) {
            $check = mysqli_query($this->db, "SHOW TABLES LIKE '$pt'");
            if ($check && mysqli_num_rows($check) > 0) {
                $productTable = $pt;
                break;
            }
        }
        
        if ($productTable) {
            // Join with products table
            $query = "SELECT c.*, p.name as product_name, p.price as product_price, 
                     (c.quantity * p.price) as item_total
                     FROM `$table` c
                     LEFT JOIN `$productTable` p ON c.product_id = p.id
                     WHERE c.user_id = ?
                     ORDER BY c.created_at DESC";
        } else {
            // Just get cart items without product details
            $query = "SELECT * FROM `$table` WHERE user_id = ? ORDER BY created_at DESC";
        }
        
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $items = [];
        $total = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $itemTotal = isset($row['item_total']) ? $row['item_total'] : 0;
            $total += $itemTotal;
            $items[] = $row;
        }
        
        return [
            'success' => true,
            'items' => $items,
            'total' => $total,
            'item_count' => count($items)
        ];
    }
    
    /**
     * Get cart item count
     */
    public function getItemCount() {
        if (!$this->userId) {
            return 0;
        }
        
        $table = $this->ensureCartTable();
        
        $query = "SELECT SUM(quantity) as total FROM `$table` WHERE user_id = ?";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, "i", $this->userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            return (int)($row['total'] ?? 0);
        }
        
        return 0;
    }
    
    /**
     * Get cart total
     */
    public function getCartTotal() {
        $cartData = $this->getCartItems();
        return $cartData['total'] ?? 0;
    }
}
?>

