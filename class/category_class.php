<?php
/**
 * Category Class
 * 
 * Handles all category-related database operations including CRUD operations.
 * Categories are user-specific (each admin can manage their own categories).
 */

class category_class extends db_class {
    
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
     * Get all categories (for public display - no user restriction).
     *
     * @param int $limit Optional limit for results.
     * @param int $offset Optional offset for pagination.
     * @return array Array of all categories.
     */
    public function get_all_categories($limit = 100, $offset = 0) {
        $sql = "SELECT c.cat_id, c.cat_name, c.created_at, c.updated_at,
                       u.customer_name as creator_name
                FROM categories c
                LEFT JOIN customer u ON c.user_id = u.customer_id
                ORDER BY c.cat_name ASC 
                LIMIT ? OFFSET ?";
        
        return $this->fetchAll($sql, [$limit, $offset]);
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
