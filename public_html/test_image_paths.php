<?php
/**
 * Simple Image Path Test
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Image Path Test</h1>";
echo "<p><strong>BASE_URL:</strong> " . BASE_URL . "</p>";
echo "<p><strong>ROOT_PATH:</strong> " . ROOT_PATH . "</p>";
echo "<p><strong>ASSETS_URL:</strong> " . ASSETS_URL . "</p>";

// Test a known image path
$test_path = "uploads/u5/p1/product_1_1762382810.webp";
$full_path = ROOT_PATH . '/' . $test_path;
$url = BASE_URL . '/' . $test_path;

echo "<hr>";
echo "<h2>Test Image Path</h2>";
echo "<p><strong>Database Path:</strong> " . htmlspecialchars($test_path) . "</p>";
echo "<p><strong>Full File Path:</strong> " . htmlspecialchars($full_path) . "</p>";
echo "<p><strong>File Exists:</strong> " . (file_exists($full_path) ? "YES" : "NO") . "</p>";
echo "<p><strong>Generated URL:</strong> " . htmlspecialchars($url) . "</p>";

if (file_exists($full_path)) {
    echo "<p><strong>Test Image:</strong></p>";
    echo "<img src='" . htmlspecialchars($url) . "' style='max-width: 300px; border: 2px solid #000;' onerror='alert(\"Image failed to load! URL: \" + this.src);'>";
} else {
    echo "<p style='color: red;'><strong>ERROR: File does not exist!</strong></p>";
}

// Test get_image_url function
echo "<hr>";
echo "<h2>get_image_url() Function Test</h2>";
$function_url = get_image_url($test_path);
echo "<p><strong>Function Result:</strong> " . htmlspecialchars($function_url) . "</p>";
if (file_exists($full_path)) {
    echo "<p><strong>Test Image (from function):</strong></p>";
    echo "<img src='" . htmlspecialchars($function_url) . "' style='max-width: 300px; border: 2px solid #000;' onerror='alert(\"Image failed to load! URL: \" + this.src);'>";
}

// List all files in uploads
echo "<hr>";
echo "<h2>Files in uploads directory:</h2>";
$uploads_dir = ROOT_PATH . '/uploads';
if (is_dir($uploads_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploads_dir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    echo "<ul>";
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relative = str_replace(ROOT_PATH . '/', '', $file->getPathname());
            $file_url = BASE_URL . '/' . $relative;
            echo "<li>";
            echo htmlspecialchars($relative) . " (" . filesize($file->getPathname()) . " bytes)";
            echo " - <a href='" . htmlspecialchars($file_url) . "' target='_blank'>View</a>";
            echo "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Uploads directory does not exist!</p>";
}

?>

