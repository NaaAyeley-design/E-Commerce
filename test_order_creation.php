<?php
/**
 * Order Creation Test Script
 * 
 * This script tests order creation independently to identify issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/class/order_class.php';
require_once __DIR__ . '/class/user_class.php';

echo "<h2>Order Creation Test</h2>";
echo "<pre>";

// Test 1: Database Connection
echo "=== TEST 1: Database Connection ===\n";
$order = new order_class();
$conn = $order->getConnection();

if (!$conn) {
    echo "✗ Connection failed\n";
    exit;
}

echo "✓ Connection successful\n";
echo "Connection type: " . get_class($conn) . "\n";

// Test if it's PDO or MySQLi
if ($conn instanceof PDO) {
    echo "✓ Using PDO\n";
    echo "Driver: " . $conn->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "Server version: " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";
} elseif ($conn instanceof mysqli) {
    echo "⚠ Using MySQLi (should be PDO)\n";
    echo "Server info: " . $conn->server_info . "\n\n";
} else {
    echo "✗ Unknown connection type\n\n";
}

// Test 2: Get customer ID
echo "=== TEST 2: Customer Verification ===\n";
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
        $db = new db_class();
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
    echo "⚠ No user logged in\n";
    // Try to find any customer
    $db = new db_class();
    $any_customer = $db->fetchRow("SELECT customer_id FROM customer LIMIT 1");
    if ($any_customer) {
        $test_customer_id = $any_customer['customer_id'];
        echo "   Using customer ID {$test_customer_id} for testing\n";
    } else {
        echo "✗ NO CUSTOMERS IN DATABASE\n";
        exit;
    }
}

// Test 3: Check tables
echo "\n=== TEST 3: Table Existence ===\n";
$db = new db_class();
$tables = ['customer', 'orders', 'order_items', 'payment'];
foreach ($tables as $table) {
    $check = $db->fetchRow("SHOW TABLES LIKE '$table'");
    if ($check) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' DOES NOT EXIST\n";
    }
}

// Test 4: Test order creation
echo "\n=== TEST 4: Order Creation Test ===\n";
$test_total = 100.00;
$test_address = "Test Address, Test City, Test Country, test@example.com";
$test_status = 'pending';

echo "Creating order with:\n";
echo "  - Customer ID: $test_customer_id\n";
echo "  - Total: $test_total\n";
echo "  - Address: $test_address\n";
echo "  - Status: $test_status\n\n";

$order_id = $order->create_order($test_customer_id, $test_total, $test_address, $test_status);

if ($order_id && $order_id > 0) {
    echo "✓ Order created! Order ID: $order_id\n";
    
    // Test 5: Test adding order item
    echo "\n=== TEST 5: Order Item Creation ===\n";
    $db = new db_class();
    $sample_product = $db->fetchRow("SELECT product_id FROM products LIMIT 1");
    if ($sample_product) {
        $test_product_id = $sample_product['product_id'];
        echo "Using product ID: $test_product_id\n";
        
        $item_added = $order->add_order_item($order_id, $test_product_id, 1, 50.00);
        
        if ($item_added) {
            echo "✓ Order item added\n";
        } else {
            echo "✗ Order item failed\n";
        }
    } else {
        echo "⚠ No products in database - skipping item test\n";
    }
    
    // Test 6: Test payment recording
    echo "\n=== TEST 6: Payment Recording ===\n";
    $payment_id = $order->record_payment(
        100.00, 
        $test_customer_id, 
        $order_id, 
        'GHS', 
        date('Y-m-d H:i:s'),
        'test',
        'TEST-REF-' . time(),
        '',
        'test'
    );
    
    if ($payment_id && $payment_id > 0) {
        echo "✓ Payment recorded! Payment ID: $payment_id\n";
    } else {
        echo "✗ Payment recording failed\n";
        echo "   Returned: " . var_export($payment_id, true) . "\n";
    }
    
    // Clean up test order
    echo "\n=== CLEANUP ===\n";
    echo "Cleaning up test order...\n";
    $db->execute("DELETE FROM payment WHERE order_id = ?", [$order_id]);
    $db->execute("DELETE FROM order_items WHERE order_id = ?", [$order_id]);
    $db->execute("DELETE FROM orders WHERE order_id = ?", [$order_id]);
    echo "✓ Test order cleaned up\n";
    
} else {
    echo "✗ Order creation failed\n";
    echo "Returned value: " . var_export($order_id, true) . "\n";
    echo "\nCheck error log for details: C:\\xampp\\apache\\logs\\error.log\n";
    echo "Look for lines starting with '=== ORDER CREATION START ==='\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "</pre>";

