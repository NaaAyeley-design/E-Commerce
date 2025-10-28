<?php
/**
 * Database Name Finder
 * This will test multiple common database names to find the right one
 */
echo "<h1>Database Name Finder</h1>";
echo "<p>Testing common database names to find where your categories are stored...</p>";

$common_names = ['shoppn', 'ecommerce', 'ecommerce_authent', 'ecommerce_2025A_naa_aryee', 'test', 'mysql'];

foreach ($common_names as $db_name) {
    echo "<h3>Testing: $db_name</h3>";
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$db_name", 'root', '');
        echo "<p style='color: green;'>✅ Database '$db_name' exists</p>";
        
        // Check for categories table
        $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Categories table exists</p>";
            
            // Get count
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
            $count = $stmt->fetch()['total'];
            echo "<p>Categories count: $count</p>";
            
            if ($count > 0) {
                // Get sample categories
                $stmt = $pdo->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name ASC LIMIT 5");
                $categories = $stmt->fetchAll();
                echo "<p>Sample categories:</p><ul>";
                foreach ($categories as $cat) {
                    echo "<li>ID: {$cat['cat_id']}, Name: {$cat['cat_name']}</li>";
                }
                echo "</ul>";
                
                if ($count >= 6) {
                    echo "<p style='color: green; font-weight: bold; background: yellow; padding: 10px;'>🎉 FOUND IT! Database '$db_name' has $count categories - this matches your screenshot!</p>";
                    echo "<p><strong>Update settings/db_cred.php to use: define('DB_NAME', '$db_name');</strong></p>";
                }
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Categories table does not exist</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database '$db_name' error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}
?>
