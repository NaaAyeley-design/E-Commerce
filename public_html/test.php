<?php
/**
 * Simple test file to verify Apache and PHP are working
 */
echo "<h1>✅ Apache and PHP are working!</h1>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p>Script name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test if core.php can be loaded
$core_path = __DIR__ . '/../settings/core.php';
if (file_exists($core_path)) {
    echo "<p>✅ Core settings file found</p>";
} else {
    echo "<p>❌ Core settings file NOT found at: $core_path</p>";
}

// Test database connection
if (file_exists(__DIR__ . '/../settings/db_cred.php')) {
    echo "<p>✅ Database credentials file found</p>";
} else {
    echo "<p>❌ Database credentials file NOT found</p>";
}
?>


