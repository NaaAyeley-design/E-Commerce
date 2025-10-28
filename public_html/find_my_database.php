<?php
/**
 * Database Finder - Web Version
 */
echo "<h1>Finding Your Database</h1>";
echo "<p>Looking for databases that contain your categories...</p>";

try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $stmt = $pdo->query('SHOW DATABASES');
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $found_databases = [];
    
    foreach ($databases as $db_name) {
        // Skip system databases
        if (in_array($db_name, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin', 'sys'])) {
            continue;
        }
        
        try {
            $pdo2 = new PDO("mysql:host=localhost;dbname=$db_name", 'root', '');
            
            // Check if categories table exists
            $stmt2 = $pdo2->query("SHOW TABLES LIKE 'categories'");
            if ($stmt2->rowCount() > 0) {
                // Get categories count
                $stmt3 = $pdo2->query('SELECT COUNT(*) as count FROM categories');
                $count = $stmt3->fetch()['count'];
                
                if ($count > 0) {
                    // Get sample categories
                    $stmt4 = $pdo2->query('SELECT cat_name FROM categories ORDER BY cat_name LIMIT 5');
                    $categories = $stmt4->fetchAll(PDO::FETCH_COLUMN);
                    
                    $found_databases[] = [
                        'name' => $db_name,
                        'count' => $count,
                        'categories' => $categories
                    ];
                }
            }
        } catch (Exception $e) {
            // Skip databases that can't be accessed
        }
    }
    
    if (empty($found_databases)) {
        echo "<p style='color: red;'>❌ No databases found with categories!</p>";
        echo "<p>Make sure:</p>";
        echo "<ul>";
        echo "<li>MySQL service is running in XAMPP</li>";
        echo "<li>Your categories table exists</li>";
        echo "<li>You have the right database name</li>";
        echo "</ul>";
    } else {
        echo "<h2>Found Databases with Categories:</h2>";
        foreach ($found_databases as $db) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h3>Database: <strong>{$db['name']}</strong></h3>";
            echo "<p>Categories count: <strong>{$db['count']}</strong></p>";
            echo "<p>Sample categories: " . implode(', ', $db['categories']) . "</p>";
            
            // Check if this matches your real categories
            $real_categories = ['bag', 'roof', 'hair', 'crate', 'clock', 'rat'];
            $matches = array_intersect(array_map('strtolower', $db['categories']), $real_categories);
            
            if (count($matches) > 0) {
                echo "<p style='color: green; font-weight: bold; background: yellow; padding: 5px;'>";
                echo "🎉 THIS IS YOUR DATABASE! Found matching categories: " . implode(', ', $matches);
                echo "</p>";
                echo "<p><strong>Update settings/db_cred.php with:</strong></p>";
                echo "<code>define('DB_NAME', '{$db['name']}');</code>";
            }
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Cannot connect to MySQL: " . $e->getMessage() . "</p>";
    echo "<p>Make sure MySQL service is running in XAMPP Control Panel</p>";
}
?>
