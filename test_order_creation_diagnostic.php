<?php
/**
 * Order Creation Diagnostic Test
 * 
 * This script tests order creation independently to identify the exact issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/class/db_class.php';
require_once __DIR__ . '/class/order_class.php';
require_once __DIR__ . '/class/user_class.php';

echo "<h2>Order Creation Diagnostic Test</h2>";
echo "<pre>";

// Test 1: Database Connection
echo "=== TEST 1: Database Connection ===\n";
$db = new db_class();
$conn = $db->getConnection();
if ($conn) {
    echo "✓ Database connection successful\n";
    echo "   Connection type: " . get_class($conn) . "\n";
} else {
    echo "✗ Database connection FAILED\n";
    echo "   Check settings/db_cred.php\n";
    exit;
}

// Test 2: Check if tables exist
echo "\n=== TEST 2: Table Existence ===\n";
$tables_to_check = ['customer', 'orders', 'order_items', 'products'];
foreach ($tables_to_check as $table) {
    $check_sql = "SHOW TABLES LIKE '$table'";
    $result = $db->fetchRow($check_sql);
    if ($result) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' DOES NOT EXIST\n";
    }
}

// Test 3: Check customer table structure
echo "\n=== TEST 3: Customer Table Structure ===\n";
$customer_structure = $db->fetchAll("DESCRIBE customer");
if ($customer_structure) {
    echo "Customer table columns:\n";
    foreach ($customer_structure as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} else {
    echo "✗ Cannot describe customer table\n";
}

// Test 4: Check orders table structure
echo "\n=== TEST 4: Orders Table Structure ===\n";
$orders_structure = $db->fetchAll("DESCRIBE orders");
if ($orders_structure) {
    echo "Orders table columns:\n";
    foreach ($orders_structure as $col) {
        $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col['Default'] !== null ? " DEFAULT '{$col['Default']}'" : '';
        echo "  - {$col['Field']} ({$col['Type']}) $null$default\n";
    }
} else {
    echo "✗ Cannot describe orders table\n";
    echo "   Attempting to create table...\n";
    require_once __DIR__ . '/db/create_orders_tables.php';
    $orders_structure = $db->fetchAll("DESCRIBE orders");
    if ($orders_structure) {
        echo "✓ Orders table created successfully\n";
    } else {
        echo "✗ Failed to create orders table\n";
    }
}

// Test 5: Check if customer exists
echo "\n=== TEST 5: Customer Existence ===\n";
$user = new user_class();
$current_user_id = get_user_id();

if ($current_user_id) {
    echo "Current logged-in user ID: $current_user_id\n";
    $customer_data = $user->get_customer_by_id($current_user_id);
    if ($customer_data) {
        echo "✓ Customer exists:\n";
        echo "   ID: {$customer_data['customer_id']}\n";
        echo "   Name: {$customer_data['customer_name']}\n";
        echo "   Email: {$customer_data['customer_email']}\n";
        $test_customer_id = $current_user_id;
    } else {
        echo "✗ Customer with ID $current_user_id NOT FOUND\n";
        // Try to find any customer
        $any_customer = $db->fetchRow("SELECT customer_id FROM customer LIMIT 1");
        if ($any_customer) {
            $test_customer_id = $any_customer['customer_id'];
            echo "   Using customer ID {$test_customer_id} for testing\n";
        } else {
            echo "✗ NO CUSTOMERS IN DATABASE\n";
            exit;
        }
    }
} else {
    echo "✗ No user logged in\n";
    // Try to find any customer
    $any_customer = $db->fetchRow("SELECT customer_id FROM customer LIMIT 1");
    if ($any_customer) {
        $test_customer_id = $any_customer['customer_id'];
        echo "   Using customer ID {$test_customer_id} for testing\n";
    } else {
        echo "✗ NO CUSTOMERS IN DATABASE\n";
        exit;
    }
}

// Test 6: Test order creation directly
echo "\n=== TEST 6: Direct Order Creation (SQL) ===\n";
$test_total = 100.00;
$test_address = "Test Address, Test City, Test Country, test@example.com";
$test_sql = "INSERT INTO orders (customer_id, total_amount, shipping_address, payment_method, order_status) 
             VALUES (?, ?, ?, ?, ?)";
$test_params = [$test_customer_id, $test_total, $test_address, 'paystack', 'pending'];

echo "SQL: $test_sql\n";
echo "Params: customer_id=$test_customer_id, total=$test_total, address='$test_address'\n";

$test_stmt = $db->execute($test_sql, $test_params);
if ($test_stmt === false) {
    echo "✗ Direct SQL execution FAILED\n";
    $error_info = $conn->errorInfo();
    echo "   PDO Error Info: " . json_encode($error_info) . "\n";
    
    if (isset($error_info[1])) {
        $error_code = $error_info[1];
        if ($error_code == 1452) {
            echo "   ERROR: Foreign key constraint - Customer ID $test_customer_id does not exist\n";
        } elseif ($error_code == 1146) {
            echo "   ERROR: Table 'orders' does not exist\n";
        } elseif ($error_code == 1054) {
            echo "   ERROR: Column does not exist in orders table\n";
        }
    }
} else {
    $row_count = $test_stmt->rowCount();
    echo "✓ SQL execution successful - Rows affected: $row_count\n";
    
    // Get last insert ID
    $last_id = $conn->lastInsertId();
    if ($last_id) {
        echo "✓ Last insert ID: $last_id\n";
        
        // Verify order was created
        $verify = $db->fetchRow("SELECT * FROM orders WHERE order_id = ?", [$last_id]);
        if ($verify) {
            echo "✓ Order verified in database:\n";
            echo "   Order ID: {$verify['order_id']}\n";
            echo "   Customer ID: {$verify['customer_id']}\n";
            echo "   Total: {$verify['total_amount']}\n";
            echo "   Status: {$verify['order_status']}\n";
            
            // Clean up test order
            $db->execute("DELETE FROM orders WHERE order_id = ?", [$last_id]);
            echo "✓ Test order cleaned up\n";
        } else {
            echo "✗ Order NOT found in database after creation\n";
        }
    } else {
        echo "✗ lastInsertId() returned: " . var_export($last_id, true) . "\n";
    }
}

// Test 7: Test order creation using order_class
echo "\n=== TEST 7: Order Creation via order_class ===\n";
$order = new order_class();
$order_id = $order->create_order($test_customer_id, $test_total, $test_address, 'paystack');

if ($order_id && $order_id > 0) {
    echo "✓ Order created via order_class - ID: $order_id\n";
    
    // Verify order
    $verify = $db->fetchRow("SELECT * FROM orders WHERE order_id = ?", [$order_id]);
    if ($verify) {
        echo "✓ Order verified in database\n";
        
        // Clean up
        $db->execute("DELETE FROM orders WHERE order_id = ?", [$order_id]);
        echo "✓ Test order cleaned up\n";
    } else {
        echo "✗ Order NOT found in database\n";
    }
} else {
    echo "✗ Order creation via order_class FAILED\n";
    echo "   Returned: " . var_export($order_id, true) . "\n";
    echo "   Check PHP error logs for details\n";
}

// Test 8: Check foreign key constraints
echo "\n=== TEST 8: Foreign Key Constraints ===\n";
$fk_check = $db->fetchAll("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'orders'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
if ($fk_check) {
    echo "Foreign keys on orders table:\n";
    foreach ($fk_check as $fk) {
        echo "  - {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "No foreign keys found (or cannot query)\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "</pre>";

