<?php
/**
 * Complete Login Debug Script
 * This will show us exactly what's happening during login
 */

echo "<h2>🔍 Complete Login Debug</h2>";
echo "<hr>";

// Test credentials
$test_email = 'admin@test.com';
$test_password = 'admin123';

echo "<h3>1. Testing Database Connection</h3>";

try {
    // Test direct PDO connection
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_authent;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Direct PDO connection: SUCCESS<br>";
    
    // Test if customer table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Customer table exists<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE customer");
        $columns = $stmt->fetchAll();
        echo "📋 Table structure:<br>";
        foreach ($columns as $col) {
            echo "&nbsp;&nbsp;- {$col['Field']} ({$col['Type']})<br>";
        }
        
        // Count total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer");
        $count = $stmt->fetch()['count'];
        echo "👥 Total users in database: $count<br>";
        
        // Check if our test user exists
        $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_email = ?");
        $stmt->execute([$test_email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✅ Test user found in database<br>";
            echo "📧 Email: {$user['customer_email']}<br>";
            echo "👤 Name: {$user['customer_name']}<br>";
            echo "🔑 Password hash: " . substr($user['customer_pass'], 0, 20) . "...<br>";
            echo "👑 Role: {$user['user_role']}<br>";
            
            // Test password verification
            if (password_verify($test_password, $user['customer_pass'])) {
                echo "✅ Password verification: SUCCESS<br>";
            } else {
                echo "❌ Password verification: FAILED<br>";
                echo "🔍 Testing with different passwords:<br>";
                
                // Test common variations
                $variations = ['admin123', 'Admin123', 'ADMIN123', 'admin', 'password'];
                foreach ($variations as $var) {
                    if (password_verify($var, $user['customer_pass'])) {
                        echo "&nbsp;&nbsp;✅ Password '$var' works!<br>";
                        break;
                    } else {
                        echo "&nbsp;&nbsp;❌ Password '$var' failed<br>";
                    }
                }
            }
        } else {
            echo "❌ Test user NOT found in database<br>";
            
            // Show all users
            $stmt = $pdo->query("SELECT customer_email, customer_name FROM customer");
            $users = $stmt->fetchAll();
            echo "📋 All users in database:<br>";
            foreach ($users as $u) {
                echo "&nbsp;&nbsp;- {$u['customer_email']} ({$u['customer_name']})<br>";
            }
        }
        
    } else {
        echo "❌ Customer table does NOT exist<br>";
        
        // Show all tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        echo "📋 All tables in database:<br>";
        foreach ($tables as $table) {
            echo "&nbsp;&nbsp;- " . array_values($table)[0] . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>2. Testing Application Classes</h3>";

try {
    // Test if we can include the classes
    require_once __DIR__ . '/settings/core.php';
    echo "✅ Core settings loaded<br>";
    
    require_once __DIR__ . '/class/db_class.php';
    echo "✅ DB class loaded<br>";
    
    require_once __DIR__ . '/class/user_class.php';
    echo "✅ User class loaded<br>";
    
    // Test database class connection
    $db = new db_class();
    echo "✅ DB class instantiated<br>";
    
    // Test user class
    $user = new user_class();
    echo "✅ User class instantiated<br>";
    
    // Test login method directly
    echo "🔍 Testing login method directly...<br>";
    $result = $user->login_customer($test_email, $test_password);
    
    if ($result === true) {
        echo "✅ User class login: SUCCESS<br>";
    } else {
        echo "❌ User class login: FAILED<br>";
        echo "📝 Error: " . $result . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Application class test failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>3. Testing Controller Function</h3>";

try {
    require_once __DIR__ . '/controller/user_controller.php';
    echo "✅ User controller loaded<br>";
    
    // Test controller function
    echo "🔍 Testing controller login function...<br>";
    $result = login_user_ctr($test_email, $test_password);
    
    if ($result === true) {
        echo "✅ Controller login: SUCCESS<br>";
    } else {
        echo "❌ Controller login: FAILED<br>";
        echo "📝 Error: " . $result . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>4. Session Test</h3>";

session_start();
echo "🔍 Current session data:<br>";
if (empty($_SESSION)) {
    echo "&nbsp;&nbsp;- Session is empty<br>";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "&nbsp;&nbsp;- $key: " . (is_array($value) ? json_encode($value) : $value) . "<br>";
    }
}

echo "<hr>";
echo "<h3>5. Recommendations</h3>";

if (!isset($user) || !$user) {
    echo "🔧 <strong>Action needed:</strong> Create the test user in database<br>";
    echo "&nbsp;&nbsp;Run: <code>php setup_local_db.php</code><br>";
} else {
    echo "✅ Database and user setup looks good<br>";
}

echo "<br>";
echo "<p><strong>🎯 Next Steps:</strong></p>";
echo "<p>1. Check the error messages above</p>";
echo "<p>2. If user doesn't exist, run the setup script</p>";
echo "<p>3. If password verification fails, we'll reset the password</p>";
echo "<p>4. If classes fail, we'll fix the class issues</p>";

echo "<hr>";
echo "<p><a href='public_html/view/user/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Login Page</a></p>";
echo "<p><a href='public_html/simple_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Use Simple Login</a></p>";
?>
