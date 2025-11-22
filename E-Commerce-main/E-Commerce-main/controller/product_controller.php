<?php
/**
 * Product Controller
 * 
 * Handles all product-related operations including CRUD functionality.
 * Acts as an intermediary between the product class and the views/actions.
 */

// Include core settings and product class
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/product_class.php';

/**
 * Add a new product.
 *
 * @param array $data Product data array containing:
 *   - user_id (int): User ID who is creating the product
 *   - cat_id (int): Category ID the product belongs to
 *   - brand_id (int): Brand ID the product belongs to
 *   - title (string): Product title/name
 *   - price (float): Product price
 *   - desc (string): Product description
 *   - keyword (string): Product keywords for SEO
 *   - image_path (string, optional): Main product image path
 *   - sku (string, optional): Stock Keeping Unit
 *   - compare_price (float, optional): Compare price
 *   - cost_price (float, optional): Cost price
 *   - stock_quantity (int, optional): Stock quantity
 *   - weight (float, optional): Product weight
 *   - dimensions (string, optional): Product dimensions
 *   - meta_title (string, optional): SEO meta title
 *   - meta_description (string, optional): SEO meta description
 * @return string "success" or an error message.
 */
function add_product_ctr($data) {
    try {
        // Validate required fields
        $required_fields = ['user_id', 'cat_id', 'brand_id', 'title', 'price', 'desc', 'keyword'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }

        // Validate user ID
        if (!is_numeric($data['user_id'])) {
            return "Invalid user ID.";
        }

        // Validate category ID
        if (!is_numeric($data['cat_id'])) {
            return "Invalid category ID.";
        }

        // Validate brand ID
        if (!is_numeric($data['brand_id'])) {
            return "Invalid brand ID.";
        }

        // Validate price
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            return "Price must be a valid positive number.";
        }

        // Sanitize input
        $data['title'] = trim($data['title']);
        $data['desc'] = trim($data['desc']);
        $data['keyword'] = trim($data['keyword']);
        
        // Validate product title length
        if (strlen($data['title']) < 2) {
            return "Product title must be at least 2 characters long.";
        }
        
        if (strlen($data['title']) > 200) {
            return "Product title must not exceed 200 characters.";
        }

        // Validate product title format (letters, numbers, spaces, hyphens, underscores, periods)
        if (!preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $data['title'])) {
            return "Product title can only contain letters, numbers, spaces, hyphens, underscores, and periods.";
        }

        // Validate description length
        if (strlen($data['desc']) < 10) {
            return "Product description must be at least 10 characters long.";
        }
        
        if (strlen($data['desc']) > 2000) {
            return "Product description must not exceed 2000 characters.";
        }

        // Validate optional fields if provided
        if (isset($data['compare_price']) && $data['compare_price'] !== '' && (!is_numeric($data['compare_price']) || $data['compare_price'] < 0)) {
            return "Compare price must be a valid positive number.";
        }

        if (isset($data['cost_price']) && $data['cost_price'] !== '' && (!is_numeric($data['cost_price']) || $data['cost_price'] < 0)) {
            return "Cost price must be a valid positive number.";
        }

        if (isset($data['stock_quantity']) && $data['stock_quantity'] !== '' && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
            return "Stock quantity must be a valid positive number.";
        }

        if (isset($data['weight']) && $data['weight'] !== '' && (!is_numeric($data['weight']) || $data['weight'] < 0)) {
            return "Weight must be a valid positive number.";
        }

        // Validate meta fields if provided
        if (isset($data['meta_title']) && strlen($data['meta_title']) > 200) {
            return "Meta title must not exceed 200 characters.";
        }

        if (isset($data['meta_description']) && strlen($data['meta_description']) > 500) {
            return "Meta description must not exceed 500 characters.";
        }

        $product = new product_class();

        // Add the product
        $result = $product->add_product(
            $data['user_id'],
            $data['cat_id'],
            $data['brand_id'],
            $data['title'],
            $data['price'],
            $data['desc'],
            $data['keyword'],
            $data['image_path'] ?? null,
            $data['sku'] ?? null,
            $data['compare_price'] ?? null,
            $data['cost_price'] ?? null,
            $data['stock_quantity'] ?? 0,
            $data['weight'] ?? null,
            $data['dimensions'] ?? null,
            $data['meta_title'] ?? null,
            $data['meta_description'] ?? null
        );

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Add product error: " . $e->getMessage());
        return "An error occurred while adding the product.";
    }
}

/**
 * Update a product.
 *
 * @param array $data Product data array containing:
 *   - product_id (int): Product ID to update
 *   - title (string): New product title
 *   - price (float): New product price
 *   - desc (string): New product description
 *   - keyword (string): New product keywords
 *   - image_path (string, optional): New main product image path
 *   - user_id (int): User ID (for security)
 *   - sku (string, optional): SKU
 *   - compare_price (float, optional): Compare price
 *   - cost_price (float, optional): Cost price
 *   - stock_quantity (int, optional): Stock quantity
 *   - weight (float, optional): Weight
 *   - dimensions (string, optional): Dimensions
 *   - meta_title (string, optional): Meta title
 *   - meta_description (string, optional): Meta description
 * @return string "success" or an error message.
 */
function update_product_ctr($data) {
    try {
        // Validate required fields
        $required_fields = ['product_id', 'title', 'price', 'desc', 'keyword', 'user_id'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }

        // Validate product ID
        if (!is_numeric($data['product_id'])) {
            return "Invalid product ID.";
        }

        // Validate user ID
        if (!is_numeric($data['user_id'])) {
            return "Invalid user ID.";
        }

        // Validate price
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            return "Price must be a valid positive number.";
        }

        // Sanitize input
        $data['title'] = trim($data['title']);
        $data['desc'] = trim($data['desc']);
        $data['keyword'] = trim($data['keyword']);
        
        // Validate product title length
        if (strlen($data['title']) < 2) {
            return "Product title must be at least 2 characters long.";
        }
        
        if (strlen($data['title']) > 200) {
            return "Product title must not exceed 200 characters.";
        }

        // Validate product title format
        if (!preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $data['title'])) {
            return "Product title can only contain letters, numbers, spaces, hyphens, underscores, and periods.";
        }

        // Validate description length
        if (strlen($data['desc']) < 10) {
            return "Product description must be at least 10 characters long.";
        }
        
        if (strlen($data['desc']) > 2000) {
            return "Product description must not exceed 2000 characters.";
        }

        // Validate optional fields if provided
        if (isset($data['compare_price']) && $data['compare_price'] !== '' && (!is_numeric($data['compare_price']) || $data['compare_price'] < 0)) {
            return "Compare price must be a valid positive number.";
        }

        if (isset($data['cost_price']) && $data['cost_price'] !== '' && (!is_numeric($data['cost_price']) || $data['cost_price'] < 0)) {
            return "Cost price must be a valid positive number.";
        }

        if (isset($data['stock_quantity']) && $data['stock_quantity'] !== '' && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
            return "Stock quantity must be a valid positive number.";
        }

        if (isset($data['weight']) && $data['weight'] !== '' && (!is_numeric($data['weight']) || $data['weight'] < 0)) {
            return "Weight must be a valid positive number.";
        }

        // Validate meta fields if provided
        if (isset($data['meta_title']) && strlen($data['meta_title']) > 200) {
            return "Meta title must not exceed 200 characters.";
        }

        if (isset($data['meta_description']) && strlen($data['meta_description']) > 500) {
            return "Meta description must not exceed 500 characters.";
        }

        $product = new product_class();

        // Update the product
        $result = $product->update_product(
            $data['product_id'],
            $data['title'],
            $data['price'],
            $data['desc'],
            $data['keyword'],
            $data['image_path'] ?? null,
            $data['user_id'],
            $data['sku'] ?? null,
            $data['compare_price'] ?? null,
            $data['cost_price'] ?? null,
            $data['stock_quantity'] ?? null,
            $data['weight'] ?? null,
            $data['dimensions'] ?? null,
            $data['meta_title'] ?? null,
            $data['meta_description'] ?? null
        );

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        return "An error occurred while updating the product.";
    }
}

/**
 * Get all products for a specific user.
 *
 * @param int $user_id User ID.
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array|string Array of products on success, error message on failure.
 */
function fetch_products_ctr($user_id, $limit = 50, $offset = 0) {
    try {
        // Validate input
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $product = new product_class();
        return $product->get_products_by_user($user_id, $limit, $offset);
    } catch (Exception $e) {
        error_log("Fetch products error: " . $e->getMessage());
        return "An error occurred while fetching products.";
    }
}

/**
 * Get a single product by ID.
 *
 * @param int $product_id Product ID.
 * @param int $user_id User ID (for security).
 * @return array|string Product data on success, error message on failure.
 */
function get_product_ctr($product_id, $user_id) {
    try {
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $product = new product_class();
        $result = $product->get_product_by_id($product_id, $user_id);
        
        return $result ? $result : "Product not found.";
    } catch (Exception $e) {
        error_log("Get product error: " . $e->getMessage());
        return "An error occurred while fetching the product.";
    }
}

/**
 * Delete a product.
 *
 * @param int $product_id Product ID.
 * @param int $user_id User ID (for security).
 * @return string "success" or an error message.
 */
function delete_product_ctr($product_id, $user_id) {
    try {
        // Validate input
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $product = new product_class();

        // Delete the product
        $result = $product->delete_product($product_id, $user_id);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        return "An error occurred while deleting the product.";
    }
}

/**
 * Get products by category for a specific user.
 *
 * @param int $user_id User ID.
 * @param int $cat_id Category ID.
 * @return array|string Array of products on success, error message on failure.
 */
function get_products_by_category_ctr($user_id, $cat_id) {
    try {
        // Validate input
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (empty($cat_id) || !is_numeric($cat_id)) {
            return "Invalid category ID.";
        }

        $product = new product_class();
        return $product->get_products_by_category($user_id, $cat_id);
    } catch (Exception $e) {
        error_log("Get products by category error: " . $e->getMessage());
        return "An error occurred while fetching products for the category.";
    }
}

/**
 * Get products by brand for a specific user.
 *
 * @param int $user_id User ID.
 * @param int $brand_id Brand ID.
 * @return array|string Array of products on success, error message on failure.
 */
function get_products_by_brand_ctr($user_id, $brand_id) {
    try {
        // Validate input
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (empty($brand_id) || !is_numeric($brand_id)) {
            return "Invalid brand ID.";
        }

        $product = new product_class();
        return $product->get_products_by_brand($user_id, $brand_id);
    } catch (Exception $e) {
        error_log("Get products by brand error: " . $e->getMessage());
        return "An error occurred while fetching products for the brand.";
    }
}

/**
 * Search products by name for a specific user.
 *
 * @param string $search_term Search term.
 * @param int $user_id User ID.
 * @param int $limit Optional limit for results.
 * @return array|string Array of products on success, error message on failure.
 */
function search_products_ctr($search_term, $user_id, $limit = 20) {
    try {
        if (empty($search_term)) {
            return fetch_products_ctr($user_id, $limit);
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $product = new product_class();
        return $product->search_products($search_term, $user_id, $limit);
    } catch (Exception $e) {
        error_log("Search products error: " . $e->getMessage());
        return "An error occurred while searching products.";
    }
}

/**
 * Get product count for a user.
 *
 * @param int $user_id User ID.
 * @return int|string Product count on success, error message on failure.
 */
function get_product_count_ctr($user_id) {
    try {
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $product = new product_class();
        return $product->count_products_by_user($user_id);
    } catch (Exception $e) {
        error_log("Get product count error: " . $e->getMessage());
        return "An error occurred while counting products.";
    }
}

/**
 * Toggle product active status.
 *
 * @param int $product_id Product ID.
 * @param int $user_id User ID (for security).
 * @param int $is_active 1 for active, 0 for inactive.
 * @return string "success" or an error message.
 */
function toggle_product_status_ctr($product_id, $user_id, $is_active) {
    try {
        // Validate input
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (!in_array($is_active, [0, 1])) {
            return "Invalid status value. Must be 0 or 1.";
        }

        $product = new product_class();

        // Toggle product status
        $result = $product->toggle_product_status($product_id, $user_id, $is_active);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Toggle product status error: " . $e->getMessage());
        return "An error occurred while updating product status.";
    }
}

/**
 * Toggle product featured status.
 *
 * @param int $product_id Product ID.
 * @param int $user_id User ID (for security).
 * @param int $is_featured 1 for featured, 0 for not featured.
 * @return string "success" or an error message.
 */
function toggle_product_featured_ctr($product_id, $user_id, $is_featured) {
    try {
        // Validate input
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (!in_array($is_featured, [0, 1])) {
            return "Invalid featured value. Must be 0 or 1.";
        }

        $product = new product_class();

        // Toggle product featured status
        $result = $product->toggle_product_featured($product_id, $user_id, $is_featured);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Toggle product featured error: " . $e->getMessage());
        return "An error occurred while updating product featured status.";
    }
}

/**
 * Add a product image.
 *
 * @param int $product_id Product ID.
 * @param string $file_path Image file path.
 * @param bool $is_primary Whether this is the primary image.
 * @param string $image_alt Optional alt text.
 * @param string $image_title Optional title.
 * @param int $sort_order Optional sort order.
 * @return string "success" or an error message.
 */
function add_product_image_ctr($product_id, $file_path, $is_primary = false, $image_alt = null, $image_title = null, $sort_order = 0) {
    try {
        // Validate input
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        if (empty($file_path)) {
            return "File path is required.";
        }

        $product = new product_class();

        // Add the product image
        $result = $product->add_product_image($product_id, $file_path, $is_primary, $image_alt, $image_title, $sort_order);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Add product image error: " . $e->getMessage());
        return "An error occurred while adding the product image.";
    }
}

/**
 * Get product images.
 *
 * @param int $product_id Product ID.
 * @return array|string Array of images on success, error message on failure.
 */
function get_product_images_ctr($product_id) {
    try {
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        $product = new product_class();
        return $product->get_product_images($product_id);
    } catch (Exception $e) {
        error_log("Get product images error: " . $e->getMessage());
        return "An error occurred while fetching product images.";
    }
}

/**
 * Validate product data.
 *
 * @param array $data Product data array.
 * @return array Array with 'valid' boolean and 'errors' array.
 */
function validate_product_data($data) {
    $errors = [];
    
    // Check required fields
    $required_fields = ['title', 'price', 'desc', 'keyword'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }
    
    // Validate title if provided
    if (isset($data['title']) && !empty($data['title'])) {
        $title = trim($data['title']);
        
        if (strlen($title) < 2) {
            $errors[] = "Product title must be at least 2 characters long.";
        }
        
        if (strlen($title) > 200) {
            $errors[] = "Product title must not exceed 200 characters.";
        }
        
        if (!preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $title)) {
            $errors[] = "Product title can only contain letters, numbers, spaces, hyphens, underscores, and periods.";
        }
    }
    
    // Validate description if provided
    if (isset($data['desc']) && !empty($data['desc'])) {
        $desc = trim($data['desc']);
        
        if (strlen($desc) < 10) {
            $errors[] = "Product description must be at least 10 characters long.";
        }
        
        if (strlen($desc) > 2000) {
            $errors[] = "Product description must not exceed 2000 characters.";
        }
    }
    
    // Validate price if provided
    if (isset($data['price']) && $data['price'] !== '') {
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = "Price must be a valid positive number.";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

?>