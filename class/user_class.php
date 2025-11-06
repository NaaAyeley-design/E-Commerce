<?php
/**
 * User Class
 * 
 * Handles all user-related database operations including authentication,
 * registration, profile management, and password operations.
 */

class user_class extends db_class {
    private static $column_cache = null;
    
    /**
     * Check if columns exist in customer table
     */
    private function check_customer_columns() {
        if (self::$column_cache !== null) {
            return self::$column_cache;
        }
        
        try {
            $db_name = DB_NAME;
            $sql = "SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = ? 
                    AND TABLE_NAME = 'customer' 
                    AND COLUMN_NAME IN ('is_active', 'created_at')";
            
            $result = $this->fetchAll($sql, [$db_name]);
            $columns = array_column($result, 'COLUMN_NAME');
            
            self::$column_cache = [
                'is_active' => in_array('is_active', $columns),
                'created_at' => in_array('created_at', $columns)
            ];
            
            return self::$column_cache;
        } catch (Exception $e) {
            error_log("Check columns error: " . $e->getMessage());
            self::$column_cache = ['is_active' => false, 'created_at' => false];
            return self::$column_cache;
        }
    }
    
    /**
     * Add new customer/user
     */
    public function add_customer($name, $email, $password, $country, $city, $contact, $role = 2) {
        // Encrypt password
        $hashed_pass = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);

        $sql = "INSERT INTO customer 
                (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $params = [$name, $email, $hashed_pass, $country, $city, $contact, $role];
        
        $stmt = $this->execute($sql, $params);
        
        return $stmt !== false;
    }

    /**
     * Check if email already exists
     */
    public function email_exists($email) {
        $sql = "SELECT customer_id FROM customer WHERE customer_email = ?";
        $result = $this->fetchRow($sql, [$email]);
        return $result !== false;
    }

    /**
     * Authenticate user login
     */
    public function login_customer($email, $password) {
        $sql = "SELECT customer_id, customer_name, customer_email, customer_pass, user_role 
                FROM customer WHERE customer_email = ?";
        
        $customer = $this->fetchRow($sql, [$email]);
        
        if ($customer && password_verify($password, $customer['customer_pass'])) {
            // Remove password from returned data for security
            unset($customer['customer_pass']);
            return $customer;
        }
        
        return false;
    }

    /**
     * Get customer by ID
     */
    public function get_customer_by_id($customer_id) {
        $columns = $this->check_customer_columns();
        
        $select_fields = "customer_id, customer_name, customer_email, customer_country, 
                customer_city, customer_contact, user_role,
                (user_role = 1) as is_admin";
        
        if ($columns['is_active']) {
            $select_fields .= ", is_active";
        } else {
            $select_fields .= ", 1 as is_active";
        }
        
        if ($columns['created_at']) {
            $select_fields .= ", created_at";
        } else {
            $select_fields .= ", '1970-01-01 00:00:00' as created_at";
        }
        
        $sql = "SELECT $select_fields FROM customer WHERE customer_id = ?";
        
        return $this->fetchRow($sql, [$customer_id]);
    }

    /**
     * Update customer information
     */
    public function update_customer($customer_id, $name, $email, $country, $city, $contact) {
        $sql = "UPDATE customer SET customer_name = ?, customer_email = ?, 
                customer_country = ?, customer_city = ?, customer_contact = ? 
                WHERE customer_id = ?";
        
        $params = [$name, $email, $country, $city, $contact, $customer_id];
        $stmt = $this->execute($sql, $params);
        
        return $stmt !== false;
    }

    /**
     * Change user password
     */
    public function change_password($customer_id, $new_password) {
        $hashed_pass = password_hash($new_password, HASH_ALGO, ['cost' => HASH_COST]);
        
        $sql = "UPDATE customer SET customer_pass = ? WHERE customer_id = ?";
        $stmt = $this->execute($sql, [$hashed_pass, $customer_id]);
        
        return $stmt !== false;
    }

    /**
     * Get customer by email
     */
    public function get_customer_by_email($email) {
        $sql = "SELECT customer_id, customer_name, customer_email FROM customer WHERE customer_email = ?";
        return $this->fetchRow($sql, [$email]);
    }

    /**
     * Create password reset token
     */
    public function create_reset_token($customer_id) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
        
        $sql = "INSERT INTO password_resets (customer_id, token, expires_at) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        
        $stmt = $this->execute($sql, [$customer_id, $token, $expires]);
        
        return $stmt !== false ? $token : false;
    }

    /**
     * Verify password reset token
     */
    public function verify_reset_token($token) {
        $sql = "SELECT pr.customer_id, c.customer_email 
                FROM password_resets pr 
                JOIN customer c ON pr.customer_id = c.customer_id 
                WHERE pr.token = ? AND pr.expires_at > NOW()";
        
        return $this->fetchRow($sql, [$token]);
    }

    /**
     * Delete password reset token
     */
    public function delete_reset_token($token) {
        $sql = "DELETE FROM password_resets WHERE token = ?";
        $stmt = $this->execute($sql, [$token]);
        return $stmt !== false;
    }

    /**
     * Get customer's current password hash (for verification)
     */
    public function get_password_hash($customer_id) {
        $sql = "SELECT customer_pass FROM customer WHERE customer_id = ?";
        $result = $this->fetchRow($sql, [$customer_id]);
        return $result ? $result['customer_pass'] : false;
    }

    /**
     * Update customer image
     */
    public function update_customer_image($customer_id, $image_path) {
        $sql = "UPDATE customer SET customer_image = ? WHERE customer_id = ?";
        $stmt = $this->execute($sql, [$image_path, $customer_id]);
        return $stmt !== false;
    }

    /**
     * Get all customers (admin function)
     */
    public function get_all_customers($limit = 50, $offset = 0) {
        $columns = $this->check_customer_columns();
        
        $select_fields = "customer_id, customer_name, customer_email, customer_country, 
                customer_city, customer_contact, user_role,
                (user_role = 1) as is_admin";
        
        if ($columns['is_active']) {
            $select_fields .= ", is_active";
        } else {
            $select_fields .= ", 1 as is_active";
        }
        
        if ($columns['created_at']) {
            $select_fields .= ", created_at";
        } else {
            $select_fields .= ", '1970-01-01 00:00:00' as created_at";
        }
        
        $sql = "SELECT $select_fields 
                FROM customer 
                ORDER BY customer_id DESC 
                LIMIT ? OFFSET ?";
        
        return $this->fetchAll($sql, [$limit, $offset]);
    }

    /**
     * Delete customer (admin function)
     */
    public function delete_customer($customer_id) {
        $sql = "DELETE FROM customer WHERE customer_id = ?";
        $stmt = $this->execute($sql, [$customer_id]);
        return $stmt !== false;
    }

    /**
     * Count total customers
     */
    public function count_customers() {
        $sql = "SELECT COUNT(*) as total FROM customer";
        $result = $this->fetchRow($sql);
        return $result ? $result['total'] : 0;
    }

    /**
     * Search customers by name or email
     */
    public function search_customers($search_term, $limit = 20) {
        $search_term = "%$search_term%";
        $sql = "SELECT customer_id, customer_name, customer_email, customer_country, 
                customer_city, user_role 
                FROM customer 
                WHERE customer_name LIKE ? OR customer_email LIKE ?
                ORDER BY customer_name ASC 
                LIMIT ?";
        
        return $this->fetchAll($sql, [$search_term, $search_term, $limit]);
    }

    /**
     * Update customer status (active/inactive)
     */
    public function update_customer_status($customer_id, $status) {
        try {
            $sql = "UPDATE customer SET is_active = ? WHERE customer_id = ?";
            $stmt = $this->execute($sql, [$status, $customer_id]);
            
            if ($stmt && $stmt->rowCount() > 0) {
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Update customer status error: " . $e->getMessage());
            return false;
        }
    }
}

?>
