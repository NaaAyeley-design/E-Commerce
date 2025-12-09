<?php
/**
 * Category Class
 *
 * Handles all category-related database operations including CRUD operations.
 * Categories are user-specific (each admin can manage their own categories).
 */

class category_class extends db_class {

    /**
     * Constructor - Initialize database connection with error handling
     */
    public function __construct() {
        try {
            parent::__construct();
        } catch (Exception $e) {
            // In development mode, we'll handle database connection failures gracefully
            if (defined('APP_ENV') && APP_ENV === 'development') {
                error_log("Database connection failed in category_class: " . $e->getMessage());
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
     * Add a new category to the database.
     *
     * @param string $cat_name Category name.
     * @param int $user_id User ID who created the category.
     * @return bool True on success, false on failure.
     */
    public function add_category($cat_name, $user_id) {
        $sql = "INSERT INTO categories (cat_name, user_id, created_at, updated_at) 
                VALUES (?, ?, NOW(), NOW())";

        $stmt = $this->execute($sql, [$cat_name, $user_id]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Check if a category name already exists for a specific user.
     *
     * @param string $cat_name The category name to check.
     * @param int $user_id The user ID.
     * @param int $exclude_id Optional category ID to exclude from check (for updates).
     * @return bool True if category name exists, false otherwise.
     */
    public function category_name_exists($cat_name, $user_id, $exclude_id = null) {
        $sql = "SELECT cat_id FROM categories WHERE cat_name = ? AND user_id = ?";
        $params = [$cat_name, $user_id];

        if ($exclude_id) {
            $sql .= " AND cat_id != ?";
            $params[] = $exclude_id;
        }

        $result = $this->fetchRow($sql, $params);
        return $result !== false;
    }

    /**
     * Get all categories for a specific user.
     *
     * @param int $user_id The user ID.
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of categories or empty array if none found.
     */
    public function get_categories_by_user($user_id, $limit = 50, $offset = 0) {
        $sql = "SELECT cat_id, cat_name, user_id, created_at, updated_at 
                FROM categories 
                WHERE user_id = ? 
                ORDER BY cat_name ASC 
                LIMIT ? OFFSET ?";

        return $this->fetchAll($sql, [$user_id, $limit, $offset]);
    }

    /**
     * Get a single category by ID and user ID.
     *
     * @param int $cat_id The category ID.
     * @param int $user_id The user ID (for security).
     * @return array|false Category data if found, false otherwise.
     */
    public function get_category_by_id($cat_id, $user_id) {
        $sql = "SELECT cat_id, cat_name, user_id, created_at, updated_at 
                FROM categories 
                WHERE cat_id = ? AND user_id = ?";

        return $this->fetchRow($sql, [$cat_id, $user_id]);
    }

    /**
     * Update a category's information.
     *
     * @param int $cat_id Category ID.
     * @param string $cat_name New category name.
     * @param int $user_id User ID (for security).
     * @return bool True on success, false on failure.
     */
    public function update_category($cat_id, $cat_name, $user_id) {
        $sql = "UPDATE categories 
                SET cat_name = ?, updated_at = NOW() 
                WHERE cat_id = ? AND user_id = ?";

        $stmt = $this->execute($sql, [$cat_name, $cat_id, $user_id]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Update a category (admin version - no user_id filter).
     *
     * @param int $cat_id Category ID.
     * @param string $cat_name New category name.
     * @return bool True on success, false on failure.
     */
    public function update_category_admin($cat_id, $cat_name) {
        $sql = "UPDATE categories 
                SET cat_name = ?, updated_at = NOW() 
                WHERE cat_id = ?";

        $stmt = $this->execute($sql, [$cat_name, $cat_id]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Check if a category name exists globally (admin check).
     *
     * @param string $cat_name The category name to check.
     * @param int|null $exclude_id Optional category ID to exclude from check.
     * @return bool True if name exists, false otherwise.
     */
    public function category_name_exists_global($cat_name, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM categories WHERE cat_name = ?";
        $params = [$cat_name];

        if ($exclude_id) {
            $sql .= " AND cat_id != ?";
            $params[] = $exclude_id;
        }

        $result = $this->fetchRow($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Delete a category.
     *
     * @param int $cat_id The category ID.
     * @param int $user_id The user ID (for security).
     * @return bool True on success, false on failure.
     */
    public function delete_category($cat_id, $user_id) {
        $sql = "DELETE FROM categories WHERE cat_id = ? AND user_id = ?";

        $stmt = $this->execute($sql, [$cat_id, $user_id]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get a single category by ID (admin version - no user_id filter).
     *
     * @param int $cat_id The category ID.
     * @return array|false Category data if found, false otherwise.
     */
    public function get_category_by_id_admin($cat_id) {
        $sql = "SELECT cat_id, cat_name, user_id, created_at, updated_at 
                FROM categories 
                WHERE cat_id = ?";

        return $this->fetchRow($sql, [$cat_id]);
    }

    /**
     * Delete a category (admin version - no user_id filter).
     *
     * @param int $cat_id The category ID.
     * @return bool True on success, false on failure.
     */
    public function delete_category_admin($cat_id) {
        $sql = "DELETE FROM categories WHERE cat_id = ?";

        $stmt = $this->execute($sql, [$cat_id]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get total count of categories for a user.
     *
     * @param int $user_id The user ID.
     * @return int Total number of categories.
     */
    public function count_categories_by_user($user_id) {
        $sql = "SELECT COUNT(*) as total FROM categories WHERE user_id = ?";
        $result = $this->fetchRow($sql, [$user_id]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Count all categories (for admin dashboard).
     *
     * @return int Total number of categories.
     */
    public function count_all_categories() {
        try {
            if (!isset($this->conn) || $this->conn === null) {
                try {
                    $this->connect();
                } catch (Exception $e) {
                    return 0;
                }
            }
            $sql = "SELECT COUNT(*) as total FROM categories";
            $result = $this->fetchRow($sql);
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            error_log("count_all_categories error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Search categories by name for a specific user.
     *
     * @param string $search_term The search term.
     * @param int $user_id The user ID.
     * @param int $limit Optional limit for results.
     * @return array Array of matching categories.
     */
    public function search_categories($search_term, $user_id, $limit = 20) {
        $sql = "SELECT cat_id, cat_name, user_id, created_at, updated_at 
                FROM categories 
                WHERE cat_name LIKE ? AND user_id = ? 
                ORDER BY cat_name ASC 
                LIMIT ?";

        $search_param = '%' . $search_term . '%';
        return $this->fetchAll($sql, [$search_param, $user_id, $limit]);
    }

    /**
     * Search all categories by name (admin function).
     *
     * @param string $search_term The search term.
     * @param int $limit Optional limit for results.
     * @return array Array of matching categories.
     */
    public function search_all_categories($search_term, $limit = 100) {
        try {
            // Check if database connection is available
            if (!isset($this->conn) || $this->conn === null) {
                throw new Exception("Database connection not available");
            }

            $sql = "SELECT c.cat_id, c.cat_name, c.created_at, c.updated_at,
                           u.customer_name as creator_name
                    FROM categories c
                    LEFT JOIN customer u ON c.user_id = u.customer_id
                    WHERE c.cat_name LIKE ? 
                    ORDER BY c.cat_name ASC 
                    LIMIT ?";

            $search_param = '%' . $search_term . '%';
            $result = $this->fetchAll($sql, [$search_param, $limit]);

            // If no categories found and we're in development, filter sample data
            if (empty($result) && APP_ENV === 'development') {
                $sample_categories = $this->get_sample_categories();
                $filtered = array_filter($sample_categories, function($cat) use ($search_term) {
                    return stripos($cat['cat_name'], $search_term) !== false;
                });
                return array_values($filtered);
            }

            return $result;
        } catch (Exception $e) {
            error_log("search_all_categories error: " . $e->getMessage());

            // Return filtered sample data in development when database is not available
            if (APP_ENV === 'development') {
                $sample_categories = $this->get_sample_categories();
                $filtered = array_filter($sample_categories, function($cat) use ($search_term) {
                    return stripos($cat['cat_name'], $search_term) !== false;
                });
                return array_values($filtered);
            }

            return [];
        }
    }

    /**
     * Get all categories (for public display - no user restriction).
     *
     * @param int $limit Optional limit for results.
     * @param int $offset Optional offset for pagination.
     * @return array Array of all categories.
     */
    public function get_all_categories($limit = 100, $offset = 0) {
        // Ensure connection is established - use same pattern as count_all_categories
        if (!isset($this->conn) || $this->conn === null) {
            try {
                $this->connect();
            } catch (Exception $e) {
                error_log("get_all_categories - Connection failed: " . $e->getMessage());
                return [];
            }
        }

        // Verify connection is still available and valid
        if (!isset($this->conn) || $this->conn === null) {
            error_log("get_all_categories - Connection is null after attempts");
            return [];
        }

        try {
            // Build SQL query - use direct SQL string like count_all_categories does
            $sql = "SELECT cat_id, cat_name, created_at, updated_at 
                    FROM categories 
                    ORDER BY cat_name ASC";

            // Add LIMIT/OFFSET if not fetching all
            if ($limit < 999999) {
                $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            }

            error_log("get_all_categories - Executing SQL: " . $sql);

            // Use direct query execution like count_all_categories pattern
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                error_log("get_all_categories - Prepare failed: " . print_r($error, true));
                return [];
            }

            $executed = $stmt->execute();
            if (!$executed) {
                $error = $stmt->errorInfo();
                error_log("get_all_categories - Execute failed: " . print_r($error, true));
                return [];
            }

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("get_all_categories - Query returned " . count($result) . " rows");

            // Return empty array if no categories found
            if (empty($result)) {
                error_log("get_all_categories - No categories found in database (limit: $limit, offset: $offset)");
                return [];
            }

            // Add creator_name field for compatibility (can be populated later if needed)
            foreach ($result as &$row) {
                if (!isset($row['creator_name'])) {
                    $row['creator_name'] = null;
                }
            }
            unset($row);

            error_log("get_all_categories - Successfully retrieved " . count($result) . " categories");
            return $result;

        } catch (PDOException $e) {
            error_log("get_all_categories - PDO error: " . $e->getMessage());
            error_log("get_all_categories - SQL: " . $sql);
            error_log("get_all_categories - Error code: " . $e->getCode());
            return [];
        } catch (Exception $e) {
            error_log("get_all_categories - General error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get sample categories for development/testing
     *
     * @return array Array of sample categories
     */
    private function get_sample_categories() {
        return [
            [
                'cat_id' => 1,
                'cat_name' => 'Electronics',
                'created_at' => '2024-01-15 10:30:00',
                'updated_at' => '2024-01-15 10:30:00',
                'creator_name' => 'Admin User'
            ],
            [
                'cat_id' => 2,
                'cat_name' => 'Clothing',
                'created_at' => '2024-01-16 14:20:00',
                'updated_at' => '2024-01-16 14:20:00',
                'creator_name' => 'Admin User'
            ],
            [
                'cat_id' => 3,
                'cat_name' => 'Books',
                'created_at' => '2024-01-17 09:15:00',
                'updated_at' => '2024-01-17 09:15:00',
                'creator_name' => 'Admin User'
            ],
            [
                'cat_id' => 4,
                'cat_name' => 'Home & Garden',
                'created_at' => '2024-01-18 16:45:00',
                'updated_at' => '2024-01-18 16:45:00',
                'creator_name' => 'Admin User'
            ],
            [
                'cat_id' => 5,
                'cat_name' => 'Sports & Outdoors',
                'created_at' => '2024-01-19 11:30:00',
                'updated_at' => '2024-01-19 11:30:00',
                'creator_name' => 'Admin User'
            ]
        ];
    }

    /**
     * Check if category has associated products (for safe deletion).
     *
     * @param int $cat_id The category ID.
     * @return bool True if category has products, false otherwise.
     */
    public function category_has_products($cat_id) {
        // This assumes you have a products table with cat_id foreign key
        $sql = "SELECT COUNT(*) as total FROM products WHERE cat_id = ?";
        $result = $this->fetchRow($sql, [$cat_id]);
        return $result ? (int)$result['total'] > 0 : false;
    }

    /**
     * Get categories with product count.
     *
     * @param int $user_id The user ID.
     * @return array Array of categories with product counts.
     */
    public function get_categories_with_product_count($user_id) {
        $sql = "SELECT c.cat_id, c.cat_name, c.created_at, c.updated_at,
                       COALESCE(COUNT(p.product_id), 0) as product_count
                FROM categories c
                LEFT JOIN products p ON c.cat_id = p.cat_id
                WHERE c.user_id = ?
                GROUP BY c.cat_id, c.cat_name, c.created_at, c.updated_at
                ORDER BY c.cat_name ASC";

        return $this->fetchAll($sql, [$user_id]);
    }
}
?>