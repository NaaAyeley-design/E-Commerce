<?php
/**
 * Brand Controller
 * 
 * Handles all brand-related operations including CRUD functionality.
 * Acts as an intermediary between the brand class and the views/actions.
 */

// Include core settings and brand class
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/brand_class.php';

/**
 * Add a new brand.
 *
 * @param int $user_id User ID who is creating the brand.
 * @param int $cat_id Category ID the brand belongs to.
 * @param string $brand_name Brand name.
 * @param string $brand_description Optional brand description.
 * @param string $brand_logo Optional brand logo URL.
 * @return string "success" or an error message.
 */
function add_brand_ctr($user_id, $cat_id, $brand_name, $brand_description = null, $brand_logo = null) {
    try {
        // Validate input
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (empty($cat_id) || !is_numeric($cat_id)) {
            return "Invalid category ID.";
        }

        if (empty($brand_name)) {
            return "Brand name is required.";
        }

        // Sanitize input
        $brand_name = trim($brand_name);
        $brand_description = $brand_description ? trim($brand_description) : null;
        $brand_logo = $brand_logo ? trim($brand_logo) : null;
        
        // Validate brand name length
        if (strlen($brand_name) < 2) {
            return "Brand name must be at least 2 characters long.";
        }
        
        if (strlen($brand_name) > 100) {
            return "Brand name must not exceed 100 characters.";
        }

        // Validate brand name format (letters, numbers, spaces, hyphens, underscores)
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $brand_name)) {
            return "Brand name can only contain letters, numbers, spaces, hyphens, and underscores.";
        }

        // Validate brand description length if provided
        if ($brand_description && strlen($brand_description) > 1000) {
            return "Brand description must not exceed 1000 characters.";
        }

        $brand = new brand_class();

        // Add the brand
        $result = $brand->add_brand($user_id, $cat_id, $brand_name, $brand_description, $brand_logo);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Add brand error: " . $e->getMessage());
        return "An error occurred while adding the brand.";
    }
}

/**
 * Get all brands for a specific user.
 *
 * @param int $user_id User ID.
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array|string Array of brands on success, error message on failure.
 */
function fetch_brands_ctr($user_id, $limit = 50, $offset = 0) {
    try {
        // Validate input
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $brand = new brand_class();
        return $brand->get_brands_by_user($user_id, $limit, $offset);
    } catch (Exception $e) {
        error_log("Fetch brands error: " . $e->getMessage());
        return "An error occurred while fetching brands.";
    }
}

/**
 * Get a single brand by ID.
 *
 * @param int $brand_id Brand ID.
 * @param int $user_id User ID (for security).
 * @return array|string Brand data on success, error message on failure.
 */
function get_brand_ctr($brand_id, $user_id) {
    try {
        if (empty($brand_id) || !is_numeric($brand_id)) {
            return "Invalid brand ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $brand = new brand_class();
        $result = $brand->get_brand_by_id($brand_id, $user_id);
        
        return $result ? $result : "Brand not found.";
    } catch (Exception $e) {
        error_log("Get brand error: " . $e->getMessage());
        return "An error occurred while fetching the brand.";
    }
}

/**
 * Update a brand.
 *
 * @param int $brand_id Brand ID.
 * @param string $brand_name New brand name.
 * @param int $user_id User ID (for security).
 * @param string $brand_description Optional new brand description.
 * @param string $brand_logo Optional new brand logo URL.
 * @return string "success" or an error message.
 */
function update_brand_ctr($brand_id, $brand_name, $user_id, $brand_description = null, $brand_logo = null) {
    try {
        // Validate input
        if (empty($brand_id) || !is_numeric($brand_id)) {
            return "Invalid brand ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (empty($brand_name)) {
            return "Brand name is required.";
        }

        // Sanitize input
        $brand_name = trim($brand_name);
        $brand_description = $brand_description ? trim($brand_description) : null;
        $brand_logo = $brand_logo ? trim($brand_logo) : null;
        
        // Validate brand name length
        if (strlen($brand_name) < 2) {
            return "Brand name must be at least 2 characters long.";
        }
        
        if (strlen($brand_name) > 100) {
            return "Brand name must not exceed 100 characters.";
        }

        // Validate brand name format
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $brand_name)) {
            return "Brand name can only contain letters, numbers, spaces, hyphens, and underscores.";
        }

        // Validate brand description length if provided
        if ($brand_description && strlen($brand_description) > 1000) {
            return "Brand description must not exceed 1000 characters.";
        }

        $brand = new brand_class();

        // Update the brand
        $result = $brand->update_brand($brand_id, $brand_name, $user_id, $brand_description, $brand_logo);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Update brand error: " . $e->getMessage());
        return "An error occurred while updating the brand.";
    }
}

/**
 * Delete a brand.
 *
 * @param int $brand_id Brand ID.
 * @param int $user_id User ID (for security).
 * @return string "success" or an error message.
 */
function delete_brand_ctr($brand_id, $user_id) {
    try {
        // Validate input
        if (empty($brand_id) || !is_numeric($brand_id)) {
            return "Invalid brand ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $brand = new brand_class();

        // Delete the brand
        $result = $brand->delete_brand($brand_id, $user_id);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Delete brand error: " . $e->getMessage());
        return "An error occurred while deleting the brand.";
    }
}

/**
 * Get brands by category for a specific user.
 *
 * @param int $user_id User ID.
 * @param int $cat_id Category ID.
 * @return array|string Array of brands on success, error message on failure.
 */
function get_brands_by_category_ctr($user_id, $cat_id) {
    try {
        // Validate input
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (empty($cat_id) || !is_numeric($cat_id)) {
            return "Invalid category ID.";
        }

        $brand = new brand_class();
        return $brand->get_brands_by_category($user_id, $cat_id);
    } catch (Exception $e) {
        error_log("Get brands by category error: " . $e->getMessage());
        return "An error occurred while fetching brands for the category.";
    }
}

/**
 * Search brands by name for a specific user.
 *
 * @param string $search_term Search term.
 * @param int $user_id User ID.
 * @param int $limit Optional limit for results.
 * @return array|string Array of brands on success, error message on failure.
 */
function search_brands_ctr($search_term, $user_id, $limit = 20) {
    try {
        if (empty($search_term)) {
            return fetch_brands_ctr($user_id, $limit);
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $brand = new brand_class();
        return $brand->search_brands($search_term, $user_id, $limit);
    } catch (Exception $e) {
        error_log("Search brands error: " . $e->getMessage());
        return "An error occurred while searching brands.";
    }
}

/**
 * Get brand count for a user.
 *
 * @param int $user_id User ID.
 * @return int|string Brand count on success, error message on failure.
 */
function get_brand_count_ctr($user_id) {
    try {
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $brand = new brand_class();
        return $brand->count_brands_by_user($user_id);
    } catch (Exception $e) {
        error_log("Get brand count error: " . $e->getMessage());
        return "An error occurred while counting brands.";
    }
}

/**
 * Get brands with product count for a specific user.
 *
 * @param int $user_id User ID.
 * @return array|string Array of brands with product counts on success, error message on failure.
 */
function get_brands_with_count_ctr($user_id) {
    try {
        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        $brand = new brand_class();
        return $brand->get_brands_with_product_count($user_id);
    } catch (Exception $e) {
        error_log("Get brands with count error: " . $e->getMessage());
        return "An error occurred while fetching brands with product counts.";
    }
}

/**
 * Toggle brand active status.
 *
 * @param int $brand_id Brand ID.
 * @param int $user_id User ID (for security).
 * @param int $is_active 1 for active, 0 for inactive.
 * @return string "success" or an error message.
 */
function toggle_brand_status_ctr($brand_id, $user_id, $is_active) {
    try {
        // Validate input
        if (empty($brand_id) || !is_numeric($brand_id)) {
            return "Invalid brand ID.";
        }

        if (empty($user_id) || !is_numeric($user_id)) {
            return "Invalid user ID.";
        }

        if (!in_array($is_active, [0, 1])) {
            return "Invalid status value. Must be 0 or 1.";
        }

        $brand = new brand_class();

        // Toggle brand status
        $result = $brand->toggle_brand_status($brand_id, $user_id, $is_active);

        return $result['success'] ? "success" : $result['message'];

    } catch (Exception $e) {
        error_log("Toggle brand status error: " . $e->getMessage());
        return "An error occurred while updating brand status.";
    }
}

/**
 * Validate brand data.
 *
 * @param string $brand_name Brand name.
 * @param string $brand_description Optional brand description.
 * @return array Array with 'valid' boolean and 'errors' array.
 */
function validate_brand_data($brand_name, $brand_description = null) {
    $errors = [];
    
    // Check if name is provided
    if (empty($brand_name)) {
        $errors[] = "Brand name is required.";
    } else {
        $brand_name = trim($brand_name);
        
        // Check length
        if (strlen($brand_name) < 2) {
            $errors[] = "Brand name must be at least 2 characters long.";
        }
        
        if (strlen($brand_name) > 100) {
            $errors[] = "Brand name must not exceed 100 characters.";
        }
        
        // Check format
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $brand_name)) {
            $errors[] = "Brand name can only contain letters, numbers, spaces, hyphens, and underscores.";
        }
    }

    // Validate description if provided
    if ($brand_description && strlen(trim($brand_description)) > 1000) {
        $errors[] = "Brand description must not exceed 1000 characters.";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

?>
