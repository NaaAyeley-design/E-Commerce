<?php
/**
 * Category Controller
 * 
 * Handles all category-related operations including CRUD functionality.
 * Acts as an intermediary between the category class and the views/actions.
 */

// Include core settings and category class
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/category_class.php';

/**
 * Add a new category.
 *
 * @param string $cat_name Category name.
 * @param int $user_id User ID who is creating the category.
 * @return string "success" or an error message.
 */
function add_category_ctr($cat_name, $user_id) {
    try {
        // Validate input
        if (empty($cat_name)) {
            return "Category name is required.";
        }

        // Sanitize input
        $cat_name = trim($cat_name);
        
        // Validate category name length
        if (strlen($cat_name) < 2) {
            return "Category name must be at least 2 characters long.";
        }
        
        if (strlen($cat_name) > 100) {
            return "Category name must not exceed 100 characters.";
        }

        // Validate category name format (letters, numbers, spaces, hyphens, underscores)
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $cat_name)) {
            return "Category name can only contain letters, numbers, spaces, hyphens, and underscores.";
        }

        $category = new category_class();

        // Check if category name already exists for this user
        if ($category->category_name_exists($cat_name, $user_id)) {
            return "A category with this name already exists.";
        }

        // Add the category
        $added = $category->add_category($cat_name, $user_id);

        return $added ? "success" : "Failed to add category. Please try again.";

    } catch (Exception $e) {
        error_log("Add category error: " . $e->getMessage());
        return "An error occurred while adding the category.";
    }
}

/**
 * Get all categories for a specific user.
 *
 * @param int $user_id User ID.
 * @param int $limit Optional limit for pagination.
 * @param int $offset Optional offset for pagination.
 * @return array|string Array of categories on success, error message on failure.
 */
function get_categories_ctr($user_id, $limit = 50, $offset = 0) {
    try {
        $category = new category_class();
        return $category->get_categories_by_user($user_id, $limit, $offset);
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        return "An error occurred while fetching categories.";
    }
}

/**
 * Get a single category by ID.
 *
 * @param int $cat_id Category ID.
 * @param int $user_id User ID (for security).
 * @return array|string Category data on success, error message on failure.
 */
function get_category_ctr($cat_id, $user_id) {
    try {
        if (empty($cat_id) || !is_numeric($cat_id)) {
            return "Invalid category ID.";
        }

        $category = new category_class();
        $result = $category->get_category_by_id($cat_id, $user_id);
        
        return $result ? $result : "Category not found.";
    } catch (Exception $e) {
        error_log("Get category error: " . $e->getMessage());
        return "An error occurred while fetching the category.";
    }
}

/**
 * Update a category.
 *
 * @param int $cat_id Category ID.
 * @param string $cat_name New category name.
 * @param int $user_id User ID (for security).
 * @return string "success" or an error message.
 */
function update_category_ctr($cat_id, $cat_name, $user_id) {
    try {
        // Validate input
        if (empty($cat_id) || !is_numeric($cat_id)) {
            return "Invalid category ID.";
        }

        if (empty($cat_name)) {
            return "Category name is required.";
        }

        // Sanitize input
        $cat_name = trim($cat_name);
        
        // Validate category name length
        if (strlen($cat_name) < 2) {
            return "Category name must be at least 2 characters long.";
        }
        
        if (strlen($cat_name) > 100) {
            return "Category name must not exceed 100 characters.";
        }

        // Validate category name format
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $cat_name)) {
            return "Category name can only contain letters, numbers, spaces, hyphens, and underscores.";
        }

        $category = new category_class();

        // Check if category exists and belongs to user
        $existing_category = $category->get_category_by_id($cat_id, $user_id);
        if (!$existing_category) {
            return "Category not found or you don't have permission to edit it.";
        }

        // Check if new name already exists for this user (excluding current category)
        if ($category->category_name_exists($cat_name, $user_id, $cat_id)) {
            return "A category with this name already exists.";
        }

        // Update the category
        $updated = $category->update_category($cat_id, $cat_name, $user_id);

        return $updated ? "success" : "Failed to update category. Please try again.";

    } catch (Exception $e) {
        error_log("Update category error: " . $e->getMessage());
        return "An error occurred while updating the category.";
    }
}

/**
 * Delete a category.
 *
 * @param int $cat_id Category ID.
 * @param int $user_id User ID (for security).
 * @return string "success" or an error message.
 */
function delete_category_ctr($cat_id, $user_id) {
    try {
        // Validate input
        if (empty($cat_id) || !is_numeric($cat_id)) {
            return "Invalid category ID.";
        }

        $category = new category_class();

        // Check if category exists and belongs to user
        $existing_category = $category->get_category_by_id($cat_id, $user_id);
        if (!$existing_category) {
            return "Category not found or you don't have permission to delete it.";
        }

        // Note: Product check removed since products table doesn't exist yet

        // Delete the category
        $deleted = $category->delete_category($cat_id, $user_id);

        return $deleted ? "success" : "Failed to delete category. Please try again.";

    } catch (Exception $e) {
        error_log("Delete category error: " . $e->getMessage());
        return "An error occurred while deleting the category.";
    }
}

/**
 * Search categories by name.
 *
 * @param string $search_term Search term.
 * @param int $user_id User ID.
 * @param int $limit Optional limit for results.
 * @return array|string Array of categories on success, error message on failure.
 */
function search_categories_ctr($search_term, $user_id, $limit = 20) {
    try {
        if (empty($search_term)) {
            return get_categories_ctr($user_id, $limit);
        }

        $category = new category_class();
        return $category->search_categories($search_term, $user_id, $limit);
    } catch (Exception $e) {
        error_log("Search categories error: " . $e->getMessage());
        return "An error occurred while searching categories.";
    }
}

/**
 * Get category count for a user.
 *
 * @param int $user_id User ID.
 * @return int|string Category count on success, error message on failure.
 */
function get_category_count_ctr($user_id) {
    try {
        $category = new category_class();
        return $category->count_categories_by_user($user_id);
    } catch (Exception $e) {
        error_log("Get category count error: " . $e->getMessage());
        return "An error occurred while counting categories.";
    }
}

/**
 * Get categories with product count.
 *
 * @param int $user_id User ID.
 * @return array|string Array of categories with product counts on success, error message on failure.
 */
function get_categories_with_count_ctr($user_id) {
    try {
        $category = new category_class();
        return $category->get_categories_with_product_count($user_id);
    } catch (Exception $e) {
        error_log("Get categories with count error: " . $e->getMessage());
        return "An error occurred while fetching categories with product counts.";
    }
}

/**
 * Validate category data.
 *
 * @param string $cat_name Category name.
 * @return array Array with 'valid' boolean and 'errors' array.
 */
function validate_category_data($cat_name) {
    $errors = [];
    
    // Check if name is provided
    if (empty($cat_name)) {
        $errors[] = "Category name is required.";
    } else {
        $cat_name = trim($cat_name);
        
        // Check length
        if (strlen($cat_name) < 2) {
            $errors[] = "Category name must be at least 2 characters long.";
        }
        
        if (strlen($cat_name) > 100) {
            $errors[] = "Category name must not exceed 100 characters.";
        }
        
        // Check format
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $cat_name)) {
            $errors[] = "Category name can only contain letters, numbers, spaces, hyphens, and underscores.";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

?>
