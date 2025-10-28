<?php
/**
 * Quick Database Test - Web Version
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Database Connection Test</h1>";
echo "<p>Testing connection to database: " . DB_NAME . "</p>";

try {
    $db = new db_class();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test categories
    $category = new category_class();
    $categories = $category->get_all_categories(10, 0);
    
    echo "<p>Categories found: " . count($categories) . "</p>";
    
    if (count($categories) > 0) {
        echo "<h3>Categories:</h3><ul>";
        foreach ($categories as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}</li>";
        }
        echo "</ul>";
        
        if (count($categories) >= 6) {
            echo "<p style='color: green; font-weight: bold;'>🎉 SUCCESS! Found " . count($categories) . " categories - this matches your database!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No categories found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Try changing the database name in settings/db_cred.php</p>";
}
?>
