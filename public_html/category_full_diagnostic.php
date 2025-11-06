<?php
/**
 * Comprehensive Category Diagnostic
 */
require_once __DIR__ . '/settings/core.php';

echo "<h1>Category Diagnostic - Complete Test</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>";

echo "<h2>1. Database Configuration:</h2>";
echo "<pre>";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USERNAME: " . DB_USERNAME . "\n";
echo "DB_PASSWORD: " . (DB_PASSWORD ? '[SET]' : '[EMPTY]') . "\n";
echo "DB_CHARSET: " . DB_CHARSET . "\n";
echo "</pre>";

echo "<h2>2. Direct PDO Connection Test:</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USERNAME,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "<p class='success'>✅ Direct PDO connection successful!</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $count = $stmt->fetch()['count'];
    echo "<p class='info'>Categories in database: <strong>$count</strong></p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT cat_id, cat_name, user_id, created_at FROM categories ORDER BY cat_name ASC LIMIT 10");
        $cats = $stmt->fetchAll();
        echo "<p class='success'>✅ Query successful! Sample categories:</p><ul>";
        foreach ($cats as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}, User: {$cat['user_id']}</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ PDO Error: " . $e->getMessage() . "</p>";
    echo "<p class='error'>Error Code: " . $e->getCode() . "</p>";
}

echo "<h2>3. db_class Connection Test:</h2>";
try {
    $db = new db_class();
    $conn = $db->getConnection();
    
    if ($conn === null) {
        echo "<p class='error'>❌ db_class connection is NULL</p>";
    } else {
        echo "<p class='success'>✅ db_class connection exists</p>";
        
        // Test query through db_class
        $result = $db->fetchAll("SELECT COUNT(*) as count FROM categories");
        if ($result !== false && !empty($result)) {
            echo "<p class='success'>✅ db_class query successful! Count: " . $result[0]['count'] . "</p>";
        } else {
            echo "<p class='error'>❌ db_class query returned false or empty</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ db_class error: " . $e->getMessage() . "</p>";
}

echo "<h2>4. category_class Test:</h2>";
try {
    $category = new category_class();
    
    // Check connection
    if (!isset($category->conn) || $category->conn === null) {
        echo "<p class='error'>❌ category_class connection is NULL</p>";
        echo "<p class='info'>Attempting to use connect() method...</p>";
        
        try {
            // Use reflection to call private connect method
            $reflection = new ReflectionClass($category);
            $method = $reflection->getMethod('connect');
            $method->setAccessible(true);
            $method->invoke($category);
            
            if ($category->conn !== null) {
                echo "<p class='success'>✅ Connection established after calling connect()</p>";
            } else {
                echo "<p class='error'>❌ Connection still NULL after connect()</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ connect() method failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>✅ category_class connection exists</p>";
    }
    
    // Test get_all_categories
    echo "<p class='info'>Testing get_all_categories()...</p>";
    $categories = $category->get_all_categories(1000, 0);
    
    if (is_array($categories)) {
        echo "<p class='success'>✅ get_all_categories() returned array with " . count($categories) . " items</p>";
        
        if (count($categories) > 0) {
            echo "<p class='success'>Categories retrieved:</p><ul>";
            foreach (array_slice($categories, 0, 5) as $cat) {
                echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='error'>❌ Array is empty - no categories returned</p>";
        }
    } else {
        echo "<p class='error'>❌ get_all_categories() did not return an array. Type: " . gettype($categories) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ category_class error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='view/admin/categories.php'>Go to Category Management Page</a></p>";
?>

