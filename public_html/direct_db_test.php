<?php
/**
 * Direct Database Test - No Fallbacks
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Direct Database Test</h1>";
echo "<p>Database: " . DB_NAME . "</p>";
echo "<p>Host: " . DB_HOST . "</p>";
echo "<p>User: " . DB_USERNAME . "</p>";

try {
    $db = new db_class();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Direct query to get categories
    $sql = "SELECT c.cat_id, c.cat_name, c.created_at, c.updated_at,
                   u.customer_name as creator_name
           FROM categories c
           LEFT JOIN customer u ON c.user_id = u.customer_id
           ORDER BY c.cat_name ASC";
    
    $result = $db->fetchAll($sql);
    
    echo "<p>Categories found: " . count($result) . "</p>";
    
    if (count($result) > 0) {
        echo "<h3>Your Real Categories:</h3><ul>";
        foreach ($result as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}, Created: {$cat['created_at']}</li>";
        }
        echo "</ul>";
        
        echo "<p style='color: green; font-weight: bold; background: yellow; padding: 10px;'>";
        echo "🎉 SUCCESS! Found " . count($result) . " real categories from your database!";
        echo "</p>";
    } else {
        echo "<p style='color: red;'>❌ No categories found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>This means the database connection is failing.</p>";
}
?>
