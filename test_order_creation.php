<?php
/**
 * Test Order Creation
 * Run this to diagnose why order creation is failing
 */

require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/settings/db_cred.php';
require_once __DIR__ . '/class/order_class.php';
require_once __DIR__ . '/class/user_class.php';

header('Content-Type: text/plain');

echo "=== ORDER CREATION DIAGNOSTIC ===\n\n";

// 1. Check database connection
echo "1. Testing database connection...\n";
$order = new order_class();
$conn = $order->getConnection();

if (!$conn) {
    echo "✗ Database connection FAILED\n";
    exit;
}
echo "✓ Database connected\n\n";

// 2. Check if orders table exists
echo "2. Checking if orders table exists...\n";
$table_check = $order->fetchRow("SHOW TABLES LIKE 'orders'");
if (!$table_check) {
    echo "✗ Orders table does NOT exist\n";
    echo "SOLUTION: Run db/create_orders_tables.php or db/schema.sql\n";
    exit;
}
echo "✓ Orders table exists\n\n";

// 3. Check table structure
echo "3. Checking orders table structure...\n";
$columns = $order->fetchAll("DESCRIBE orders");
echo "Columns in orders table:\n";
foreach ($columns as $col) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}
echo "\n";

// 4. Check if customer table exists
echo "4. Checking if customer table exists...\n";
$customer_table = $order->fetchRow("SHOW TABLES LIKE 'customer'");
if (!$customer_table) {
    echo "✗ Customer table does NOT exist\n";
    exit;
}
echo "✓ Customer table exists\n\n";

// 5. Get a test customer ID
echo "5. Getting a test customer...\n";
$user = new user_class();
$customers = $user->get_all_customers();
if (empty($customers)) {
    echo "✗ No customers found in database\n";
    echo "SOLUTION: Create a customer account first\n";
    exit;
}
$test_customer = $customers[0];
$test_customer_id = $test_customer['customer_id'];
echo "✓ Found test customer: ID=$test_customer_id, Name=" . ($test_customer['customer_name'] ?? 'N/A') . "\n\n";

// 6. Test order creation
echo "6. Testing order creation...\n";
echo "Parameters:\n";
echo "  - customer_id: $test_customer_id\n";
echo "  - total_amount: 10.00\n";
echo "  - shipping_address: Test Address\n";
echo "  - payment_method: test\n\n";

$order_id = $order->create_order($test_customer_id, 10.00, "Test Address, Test City, Test Country", 'test');

if ($order_id && $order_id > 0) {
    echo "✓ Order created successfully! Order ID: $order_id\n\n";
    
    // 7. Verify order was inserted
    echo "7. Verifying order in database...\n";
    $created_order = $order->fetchRow("SELECT * FROM orders WHERE order_id = ?", [$order_id]);
    if ($created_order) {
        echo "✓ Order found in database:\n";
        echo "  - Order ID: " . $created_order['order_id'] . "\n";
        echo "  - Customer ID: " . $created_order['customer_id'] . "\n";
        echo "  - Total Amount: " . $created_order['total_amount'] . "\n";
        echo "  - Status: " . $created_order['order_status'] . "\n";
        
        // Clean up test order
        echo "\n8. Cleaning up test order...\n";
        $order->execute("DELETE FROM orders WHERE order_id = ?", [$order_id]);
        echo "✓ Test order deleted\n";
    } else {
        echo "✗ Order not found in database (even though create_order returned ID)\n";
    }
} else {
    echo "✗ Order creation FAILED\n";
    echo "Returned: " . var_export($order_id, true) . "\n\n";
    
    // Check for PDO errors
    $conn = $order->getConnection();
    if ($conn) {
        $error_info = $conn->errorInfo();
        echo "PDO Error Info:\n";
        print_r($error_info);
        
        if (isset($error_info[1])) {
            $error_code = $error_info[1];
            echo "\nError Code: $error_code\n";
            if ($error_code == 1452) {
                echo "ERROR: Foreign key constraint violation\n";
                echo "SOLUTION: Customer ID $test_customer_id does not exist in customer table\n";
            } elseif ($error_code == 1146) {
                echo "ERROR: Table not found\n";
                echo "SOLUTION: Orders table does not exist\n";
            } elseif ($error_code == 1054) {
                echo "ERROR: Column not found\n";
                echo "SOLUTION: Check table structure matches expected columns\n";
            }
        }
    }
    
    // Check last PHP error
    $last_error = error_get_last();
    if ($last_error) {
        echo "\nLast PHP Error:\n";
        print_r($last_error);
    }
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";

