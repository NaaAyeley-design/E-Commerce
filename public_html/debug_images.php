<?php
/**
 * Image Path Diagnostic Script
 * This script checks image paths in the database and verifies file existence
 */

require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/class/product_class.php';

// Get database connection
$product_class = new product_class();

// Get a few products to check
$sql = "SELECT product_id, product_title, product_image FROM products LIMIT 10";
$products = $product_class->fetchAll($sql, []);

echo "<h2>Image Path Diagnostic</h2>";
echo "<h3>BASE_URL: " . BASE_URL . "</h3>";
echo "<h3>ROOT_PATH: " . ROOT_PATH . "</h3>";
echo "<h3>ASSETS_URL: " . ASSETS_URL . "</h3>";
echo "<hr>";

echo "<h3>Products and Image Paths:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Product ID</th><th>Product Title</th><th>Database Path</th><th>Full File Path</th><th>File Exists?</th><th>Generated URL</th><th>Test Image</th></tr>";

foreach ($products as $product) {
    $db_path = $product['product_image'] ?? 'NULL';
    $full_path = '';
    $file_exists = false;
    $generated_url = '';
    
    if (!empty($db_path) && $db_path !== 'NULL') {
        // Clean the path
        $clean_path = ltrim($db_path, '/');
        
        // Check if it's an uploads path
        if (strpos($clean_path, 'uploads/') === 0) {
            $full_path = ROOT_PATH . '/' . $clean_path;
            $file_exists = file_exists($full_path);
            $generated_url = BASE_URL . '/' . $clean_path;
        } else {
            $full_path = ROOT_PATH . '/' . $clean_path;
            $file_exists = file_exists($full_path);
            $generated_url = BASE_URL . '/' . $clean_path;
        }
        
        // Also test with get_image_url function
        $test_url = get_image_url($db_path);
    } else {
        $test_url = get_image_url('');
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
    echo "<td>" . htmlspecialchars($product['product_title']) . "</td>";
    echo "<td>" . htmlspecialchars($db_path) . "</td>";
    echo "<td>" . htmlspecialchars($full_path) . "</td>";
    echo "<td>" . ($file_exists ? "YES" : "NO") . "</td>";
    echo "<td>" . htmlspecialchars($test_url) . "</td>";
    echo "<td>";
    if ($file_exists && !empty($test_url)) {
        echo "<img src='" . htmlspecialchars($test_url) . "' style='max-width: 100px; max-height: 100px;' onerror='this.style.border=\"2px solid red\"; this.alt=\"FAILED TO LOAD\";'>";
    } else {
        echo "N/A";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Check uploads directory structure
echo "<hr>";
echo "<h3>Uploads Directory Structure:</h3>";
echo "<pre>";
if (is_dir(ROOT_PATH . '/uploads')) {
    echo "✓ uploads directory exists at: " . ROOT_PATH . "/uploads\n";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(ROOT_PATH . '/uploads'),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relative_path = str_replace(ROOT_PATH . '/', '', $file->getPathname());
            echo "  File: " . $relative_path . " (" . filesize($file->getPathname()) . " bytes)\n";
        }
    }
} else {
    echo "✗ uploads directory does NOT exist at: " . ROOT_PATH . "/uploads\n";
}
echo "</pre>";

// Test get_image_url function directly
echo "<hr>";
echo "<h3>Testing get_image_url() function:</h3>";
$test_paths = [
    'uploads/u5/p1/product_1_1762382810.webp',
    'uploads/u5/p11/product_11_1762428669.jpeg',
    'uploads/temp/test.jpg',
    '',
    null
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Input Path</th><th>Generated URL</th><th>File Exists</th></tr>";
foreach ($test_paths as $test_path) {
    $url = get_image_url($test_path);
    $full_test_path = !empty($test_path) ? ROOT_PATH . '/' . ltrim($test_path, '/') : '';
    $exists = !empty($full_test_path) && file_exists($full_test_path);
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test_path ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($url) . "</td>";
    echo "<td>" . ($exists ? "YES" : "NO") . "</td>";
    echo "</tr>";
}
echo "</table>";

?>

