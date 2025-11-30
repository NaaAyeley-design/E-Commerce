<?php
/**
 * Order Creation Diagnostic Script
 * 
 * This script helps diagnose why order creation is failing
 * Run this to check your database setup
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/db_class.php';
require_once __DIR__ . '/../class/order_class.php';

header('Content-Type: text/plain');

echo "=== ORDER CREATION DIAGNOSTIC TOOL ===\n\n";

$db = new db_class();
$order = new order_class();

// 1. Check database connection
echo "1. Database Connection:\n";
$conn = $order->getConnection();
if ($conn) {
    echo "   ✓ Database connection successful\n";
    echo "   Connection type: " . get_class($conn) . "\n";
} else {
    echo "   ✗ Database connection FAILED\n";
    echo "   ERROR: Cannot proceed without database connection\n";
    exit(1);
}

// 2. Check required tables
echo "\n2. Required Tables:\n";
$required_tables = ['customer', 'orders', 'order_items', 'payment', 'products', 'cart'];
foreach ($required_tables as $table) {
    $check = $db->fetchRow("SHOW TABLES LIKE '$table'");
    if ($check) {
        echo "   ✓ Table '$table' exists\n";
    } else {
        echo "   ✗ Table '$table' MISSING\n";
    }
}

// 3. Check orders table structure
echo "\n3. Orders Table Structure:\n";
$orders_columns = $db->fetchAll("DESCRIBE orders");
if ($orders_columns) {
    $required_columns = ['order_id', 'customer_id', 'invoice_no', 'total_amount', 'shipping_address', 'payment_method', 'order_status', 'order_date'];
    $existing_columns = array_column($orders_columns, 'Field');
    
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "   ✓ Column '$col' exists\n";
        } else {
            echo "   ✗ Column '$col' MISSING\n";
        }
    }
} else {
    echo "   ✗ Cannot describe orders table\n";
}

// 4. Check order_items table structure
echo "\n4. Order Items Table Structure:\n";
$order_items_columns = $db->fetchAll("DESCRIBE order_items");
if ($order_items_columns) {
    $required_columns = ['item_id', 'order_id', 'product_id', 'quantity', 'price'];
    $existing_columns = array_column($order_items_columns, 'Field');
    
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "   ✓ Column '$col' exists\n";
        } else {
            echo "   ✗ Column '$col' MISSING\n";
        }
    }
} else {
    echo "   ✗ Cannot describe order_items table\n";
}

// 5. Check payment table structure
echo "\n5. Payment Table Structure:\n";
$payment_columns = $db->fetchAll("DESCRIBE payment");
if ($payment_columns) {
    $required_columns = ['payment_id', 'amt', 'customer_id', 'order_id', 'currency', 'payment_date', 'payment_method'];
    $existing_columns = array_column($payment_columns, 'Field');
    
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "   ✓ Column '$col' exists\n";
        } else {
            echo "   ✗ Column '$col' MISSING\n";
        }
    }
    
    // Check for Paystack-specific columns
    $paystack_columns = ['transaction_ref', 'authorization_code', 'payment_channel'];
    foreach ($paystack_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "   ✓ Paystack column '$col' exists\n";
        } else {
            echo "   ⚠ Paystack column '$col' missing (optional)\n";
        }
    }
} else {
    echo "   ✗ Cannot describe payment table\n";
}

// 6. Test order creation (dry run)
echo "\n6. Test Order Creation (Dry Run):\n";
if (is_logged_in()) {
    $customer_id = get_user_id();
    echo "   Customer ID: $customer_id\n";
    
    // Check if customer exists
    $customer = $db->fetchRow("SELECT customer_id, customer_name, customer_email FROM customer WHERE customer_id = ?", [$customer_id]);
    if ($customer) {
        echo "   ✓ Customer exists: " . $customer['customer_name'] . " (" . $customer['customer_email'] . ")\n";
    } else {
        echo "   ✗ Customer ID $customer_id does NOT exist\n";
    }
    
    // Check cart items
    require_once __DIR__ . '/../controller/cart_controller.php';
    $cart_items = get_cart_items_ctr($customer_id);
    echo "   Cart items: " . count($cart_items) . "\n";
    
    if (count($cart_items) > 0) {
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'] ?? $item['p_id'] ?? null;
            if ($product_id) {
                $product = $db->fetchRow("SELECT product_id, product_title FROM products WHERE product_id = ?", [$product_id]);
                if ($product) {
                    echo "   ✓ Product #$product_id exists: " . $product['product_title'] . "\n";
                } else {
                    echo "   ✗ Product #$product_id does NOT exist\n";
                }
            }
        }
    }
} else {
    echo "   ⚠ Not logged in - cannot test with real customer data\n";
}

// 7. Check foreign key constraints
echo "\n7. Foreign Key Constraints:\n";
$fk_check = $db->fetchAll("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND TABLE_NAME IN ('orders', 'order_items', 'payment')
");

if ($fk_check) {
    foreach ($fk_check as $fk) {
        echo "   ✓ FK: {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "   ⚠ No foreign keys found (may be using MyISAM engine)\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "\nIf you see any ✗ marks above, those are the issues to fix.\n";
echo "Check your error logs for detailed error messages during order creation.\n";

