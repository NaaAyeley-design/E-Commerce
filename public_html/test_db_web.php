<?php
/**
 * Web-based Database Test
 * Access this through: http://localhost:8000/test_db_web.php
 */

require_once __DIR__ . '/settings/core.php';

echo "<h1>Web Database Test</h1>";

echo "<h2>Environment:</h2>";
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'NOT DEFINED') . "<br>";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";

echo "<h2>Database Connection Test:</h2>";
try {
    $db = new db_class();
    echo "✅ Database class created successfully<br>";
    
    // Test basic query
    $result = $db->fetchAll('SELECT COUNT(*) as total FROM categories');
    echo "Categories count: " . $result[0]['total'] . "<br>";
    
    // Get actual categories
    $categories = $db->fetchAll('SELECT cat_id, cat_name, user_id, created_at FROM categories ORDER BY cat_name ASC');
    echo "<h3>Categories from database:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>User ID</th><th>Created</th></tr>";
    foreach ($categories as $cat) {
        echo "<tr>";
        echo "<td>" . $cat['cat_id'] . "</td>";
        echo "<td>" . htmlspecialchars($cat['cat_name']) . "</td>";
        echo "<td>" . $cat['user_id'] . "</td>";
        echo "<td>" . $cat['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Category Class Test:</h2>";
try {
    $category = new category_class();
    echo "✅ Category class created successfully<br>";
    
    // Test get_all_categories method
    $categories = $category->get_all_categories(10, 0);
    echo "get_all_categories result count: " . count($categories) . "<br>";
    
    if (count($categories) > 0) {
        echo "<h3>Categories from get_all_categories:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Created</th><th>Creator</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr>";
            echo "<td>" . $cat['cat_id'] . "</td>";
            echo "<td>" . htmlspecialchars($cat['cat_name']) . "</td>";
            echo "<td>" . $cat['created_at'] . "</td>";
            echo "<td>" . ($cat['creator_name'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Category class error: " . $e->getMessage() . "<br>";
}
?>
