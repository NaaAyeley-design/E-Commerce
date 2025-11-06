<?php
/**
 * Cart Class
 * 
 * Handles all cart-related database operations
 * Uses existing cart table structure: p_id, ip_add, c_id, qty
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/db_class.php';

class cart_class {
    private $db;

    public function __construct() {
        $this->db = new db_class();
    }

    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Add product to cart
     * 
     * @param int $customer_id Customer ID
     * @param int $product_id Product ID
     * @param int $quantity Quantity to add
     * @return bool|string Success or error message
     */
    public function add_to_cart($customer_id, $product_id, $quantity = 1) {
        try {
            // Validate inputs
            if (empty($customer_id) || empty($product_id) || $quantity <= 0) {
                return "Invalid cart data.";
            }

            // Check if cart table exists
            try {
                $table_check = $this->db->fetchRow("SHOW TABLES LIKE 'cart'");
                if (!$table_check) {
                    error_log("Cart table does not exist");
                    return "Cart table does not exist. Please contact administrator.";
                }
            } catch (Exception $e) {
                // If table check fails, try the query anyway
                error_log("Cart table check failed: " . $e->getMessage());
            }

            $ip_address = $this->get_client_ip();

            // Check if product already exists in cart for this customer
            $existing = $this->db->fetchRow(
                "SELECT qty FROM cart WHERE c_id = ? AND p_id = ?",
                [$customer_id, $product_id]
            );

            if ($existing) {
                // Update quantity
                $new_quantity = $existing['qty'] + $quantity;
                return $this->update_cart_item($customer_id, $product_id, $new_quantity);
            } else {
                // Insert new cart item
                $result = $this->db->execute(
                    "INSERT INTO cart (c_id, p_id, ip_add, qty) VALUES (?, ?, ?, ?)",
                    [$customer_id, $product_id, $ip_address, $quantity]
                );
                
                return $result && $result->rowCount() > 0 ? true : "Failed to add product to cart.";
            }
        } catch (PDOException $e) {
            error_log("Add to cart PDO error: " . $e->getMessage());
            error_log("Add to cart PDO trace: " . $e->getTraceAsString());
            return "Database error occurred. Please try again.";
        } catch (Exception $e) {
            error_log("Add to cart error: " . $e->getMessage());
            error_log("Add to cart trace: " . $e->getTraceAsString());
            return "An error occurred while adding to cart.";
        } catch (Throwable $e) {
            error_log("Add to cart throwable error: " . $e->getMessage());
            error_log("Add to cart throwable trace: " . $e->getTraceAsString());
            return "An unexpected error occurred.";
        }
    }

    /**
     * Update cart item quantity
     * 
     * @param int $customer_id Customer ID
     * @param int $product_id Product ID
     * @param int $quantity New quantity
     * @return bool|string Success or error message
     */
    public function update_cart_item($customer_id, $product_id, $quantity) {
        try {
            if (empty($customer_id) || empty($product_id) || $quantity <= 0) {
                return "Invalid cart data.";
            }

            $result = $this->db->execute(
                "UPDATE cart SET qty = ? WHERE c_id = ? AND p_id = ?",
                [$quantity, $customer_id, $product_id]
            );
            
            return $result && $result->rowCount() > 0 ? true : "Failed to update cart item.";
        } catch (Exception $e) {
            error_log("Update cart item error: " . $e->getMessage());
            return "An error occurred while updating cart item.";
        }
    }

    /**
     * Remove item from cart
     * 
     * @param int $customer_id Customer ID
     * @param int $product_id Product ID
     * @return bool|string Success or error message
     */
    public function remove_from_cart($customer_id, $product_id) {
        try {
            if (empty($customer_id) || empty($product_id)) {
                return "Invalid cart data.";
            }

            $result = $this->db->execute(
                "DELETE FROM cart WHERE c_id = ? AND p_id = ?",
                [$customer_id, $product_id]
            );
            
            return $result ? true : "Failed to remove item from cart.";
        } catch (Exception $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            return "An error occurred while removing item from cart.";
        }
    }

    /**
     * Get all cart items for a customer
     * 
     * @param int $customer_id Customer ID
     * @return array|false Cart items with product details or false on error
     */
    public function get_cart_items($customer_id) {
        try {
            if (empty($customer_id)) {
                return [];
            }

            // Check if cart table exists (with error handling)
            try {
                $table_check = $this->db->fetchRow("SHOW TABLES LIKE 'cart'");
                if (!$table_check) {
                    error_log("Cart table does not exist");
                    return [];
                }
            } catch (Exception $e) {
                // If table check fails, try the query anyway
                error_log("Cart table check failed: " . $e->getMessage());
            }

            $sql = "SELECT 
                        c.c_id as customer_id,
                        c.p_id as product_id,
                        c.qty as quantity,
                        c.ip_add as ip_address,
                        p.product_id,
                        p.product_title,
                        p.product_price,
                        p.product_image,
                        p.product_desc,
                        cat.cat_name,
                        b.brand_name
                    FROM cart c
                    INNER JOIN products p ON c.p_id = p.product_id
                    LEFT JOIN categories cat ON p.product_cat = cat.cat_id
                    LEFT JOIN brands b ON p.product_brand = b.brand_id
                    WHERE c.c_id = ?
                    ORDER BY c.p_id DESC";

            $items = $this->db->fetchAll($sql, [$customer_id]);
            
            // Handle null or false result
            if (!$items || !is_array($items)) {
                return [];
            }
            
            // Add cart_id equivalent (using customer_id and product_id combination)
            foreach ($items as &$item) {
                if (isset($item['customer_id']) && isset($item['product_id'])) {
                    $item['cart_id'] = $item['customer_id'] . '_' . $item['product_id'];
                }
            }
            
            return $items;
        } catch (PDOException $e) {
            error_log("Get cart items PDO error: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Get cart items error: " . $e->getMessage());
            return [];
        } catch (Throwable $e) {
            error_log("Get cart items throwable error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cart item count for a customer
     * 
     * @param int $customer_id Customer ID
     * @return int Item count
     */
    public function get_cart_count($customer_id) {
        try {
            if (empty($customer_id)) {
                return 0;
            }

            $result = $this->db->fetchRow(
                "SELECT SUM(qty) as total FROM cart WHERE c_id = ?",
                [$customer_id]
            );

            // Handle null result (no items in cart)
            if (!$result || !isset($result['total']) || $result['total'] === null) {
                return 0;
            }

            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Get cart count PDO error: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            error_log("Get cart count error: " . $e->getMessage());
            return 0;
        } catch (Throwable $e) {
            error_log("Get cart count throwable error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get cart total for a customer
     * 
     * @param int $customer_id Customer ID
     * @return float Total amount
     */
    public function get_cart_total($customer_id) {
        try {
            if (empty($customer_id)) {
                return 0.00;
            }

            $sql = "SELECT SUM(p.product_price * c.qty) as total
                    FROM cart c
                    INNER JOIN products p ON c.p_id = p.product_id
                    WHERE c.c_id = ?";

            $result = $this->db->fetchRow($sql, [$customer_id]);

            // Handle null result (no items in cart)
            if (!$result || !isset($result['total']) || $result['total'] === null) {
                return 0.00;
            }

            return (float)$result['total'];
        } catch (PDOException $e) {
            error_log("Get cart total PDO error: " . $e->getMessage());
            return 0.00;
        } catch (Exception $e) {
            error_log("Get cart total error: " . $e->getMessage());
            return 0.00;
        } catch (Throwable $e) {
            error_log("Get cart total throwable error: " . $e->getMessage());
            return 0.00;
        }
    }

    /**
     * Clear cart for a customer
     * 
     * @param int $customer_id Customer ID
     * @return bool Success
     */
    public function clear_cart($customer_id) {
        try {
            if (empty($customer_id)) {
                return false;
            }

            $result = $this->db->execute(
                "DELETE FROM cart WHERE c_id = ?",
                [$customer_id]
            );

            return $result ? true : false;
        } catch (Exception $e) {
            error_log("Clear cart error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get single cart item
     * 
     * @param int $customer_id Customer ID
     * @param int $product_id Product ID
     * @return array|false Cart item or false
     */
    public function get_cart_item($customer_id, $product_id) {
        try {
            if (empty($customer_id) || empty($product_id)) {
                return false;
            }

            $sql = "SELECT 
                        c.c_id as customer_id,
                        c.p_id as product_id,
                        c.qty as quantity,
                        c.ip_add as ip_address,
                        p.product_id,
                        p.product_title,
                        p.product_price,
                        p.product_image,
                        p.product_desc,
                        cat.cat_name,
                        b.brand_name
                    FROM cart c
                    INNER JOIN products p ON c.p_id = p.product_id
                    LEFT JOIN categories cat ON p.product_cat = cat.cat_id
                    LEFT JOIN brands b ON p.product_brand = b.brand_id
                    WHERE c.c_id = ? AND c.p_id = ?";

            $item = $this->db->fetchRow($sql, [$customer_id, $product_id]);
            
            if ($item) {
                $item['cart_id'] = $customer_id . '_' . $product_id;
            }
            
            return $item;
        } catch (Exception $e) {
            error_log("Get cart item error: " . $e->getMessage());
            return false;
        }
    }
}
