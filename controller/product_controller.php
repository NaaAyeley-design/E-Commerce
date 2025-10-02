<?php
/**
 * Product Controller
 * 
 * Handles all product-related operations including product management,
 * categories, search, and inventory operations.
 */

// Include core settings and general controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/general_controller.php';

/**
 * Get all products with pagination
 */
function get_products_ctr($page = 1, $limit = 20, $category_id = null, $search = null) {
    try {
        $product = new product_class();
        
        // Calculate pagination
        $offset = ($page - 1) * $limit;
        
        if ($search) {
            // Search products
            $products = $product->search_products($search, $limit);
            $total_products = count($products); // Simplified for now
        } elseif ($category_id) {
            // Get products by category
            $products = $product->get_products_by_category($category_id, $limit, $offset);
            $total_products = 100; // Placeholder - would need a count method
        } else {
            // Get all products
            $products = $product->get_all_products($limit, $offset);
            $total_products = 100; // Placeholder - would need a count method
        }
        
        $pagination = get_paginated_results($total_products, $limit, $page);
        
        return [
            'success' => true,
            'products' => $products,
            'pagination' => $pagination
        ];
        
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving products.'
        ];
    }
}

/**
 * Get single product by ID
 */
function get_product_ctr($product_id) {
    try {
        if (!is_numeric($product_id) || $product_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid product ID.'
            ];
        }
        
        $product = new product_class();
        $product_data = $product->get_product_by_id($product_id);
        
        if (!$product_data) {
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }
        
        // Log product view
        log_activity('product_viewed', "Product viewed: {$product_data['product_name']}", $_SESSION['customer_id'] ?? null);
        
        return [
            'success' => true,
            'product' => $product_data
        ];
        
    } catch (Exception $e) {
        error_log("Get product error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving product.'
        ];
    }
}

/**
 * Add new product (Admin only)
 */
function add_product_ctr($name, $description, $price, $category_id, $image = null, $stock = 0) {
    try {
        // Check admin privileges
        require_admin();
        
        // Validate input
        $validation_rules = [
            'name' => ['required' => true, 'max_length' => 200],
            'description' => ['required' => true, 'max_length' => 1000],
            'price' => ['required' => true],
            'category_id' => ['required' => true],
            'stock' => ['required' => true]
        ];
        
        $data = compact('name', 'description', 'price', 'category_id', 'stock');
        $errors = validate_form($data, $validation_rules);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors
            ];
        }
        
        // Validate price and stock
        $price_validation = validate_numeric($price, 0.01, null, 'Price');
        if ($price_validation !== true) {
            return [
                'success' => false,
                'message' => $price_validation
            ];
        }
        
        $stock_validation = validate_numeric($stock, 0, null, 'Stock');
        if ($stock_validation !== true) {
            return [
                'success' => false,
                'message' => $stock_validation
            ];
        }
        
        // Handle image upload if provided
        $image_path = null;
        if ($image && $image['size'] > 0) {
            $upload_result = handle_file_upload($image, 'products/', ['jpg', 'jpeg', 'png']);
            if (!$upload_result['success']) {
                return [
                    'success' => false,
                    'message' => $upload_result['message']
                ];
            }
            $image_path = $upload_result['filepath'];
        }
        
        $product = new product_class();
        $added = $product->add_product($name, $description, $price, $category_id, $image_path, $stock);
        
        if ($added) {
            // Log product creation
            log_activity('product_created', "Product created: $name", $_SESSION['customer_id']);
            
            return [
                'success' => true,
                'message' => 'Product added successfully.'
            ];
        } else {
            // Delete uploaded image if product creation failed
            if ($image_path) {
                delete_file($image_path);
            }
            
            return [
                'success' => false,
                'message' => 'Failed to add product.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Add product error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error adding product.'
        ];
    }
}

/**
 * Update product (Admin only)
 */
function update_product_ctr($product_id, $name, $description, $price, $category_id, $stock) {
    try {
        // Check admin privileges
        require_admin();
        
        // Validate input
        if (!is_numeric($product_id) || $product_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid product ID.'
            ];
        }
        
        $validation_rules = [
            'name' => ['required' => true, 'max_length' => 200],
            'description' => ['required' => true, 'max_length' => 1000],
            'price' => ['required' => true],
            'category_id' => ['required' => true],
            'stock' => ['required' => true]
        ];
        
        $data = compact('name', 'description', 'price', 'category_id', 'stock');
        $errors = validate_form($data, $validation_rules);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors
            ];
        }
        
        // Validate price and stock
        $price_validation = validate_numeric($price, 0.01, null, 'Price');
        if ($price_validation !== true) {
            return [
                'success' => false,
                'message' => $price_validation
            ];
        }
        
        $stock_validation = validate_numeric($stock, 0, null, 'Stock');
        if ($stock_validation !== true) {
            return [
                'success' => false,
                'message' => $stock_validation
            ];
        }
        
        $product = new product_class();
        $updated = $product->update_product($product_id, $name, $description, $price, $category_id, $stock);
        
        if ($updated) {
            // Log product update
            log_activity('product_updated', "Product updated: $name", $_SESSION['customer_id']);
            
            return [
                'success' => true,
                'message' => 'Product updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update product.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating product.'
        ];
    }
}

/**
 * Delete product (Admin only)
 */
function delete_product_ctr($product_id) {
    try {
        // Check admin privileges
        require_admin();
        
        if (!is_numeric($product_id) || $product_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid product ID.'
            ];
        }
        
        $product = new product_class();
        
        // Get product data before deletion (for logging)
        $product_data = $product->get_product_by_id($product_id);
        if (!$product_data) {
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }
        
        $deleted = $product->delete_product($product_id);
        
        if ($deleted) {
            // Delete product image if exists
            if (!empty($product_data['product_image'])) {
                delete_file($product_data['product_image']);
            }
            
            // Log product deletion
            log_activity('product_deleted', "Product deleted: {$product_data['product_name']}", $_SESSION['customer_id']);
            
            return [
                'success' => true,
                'message' => 'Product deleted successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete product.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deleting product.'
        ];
    }
}

/**
 * Search products
 */
function search_products_ctr($search_term, $limit = 20) {
    try {
        $search_result = handle_search($search_term, 'products');
        if (!$search_result['success']) {
            return $search_result;
        }
        
        $product = new product_class();
        $products = $product->search_products($search_term, $limit);
        
        return [
            'success' => true,
            'products' => $products,
            'search_term' => $search_term,
            'total_results' => count($products)
        ];
        
    } catch (Exception $e) {
        error_log("Search products error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error searching products.'
        ];
    }
}

/**
 * Check product availability
 */
function check_product_availability_ctr($product_id, $quantity = 1) {
    try {
        if (!is_numeric($product_id) || $product_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid product ID.'
            ];
        }
        
        if (!is_numeric($quantity) || $quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid quantity.'
            ];
        }
        
        $product = new product_class();
        $available = $product->is_available($product_id, $quantity);
        
        return [
            'success' => true,
            'available' => $available,
            'product_id' => $product_id,
            'requested_quantity' => $quantity
        ];
        
    } catch (Exception $e) {
        error_log("Check availability error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error checking product availability.'
        ];
    }
}

/**
 * Update product stock (Admin only)
 */
function update_product_stock_ctr($product_id, $new_stock) {
    try {
        // Check admin privileges
        require_admin();
        
        if (!is_numeric($product_id) || $product_id <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid product ID.'
            ];
        }
        
        $stock_validation = validate_numeric($new_stock, 0, null, 'Stock');
        if ($stock_validation !== true) {
            return [
                'success' => false,
                'message' => $stock_validation
            ];
        }
        
        $product = new product_class();
        $updated = $product->update_stock($product_id, $new_stock);
        
        if ($updated) {
            // Log stock update
            log_activity('stock_updated', "Stock updated for product ID: $product_id to $new_stock", $_SESSION['customer_id']);
            
            return [
                'success' => true,
                'message' => 'Stock updated successfully.',
                'new_stock' => $new_stock
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update stock.'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Update stock error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating stock.'
        ];
    }
}

/**
 * Get featured products
 */
function get_featured_products_ctr($limit = 8) {
    try {
        $product = new product_class();
        
        // For now, just get the latest products
        // In the future, this could be based on a 'featured' flag in the database
        $products = $product->get_all_products($limit, 0);
        
        return [
            'success' => true,
            'products' => $products
        ];
        
    } catch (Exception $e) {
        error_log("Get featured products error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving featured products.'
        ];
    }
}

/**
 * Get product categories (placeholder for future implementation)
 */
function get_product_categories_ctr() {
    try {
        // Placeholder categories - in the future, this would come from a categories table
        $categories = [
            ['id' => 1, 'name' => 'Electronics', 'description' => 'Electronic devices and gadgets'],
            ['id' => 2, 'name' => 'Clothing', 'description' => 'Fashion and apparel'],
            ['id' => 3, 'name' => 'Home & Garden', 'description' => 'Home improvement and garden supplies'],
            ['id' => 4, 'name' => 'Books', 'description' => 'Books and educational materials'],
            ['id' => 5, 'name' => 'Sports', 'description' => 'Sports equipment and accessories']
        ];
        
        return [
            'success' => true,
            'categories' => $categories
        ];
        
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error retrieving categories.'
        ];
    }
}

/**
 * Get products by price range
 */
function get_products_by_price_range_ctr($min_price, $max_price, $limit = 20) {
    try {
        $min_validation = validate_numeric($min_price, 0, null, 'Minimum price');
        if ($min_validation !== true) {
            return [
                'success' => false,
                'message' => $min_validation
            ];
        }
        
        $max_validation = validate_numeric($max_price, $min_price, null, 'Maximum price');
        if ($max_validation !== true) {
            return [
                'success' => false,
                'message' => $max_validation
            ];
        }
        
        // This would require a method in product_class to filter by price range
        // For now, return a placeholder response
        return [
            'success' => true,
            'products' => [],
            'min_price' => $min_price,
            'max_price' => $max_price,
            'message' => 'Price range filtering not yet implemented.'
        ];
        
    } catch (Exception $e) {
        error_log("Get products by price range error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error filtering products by price range.'
        ];
    }
}

?>
