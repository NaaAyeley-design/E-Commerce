<?php
/**
 * Cart Controller
 * 
 * Handles cart-related business logic and acts as an intermediary
 * between the cart class and views/actions
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/cart_class.php';

/**
 * Add product to cart
 * 
 * @param int $customer_id Customer ID
 * @param int $product_id Product ID
 * @param int $quantity Quantity to add
 * @return array Result array with success status and message
 */
function add_to_cart_ctr($customer_id, $product_id, $quantity = 1) {
    try {
        // Validate inputs
        if (empty($customer_id) || empty($product_id)) {
            return [
                'success' => false,
                'message' => 'Customer ID and Product ID are required.'
            ];
        }

        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Quantity must be greater than 0.'
            ];
        }

        // Add to cart
        $cart = new cart_class();
        $result = $cart->add_to_cart($customer_id, $product_id, $quantity);

        if ($result === true) {
            return [
                'success' => true,
                'message' => 'Product added to cart successfully!'
            ];
        } else {
            return [
                'success' => false,
                'message' => $result
            ];
        }
    } catch (Exception $e) {
        error_log("Add to cart controller error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while adding to cart.'
        ];
    }
}

/**
 * Update cart item quantity
 * 
 * @param int $customer_id Customer ID
 * @param int $product_id Product ID
 * @param int $quantity New quantity
 * @return array Result array with success status and message
 */
function update_cart_item_ctr($customer_id, $product_id, $quantity) {
    try {
        // Validate inputs
        if (empty($customer_id) || empty($product_id)) {
            return [
                'success' => false,
                'message' => 'Customer ID and Product ID are required.'
            ];
        }

        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Quantity must be greater than 0.'
            ];
        }

        // Update cart item
        $cart = new cart_class();
        $result = $cart->update_cart_item($customer_id, $product_id, $quantity);

        if ($result === true) {
            return [
                'success' => true,
                'message' => 'Cart item updated successfully!'
            ];
        } else {
            return [
                'success' => false,
                'message' => $result
            ];
        }
    } catch (Exception $e) {
        error_log("Update cart item controller error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while updating cart item.'
        ];
    }
}

/**
 * Remove item from cart
 * 
 * @param int $customer_id Customer ID
 * @param int $product_id Product ID
 * @return array Result array with success status and message
 */
function remove_from_cart_ctr($customer_id, $product_id) {
    try {
        // Validate inputs
        if (empty($customer_id) || empty($product_id)) {
            return [
                'success' => false,
                'message' => 'Customer ID and Product ID are required.'
            ];
        }

        // Remove from cart
        $cart = new cart_class();
        $result = $cart->remove_from_cart($customer_id, $product_id);

        if ($result === true) {
            return [
                'success' => true,
                'message' => 'Item removed from cart successfully!'
            ];
        } else {
            return [
                'success' => false,
                'message' => $result
            ];
        }
    } catch (Exception $e) {
        error_log("Remove from cart controller error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred while removing item from cart.'
        ];
    }
}

/**
 * Get all cart items for a customer
 * 
 * @param int $customer_id Customer ID
 * @return array Cart items (empty array on error or no items)
 */
function get_cart_items_ctr($customer_id) {
    try {
        if (empty($customer_id)) {
            return [];
        }

        $cart = new cart_class();
        $items = $cart->get_cart_items($customer_id);
        return is_array($items) ? $items : [];
    } catch (Exception $e) {
        error_log("Get cart items controller error: " . $e->getMessage());
        return [];
    } catch (PDOException $e) {
        error_log("Get cart items controller PDO error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get cart count for a customer
 * 
 * @param int $customer_id Customer ID
 * @return int Item count
 */
function get_cart_count_ctr($customer_id) {
    try {
        if (empty($customer_id)) {
            return 0;
        }

        $cart = new cart_class();
        return $cart->get_cart_count($customer_id);
    } catch (Exception $e) {
        error_log("Get cart count controller error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get cart total for a customer
 * 
 * @param int $customer_id Customer ID
 * @return float Total amount
 */
function get_cart_total_ctr($customer_id) {
    try {
        if (empty($customer_id)) {
            return 0.00;
        }

        $cart = new cart_class();
        return $cart->get_cart_total($customer_id);
    } catch (Exception $e) {
        error_log("Get cart total controller error: " . $e->getMessage());
        return 0.00;
    }
}

/**
 * Clear cart for a customer
 * 
 * @param int $customer_id Customer ID
 * @return bool Success
 */
function clear_cart_ctr($customer_id) {
    try {
        if (empty($customer_id)) {
            return false;
        }

        $cart = new cart_class();
        return $cart->clear_cart($customer_id);
    } catch (Exception $e) {
        error_log("Clear cart controller error: " . $e->getMessage());
        return false;
    }
}
