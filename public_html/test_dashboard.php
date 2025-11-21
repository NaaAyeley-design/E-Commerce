<?php
/**
 * Quick test to verify dashboard file is accessible
 */
echo "<h1>Dashboard File Test</h1>";
echo "<p>If you can see this, PHP is working.</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>File last modified: " . date('Y-m-d H:i:s', filemtime(__FILE__)) . "</p>";

// Check if dashboard file exists
$dashboard_file = __DIR__ . '/view/user/dashboard.php';
if (file_exists($dashboard_file)) {
    echo "<p style='color: green;'>✓ Dashboard file exists</p>";
    echo "<p>Dashboard file size: " . filesize($dashboard_file) . " bytes</p>";
    echo "<p>Dashboard file last modified: " . date('Y-m-d H:i:s', filemtime($dashboard_file)) . "</p>";
} else {
    echo "<p style='color: red;'>✗ Dashboard file NOT found</p>";
}

// Check dashboard file content
$content = file_get_contents($dashboard_file);
if (strpos($content, 'Cultural Heritage Redesign') !== false) {
    echo "<p style='color: green;'>✓ Dashboard contains new content (Cultural Heritage Redesign)</p>";
} else {
    echo "<p style='color: red;'>✗ Dashboard does NOT contain new content</p>";
}

if (strpos($content, 'cultural-dashboard') !== false) {
    echo "<p style='color: green;'>✓ Dashboard contains cultural-dashboard class</p>";
} else {
    echo "<p style='color: red;'>✗ Dashboard does NOT contain cultural-dashboard class</p>";
}
?>





