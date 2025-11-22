<?php
/**
 * Upload Product Image Action (Enhanced for Bulk Upload)
 * 
 * Handles single and multiple product image upload requests with file validation and directory management
 */

// Include core settings and product controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/product_controller.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to upload images']);
    exit;
}

// Check if user is admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) || isset($_FILES) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

try {
    // Validate CSRF token if available
    if (isset($_POST['csrf_token'])) {
        validate_csrf_token($_POST['csrf_token']);
    }

    // Get user ID and product ID
    $user_id = $_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    // Validate product ID
    if (empty($product_id)) {
        $error_msg = 'Product ID is required.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Verify product belongs to user
    $product = get_product_ctr($product_id, $user_id);
    if (is_string($product)) {
        $error_msg = 'Product not found or access denied.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Check if files were uploaded
    if (!isset($_FILES['images']) && !isset($_FILES['image'])) {
        $error_msg = 'No files uploaded.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Determine if this is a single file or multiple files upload
    $is_multiple = isset($_FILES['images']) && is_array($_FILES['images']['name']);
    $files = [];
    
    if ($is_multiple) {
        // Multiple files upload
        $file_count = count($_FILES['images']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $files[] = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
            }
        }
    } else {
        // Single file upload (backward compatibility)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $files[] = $_FILES['image'];
        } elseif (isset($_FILES['images']) && $_FILES['images']['error'] === UPLOAD_ERR_OK) {
            $files[] = $_FILES['images'];
        }
    }
    
    // Check if any valid files were found
    if (empty($files)) {
        $error_msg = 'No valid files uploaded or upload error occurred.';
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Create uploads directory structure
    $uploads_base = __DIR__ . '/../uploads';
    $user_dir = $uploads_base . '/u' . $user_id;
    $product_dir = $user_dir . '/p' . $product_id;
    
    // Create directories if they don't exist
    if (!is_dir($uploads_base)) {
        if (!mkdir($uploads_base, 0755, true)) {
            $error_msg = 'Failed to create uploads directory.';
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $error_msg]);
            } else {
                echo $error_msg;
            }
            exit;
        }
    }
    
    if (!is_dir($user_dir)) {
        if (!mkdir($user_dir, 0755, true)) {
            $error_msg = 'Failed to create user directory.';
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $error_msg]);
            } else {
                echo $error_msg;
            }
            exit;
        }
    }
    
    if (!is_dir($product_dir)) {
        if (!mkdir($product_dir, 0755, true)) {
            $error_msg = 'Failed to create product directory.';
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $error_msg]);
            } else {
                echo $error_msg;
            }
            exit;
        }
    }
    
    // Process each file
    $upload_results = [];
    $success_count = 0;
    $error_count = 0;
    $uploaded_files = [];
    
    foreach ($files as $index => $file) {
        $file_result = [
            'index' => $index,
            'original_name' => $file['name'],
            'success' => false,
            'error' => null,
            'file_path' => null,
            'file_name' => null
        ];
        
        try {
            // Validate file size (max 5MB)
            $max_size = 5 * 1024 * 1024; // 5MB in bytes
            if ($file['size'] > $max_size) {
                $file_result['error'] = 'File size must not exceed 5MB.';
                $upload_results[] = $file_result;
                $error_count++;
                continue;
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($file['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $file_result['error'] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.';
                $upload_results[] = $file_result;
                $error_count++;
                continue;
            }
            
            // Validate file extension
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $file_result['error'] = 'Invalid file extension. Only .jpg, .jpeg, .png, .gif, and .webp files are allowed.';
                $upload_results[] = $file_result;
                $error_count++;
                continue;
            }
            
            // Generate unique filename with sequential naming
            $timestamp = time();
            $random_string = bin2hex(random_bytes(4));
            $new_filename = 'image_' . ($index + 1) . '_' . $timestamp . '_' . $random_string . '.' . $file_extension;
            $file_path = $product_dir . '/' . $new_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                $file_result['error'] = 'Failed to save uploaded file.';
                $upload_results[] = $file_result;
                $error_count++;
                continue;
            }
            
            // Generate relative path for database storage
            $relative_path = 'uploads/u' . $user_id . '/p' . $product_id . '/' . $new_filename;
            
            // Get additional parameters
            $is_primary = ($index === 0) && isset($_POST['is_primary']) && $_POST['is_primary'] === '1';
            $image_alt = trim($_POST['image_alt'] ?? '');
            $image_title = trim($_POST['image_title'] ?? '');
            $sort_order = (int)($_POST['sort_order'] ?? $index);
            
            // Add image to database
            $result = add_product_image_ctr($product_id, $relative_path, $is_primary, $image_alt, $image_title, $sort_order);
            
            if ($result === "success") {
                $file_result['success'] = true;
                $file_result['file_path'] = $relative_path;
                $file_result['file_name'] = $new_filename;
                $file_result['file_size'] = $file['size'];
                $file_result['file_type'] = $file_type;
                $file_result['is_primary'] = $is_primary;
                $file_result['uploaded_at'] = date('Y-m-d H:i:s');
                
                $uploaded_files[] = $file_result;
                $success_count++;
            } else {
                // If database insert failed, remove the uploaded file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $file_result['error'] = $result;
                $error_count++;
            }
            
        } catch (Exception $e) {
            // Clean up uploaded file if it exists
            if (isset($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
            $file_result['error'] = 'An error occurred while processing this file: ' . $e->getMessage();
            $error_count++;
        }
        
        $upload_results[] = $file_result;
    }
    
    // Prepare response
    $overall_success = $success_count > 0;
    $message = '';
    
    if ($success_count > 0 && $error_count === 0) {
        $message = $success_count === 1 ? 'Image uploaded successfully!' : "All {$success_count} images uploaded successfully!";
    } elseif ($success_count > 0 && $error_count > 0) {
        $message = "{$success_count} image(s) uploaded successfully, {$error_count} failed.";
    } else {
        $message = 'No images were uploaded successfully.';
    }
    
    if ($is_ajax) {
        echo json_encode([
            'success' => $overall_success,
            'message' => $message,
            'data' => [
                'total_files' => count($files),
                'success_count' => $success_count,
                'error_count' => $error_count,
                'uploaded_files' => $uploaded_files,
                'upload_results' => $upload_results,
                'is_multiple' => $is_multiple
            ]
        ]);
    } else {
        echo $message;
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Upload product image action error: " . $e->getMessage());
    
    // Clean up any uploaded files if they exist
    if (isset($uploaded_files) && !empty($uploaded_files)) {
        foreach ($uploaded_files as $uploaded_file) {
            if (isset($uploaded_file['file_path'])) {
                $full_path = __DIR__ . '/../' . $uploaded_file['file_path'];
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }
        }
    }
    
    // Return generic error message
    $error_msg = 'An error occurred while uploading the images. Please try again.';
    if ($is_ajax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo $error_msg;
    }
}
?>
