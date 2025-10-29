<?php
/**
 * Simple Category Test - Direct Database Query
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Category Retrieval Test</h1>";

// Test 1: Direct PDO query
echo "<h2>Test 1: Direct Database Query</h2>";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT cat_id, cat_name, user_id, created_at FROM categories ORDER BY cat_name ASC");
    $direct_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Direct query successful! Found " . count($direct_cats) . " categories</p>";
    
    if (count($direct_cats) > 0) {
        echo "<ul>";
        foreach ($direct_cats as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}, User: {$cat['user_id']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Direct query failed: " . $e->getMessage() . "</p>";
}

// Test 2: Using category_class
echo "<h2>Test 2: Using category_class</h2>";
try {
    $category = new category_class();
    $cats = $category->get_all_categories(1000, 0);
    
    echo "<p>Categories from get_all_categories(): " . count($cats) . "</p>";
    
    if (count($cats) > 0) {
        echo "<ul>";
        foreach ($cats as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}</li>";
        }
        echo "</ul>";
        echo "<p style='color: green;'>✅ Category class working!</p>";
    } else {
        echo "<p style='color: red;'>❌ No categories returned from category_class</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Category class error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='view/admin/categories.php'>Go to Category Management Page</a></p>";
?>
