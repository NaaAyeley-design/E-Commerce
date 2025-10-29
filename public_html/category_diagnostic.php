<?php
/**
 * Category Diagnostic Test
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Category Management Diagnostic</h1>";

echo "<h2>Database Configuration:</h2>";
echo "<p>DB_HOST: " . DB_HOST . "</p>";
echo "<p>DB_NAME: " . DB_NAME . "</p>";
echo "<p>DB_USERNAME: " . DB_USERNAME . "</p>";
echo "<p>DB_PASSWORD: " . (DB_PASSWORD ? '[SET]' : '[EMPTY]') . "</p>";

echo "<h2>Direct Database Test:</h2>";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Direct query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
    $count = $stmt->fetch()['total'];
    echo "<p>Categories in database (direct query): <strong>$count</strong></p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT cat_id, cat_name, user_id, created_at FROM categories ORDER BY cat_name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Categories from database:</h3><ul>";
        foreach ($categories as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}, User ID: {$cat['user_id']}, Created: {$cat['created_at']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Category Class Test:</h2>";
try {
    $category = new category_class();
    echo "<p style='color: green;'>✅ Category class created successfully</p>";
    
    $categories = $category->get_all_categories(1000, 0);
    echo "<p>Categories from get_all_categories(): <strong>" . count($categories) . "</strong></p>";
    
    if (count($categories) > 0) {
        echo "<h3>Categories from class method:</h3><ul>";
        foreach ($categories as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ No categories returned from get_all_categories()</p>";
        
        // Check connection
        echo "<h3>Connection Status:</h3>";
        if (isset($category->conn) && $category->conn !== null) {
            echo "<p style='color: green;'>✅ Connection object exists</p>";
        мощности } else {
            echo "<p style='color: red;'>❌ Connection object is NULL</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Category class error: " . $e->getMessage() . "</p>";
}
?>
