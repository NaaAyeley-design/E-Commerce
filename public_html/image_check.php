<?php
/**
 * Simple Image Check - No dependencies, just basic file check
 */
echo "<h1>Image Files Check</h1>";

$base_dir = __DIR__;
$uploads_dir = $base_dir . '/uploads';

echo "<p><strong>Base Directory:</strong> " . htmlspecialchars($base_dir) . "</p>";
echo "<p><strong>Uploads Directory:</strong> " . htmlspecialchars($uploads_dir) . "</p>";
echo "<p><strong>Uploads Exists:</strong> " . (is_dir($uploads_dir) ? "YES" : "NO") . "</p>";

if (is_dir($uploads_dir)) {
    echo "<h2>Image Files Found:</h2>";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploads_dir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>File Path</th><th>Size</th><th>Direct URL</th></tr>";
    
    $file_count = 0;
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $file_count++;
            $relative_path = str_replace($base_dir . '/', '', $file->getPathname());
            $relative_path = str_replace('\\', '/', $relative_path);
            
            // Try to construct URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script_path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $url = $protocol . $host . $script_path . '/' . $relative_path;
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($relative_path) . "</td>";
            echo "<td>" . number_format(filesize($file->getPathname()) / 1024, 2) . " KB</td>";
            echo "<td><a href='" . htmlspecialchars($url) . "' target='_blank'>Test</a></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    echo "<p><strong>Total files:</strong> " . $file_count . "</p>";
} else {
    echo "<p style='color: red;'>Uploads directory does not exist!</p>";
}

// Show server info
echo "<hr>";
echo "<h2>Server Information:</h2>";
echo "<ul>";
echo "<li><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "</li>";
echo "<li><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "</li>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "</li>";
echo "<li><strong>DOCUMENT_ROOT:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "</li>";
echo "</ul>";

?>

