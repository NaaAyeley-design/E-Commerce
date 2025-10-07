<?php
/**
 * Test URL Function
 */

require_once __DIR__ . '/settings/core.php';

echo "<h2>🔍 URL Function Test</h2>";

echo "<h3>URL Function Results:</h3>";
echo "url('../actions/process_login.php'): " . url('../actions/process_login.php') . "<br>";
echo "BASE_URL: " . BASE_URL . "<br>";
echo "ASSETS_URL: " . ASSETS_URL . "<br>";

echo "<h3>File Paths:</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "ROOT_PATH: " . ROOT_PATH . "<br>";
echo "PUBLIC_PATH: " . PUBLIC_PATH . "<br>";

echo "<h3>Test Form Action:</h3>";
echo "Form would submit to: " . url('../actions/process_login.php') . "<br>";

echo "<h3>Direct Test:</h3>";
echo "Direct path: " . PUBLIC_PATH . "/../actions/process_login.php<br>";
echo "File exists: " . (file_exists(PUBLIC_PATH . "/../actions/process_login.php") ? "YES" : "NO") . "<br>";

echo "<hr>";
echo "<p><strong>🎯 Test Complete</strong></p>";
?>
