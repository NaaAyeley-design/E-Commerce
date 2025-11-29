<?php
/**
 * Add Product Action for Producers
 * 
 * Handles product creation with all producer-specific fields
 */

require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../class/product_class.php';
require_once __DIR__ . '/../../class/brand_class.php';

header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add products.']);
    exit;
}

// Check user role (must be producer/designer - role 3)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Only producers can add products.']);
    exit;
}

$producer_id = $_SESSION['user_id'];
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

try {
    // Validate CSRF token
    if (isset($_POST['csrf_token'])) {
        validate_csrf_token($_POST['csrf_token']);
    }

    // Get and sanitize input
    $product_name = trim($_POST['product_name'] ?? '');
    $category_id = (int)($_POST['category'] ?? 0);
    $brand_id = (int)($_POST['brand'] ?? 0);
    $sku = trim($_POST['sku'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cultural_story = trim($_POST['cultural_story'] ?? '');
    $materials = trim($_POST['materials'] ?? '');
    $care_instructions = trim($_POST['care_instructions'] ?? '');
    $base_price = $_POST['base_price'] ?? '';
    $compare_price = $_POST['compare_price'] ?? '';
    $cost_per_item = $_POST['cost_per_item'] ?? '';
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $low_stock_threshold = (int)($_POST['low_stock_threshold'] ?? 5);
    $track_inventory = isset($_POST['track_inventory']) ? 1 : 0;
    $product_status = trim($_POST['product_status'] ?? 'draft');
    $visibility = trim($_POST['visibility'] ?? 'public');
    $tags = trim($_POST['tags'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $product_weight = $_POST['product_weight'] ?? null;
    $product_length = $_POST['product_length'] ?? null;
    $product_width = $_POST['product_width'] ?? null;
    $product_height = $_POST['product_height'] ?? null;
    $ships_from = trim($_POST['ships_from'] ?? '');
    $processing_time = trim($_POST['processing_time'] ?? '');
    $action = trim($_POST['action'] ?? 'draft');

    // Validation
    if (empty($product_name)) {
        throw new Exception('Product name is required.');
    }
    if (empty($category_id)) {
        throw new Exception('Category is required.');
    }
    if (empty($brand_id)) {
        throw new Exception('Brand is required.');
    }
    if (empty($description) || strlen($description) < 100) {
        throw new Exception('Product description must be at least 100 characters.');
    }
    if (empty($base_price) || !is_numeric($base_price) || $base_price < 0) {
        throw new Exception('Valid base price is required.');
    }
    if (!in_array($product_status, ['draft', 'active', 'inactive'])) {
        $product_status = 'draft';
    }
    if (!in_array($visibility, ['public', 'hidden'])) {
        $visibility = 'public';
    }

    // Verify brand belongs to producer
    $brand_class = new brand_class();
    $brand = $brand_class->get_brand_by_id($brand_id);
    if (!$brand || $brand['user_id'] != $producer_id) {
        throw new Exception('Invalid brand selected.');
    }

    // Generate SKU if not provided
    if (empty($sku)) {
        $sku = 'PRD-' . strtoupper(substr($product_name, 0, 3)) . '-' . time() . '-' . rand(100, 999);
    }

    // Handle main image upload
    $main_image_path = null;
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['main_image'];
        
        // Validate file size (max 5MB)
        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            throw new Exception('Main image size must not exceed 5MB.');
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid image type. Only JPEG, PNG, and WebP are allowed.');
        }
        
        // Create uploads directory
        $uploads_base = __DIR__ . '/../../uploads';
        $user_dir = $uploads_base . '/u' . $producer_id;
        if (!is_dir($user_dir)) {
            mkdir($user_dir, 0755, true);
        }
        
        // Generate filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $timestamp = time();
        $random_string = bin2hex(random_bytes(4));
        $filename = 'product_main_' . $timestamp . '_' . $random_string . '.' . $file_extension;
        
        // Save to temp directory first (will move after product creation)
        $temp_dir = $uploads_base . '/temp';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        $temp_path = $temp_dir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $temp_path)) {
            throw new Exception('Failed to upload main image.');
        }
        
        $main_image_path = 'uploads/temp/' . $filename;
    } else {
        throw new Exception('Main product image is required.');
    }

    // Handle additional images
    $additional_images = [];
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        $temp_dir = __DIR__ . '/../../uploads/temp';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
            if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['additional_images']['name'][$i],
                    'type' => $_FILES['additional_images']['type'][$i],
                    'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                    'error' => $_FILES['additional_images']['error'][$i],
                    'size' => $_FILES['additional_images']['size'][$i]
                ];
                
                // Validate
                if ($file['size'] > $max_size) {
                    continue; // Skip oversized files
                }
                
                $file_type = mime_content_type($file['tmp_name']);
                if (!in_array($file_type, $allowed_types)) {
                    continue; // Skip invalid types
                }
                
                // Save
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $timestamp = time();
                $random_string = bin2hex(random_bytes(4));
                $filename = 'product_add_' . ($i + 1) . '_' . $timestamp . '_' . $random_string . '.' . $file_extension;
                $temp_path = $temp_dir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $temp_path)) {
                    $additional_images[] = 'uploads/temp/' . $filename;
                }
            }
        }
    }

    // Create product using product_class
    $product_class = new product_class();
    
    // Build SQL with all new fields
    $sql = "INSERT INTO products (
        product_cat, product_brand, producer_id, product_title, product_price,
        compare_at_price, cost_per_item, product_desc, cultural_story, materials_used,
        care_instructions, product_image, product_keywords, meta_description,
        sku, stock_quantity, low_stock_threshold, track_inventory,
        product_status, visibility, product_weight, product_length, product_width,
        product_height, ships_from, processing_time, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $params = [
        $category_id,
        $brand_id,
        $producer_id,
        $product_name,
        (float)$base_price,
        !empty($compare_price) ? (float)$compare_price : null,
        !empty($cost_per_item) ? (float)$cost_per_item : null,
        $description,
        !empty($cultural_story) ? $cultural_story : null,
        !empty($materials) ? $materials : null,
        !empty($care_instructions) ? $care_instructions : null,
        $main_image_path, // Will be updated after moving files
        !empty($tags) ? $tags : null,
        !empty($meta_description) ? $meta_description : null,
        $sku,
        $stock_quantity,
        $low_stock_threshold,
        $track_inventory,
        $product_status,
        $visibility,
        !empty($product_weight) ? (float)$product_weight : null,
        !empty($product_length) ? (float)$product_length : null,
        !empty($product_width) ? (float)$product_width : null,
        !empty($product_height) ? (float)$product_height : null,
        !empty($ships_from) ? $ships_from : null,
        !empty($processing_time) ? $processing_time : null
    ];
    
    $stmt = $product_class->execute($sql, $params);
    
    if (!$stmt || $stmt->rowCount() === 0) {
        throw new Exception('Failed to create product.');
    }
    
    $product_id = $product_class->lastInsertId();
    
    // Move images from temp to final location
    $user_dir = __DIR__ . '/../../uploads/u' . $producer_id;
    $product_dir = $user_dir . '/p' . $product_id;
    
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0755, true);
    }
    if (!is_dir($product_dir)) {
        mkdir($product_dir, 0755, true);
    }
    
    // Move main image
    if ($main_image_path && strpos($main_image_path, 'uploads/temp/') === 0) {
        $temp_file = __DIR__ . '/../../' . $main_image_path;
        if (file_exists($temp_file)) {
            $final_filename = 'main_' . $product_id . '_' . time() . '.' . pathinfo($temp_file, PATHINFO_EXTENSION);
            $final_path = $product_dir . '/' . $final_filename;
            if (rename($temp_file, $final_path)) {
                $main_image_path = 'uploads/u' . $producer_id . '/p' . $product_id . '/' . $final_filename;
                
                // Update product with final image path
                $update_sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
                $product_class->execute($update_sql, [$main_image_path, $product_id]);
            }
        }
    }
    
    // Move additional images and save to product_images table
    if (!empty($additional_images)) {
        require_once __DIR__ . '/../../class/db_class.php';
        $db = new db_class();
        
        foreach ($additional_images as $index => $temp_image_path) {
            if (strpos($temp_image_path, 'uploads/temp/') === 0) {
                $temp_file = __DIR__ . '/../../' . $temp_image_path;
                if (file_exists($temp_file)) {
                    $final_filename = 'add_' . ($index + 1) . '_' . $product_id . '_' . time() . '.' . pathinfo($temp_file, PATHINFO_EXTENSION);
                    $final_path = $product_dir . '/' . $final_filename;
                    if (rename($temp_file, $final_path)) {
                        $final_image_path = 'uploads/u' . $producer_id . '/p' . $product_id . '/' . $final_filename;
                        
                        // Insert into product_images table
                        $image_sql = "INSERT INTO product_images (product_id, image_url, sort_order, is_primary, created_at) 
                                     VALUES (?, ?, ?, ?, NOW())";
                        $db->execute($image_sql, [$product_id, $final_image_path, $index + 1, 0]);
                    }
                }
            }
        }
    }
    
    // Update product status based on action
    if ($action === 'publish' && $product_status === 'draft') {
        $product_status = 'active';
        $update_status_sql = "UPDATE products SET product_status = ? WHERE product_id = ?";
        $product_class->execute($update_status_sql, [$product_status, $product_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'publish' ? 'Product published successfully!' : 'Product saved as draft.',
        'product_id' => $product_id,
        'redirect' => url('view/producer/products.php')
    ]);

} catch (Exception $e) {
    error_log("Add product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

