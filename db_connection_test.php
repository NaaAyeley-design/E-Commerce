<?php
/**
 * Comprehensive Database Connection Test
 * This will help identify the exact connection issue
 */

echo "<h1>Database Connection Diagnostic</h1>";

// Test 1: Check if we can connect to MySQL server at all
echo "<h2>Test 1: Basic MySQL Connection</h2>";
try {
    $pdo = new PDO("mysql:host=localhost", 'root', '');
    echo "✅ Successfully connected to MySQL server<br>";
} catch (Exception $e) {
    echo "❌ Cannot connect to MySQL server: " . $e->getMessage() . "<br>";
    echo "<strong>Solution:</strong> Start MySQL service in XAMPP Control Panel<br>";
    exit;
}

// Test 2: Check if database exists
echo "<h2>Test 2: Database Existence</h2>";
$databases = ['shoppn', 'ecommerce_authent', 'ecommerce'];
foreach ($databases as $db_name) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$db_name", 'root', '');
        echo "✅ Database '$db_name' exists and is accessible<br>";
        
        // Test 3: Check categories table
        echo "<h3>Test 3: Categories Table in '$db_name'</h3>";
        $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Categories table exists<br>";
            
            // Get categories count
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
            $count = $stmt->fetch()['total'];
            echo "Categories count: $count<br>";
            
            if ($count > 0) {
                // Get actual categories
                $stmt = $pdo->query("SELECT cat_id, cat_name, user_id FROM categories ORDER BY cat_name ASC");
                $categories = $stmt->fetchAll();
                echo "Categories:<br>";
                echo "<ul>";
                foreach ($categories as $cat) {
                    echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}, User: {$cat['user_id']}</li>";
                }
                echo "</ul>";
                
                echo "<h3>✅ FOUND WORKING DATABASE: '$db_name'</h3>";
                echo "<strong>This is the database we should use!</strong><br>";
                break;
            }
        } else {
            echo "❌ Categories table does not exist<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database '$db_name' error: " . $e->getMessage() . "<br>";
    }
}

// Test 4: Check current application configuration
echo "<h2>Test 4: Current Application Configuration</h2>";
require_once __DIR__ . '/settings/core.php';
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'NOT DEFINED') . "<br>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
echo "DB_USERNAME: " . (defined('DB_USERNAME') ? DB_USERNAME : 'NOT DEFINED') . "<br>";
echo "DB_PASSWORD: " . (defined('DB_PASSWORD') ? (DB_PASSWORD ? '[SET]' : '[EMPTY]') : 'NOT DEFINED') . "<br>";

// Test 5: Test application database class
echo "<h2>Test 5: Application Database Class</h2>";
try {
    $db = new db_class();
    echo "✅ Database class created successfully<br>";
    
    $result = $db->fetchAll('SELECT COUNT(*) as total FROM categories');
    echo "Categories count via app: " . $result[0]['total'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Application database class error: " . $e->getMessage() . "<br>";
}

// Test 6: Test category class
echo "<h2>Test 6: Category Class</h2>";
try {
    $category = new category_class();
    echo "✅ Category class created successfully<br>";
    
    $categories = $category->get_all_categories(10, 0);
    echo "Categories from get_all_categories: " . count($categories) . "<br>";
    
    if (count($categories) > 0) {
        echo "Sample categories:<br>";
        echo "<ul>";
        foreach (array_slice($categories, 0, 3) as $cat) {
            echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "❌ Category class error: " . $e->getMessage() . "<br>";
}
?>
