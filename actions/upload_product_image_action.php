<?php
/**
 * Upload Product Image Action (Enhanced for Bulk Upload)
 * 
 * Handles single and multiple product image upload requests with file validation and directory management
 */

// Start output buffering to catch any warnings/notices
ob_start();

// Temporarily disable error handler to catch real errors
$old_error_handler = set_error_handler(null);
$old_exception_handler = set_exception_handler(null);

// Include core settings and product controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/product_controller.php';

// Restore error handlers after includes
if ($old_error_handler) {
    set_error_handler($old_error_handler);
}
if ($old_exception_handler) {
    set_exception_handler($old_exception_handler);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    ob_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please log in to upload images']);
    ob_end_flush();
    exit;
}

// Check if user is admin
if (!is_admin()) {
    ob_clean();
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    ob_end_flush();
    exit;
}

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) || isset($_FILES) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// Set custom error handler for this script that doesn't interfere with our try-catch
set_error_handler(function($severity, $message, $file, $line) {
    // Only log warnings/notices, don't output - let our try-catch handle exceptions
    @error_log("Upload action warning: [$severity] $message in $file on line $line");
    return false; // Let PHP handle it normally, don't convert to exception
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);

// Also override exception handler to not interfere
set_exception_handler(function($exception) {
    // Log but don't output - our try-catch will handle it
    @error_log("Upload action exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    // Re-throw so our try-catch can catch it
    throw $exception;
});

try {
    // Validate CSRF token if available (optional for now)
    if (isset($_POST['csrf_token']) && function_exists('validate_csrf_token')) {
        $token_valid = validate_csrf_token($_POST['csrf_token']);
        if (!$token_valid) {
            $error_msg = 'Invalid security token. Please refresh the page and try again.';
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            ob_end_flush();
            exit;
        }
    }

    // Get user ID and product ID
    $user_id = $_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    // Allow product_id = 0 for new products (temporary uploads)
    // For existing products, verify they exist
    if ($product_id > 0) {
        $product = get_product_ctr($product_id);
        if (is_string($product) || !$product) {
            $error_msg = 'Product not found.';
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $error_msg]);
            } else {
                echo $error_msg;
            }
            exit;
        }
    }
    
    // Debug: Log what we received (only in development)
    if (defined('APP_ENV') && APP_ENV === 'development') {
        @error_log("Upload debug - POST keys: " . implode(', ', array_keys($_POST)));
        @error_log("Upload debug - FILES keys: " . implode(', ', array_keys($_FILES)));
    }
    
    // Check if files were uploaded
    if (!isset($_FILES['images']) && !isset($_FILES['image']) && !isset($_FILES['product_image'])) {
        $error_msg = 'No files uploaded. Received: ' . implode(', ', array_keys($_FILES));
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_msg, 'debug' => ['files_keys' => array_keys($_FILES), 'post' => $_POST]]);
        } else {
            echo $error_msg;
        }
        exit;
    }
    
    // Determine if this is a single file or multiple files upload
    $files = [];
    $is_multiple = false;
    
    // Check for images[] (can be single or multiple)
    if (isset($_FILES['images'])) {
        // Check if it's structured as an array (multiple files)
        if (is_array($_FILES['images']['name'])) {
            $is_multiple = true;
            $file_count = count($_FILES['images']['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if (isset($_FILES['images']['error'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
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
            // Single file sent as images[]
            if (isset($_FILES['images']['error']) && $_FILES['images']['error'] === UPLOAD_ERR_OK) {
                $files[] = $_FILES['images'];
            }
        }
    }
    
    // Check for single file inputs (backward compatibility)
    if (empty($files)) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $files[] = $_FILES['image'];
        } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $files[] = $_FILES['product_image'];
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
    
    // For new products (product_id = 0), use temp directory
    if ($product_id === 0) {
        $product_dir = $uploads_base . '/temp';
    } else {
        $user_dir = $uploads_base . '/u' . $user_id;
        $product_dir = $user_dir . '/p' . $product_id;
        
        // Create user directory if it doesn't exist (only for existing products)
        if (!is_dir($user_dir)) {
            if (!@mkdir($user_dir, 0755, true)) {
                $error_msg = 'Failed to create user directory: ' . $user_dir;
                if ($is_ajax) {
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error_msg]);
                    ob_end_flush();
                } else {
                    echo $error_msg;
                }
                exit;
            }
        }
    }
    
    // Create base uploads directory if it doesn't exist
    if (!is_dir($uploads_base)) {
        if (!@mkdir($uploads_base, 0755, true)) {
            $error_msg = 'Failed to create uploads directory: ' . $uploads_base;
            if ($is_ajax) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_msg]);
                ob_end_flush();
            } else {
                echo $error_msg;
            }
            exit;
        }
    }
    
    // Create product/temp directory if it doesn't exist
    if (!is_dir($product_dir)) {
        if (!@mkdir($product_dir, 0755, true)) {
            $error_msg = 'Failed to create product directory: ' . $product_dir;
            if ($is_ajax) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_msg]);
                ob_end_flush();
            } else {
                echo $error_msg;
            }
            exit;
        }
    }
    
    // Verify directory is writable
    if (!is_writable($product_dir)) {
        $error_msg = 'Product directory is not writable: ' . $product_dir;
        if ($is_ajax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            ob_end_flush();
        } else {
            echo $error_msg;
        }
        exit;
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
            if ($product_id === 0) {
                // For new products, save to temp directory
                $relative_path = 'uploads/temp/' . $new_filename;
            } else {
                $relative_path = 'uploads/u' . $user_id . '/p' . $product_id . '/' . $new_filename;
            }
            
            // Get additional parameters
            $is_primary = ($index === 0) && isset($_POST['is_primary']) && $_POST['is_primary'] === '1';
            $image_alt = trim($_POST['image_alt'] ?? '');
            $image_title = trim($_POST['image_title'] ?? '');
            $sort_order = (int)($_POST['sort_order'] ?? $index);
            
            // Only add to database if product exists (product_id > 0)
            // For new products, just save the file and return the path
            if ($product_id > 0) {
                $result = add_product_image_ctr($product_id, $relative_path, $is_primary, $image_alt, $image_title, $sort_order);
            } else {
                // For new products, just mark as success (file is saved, will be moved when product is created)
                $result = "success";
            }
            
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
    
    // Clear any output that might have been generated
    ob_clean();
    
    if ($is_ajax) {
        header('Content-Type: application/json');
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
    
    ob_end_flush();
    
} catch (Throwable $e) {
    // Log error with full details (catch both Exception and Error)
    $error_message = $e->getMessage();
    $error_file = $e->getFile();
    $error_line = $e->getLine();
    $error_trace = $e->getTraceAsString();
    $error_type = get_class($e);
    
    @error_log("Upload product image action error: " . $error_message);
    @error_log("Error type: " . $error_type);
    @error_log("Error in file: " . $error_file . " on line: " . $error_line);
    @error_log("Stack trace: " . $error_trace);
    
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
    
    // Always show details in development mode
    $show_details = defined('APP_ENV') && (APP_ENV === 'development' || APP_ENV !== 'production');
    
    // Build error message
    $error_msg = 'An error occurred while uploading the images.';
    if ($show_details) {
        $error_msg .= ' Error: ' . $error_message . ' (File: ' . basename($error_file) . ', Line: ' . $error_line . ')';
    } else {
        $error_msg .= ' Please try again.';
    }
    
    // Clear any output that might have been generated
    ob_clean();
    
    if ($is_ajax) {
        http_response_code(500);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false, 
            'message' => $error_msg
        ];
        
        // Always include debug info in development
        if ($show_details) {
            $response['debug'] = [
                'error' => $error_message,
                'type' => $error_type,
                'file' => basename($error_file),
                'line' => $error_line,
                'trace' => substr($error_trace, 0, 1000) // First 1000 chars of trace
            ];
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        echo $error_msg;
    }
    
    ob_end_flush();
    
    // Restore original error handler
    restore_error_handler();
}
?>
