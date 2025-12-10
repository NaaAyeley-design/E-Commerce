<?php
// Simple check - no dependencies
echo "<h1>Quick Image Check</h1>";
echo "<p>If you can see this, the server is working!</p>";

$uploads = __DIR__ . '/uploads';
if (is_dir($uploads)) {
    $files = glob($uploads . '/**/*');
    $image_files = array_filter($files, function($f) {
        return is_file($f) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
    });
    
    echo "<p><strong>Found " . count($image_files) . " image files</strong></p>";
    echo "<ul>";
    foreach ($image_files as $file) {
        $rel = str_replace(__DIR__ . '/', '', $file);
        $rel = str_replace('\\', '/', $rel);
        echo "<li>" . htmlspecialchars($rel) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>Uploads folder not found!</p>";
}
?>

