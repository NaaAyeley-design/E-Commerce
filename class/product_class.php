<?php
/**
 * Product Class
 * 
 * Handles all product-related database operations including CRUD operations.
 * Products belong to categories and brands, and are user-specific (each admin can manage their own products).
 */

class product_class extends db_class {
    
    /**
     * Add a new product to the database.
     *
     * @param int $user_id User ID who created the product.
     * @param int $cat_id Category ID the product belongs to.
     * @param int $brand_id Brand ID the product belongs to.
     * @param string $title Product title/name.
     * @param float $price Product price.
     * @param string $desc Product description.
     * @param string $keyword Product keywords for SEO.
     * @param string $image_path Optional main product image path.
     * @param string $sku Optional Stock Keeping Unit.
     * @param float $compare_price Optional compare price.
     * @param float $cost_price Optional cost price.
     * @param int $stock_quantity Optional stock quantity.
     * @param float $weight Optional product weight.
     * @param string $dimensions Optional product dimensions.
     * @param string $meta_title Optional SEO meta title.
     * @param string $meta_description Optional SEO meta description.
     * @return array Result array with success status, message, and product ID.
     */
    public function add_product($user_id, $cat_id, $brand_id, $title, $price, $desc, $keyword, $image_path = null, $sku = null, $compare_price = null, $cost_price = null, $stock_quantity = 0, $weight = null, $dimensions = null, $meta_title = null, $meta_description = null) {
        // Validate input
        if (empty($title) || empty($cat_id) || empty($brand_id) || empty($user_id) || $price === null || $price === '') {
            return ['success' => false, 'message' => 'Title, category ID, brand ID, user ID, and price are required.'];
        }
        
        // Validate price
        if (!is_numeric($price) || $price < 0) {
            return ['success' => false, 'message' => 'Price must be a valid positive number.'];
        }
        
        // Check if product title already exists for this user in this category and brand
        if ($this->product_title_exists($title, $cat_id, $brand_id, $user_id)) {
            return ['success' => false, 'message' => 'Product title already exists in this category and brand combination.'];
        }
        
        // Verify category belongs to user
        if (!$this->category_belongs_to_user($cat_id, $user_id)) {
            return ['success' => false, 'message' => 'Category not found or access denied.'];
        }
        
        // Verify brand belongs to user and category
        if (!$this->brand_belongs_to_user_and_category($brand_id, $user_id, $cat_id)) {
            return ['success' => false, 'message' => 'Brand not found or does not belong to the selected category.'];
        }
        
        // Validate optional prices
        if ($compare_price !== null && (!is_numeric($compare_price) || $compare_price < 0)) {
            return ['success' => false, 'message' => 'Compare price must be a valid positive number.'];
        }
        
        if ($cost_price !== null && (!is_numeric($cost_price) || $cost_price < 0)) {
            return ['success' => false, 'message' => 'Cost price must be a valid positive number.'];
        }
        
        // Validate stock quantity
        if (!is_numeric($stock_quantity) || $stock_quantity < 0) {
            $stock_quantity = 0;
        }
        
        // Validate weight
        if ($weight !== null && (!is_numeric($weight) || $weight < 0)) {
            return ['success' => false, 'message' => 'Weight must be a valid positive number.'];
        }
        
        $sql = "INSERT INTO products (
                    product_name, product_description, product_short_desc, 
                    cat_id, brand_id, user_id, sku, price, compare_price, cost_price, 
                    stock_quantity, weight, dimensions, meta_title, meta_description, meta_keywords,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->execute($sql, [
            $title, $desc, $desc, // Using description for both long and short desc
            $cat_id, $brand_id, $user_id, $sku, $price, $compare_price, $cost_price,
            $stock_quantity, $weight, $dimensions, $meta_title, $meta_description, $keyword
        ]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $product_id = $this->lastInsertId();
            
            // Add main image if provided
            if (!empty($image_path)) {
                $this->add_product_image($product_id, $image_path, true); // Set as primary image
            }
            
            return ['success' => true, 'message' => 'Product added successfully.', 'product_id' => $product_id];
        } else {
            return ['success' => false, 'message' => 'Failed to add product.'];
        }
    }

    /**
     * Update a product's information.
     *
     * @param int $product_id Product ID.
     * @param string $title New product title.
     * @param float $price New product price.
     * @param string $desc New product description.
     * @param string $keyword New product keywords.
     * @param string $image_path Optional new main product image path.
     * @param int $user_id User ID (for security).
     * @param string $sku Optional SKU.
     * @param float $compare_price Optional compare price.
     * @param float $cost_price Optional cost price.
     * @param int $stock_quantity Optional stock quantity.
     * @param float $weight Optional weight.
     * @param string $dimensions Optional dimensions.
     * @param string $meta_title Optional meta title.
     * @param string $meta_description Optional meta description.
     * @return array Result array with success status and message.
     */
    public function update_product($product_id, $title, $price, $desc, $keyword, $image_path = null, $user_id = null, $sku = null, $compare_price = null, $cost_price = null, $stock_quantity = null, $weight = null, $dimensions = null, $meta_title = null, $meta_description = null) {
        // Validate input
        if (empty($product_id) || empty($title) || $price === null || $price === '') {
            return ['success' => false, 'message' => 'Product ID, title, and price are required.'];
        }
        
        // Validate price
        if (!is_numeric($price) || $price < 0) {
            return ['success' => false, 'message' => 'Price must be a valid positive number.'];
        }
        
        // Get current product data
        $current_product = $this->get_product_by_id($product_id, $user_id);
        if (!$current_product) {
            return ['success' => false, 'message' => 'Product not found or access denied.'];
        }
        
        // Check if new title already exists for this user in this category and brand (excluding current product)
        if ($title !== $current_product['product_name'] && 
            $this->product_title_exists($title, $current_product['cat_id'], $current_product['brand_id'], $user_id, $product_id)) {
            return ['success' => false, 'message' => 'Product title already exists in this category and brand combination.'];
        }
        
        // Validate optional prices
        if ($compare_price !== null && (!is_numeric($compare_price) || $compare_price < 0)) {
            return ['success' => false, 'message' => 'Compare price must be a valid positive number.'];
        }
        
        if ($cost_price !== null && (!is_numeric($cost_price) || $cost_price < 0)) {
            return ['success' => false, 'message' => 'Cost price must be a valid positive number.'];
        }
        
        // Validate stock quantity
        if ($stock_quantity !== null && (!is_numeric($stock_quantity) || $stock_quantity < 0)) {
            return ['success' => false, 'message' => 'Stock quantity must be a valid positive number.'];
        }
        
        // Validate weight
        if ($weight !== null && (!is_numeric($weight) || $weight < 0)) {
            return ['success' => false, 'message' => 'Weight must be a valid positive number.'];
        }
        
        $sql = "UPDATE products 
                SET product_name = ?, product_description = ?, product_short_desc = ?, 
                    sku = ?, price = ?, compare_price = ?, cost_price = ?, 
                    stock_quantity = ?, weight = ?, dimensions = ?, 
                    meta_title = ?, meta_description = ?, meta_keywords = ?, 
                    updated_at = NOW() 
                WHERE product_id = ?";
        
        $params = [
            $title, $desc, $desc, // Using description for both long and short desc
            $sku, $price, $compare_price, $cost_price,
            $stock_quantity, $weight, $dimensions,
            $meta_title, $meta_description, $keyword,
            $product_id
        ];
        
        // Add user_id to WHERE clause if provided for security
        if ($user_id !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $this->execute($sql, $params);
        
        if ($stmt && $stmt->rowCount() > 0) {
            // Update main image if provided
            if (!empty($image_path)) {
                $this->update_primary_image($product_id, $image_path);
            }
            
            return ['success' => true, 'message' => 'Product updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update product or no changes made.'];
        }
    }

    /**
     * Get all products for a specific user.
     *
     * @param int $user_id The user ID.
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of products or empty array if none found.
     */
    public function get_products_by_user($user_id, $limit = 50, $offset = 0) {
        $sql = "SELECT p.product_id, p.product_name, p.product_description, p.product_short_desc, 
                       p.sku, p.price, p.compare_price, p.cost_price, p.stock_quantity, 
                       p.weight, p.dimensions, p.is_active, p.is_featured, 
                       p.meta_title, p.meta_description, p.meta_keywords,
                       p.created_at, p.updated_at,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.cat_id = c.cat_id
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                WHERE p.user_id = ? 
                ORDER BY p.product_name ASC 
                LIMIT ? OFFSET ?";
        
        return $this->fetchAll($sql, [$user_id, $limit, $offset]);
    }

    /**
     * Get products by category for a specific user.
     *
     * @param int $user_id The user ID.
     * @param int $cat_id The category ID.
     * @return array Array of products in the specified category.
     */
    public function get_products_by_category($user_id, $cat_id) {
        $sql = "SELECT p.product_id, p.product_name, p.product_description, p.product_short_desc, 
                       p.sku, p.price, p.compare_price, p.cost_price, p.stock_quantity, 
                       p.weight, p.dimensions, p.is_active, p.is_featured,
                       p.created_at, p.updated_at,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.cat_id = c.cat_id
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                WHERE p.user_id = ? AND p.cat_id = ? AND p.is_active = 1
                ORDER BY p.product_name ASC";
        
        return $this->fetchAll($sql, [$user_id, $cat_id]);
    }

    /**
     * Get products by brand for a specific user.
     *
     * @param int $user_id The user ID.
     * @param int $brand_id The brand ID.
     * @return array Array of products for the specified brand.
     */
    public function get_products_by_brand($user_id, $brand_id) {
        $sql = "SELECT p.product_id, p.product_name, p.product_description, p.product_short_desc, 
                       p.sku, p.price, p.compare_price, p.cost_price, p.stock_quantity, 
                       p.weight, p.dimensions, p.is_active, p.is_featured,
                       p.created_at, p.updated_at,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.cat_id = c.cat_id
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                WHERE p.user_id = ? AND p.brand_id = ? AND p.is_active = 1
                ORDER BY p.product_name ASC";
        
        return $this->fetchAll($sql, [$user_id, $brand_id]);
    }

    /**
     * Get a single product by ID and user ID.
     *
     * @param int $product_id The product ID.
     * @param int $user_id The user ID (for security).
     * @return array|false Product data if found, false otherwise.
     */
    public function get_product_by_id($product_id, $user_id) {
        $sql = "SELECT p.product_id, p.product_name, p.product_description, p.product_short_desc, 
                       p.cat_id, p.brand_id, p.user_id, p.sku, p.price, p.compare_price, p.cost_price, 
                       p.stock_quantity, p.min_stock_level, p.weight, p.dimensions, 
                       p.is_active, p.is_featured, p.meta_title, p.meta_description, p.meta_keywords,
                       p.created_at, p.updated_at,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.cat_id = c.cat_id
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                WHERE p.product_id = ? AND p.user_id = ?";
        
        return $this->fetchRow($sql, [$product_id, $user_id]);
    }

    /**
     * Add a product image to the database.
     *
     * @param int $product_id Product ID.
     * @param string $file_path Image file path/URL.
     * @param bool $is_primary Whether this is the primary image.
     * @param string $image_alt Optional alt text for the image.
     * @param string $image_title Optional title for the image.
     * @param int $sort_order Optional sort order.
     * @return array Result array with success status and message.
     */
    public function add_product_image($product_id, $file_path, $is_primary = false, $image_alt = null, $image_title = null, $sort_order = 0) {
        // Validate input
        if (empty($product_id) || empty($file_path)) {
            return ['success' => false, 'message' => 'Product ID and file path are required.'];
        }
        
        // Verify product exists
        $product = $this->get_product_by_id($product_id, null);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }
        
        // If this is set as primary, unset other primary images
        if ($is_primary) {
            $this->unset_primary_images($product_id);
        }
        
        $sql = "INSERT INTO product_images (product_id, image_url, image_alt, image_title, sort_order, is_primary, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->execute($sql, [$product_id, $file_path, $image_alt, $image_title, $sort_order, $is_primary ? 1 : 0]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $image_id = $this->lastInsertId();
            return ['success' => true, 'message' => 'Product image added successfully.', 'image_id' => $image_id];
        } else {
            return ['success' => false, 'message' => 'Failed to add product image.'];
        }
    }

    /**
     * Get all images for a product.
     *
     * @param int $product_id Product ID.
     * @return array Array of product images.
     */
    public function get_product_images($product_id) {
        $sql = "SELECT image_id, image_url, image_alt, image_title, sort_order, is_primary, created_at 
                FROM product_images 
                WHERE product_id = ? 
                ORDER BY is_primary DESC, sort_order ASC, created_at ASC";
        
        return $this->fetchAll($sql, [$product_id]);
    }

    /**
     * Delete a product.
     *
     * @param int $product_id The product ID.
     * @param int $user_id The user ID (for security).
     * @return array Result array with success status and message.
     */
    public function delete_product($product_id, $user_id) {
        // Validate input
        if (empty($product_id) || empty($user_id)) {
            return ['success' => false, 'message' => 'Product ID and user ID are required.'];
        }
        
        // Check if product exists and belongs to user
        $product = $this->get_product_by_id($product_id, $user_id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found or access denied.'];
        }
        
        $sql = "DELETE FROM products WHERE product_id = ? AND user_id = ?";
        
        $stmt = $this->execute($sql, [$product_id, $user_id]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Product deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete product.'];
        }
    }

    /**
     * Check if a product title already exists for a specific user in a specific category and brand.
     *
     * @param string $title The product title to check.
     * @param int $cat_id The category ID.
     * @param int $brand_id The brand ID.
     * @param int $user_id The user ID.
     * @param int $exclude_id Optional product ID to exclude from check (for updates).
     * @return bool True if product title exists, false otherwise.
     */
    public function product_title_exists($title, $cat_id, $brand_id, $user_id, $exclude_id = null) {
        $sql = "SELECT product_id FROM products WHERE product_name = ? AND cat_id = ? AND brand_id = ? AND user_id = ?";
        $params = [$title, $cat_id, $brand_id, $user_id];
        
        if ($exclude_id) {
            $sql .= " AND product_id != ?";
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
     * Check if brand belongs to user and category.
     *
     * @param int $brand_id The brand ID.
     * @param int $user_id The user ID.
     * @param int $cat_id The category ID.
     * @return bool True if brand belongs to user and category, false otherwise.
     */
    private function brand_belongs_to_user_and_category($brand_id, $user_id, $cat_id) {
        $sql = "SELECT brand_id FROM brands WHERE brand_id = ? AND user_id = ? AND cat_id = ?";
        $result = $this->fetchRow($sql, [$brand_id, $user_id, $cat_id]);
        return $result !== false;
    }

    /**
     * Unset all primary images for a product.
     *
     * @param int $product_id Product ID.
     * @return bool True on success, false on failure.
     */
    private function unset_primary_images($product_id) {
        $sql = "UPDATE product_images SET is_primary = 0 WHERE product_id = ?";
        $stmt = $this->execute($sql, [$product_id]);
        return $stmt !== false;
    }

    /**
     * Update the primary image for a product.
     *
     * @param int $product_id Product ID.
     * @param string $image_path New primary image path.
     * @return bool True on success, false on failure.
     */
    private function update_primary_image($product_id, $image_path) {
        // First, unset all primary images
        $this->unset_primary_images($product_id);
        
        // Then add the new primary image
        $result = $this->add_product_image($product_id, $image_path, true);
        return $result['success'];
    }

    /**
     * Get total count of products for a user.
     *
     * @param int $user_id The user ID.
     * @return int Total number of products.
     */
    public function count_products_by_user($user_id) {
        $sql = "SELECT COUNT(*) as total FROM products WHERE user_id = ?";
        $result = $this->fetchRow($sql, [$user_id]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Search products by name for a specific user.
     *
     * @param string $search_term The search term.
     * @param int $user_id The user ID.
     * @param int $limit Optional limit for results.
     * @return array Array of matching products.
     */
    public function search_products($search_term, $user_id, $limit = 20) {
        $sql = "SELECT p.product_id, p.product_name, p.product_description, p.product_short_desc, 
                       p.sku, p.price, p.compare_price, p.cost_price, p.stock_quantity, 
                       p.is_active, p.is_featured, p.created_at, p.updated_at,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.cat_id = c.cat_id
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                WHERE p.product_name LIKE ? AND p.user_id = ? 
                ORDER BY p.product_name ASC 
                LIMIT ?";
        
        $search_param = '%' . $search_term . '%';
        return $this->fetchAll($sql, [$search_param, $user_id, $limit]);
    }

    /**
     * Toggle product active status.
     *
     * @param int $product_id The product ID.
     * @param int $user_id The user ID (for security).
     * @param int $is_active 1 for active, 0 for inactive.
     * @return array Result array with success status and message.
     */
    public function toggle_product_status($product_id, $user_id, $is_active) {
        $sql = "UPDATE products 
                SET is_active = ?, updated_at = NOW() 
                WHERE product_id = ? AND user_id = ?";
        
        $stmt = $this->execute($sql, [$is_active, $product_id, $user_id]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $status_text = $is_active ? 'activated' : 'deactivated';
            return ['success' => true, 'message' => "Product {$status_text} successfully."];
        } else {
            return ['success' => false, 'message' => 'Failed to update product status.'];
        }
    }

    /**
     * Toggle product featured status.
     *
     * @param int $product_id The product ID.
     * @param int $user_id The user ID (for security).
     * @param int $is_featured 1 for featured, 0 for not featured.
     * @return array Result array with success status and message.
     */
    public function toggle_product_featured($product_id, $user_id, $is_featured) {
        $sql = "UPDATE products 
                SET is_featured = ?, updated_at = NOW() 
                WHERE product_id = ? AND user_id = ?";
        
        $stmt = $this->execute($sql, [$is_featured, $product_id, $user_id]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $status_text = $is_featured ? 'featured' : 'unfeatured';
            return ['success' => true, 'message' => "Product {$status_text} successfully."];
        } else {
            return ['success' => false, 'message' => 'Failed to update product featured status.'];
        }
    }
}
?>