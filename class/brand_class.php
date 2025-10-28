<?php
/**
 * Brand Class
 * 
 * Handles all brand-related database operations including CRUD operations.
 * Brands belong to categories and are user-specific (each admin can manage their own brands).
 */

class brand_class extends db_class {
    
    /**
     * Constructor - Initialize database connection with error handling
     */
    public function __construct() {
        try {
            parent::__construct();
        } catch (Exception $e) {
            // In development mode, we'll handle database connection failures gracefully
            if (defined('APP_ENV') && APP_ENV === 'development') {
                error_log("Database connection failed in brand_class: " . $e->getMessage());
                // Don't throw the exception, just log it
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * Try to establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = array_merge(DB_OPTIONS, [
                PDO::ATTR_PERSISTENT => DB_PERSISTENT,
                PDO::ATTR_TIMEOUT => DB_TIMEOUT
            ]);
            
            $this->conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Add a new brand to the database.
     *
     * @param int $user_id User ID who created the brand.
     * @param int $cat_id Category ID the brand belongs to.
     * @param string $brand_name Brand name.
     * @param string $brand_description Optional brand description.
     * @param string $brand_logo Optional brand logo URL.
     * @return array Result array with success status and message.
     */
    public function add_brand($user_id, $cat_id, $brand_name, $brand_description = null, $brand_logo = null) {
        // Validate input
        if (empty($brand_name) || empty($cat_id) || empty($user_id)) {
            return ['success' => false, 'message' => 'Brand name, category ID, and user ID are required.'];
        }
        
        // Check if brand name already exists for this user in this category
        if ($this->brand_name_exists($brand_name, $cat_id, $user_id)) {
            return ['success' => false, 'message' => 'Brand name already exists in this category.'];
        }
        
        // Verify category belongs to user
        if (!$this->category_belongs_to_user($cat_id, $user_id)) {
            return ['success' => false, 'message' => 'Category not found or access denied.'];
        }
        
        $sql = "INSERT INTO brands (brand_name, brand_description, cat_id, user_id, brand_logo, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->execute($sql, [$brand_name, $brand_description, $cat_id, $user_id, $brand_logo]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $brand_id = $this->lastInsertId();
            return ['success' => true, 'message' => 'Brand added successfully.', 'brand_id' => $brand_id];
        } else {
            return ['success' => false, 'message' => 'Failed to add brand.'];
        }
    }

    /**
     * Get all brands for a specific user.
     *
     * @param int $user_id The user ID.
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of brands or empty array if none found.
     */
    public function get_brands_by_user($user_id, $limit = 50, $offset = 0) {
        $sql = "SELECT b.brand_id, b.brand_name, b.brand_description, b.brand_logo, 
                       b.cat_id, b.user_id, b.is_active, b.created_at, b.updated_at,
                       c.cat_name
                FROM brands b
                LEFT JOIN categories c ON b.cat_id = c.cat_id
                WHERE b.user_id = ? 
                ORDER BY b.brand_name ASC 
                LIMIT ? OFFSET ?";
        
        return $this->fetchAll($sql, [$user_id, $limit, $offset]);
    }

    /**
     * Get brands by category for a specific user.
     *
     * @param int $user_id The user ID.
     * @param int $cat_id The category ID.
     * @return array Array of brands in the specified category.
     */
    public function get_brands_by_category($user_id, $cat_id) {
        $sql = "SELECT b.brand_id, b.brand_name, b.brand_description, b.brand_logo, 
                       b.is_active, b.created_at, b.updated_at
                FROM brands b
                WHERE b.user_id = ? AND b.cat_id = ? AND b.is_active = 1
                ORDER BY b.brand_name ASC";
        
        return $this->fetchAll($sql, [$user_id, $cat_id]);
    }

    /**
     * Get a single brand by ID and user ID.
     *
     * @param int $brand_id The brand ID.
     * @param int $user_id The user ID (for security).
     * @return array|false Brand data if found, false otherwise.
     */
    public function get_brand_by_id($brand_id, $user_id) {
        $sql = "SELECT b.brand_id, b.brand_name, b.brand_description, b.brand_logo, 
                       b.cat_id, b.user_id, b.is_active, b.created_at, b.updated_at,
                       c.cat_name
                FROM brands b
                LEFT JOIN categories c ON b.cat_id = c.cat_id
                WHERE b.brand_id = ? AND b.user_id = ?";
        
        return $this->fetchRow($sql, [$brand_id, $user_id]);
    }

    /**
     * Update a brand's information.
     *
     * @param int $brand_id Brand ID.
     * @param string $brand_name New brand name.
     * @param int $user_id User ID (for security).
     * @param string $brand_description Optional new brand description.
     * @param string $brand_logo Optional new brand logo URL.
     * @return array Result array with success status and message.
     */
    public function update_brand($brand_id, $brand_name, $user_id, $brand_description = null, $brand_logo = null) {
        // Validate input
        if (empty($brand_name) || empty($brand_id) || empty($user_id)) {
            return ['success' => false, 'message' => 'Brand ID, brand name, and user ID are required.'];
        }
        
        // Get current brand data
        $current_brand = $this->get_brand_by_id($brand_id, $user_id);
        if (!$current_brand) {
            return ['success' => false, 'message' => 'Brand not found or access denied.'];
        }
        
        // Check if new brand name already exists for this user in this category (excluding current brand)
        if ($brand_name !== $current_brand['brand_name'] && 
            $this->brand_name_exists($brand_name, $current_brand['cat_id'], $user_id, $brand_id)) {
            return ['success' => false, 'message' => 'Brand name already exists in this category.'];
        }
        
        $sql = "UPDATE brands 
                SET brand_name = ?, brand_description = ?, brand_logo = ?, updated_at = NOW() 
                WHERE brand_id = ? AND user_id = ?";
        
        $stmt = $this->execute($sql, [$brand_name, $brand_description, $brand_logo, $brand_id, $user_id]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Brand updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update brand or no changes made.'];
        }
    }

    /**
     * Delete a brand.
     *
     * @param int $brand_id The brand ID.
     * @param int $user_id The user ID (for security).
     * @return array Result array with success status and message.
     */
    public function delete_brand($brand_id, $user_id) {
        // Validate input
        if (empty($brand_id) || empty($user_id)) {
            return ['success' => false, 'message' => 'Brand ID and user ID are required.'];
        }
        
        // Check if brand exists and belongs to user
        $brand = $this->get_brand_by_id($brand_id, $user_id);
        if (!$brand) {
            return ['success' => false, 'message' => 'Brand not found or access denied.'];
        }
        
        // Check if brand has associated products
        if ($this->brand_has_products($brand_id)) {
            return ['success' => false, 'message' => 'Cannot delete brand. It has associated products.'];
        }
        
        $sql = "DELETE FROM brands WHERE brand_id = ? AND user_id = ?";
        
        $stmt = $this->execute($sql, [$brand_id, $user_id]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Brand deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete brand.'];
        }
    }

    /**
     * Check if a brand name already exists for a specific user in a specific category.
     *
     * @param string $brand_name The brand name to check.
     * @param int $cat_id The category ID.
     * @param int $user_id The user ID.
     * @param int $exclude_id Optional brand ID to exclude from check (for updates).
     * @return bool True if brand name exists, false otherwise.
     */
    public function brand_name_exists($brand_name, $cat_id, $user_id, $exclude_id = null) {
        $sql = "SELECT brand_id FROM brands WHERE brand_name = ? AND cat_id = ? AND user_id = ?";
        $params = [$brand_name, $cat_id, $user_id];
        
        if ($exclude_id) {
            $sql .= " AND brand_id != ?";
            $params[] = $exclude_id;
        }
        
        $result = $this->fetchRow($sql, $params);
        return $result !== false;
    }

    /**
     * Check if category belongs to user.
     *
     * @param int $cat_id The category ID.
     * @param int $user_id The user ID.
     * @return bool True if category belongs to user, false otherwise.
     */
    private function category_belongs_to_user($cat_id, $user_id) {
        $sql = "SELECT cat_id FROM categories WHERE cat_id = ? AND user_id = ?";
        $result = $this->fetchRow($sql, [$cat_id, $user_id]);
        return $result !== false;
    }

    /**
     * Check if brand has associated products (for safe deletion).
     *
     * @param int $brand_id The brand ID.
     * @return bool True if brand has products, false otherwise.
     */
    public function brand_has_products($brand_id) {
        $sql = "SELECT COUNT(*) as total FROM products WHERE brand_id = ?";
        $result = $this->fetchRow($sql, [$brand_id]);
        return $result ? (int)$result['total'] > 0 : false;
    }

    /**
     * Get total count of brands for a user.
     *
     * @param int $user_id The user ID.
     * @return int Total number of brands.
     */
    public function count_brands_by_user($user_id) {
        $sql = "SELECT COUNT(*) as total FROM brands WHERE user_id = ?";
        $result = $this->fetchRow($sql, [$user_id]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Search brands by name for a specific user.
     *
     * @param string $search_term The search term.
     * @param int $user_id The user ID.
     * @param int $limit Optional limit for results.
     * @return array Array of matching brands.
     */
    public function search_brands($search_term, $user_id, $limit = 20) {
        $sql = "SELECT b.brand_id, b.brand_name, b.brand_description, b.brand_logo, 
                       b.cat_id, b.is_active, b.created_at, b.updated_at,
                       c.cat_name
                FROM brands b
                LEFT JOIN categories c ON b.cat_id = c.cat_id
                WHERE b.brand_name LIKE ? AND b.user_id = ? 
                ORDER BY b.brand_name ASC 
                LIMIT ?";
        
        $search_param = '%' . $search_term . '%';
        return $this->fetchAll($sql, [$search_param, $user_id, $limit]);
    }

    /**
     * Toggle brand active status.
     *
     * @param int $brand_id The brand ID.
     * @param int $user_id The user ID (for security).
     * @param int $is_active 1 for active, 0 for inactive.
     * @return array Result array with success status and message.
     */
    public function toggle_brand_status($brand_id, $user_id, $is_active) {
        $sql = "UPDATE brands 
                SET is_active = ?, updated_at = NOW() 
                WHERE brand_id = ? AND user_id = ?";
        
        $stmt = $this->execute($sql, [$is_active, $brand_id, $user_id]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $status_text = $is_active ? 'activated' : 'deactivated';
            return ['success' => true, 'message' => "Brand {$status_text} successfully."];
        } else {
            return ['success' => false, 'message' => 'Failed to update brand status.'];
        }
    }

    /**
     * Get brands with product count for a specific user.
     *
     * @param int $user_id The user ID.
     * @return array Array of brands with product counts.
     */
    public function get_brands_with_product_count($user_id) {
        $sql = "SELECT b.brand_id, b.brand_name, b.brand_description, b.brand_logo, 
                       b.cat_id, b.is_active, b.created_at, b.updated_at,
                       c.cat_name,
                       COALESCE(COUNT(p.product_id), 0) as product_count
                FROM brands b
                LEFT JOIN categories c ON b.cat_id = c.cat_id
                LEFT JOIN products p ON b.brand_id = p.brand_id
                WHERE b.user_id = ?
                GROUP BY b.brand_id, b.brand_name, b.brand_description, b.brand_logo, 
                         b.cat_id, b.is_active, b.created_at, b.updated_at, c.cat_name
                ORDER BY b.brand_name ASC";
        
        return $this->fetchAll($sql, [$user_id]);
    }
    
    /**
     * Get all brands (for admin display - no user restriction).
     *
     * @param int $limit Optional limit for results.
     * @param int $offset Optional offset for pagination.
     * @return array Array of all brands.
     */
    public function get_all_brands($limit = 100, $offset = 0) {
        try {
            // Check if database connection is available
            if (!isset($this->conn) || $this->conn === null) {
                // Try to reconnect
                try {
                    $this->connect();
                } catch (Exception $e) {
                    throw new Exception("Database connection not available");
                }
            }
            
            $sql = "SELECT b.brand_id, b.brand_name, b.brand_description, b.brand_logo, 
                           b.cat_id, b.is_active, b.created_at, b.updated_at,
                           c.cat_name,
                           u.customer_name as creator_name
                    FROM brands b
                    LEFT JOIN categories c ON b.cat_id = c.cat_id
                    LEFT JOIN customer u ON b.user_id = u.customer_id
                    ORDER BY b.brand_name ASC 
                    LIMIT ? OFFSET ?";
            
            $result = $this->fetchAll($sql, [$limit, $offset]);
            
            // If no brands found and we're in development, return sample data
            if (empty($result) && APP_ENV === 'development') {
                return $this->get_sample_brands();
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("get_all_brands error: " . $e->getMessage());
            
            // Return sample data in development when database is not available
            if (APP_ENV === 'development') {
                return $this->get_sample_brands();
            }
            
            return [];
        }
    }
    
    /**
     * Get sample brands for development/testing
     *
     * @return array Array of sample brands
     */
    private function get_sample_brands() {
        return [
            [
                'brand_id' => 1,
                'brand_name' => 'Nike',
                'brand_description' => 'Just Do It',
                'brand_logo' => '',
                'cat_id' => 1,
                'is_active' => 1,
                'created_at' => '2024-01-15 10:30:00',
                'updated_at' => '2024-01-15 10:30:00',
                'cat_name' => 'Electronics',
                'creator_name' => 'Admin User'
            ],
            [
                'brand_id' => 2,
                'brand_name' => 'Adidas',
                'brand_description' => 'Impossible is Nothing',
                'brand_logo' => '',
                'cat_id' => 2,
                'is_active' => 1,
                'created_at' => '2024-01-16 14:20:00',
                'updated_at' => '2024-01-16 14:20:00',
                'cat_name' => 'Clothing',
                'creator_name' => 'Admin User'
            ],
            [
                'brand_id' => 3,
                'brand_name' => 'Apple',
                'brand_description' => 'Think Different',
                'brand_logo' => '',
                'cat_id' => 1,
                'is_active' => 1,
                'created_at' => '2024-01-17 09:15:00',
                'updated_at' => '2024-01-17 09:15:00',
                'cat_name' => 'Electronics',
                'creator_name' => 'Admin User'
            ]
        ];
    }
}
?>
