<?php
/**
 * Product Class
 * 
 * Handles all product-related database operations including
 * product management, categories, and inventory.
 */

class product_class extends db_class {
    
    /**
     * Add new product
     */
    public function add_product($name, $description, $price, $category_id, $image = null, $stock = 0) {
        $sql = "INSERT INTO products 
                (product_name, product_description, product_price, category_id, product_image, stock_quantity)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [$name, $description, $price, $category_id, $image, $stock];
        $stmt = $this->execute($sql, $params);
        
        return $stmt !== false;
    }
    
    /**
     * Get product by ID
     */
    public function get_product_by_id($product_id) {
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.product_id = ?";
        
        return $this->fetchRow($sql, [$product_id]);
    }
    
    /**
     * Get all products with pagination
     */
    public function get_all_products($limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->fetchAll($sql, [$limit, $offset]);
    }
    
    /**
     * Get products by category
     */
    public function get_products_by_category($category_id, $limit = 20, $offset = 0) {
        $sql = "SELECT * FROM products 
                WHERE category_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->fetchAll($sql, [$category_id, $limit, $offset]);
    }
    
    /**
     * Search products
     */
    public function search_products($search_term, $limit = 20) {
        $search_term = "%$search_term%";
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.product_name LIKE ? OR p.product_description LIKE ?
                ORDER BY p.product_name ASC 
                LIMIT ?";
        
        return $this->fetchAll($sql, [$search_term, $search_term, $limit]);
    }
    
    /**
     * Update product
     */
    public function update_product($product_id, $name, $description, $price, $category_id, $stock) {
        $sql = "UPDATE products SET 
                product_name = ?, product_description = ?, product_price = ?, 
                category_id = ?, stock_quantity = ?
                WHERE product_id = ?";
        
        $params = [$name, $description, $price, $category_id, $stock, $product_id];
        $stmt = $this->execute($sql, $params);
        
        return $stmt !== false;
    }
    
    /**
     * Delete product
     */
    public function delete_product($product_id) {
        $sql = "DELETE FROM products WHERE product_id = ?";
        $stmt = $this->execute($sql, [$product_id]);
        return $stmt !== false;
    }
    
    /**
     * Update product stock
     */
    public function update_stock($product_id, $quantity) {
        $sql = "UPDATE products SET stock_quantity = ? WHERE product_id = ?";
        $stmt = $this->execute($sql, [$quantity, $product_id]);
        return $stmt !== false;
    }
    
    /**
     * Check product availability
     */
    public function is_available($product_id, $quantity = 1) {
        $sql = "SELECT stock_quantity FROM products WHERE product_id = ?";
        $result = $this->fetchRow($sql, [$product_id]);
        
        return $result && $result['stock_quantity'] >= $quantity;
    }
}

?>
