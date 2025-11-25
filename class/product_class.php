<?php
/**
 * Product Class
 * 
 * Handles all product-related database operations including CRUD operations.
 * Products belong to categories and brands.
 */

class product_class extends db_class {
    
    /**
     * Add a new product to the database.
     *
     * @param int $cat_id Category ID the product belongs to.
     * @param int $brand_id Brand ID the product belongs to.
     * @param string $title Product title/name.
     * @param float $price Product price.
     * @param string $desc Product description.
     * @param string $keyword Product keywords.
     * @param string $image_path Optional product image path.
     * @return array Result array with success status, message, and product ID.
     */
    public function add_product($cat_id, $brand_id, $title, $price, $desc, $keyword, $image_path = null) {
        // Validate input
        if (empty($title) || empty($cat_id) || empty($brand_id) || $price === null || $price === '') {
            return ['success' => false, 'message' => 'Title, category ID, brand ID, and price are required.'];
        }
        
        // Validate price
        if (!is_numeric($price) || $price < 0) {
            return ['success' => false, 'message' => 'Price must be a valid positive number.'];
        }
        
        // Check if product title already exists in this category and brand
        if ($this->product_title_exists($title, $cat_id, $brand_id)) {
            return ['success' => false, 'message' => 'Product title already exists in this category and brand combination.'];
        }
        
        $sql = "INSERT INTO products (
                    product_cat, product_brand, product_title, product_price, 
                    product_desc, product_image, product_keywords
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->execute($sql, [
            $cat_id, $brand_id, $title, $price, $desc, $image_path, $keyword
        ]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $product_id = $this->lastInsertId();
            return ['success' => true, 'message' => 'Product added successfully.', 'product_id' => $product_id];
        } else {
            return ['success' => false, 'message' => 'Failed to add product.'];
        }
    }

    /**
     * Update a product's information.
     *
     * @param int $product_id Product ID.
     * @param int $cat_id Category ID the product belongs to.
     * @param int $brand_id Brand ID the product belongs to.
     * @param string $title New product title.
     * @param float $price New product price.
     * @param string $desc New product description.
     * @param string $keyword New product keywords.
     * @param string $image_path Optional new product image path.
     * @return array Result array with success status and message.
     */
    public function update_product($product_id, $cat_id, $brand_id, $title, $price, $desc, $keyword, $image_path = null) {
        // Validate input
        if (empty($product_id) || empty($title) || empty($cat_id) || empty($brand_id) || $price === null || $price === '') {
            return ['success' => false, 'message' => 'Product ID, title, category ID, brand ID, and price are required.'];
        }
        
        // Validate price
        if (!is_numeric($price) || $price < 0) {
            return ['success' => false, 'message' => 'Price must be a valid positive number.'];
        }
        
        // Get current product data
        $current_product = $this->get_product_by_id($product_id);
        if (!$current_product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }
        
        // Check if new title already exists in this category and brand (excluding current product)
        if ($title !== $current_product['product_title'] && 
            $this->product_title_exists($title, $cat_id, $brand_id, $product_id)) {
            return ['success' => false, 'message' => 'Product title already exists in this category and brand combination.'];
        }
        
        $sql = "UPDATE products 
                SET product_cat = ?, product_brand = ?, product_title = ?, 
                    product_price = ?, product_desc = ?, product_image = ?, product_keywords = ?
                WHERE product_id = ?";
        
        $params = [
            $cat_id, $brand_id, $title, $price, $desc, $image_path, $keyword, $product_id
        ];
        
        $stmt = $this->execute($sql, $params);
        
        if ($stmt && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Product updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update product or no changes made.'];
        }
    }

    /**
     * Get all products.
     *
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of products or empty array if none found.
     */
    public function get_all_products($limit = 50, $offset = 0) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                ORDER BY p.product_title ASC 
                LIMIT ? OFFSET ?";
        
        // Cast limit and offset to integers and use explicit binding
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        try {
            // Ensure database connection is established
            $conn = $this->getConnection();
            if ($conn === null) {
                error_log("get_all_products error: Database connection is null");
                return [];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("get_all_products error: " . $e->getMessage());
            return $this->fetchAll($sql, [$limit, $offset]);
        }
    }

    /**
     * Get products by category.
     *
     * @param int $cat_id The category ID.
     * @return array Array of products in the specified category.
     */
    public function get_products_by_category($cat_id) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = ?
                ORDER BY p.product_title ASC";
        
        return $this->fetchAll($sql, [$cat_id]);
    }

    /**
     * Get products by brand.
     *
     * @param int $brand_id The brand ID.
     * @return array Array of products for the specified brand.
     */
    public function get_products_by_brand($brand_id) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = ?
                ORDER BY p.product_title ASC";
        
        return $this->fetchAll($sql, [$brand_id]);
    }

    /**
     * Get a single product by ID.
     *
     * @param int $product_id The product ID.
     * @return array|false Product data if found, false otherwise.
     */
    public function get_product_by_id($product_id) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_id = ?";
        
        return $this->fetchRow($sql, [$product_id]);
    }

    /**
     * Delete a product.
     *
     * @param int $product_id The product ID.
     * @return array Result array with success status and message.
     */
    public function delete_product($product_id) {
        // Validate input
        if (empty($product_id)) {
            return ['success' => false, 'message' => 'Product ID is required.'];
        }
        
        // Check if product exists
        $product = $this->get_product_by_id($product_id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }
        
        $sql = "DELETE FROM products WHERE product_id = ?";
        
        $stmt = $this->execute($sql, [$product_id]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Product deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete product.'];
        }
    }

    /**
     * Check if a product title already exists in a specific category and brand.
     *
     * @param string $title The product title to check.
     * @param int $cat_id The category ID.
     * @param int $brand_id The brand ID.
     * @param int $exclude_id Optional product ID to exclude from check (for updates).
     * @return bool True if product title exists, false otherwise.
     */
    public function product_title_exists($title, $cat_id, $brand_id, $exclude_id = null) {
        $sql = "SELECT product_id FROM products WHERE product_title = ? AND product_cat = ? AND product_brand = ?";
        $params = [$title, $cat_id, $brand_id];
        
        if ($exclude_id) {
            $sql .= " AND product_id != ?";
            $params[] = $exclude_id;
        }
        
        $result = $this->fetchRow($sql, $params);
        return $result !== false;
    }

    /**
     * Get total count of products.
     *
     * @return int Total number of products.
     */
    public function count_all_products() {
        try {
            // Ensure database connection is established
            $conn = $this->getConnection();
            if ($conn === null) {
                error_log("count_all_products error: Database connection is null");
                return 0;
            }
            
            $sql = "SELECT COUNT(*) as total FROM products";
            $result = $this->fetchRow($sql);
            
            if ($result === false) {
                error_log("count_all_products error: fetchRow returned false");
                return 0;
            }
            
            $count = isset($result['total']) ? (int)$result['total'] : 0;
            error_log("count_all_products - Successfully counted " . $count . " products");
            return $count;
            
        } catch (PDOException $e) {
            error_log("count_all_products PDO error: " . $e->getMessage());
            error_log("count_all_products PDO trace: " . $e->getTraceAsString());
            return 0;
        } catch (Exception $e) {
            error_log("count_all_products error: " . $e->getMessage());
            error_log("count_all_products trace: " . $e->getTraceAsString());
            return 0;
        }
    }
    
    /**
     * Search products by title, description, and keywords.
     *
     * @param string $search_term The search term.
     * @param int $limit Optional limit for results.
     * @param int $offset Optional offset for pagination.
     * @return array Array of matching products.
     */
    public function search_products($search_term, $limit = 20, $offset = 0) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_title LIKE ? 
                   OR p.product_desc LIKE ? 
                   OR p.product_keywords LIKE ?
                ORDER BY p.product_title ASC 
                LIMIT ? OFFSET ?";
        
        $search_param = '%' . $search_term . '%';
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        try {
            $conn = $this->getConnection();
            if ($conn === null) {
                error_log("search_products error: Database connection is null");
                return [];
            }
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $search_param, PDO::PARAM_STR);
            $stmt->bindValue(2, $search_param, PDO::PARAM_STR);
            $stmt->bindValue(3, $search_param, PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
            $stmt->bindValue(5, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("search_products error: " . $e->getMessage());
            return $this->fetchAll($sql, [$search_param, $search_param, $search_param, $limit, $offset]);
        }
    }
    
    /**
     * Filter products by category with pagination.
     *
     * @param int $cat_id The category ID.
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of products in the specified category.
     */
    public function filter_products_by_category($cat_id, $limit = 20, $offset = 0) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = ?
                ORDER BY p.product_title ASC 
                LIMIT ? OFFSET ?";
        
        $cat_id = (int)$cat_id;
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        try {
            $conn = $this->getConnection();
            if ($conn === null) {
                error_log("filter_products_by_category error: Database connection is null");
                return [];
            }
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $cat_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("filter_products_by_category error: " . $e->getMessage());
            return $this->fetchAll($sql, [$cat_id, $limit, $offset]);
        }
    }
    
    /**
     * Filter products by brand with pagination.
     *
     * @param int $brand_id The brand ID.
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of products for the specified brand.
     */
    public function filter_products_by_brand($brand_id, $limit = 20, $offset = 0) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = ?
                ORDER BY p.product_title ASC 
                LIMIT ? OFFSET ?";
        
        $brand_id = (int)$brand_id;
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        try {
            $conn = $this->getConnection();
            if ($conn === null) {
                error_log("filter_products_by_brand error: Database connection is null");
                return [];
            }
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $brand_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("filter_products_by_brand error: " . $e->getMessage());
            return $this->fetchAll($sql, [$brand_id, $limit, $offset]);
        }
    }
    
    /**
     * Advanced composite search with filters.
     * Supports searching by query, category, brand, and max price.
     *
     * @param array $filters Array containing:
     *   - query (string, optional): Search term for title, description, keywords
     *   - cat_id (int, optional): Filter by category ID
     *   - brand_id (int, optional): Filter by brand ID
     *   - max_price (float, optional): Maximum price filter
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of matching products.
     */
    public function composite_search($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT p.product_id, p.product_cat, p.product_brand, p.product_title, 
                       p.product_price, p.product_desc, p.product_image, p.product_keywords,
                       c.cat_name, b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE 1=1";
        
        $params = [];
        
        // Search query filter
        if (!empty($filters['query'])) {
            $sql .= " AND (p.product_title LIKE ? OR p.product_desc LIKE ? OR p.product_keywords LIKE ?)";
            $search_param = '%' . $filters['query'] . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Category filter
        if (!empty($filters['cat_id'])) {
            $sql .= " AND p.product_cat = ?";
            $params[] = (int)$filters['cat_id'];
        }
        
        // Brand filter
        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.product_brand = ?";
            $params[] = (int)$filters['brand_id'];
        }
        
        // Price filter
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND p.product_price <= ?";
            $params[] = (float)$filters['max_price'];
        }
        
        $sql .= " ORDER BY p.product_title ASC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        
        try {
            $conn = $this->getConnection();
            if ($conn === null) {
                error_log("composite_search error: Database connection is null");
                return [];
            }
            $stmt = $conn->prepare($sql);
            foreach ($params as $index => $param) {
                $param_type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($index + 1, $param, $param_type);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("composite_search error: " . $e->getMessage());
            return $this->fetchAll($sql, $params);
        }
    }
    
    /**
     * Get total count of products matching filters.
     *
     * @param array $filters Array containing filters (same as composite_search).
     * @return int Total number of matching products.
     */
    public function count_filtered_products($filters = []) {
        $sql = "SELECT COUNT(*) as total
                FROM products p
                WHERE 1=1";
        
        $params = [];
        
        // Search query filter
        if (!empty($filters['query'])) {
            $sql .= " AND (p.product_title LIKE ? OR p.product_desc LIKE ? OR p.product_keywords LIKE ?)";
            $search_param = '%' . $filters['query'] . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Category filter
        if (!empty($filters['cat_id'])) {
            $sql .= " AND p.product_cat = ?";
            $params[] = (int)$filters['cat_id'];
        }
        
        // Brand filter
        if (!empty($filters['brand_id'])) {
            $sql .= " AND p.product_brand = ?";
            $params[] = (int)$filters['brand_id'];
        }
        
        // Price filter
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND p.product_price <= ?";
            $params[] = (float)$filters['max_price'];
        }
        
        try {
            $result = $this->fetchRow($sql, $params);
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            error_log("count_filtered_products error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * View all products (alias for get_all_products for customer-facing API).
     *
     * @param int $limit Optional limit for pagination.
     * @param int $offset Optional offset for pagination.
     * @return array Array of products.
     */
    public function view_all_products($limit = 10, $offset = 0) {
        return $this->get_all_products($limit, $offset);
    }
    
    /**
     * View single product (alias for get_product_by_id for customer-facing API).
     *
     * @param int $product_id The product ID.
     * @return array|false Product data if found, false otherwise.
     */
    public function view_single_product($product_id) {
        return $this->get_product_by_id($product_id);
    }
    
    /**
     * Add a product image to the product_images table.
     *
     * @param int $product_id The product ID.
     * @param string $image_url The image file path/URL.
     * @param bool $is_primary Whether this is the primary image.
     * @param string|null $image_alt Optional alt text for the image.
     * @param string|null $image_title Optional title for the image.
     * @param int $sort_order Sort order for displaying images.
     * @return array Result array with success status and message.
     */
    public function add_product_image($product_id, $image_url, $is_primary = false, $image_alt = null, $image_title = null, $sort_order = 0) {
        try {
            // Validate input
            if (empty($product_id) || !is_numeric($product_id)) {
                error_log("add_product_image: Invalid product ID: " . var_export($product_id, true));
                return ['success' => false, 'message' => 'Invalid product ID.'];
            }
            
            if (empty($image_url)) {
                error_log("add_product_image: Empty image URL for product ID: $product_id");
                return ['success' => false, 'message' => 'Image URL is required.'];
            }
            
            // Check if product exists
            $product = $this->get_product_by_id($product_id);
            if (!$product) {
                error_log("add_product_image: Product not found. ID: $product_id");
                return ['success' => false, 'message' => 'Product not found.'];
            }
            
            // If this is set as primary, unset other primary images
            if ($is_primary) {
                $sql_unset = "UPDATE product_images SET is_primary = 0 WHERE product_id = ?";
                $unset_result = $this->execute($sql_unset, [$product_id]);
                if ($unset_result === false) {
                    error_log("add_product_image: Failed to unset other primary images for product ID: $product_id");
                }
            }
            
            // Insert the new image
            $sql = "INSERT INTO product_images (product_id, image_url, image_alt, image_title, sort_order, is_primary)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $params = [
                (int)$product_id,
                $image_url,
                $image_alt,
                $image_title,
                (int)$sort_order,
                $is_primary ? 1 : 0
            ];
            
            error_log("add_product_image: Attempting to insert image. Product ID: $product_id, Image URL: $image_url, Is Primary: " . ($is_primary ? 'yes' : 'no'));
            error_log("add_product_image: SQL: $sql");
            error_log("add_product_image: Params: " . json_encode($params));
            
            // Use direct PDO connection to get better error information
            $conn = $this->getConnection();
            if (!$conn) {
                error_log("add_product_image: ERROR - No database connection available");
                return ['success' => false, 'message' => 'Database connection error.'];
            }
            
            try {
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    $error_info = $conn->errorInfo();
                    error_log("add_product_image: Prepare failed. Error: " . json_encode($error_info));
                    return ['success' => false, 'message' => 'Database prepare error: ' . ($error_info[2] ?? 'Unknown error')];
                }
                
                $execute_result = $stmt->execute($params);
                
                if (!$execute_result) {
                    $error_info = $stmt->errorInfo();
                    error_log("add_product_image: Execute failed. Error: " . json_encode($error_info));
                    $error_message = $error_info[2] ?? 'Unknown database error';
                    
                    // Check for common issues
                    if (strpos($error_message, 'foreign key') !== false || strpos($error_message, 'Cannot add or update') !== false) {
                        return ['success' => false, 'message' => 'Product not found or invalid product ID.'];
                    } elseif (strpos($error_message, 'Duplicate entry') !== false) {
                        return ['success' => false, 'message' => 'Image already exists for this product.'];
                    } else {
                        return ['success' => false, 'message' => 'Database error: ' . $error_message];
                    }
                }
                
                // Check if insert was successful using lastInsertId
                $image_id = $conn->lastInsertId();
                $row_count = $stmt->rowCount();
                
                error_log("add_product_image: Insert result - rowCount: $row_count, lastInsertId: $image_id");
                
                if ($image_id > 0) {
                    // Insert was successful
                    error_log("add_product_image: Image inserted successfully. Image ID: $image_id");
                    
                    // Also update the main product_image field if this is the primary image
                    if ($is_primary) {
                        $update_sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
                        $update_result = $this->execute($update_sql, [$image_url, $product_id]);
                        if ($update_result === false) {
                            error_log("add_product_image: Warning - Failed to update main product_image field, but image was inserted.");
                        }
                    }
                    
                    return ['success' => true, 'message' => 'Product image added successfully.', 'image_id' => $image_id];
                } else {
                    // Insert failed - no ID returned
                    error_log("add_product_image: Insert failed - lastInsertId is 0, rowCount: $row_count");
                    error_log("add_product_image: This usually means the INSERT didn't execute or product_id doesn't exist");
                    
                    // Verify product exists
                    $product_check = $this->get_product_by_id($product_id);
                    if (!$product_check) {
                        error_log("add_product_image: Product ID $product_id does not exist in database");
                        return ['success' => false, 'message' => 'Product not found. Product ID: ' . $product_id];
                    }
                    
                    return ['success' => false, 'message' => 'Failed to add product image. Insert executed but no ID returned. Check database constraints.'];
                }
                
            } catch (PDOException $e) {
                error_log("add_product_image: PDOException caught: " . $e->getMessage());
                error_log("add_product_image: Exception code: " . $e->getCode());
                error_log("add_product_image: Exception trace: " . $e->getTraceAsString());
                
                $error_message = $e->getMessage();
                if (strpos($error_message, 'foreign key') !== false) {
                    return ['success' => false, 'message' => 'Product not found or invalid product ID.'];
                } elseif (strpos($error_message, 'Duplicate entry') !== false) {
                    return ['success' => false, 'message' => 'Image already exists for this product.'];
                } else {
                    return ['success' => false, 'message' => 'Database error: ' . $error_message];
                }
            }
            
        } catch (Exception $e) {
            error_log("add_product_image exception: " . $e->getMessage());
            error_log("add_product_image exception trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'An error occurred while adding the product image: ' . $e->getMessage()];
        } catch (Throwable $e) {
            error_log("add_product_image throwable: " . $e->getMessage());
            error_log("add_product_image throwable trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'An error occurred while adding the product image: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all images for a product.
     *
     * @param int $product_id The product ID.
     * @return array|false Array of product images or false on error.
     */
    public function get_product_images($product_id) {
        try {
            if (empty($product_id) || !is_numeric($product_id)) {
                return false;
            }
            
            $sql = "SELECT image_id, product_id, image_url, image_alt, image_title, sort_order, is_primary, 
                           file_size, mime_type, created_at, updated_at
                    FROM product_images
                    WHERE product_id = ?
                    ORDER BY is_primary DESC, sort_order ASC, created_at ASC";
            
            return $this->fetchAll($sql, [$product_id]);
            
        } catch (Exception $e) {
            error_log("get_product_images error: " . $e->getMessage());
            return false;
        }
    }
}
?>
