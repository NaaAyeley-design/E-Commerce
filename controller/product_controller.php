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
 *   - cat_id (int): Category ID the product belongs to
 *   - brand_id (int): Brand ID the product belongs to
 *   - title (string): Product title/name
 *   - price (float): Product price
 *   - desc (string): Product description
 *   - keyword (string): Product keywords
 *   - image_path (string, optional): Product image path
 * @return string "success" or an error message.
 */
function add_product_ctr($data) {
    try {
        // Validate required fields
        $required_fields = ['cat_id', 'brand_id', 'title', 'price', 'desc', 'keyword'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
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

        // Validate product title format (allows letters including accented, numbers, spaces, and common punctuation)
        if (!preg_match('/^[\p{L}\p{N}\s\'\-_.&,()]+$/u', $data['title'])) {
            return "Product title contains invalid characters.";
        }

        // Validate description length
        if (strlen($data['desc']) < 10) {
            return "Product description must be at least 10 characters long.";
        }
        
        if (strlen($data['desc']) > 2000) {
            return "Product description must not exceed 2000 characters.";
        }

        $product = new product_class();

        // Add the product
        $result = $product->add_product(
            $data['cat_id'],
            $data['brand_id'],
            $data['title'],
            $data['price'],
            $data['desc'],
            $data['keyword'],
            $data['image_path'] ?? null
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
 *   - cat_id (int): Category ID the product belongs to
 *   - brand_id (int): Brand ID the product belongs to
 *   - title (string): New product title
 *   - price (float): New product price
 *   - desc (string): New product description
 *   - keyword (string): New product keywords
 *   - image_path (string, optional): New product image path
 * @return string "success" or an error message.
 */
function update_product_ctr($data) {
    try {
        // Validate required fields
        $required_fields = ['product_id', 'cat_id', 'brand_id', 'title', 'price', 'desc', 'keyword'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }

        // Validate product ID
        if (!is_numeric($data['product_id'])) {
            return "Invalid product ID.";
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

        $product = new product_class();

        // Update the product
        $result = $product->update_product(
            $data['product_id'],
            $data['cat_id'],
            $data['brand_id'],
            $data['title'],
            $data['price'],
            $data['desc'],
            $data['keyword'],
            $data['image_path'] ?? null
        );

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        return "An error occurred while updating the product.";
    }
}

/**
 * Get all products.
 *
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array|string Array of products on success, error message on failure.
 */
function fetch_products_ctr($limit = 50, $offset = 0) {
    try {
        $product = new product_class();
        return $product->get_all_products($limit, $offset);
    } catch (Exception $e) {
        error_log("Fetch products error: " . $e->getMessage());
        return "An error occurred while fetching products.";
    }
}

/**
 * Get a single product by ID.
 *
 * @param int $product_id Product ID.
 * @return array|string Product data on success, error message on failure.
 */
function get_product_ctr($product_id) {
    try {
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        $product = new product_class();
        $result = $product->get_product_by_id($product_id);
        
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
 * @return string "success" or an error message.
 */
function delete_product_ctr($product_id) {
    try {
        // Validate input
        if (empty($product_id) || !is_numeric($product_id)) {
            return "Invalid product ID.";
        }

        $product = new product_class();

        // Delete the product
        $result = $product->delete_product($product_id);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        return "An error occurred while deleting the product.";
    }
}

/**
 * Get products by category.
 *
 * @param int $cat_id Category ID.
 * @return array|string Array of products on success, error message on failure.
 */
function get_products_by_category_ctr($cat_id) {
    try {
        if (empty($cat_id) || !is_numeric($cat_id)) {
            return "Invalid category ID.";
        }

        $product = new product_class();
        return $product->get_products_by_category($cat_id);
    } catch (Exception $e) {
        error_log("Get products by category error: " . $e->getMessage());
        return "An error occurred while fetching products for the category.";
    }
}

/**
 * Get products by brand.
 *
 * @param int $brand_id Brand ID.
 * @return array|string Array of products on success, error message on failure.
 */
function get_products_by_brand_ctr($brand_id) {
    try {
        if (empty($brand_id) || !is_numeric($brand_id)) {
            return "Invalid brand ID.";
        }

        $product = new product_class();
        return $product->get_products_by_brand($brand_id);
    } catch (Exception $e) {
        error_log("Get products by brand error: " . $e->getMessage());
        return "An error occurred while fetching products for the brand.";
    }
}

/**
 * Search products by title, description, and keywords.
 *
 * @param string $search_term Search term.
 * @param int $limit Optional limit for results.
 * @param int $offset Optional offset for pagination.
 * @return array|string Array of products on success, error message on failure.
 */
function search_products_ctr($search_term, $limit = 20, $offset = 0) {
    try {
        if (empty($search_term)) {
            return fetch_products_ctr($limit);
        }

        $product = new product_class();
        return $product->search_products($search_term, $limit, $offset);
    } catch (Exception $e) {
        error_log("Search products error: " . $e->getMessage());
        return "An error occurred while searching products.";
    }
}

/**
 * Get total product count.
 *
 * @return int|string Product count on success, error message on failure.
 */
function get_product_count_ctr() {
    try {
        $product = new product_class();
        return $product->count_all_products();
    } catch (Exception $e) {
        error_log("Get product count error: " . $e->getMessage());
        return "An error occurred while counting products.";
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
        
        if (!preg_match('/^[\p{L}\p{N}\s\'\-_.&,()]+$/u', $title)) {
            $errors[] = "Product title contains invalid characters.";
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

/**
 * View all products (customer-facing).
 *
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array Array of products.
 */
function view_all_products_ctr($limit = 10, $offset = 0) {
    try {
        $product = new product_class();
        return $product->view_all_products($limit, $offset);
    } catch (Exception $e) {
        error_log("View all products error: " . $e->getMessage());
        return [];
    }
}

/**
 * Filter products by category (customer-facing).
 *
 * @param int $cat_id Category ID.
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array Array of products in the category.
 */
function filter_products_by_category_ctr($cat_id, $limit = 20, $offset = 0) {
    try {
        $product = new product_class();
        return $product->filter_products_by_category($cat_id, $limit, $offset);
    } catch (Exception $e) {
        error_log("Filter products by category error: " . $e->getMessage());
        return [];
    }
}

/**
 * Filter products by brand (customer-facing).
 *
 * @param int $brand_id Brand ID.
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array Array of products for the brand.
 */
function filter_products_by_brand_ctr($brand_id, $limit = 20, $offset = 0) {
    try {
        $product = new product_class();
        return $product->filter_products_by_brand($brand_id, $limit, $offset);
    } catch (Exception $e) {
        error_log("Filter products by brand error: " . $e->getMessage());
        return [];
    }
}

/**
 * View single product (customer-facing).
 *
 * @param int $product_id Product ID.
 * @return array|false Product data if found, false otherwise.
 */
function view_single_product_ctr($product_id) {
    try {
        $product = new product_class();
        return $product->view_single_product($product_id);
    } catch (Exception $e) {
        error_log("View single product error: " . $e->getMessage());
        return false;
    }
}

/**
 * Composite search with filters (customer-facing).
 *
 * @param array $filters Array containing query, cat_id, brand_id, max_price.
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array Array of matching products.
 */
function composite_search_ctr($filters = [], $limit = 20, $offset = 0) {
    try {
        $product = new product_class();
        return $product->composite_search($filters, $limit, $offset);
    } catch (Exception $e) {
        error_log("Composite search error: " . $e->getMessage());
        return [];
    }
}

/**
 * Count filtered products (customer-facing).
 *
 * @param array $filters Array containing filters.
 * @return int Total number of matching products.
 */
function count_filtered_products_ctr($filters = []) {
    try {
        $product = new product_class();
        return $product->count_filtered_products($filters);
    } catch (Exception $e) {
        error_log("Count filtered products error: " . $e->getMessage());
        return 0;
    }
}

?>