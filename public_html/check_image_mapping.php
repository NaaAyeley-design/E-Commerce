<?php
/**
 * Check Image Mapping - Verify database paths match actual files
 */
require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/class/product_class.php';

$product_class = new product_class();

// Get all products with their image paths
$sql = "SELECT product_id, product_title, product_image FROM products WHERE product_image IS NOT NULL AND product_image != ''";
$products = $product_class->fetchAll($sql, []);

echo "<h1>Image File Mapping Check</h1>";
echo "<p><strong>BASE_URL:</strong> " . BASE_URL . "</p>";
echo "<p><strong>ROOT_PATH:</strong> " . ROOT_PATH . "</p>";
echo "<hr>";

echo "<h2>Products and Image Status:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Product ID</th><th>Product Title</th><th>Database Path</th><th>File Exists?</th><th>Full File Path</th><th>Generated URL</th><th>Action</th>";
echo "</tr>";

$fixed_count = 0;
$missing_count = 0;

foreach ($products as $product) {
    $product_id = $product['product_id'];
    $db_path = $product['product_image'];
    $clean_path = ltrim($db_path, '/');
    
    // Check if file exists
    $full_path = ROOT_PATH . '/' . $clean_path;
    $file_exists = file_exists($full_path);
    
    // Generate URL
    $image_url = get_image_url($db_path);
    
    // Check if file exists in alternative locations
    $alt_paths = [];
    if (strpos($clean_path, 'uploads/') === 0) {
        // Check root uploads folder
        $root_uploads_path = dirname(ROOT_PATH) . '/' . $clean_path;
        if (file_exists($root_uploads_path)) {
            $alt_paths[] = "Root uploads: " . $root_uploads_path;
        }
    }
    
    $row_color = $file_exists ? '#d4edda' : '#f8d7da';
    
    echo "<tr style='background: " . $row_color . ";'>";
    echo "<td>" . htmlspecialchars($product_id) . "</td>";
    echo "<td>" . htmlspecialchars($product['product_title']) . "</td>";
    echo "<td>" . htmlspecialchars($db_path) . "</td>";
    echo "<td>" . ($file_exists ? "✓ YES" : "✗ NO") . "</td>";
    echo "<td>" . htmlspecialchars($full_path) . "</td>";
    echo "<td><a href='" . htmlspecialchars($image_url) . "' target='_blank'>" . htmlspecialchars($image_url) . "</a></td>";
    echo "<td>";
    
    if (!$file_exists && !empty($alt_paths)) {
        echo "<span style='color: orange;'>File found in alternative location</span><br>";
        foreach ($alt_paths as $alt) {
            echo "<small>" . htmlspecialchars($alt) . "</small><br>";
        }
    } elseif (!$file_exists) {
        echo "<span style='color: red;'>File missing - needs re-upload</span>";
        $missing_count++;
    } else {
        echo "<span style='color: green;'>OK</span>";
    }
    echo "</td>";
    echo "</tr>";
    
    if (!$file_exists) {
        $missing_count++;
    } else {
        $fixed_count++;
    }
}

echo "</table>";

echo "<hr>";
echo "<h2>Summary:</h2>";
echo "<ul>";
echo "<li><strong>Total Products with Images:</strong> " . count($products) . "</li>";
echo "<li><strong>Images Found:</strong> " . $fixed_count . "</li>";
echo "<li><strong>Images Missing:</strong> " . $missing_count . "</li>";
echo "</ul>";

// List all actual image files
echo "<hr>";
echo "<h2>All Image Files in public_html/uploads:</h2>";
$uploads_dir = ROOT_PATH . '/uploads';
if (is_dir($uploads_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploads_dir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    echo "<ul>";
    $file_count = 0;
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relative = str_replace(ROOT_PATH . '/', '', $file->getPathname());
            echo "<li>" . htmlspecialchars($relative) . " (" . number_format(filesize($file->getPathname()) / 1024, 2) . " KB)</li>";
            $file_count++;
        }
    }
    echo "</ul>";
    echo "<p><strong>Total files:</strong> " . $file_count . "</p>";
} else {
    echo "<p style='color: red;'>Uploads directory does not exist!</p>";
}

?>

