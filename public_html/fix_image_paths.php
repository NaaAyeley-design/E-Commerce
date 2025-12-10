<?php
/**
 * Fix Image Paths - Update database paths to match actual files
 */
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/class/product_class.php';

$product_class = new product_class();

// Get all products
$sql = "SELECT product_id, product_title, product_image FROM products";
$products = $product_class->fetchAll($sql, []);

echo "<h1>Fix Image Paths</h1>";
echo "<p>This script will check and fix image paths in the database to match actual files.</p>";
echo "<hr>";

$fixed = 0;
$not_found = 0;
$already_correct = 0;

foreach ($products as $product) {
    $product_id = $product['product_id'];
    $current_path = $product['product_image'] ?? '';
    
    echo "<h3>Product #{$product_id}: " . htmlspecialchars($product['product_title']) . "</h3>";
    
    if (empty($current_path)) {
        echo "<p style='color: orange;'>No image path in database. Skipping.</p><hr>";
        continue;
    }
    
    $clean_path = ltrim($current_path, '/');
    $full_path = ROOT_PATH . '/' . $clean_path;
    
    // Check if file exists at current path
    if (file_exists($full_path)) {
        echo "<p style='color: green;'>✓ File exists at: {$clean_path}</p>";
        $already_correct++;
    } else {
        echo "<p style='color: red;'>✗ File NOT found at: {$clean_path}</p>";
        
        // Try to find the file
        $found = false;
        
        // Check if it's in uploads/u{user_id}/p{product_id}/ format
        $uploads_dir = ROOT_PATH . '/uploads';
        if (is_dir($uploads_dir)) {
            // Look for files in user/product directories
            $user_dirs = glob($uploads_dir . '/u*/p' . $product_id . '/*');
            if (!empty($user_dirs)) {
                foreach ($user_dirs as $file_path) {
                    if (is_file($file_path)) {
                        $relative_path = str_replace(ROOT_PATH . '/', '', $file_path);
                        $relative_path = str_replace('\\', '/', $relative_path);
                        
                        // Check if this looks like a main product image
                        $filename = basename($file_path);
                        if (strpos($filename, 'product_') === 0 || strpos($filename, 'main_') === 0) {
                            echo "<p style='color: blue;'>Found file: {$relative_path}</p>";
                            
                            // Update database
                            $update_sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
                            $result = $product_class->execute($update_sql, [$relative_path, $product_id]);
                            
                            if ($result) {
                                echo "<p style='color: green;'>✓ Updated database path to: {$relative_path}</p>";
                                $fixed++;
                                $found = true;
                                break;
                            } else {
                                echo "<p style='color: red;'>✗ Failed to update database</p>";
                            }
                        }
                    }
                }
            }
            
            // If not found, try any file in product directory
            if (!$found) {
                $user_dirs = glob($uploads_dir . '/u*/p' . $product_id . '/*');
                if (!empty($user_dirs)) {
                    foreach ($user_dirs as $file_path) {
                        if (is_file($file_path)) {
                            $relative_path = str_replace(ROOT_PATH . '/', '', $file_path);
                            $relative_path = str_replace('\\', '/', $relative_path);
                            
                            echo "<p style='color: blue;'>Found file (using first available): {$relative_path}</p>";
                            
                            // Update database
                            $update_sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
                            $result = $product_class->execute($update_sql, [$relative_path, $product_id]);
                            
                            if ($result) {
                                echo "<p style='color: green;'>✓ Updated database path to: {$relative_path}</p>";
                                $fixed++;
                                $found = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        if (!$found) {
            echo "<p style='color: red;'>✗ Could not find image file for this product. You may need to re-upload.</p>";
            $not_found++;
        }
    }
    
    echo "<hr>";
}

echo "<h2>Summary:</h2>";
echo "<ul>";
echo "<li><strong>Already Correct:</strong> {$already_correct}</li>";
echo "<li><strong>Fixed:</strong> {$fixed}</li>";
echo "<li><strong>Not Found (need re-upload):</strong> {$not_found}</li>";
echo "</ul>";

if ($fixed > 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ Database paths have been updated! Try viewing your products now.</p>";
}

?>

