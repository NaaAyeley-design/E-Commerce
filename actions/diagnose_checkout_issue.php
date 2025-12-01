<?php
/**
 * Checkout System Diagnostic Script
 * 
 * This script diagnoses issues with the checkout system:
 * - Order creation failures
 * - Cart clearing failures
 * - Database connection issues
 * - Missing tables/columns
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/db_class.php';
require_once __DIR__ . '/../class/order_class.php';
require_once __DIR__ . '/../class/cart_class.php';
require_once __DIR__ . '/../controller/cart_controller.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Checkout System Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        h2 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>🔍 Checkout System Diagnostic Report</h1>
    <p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $db = new db_class();
    $issues = [];
    $warnings = [];
    $successes = [];
    
    // 1. Check Database Connection
    echo "<div class='section'><h2>1. Database Connection</h2>";
    try {
        $conn = $db->getConnection();
        if ($conn) {
            echo "<p class='success'>✓ Database connection successful</p>";
            $successes[] = "Database connection";
        } else {
            echo "<p class='error'>✗ Database connection failed - connection is null</p>";
            $issues[] = "Database connection is null";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
        $issues[] = "Database connection error: " . $e->getMessage();
    }
    echo "</div>";
    
    // 2. Check Required Tables
    echo "<div class='section'><h2>2. Required Database Tables</h2>";
    $required_tables = ['orders', 'order_items', 'payment', 'cart', 'customer', 'products'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        try {
            $check = $db->fetchRow("SHOW TABLES LIKE ?", [$table]);
            if ($check) {
                echo "<p class='success'>✓ Table '$table' exists</p>";
            } else {
                echo "<p class='error'>✗ Table '$table' is MISSING</p>";
                $missing_tables[] = $table;
                $issues[] = "Missing table: $table";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error checking table '$table': " . htmlspecialchars($e->getMessage()) . "</p>";
            $issues[] = "Error checking table $table";
        }
    }
    echo "</div>";
    
    // 3. Check Orders Table Structure
    if (!in_array('orders', $missing_tables)) {
        echo "<div class='section'><h2>3. Orders Table Structure</h2>";
        try {
            $columns = $db->fetchAll("DESCRIBE orders");
            if ($columns) {
                echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                $required_cols = ['order_id', 'customer_id', 'total_amount', 'shipping_address', 'payment_method', 'order_status', 'invoice_no', 'order_date'];
                $found_cols = [];
                
                foreach ($columns as $col) {
                    $found_cols[] = $col['Field'];
                    echo "<tr>
                        <td>{$col['Field']}</td>
                        <td>{$col['Type']}</td>
                        <td>{$col['Null']}</td>
                        <td>{$col['Key']}</td>
                        <td>" . ($col['Default'] ?? 'NULL') . "</td>
                    </tr>";
                }
                echo "</table>";
                
                $missing_cols = array_diff($required_cols, $found_cols);
                if (empty($missing_cols)) {
                    echo "<p class='success'>✓ All required columns exist</p>";
                } else {
                    echo "<p class='error'>✗ Missing columns: " . implode(', ', $missing_cols) . "</p>";
                    $issues[] = "Missing columns in orders table: " . implode(', ', $missing_cols);
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error checking orders table structure: " . htmlspecialchars($e->getMessage()) . "</p>";
            $issues[] = "Error checking orders table structure";
        }
        echo "</div>";
    }
    
    // 4. Check Cart Table Structure
    if (!in_array('cart', $missing_tables)) {
        echo "<div class='section'><h2>4. Cart Table Structure</h2>";
        try {
            $columns = $db->fetchAll("DESCRIBE cart");
            if ($columns) {
                echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                foreach ($columns as $col) {
                    echo "<tr>
                        <td>{$col['Field']}</td>
                        <td>{$col['Type']}</td>
                        <td>{$col['Null']}</td>
                        <td>{$col['Key']}</td>
                    </tr>";
                }
                echo "</table>";
                
                $col_names = array_column($columns, 'Field');
                if (in_array('c_id', $col_names)) {
                    echo "<p class='success'>✓ Cart table has 'c_id' column (customer ID)</p>";
                } else {
                    echo "<p class='error'>✗ Cart table missing 'c_id' column</p>";
                    $issues[] = "Cart table missing c_id column";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error checking cart table: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        echo "</div>";
    }
    
    // 5. Test Order Creation
    echo "<div class='section'><h2>5. Order Creation Test</h2>";
    if (is_logged_in()) {
        $customer_id = get_user_id();
        echo "<p class='info'>Testing with customer ID: $customer_id</p>";
        
        // Check if customer exists
        $customer = $db->fetchRow("SELECT customer_id, customer_name, customer_email FROM customer WHERE customer_id = ?", [$customer_id]);
        if ($customer) {
            echo "<p class='success'>✓ Customer exists: {$customer['customer_name']} ({$customer['customer_email']})</p>";
            
            // Test order creation (dry run - don't actually create)
            try {
                $order = new order_class();
                $conn = $order->getConnection();
                
                if ($conn) {
                    echo "<p class='success'>✓ Order class connection established</p>";
                    
                    // Check if we can prepare an insert statement
                    try {
                        $test_sql = "INSERT INTO orders (customer_id, total_amount, shipping_address, payment_method, order_status, order_date) VALUES (?, ?, ?, ?, ?, NOW())";
                        $stmt = $conn->prepare($test_sql);
                        if ($stmt) {
                            echo "<p class='success'>✓ Order INSERT statement can be prepared</p>";
                        } else {
                            echo "<p class='error'>✗ Cannot prepare order INSERT statement</p>";
                            $issues[] = "Cannot prepare order INSERT";
                        }
                    } catch (Exception $e) {
                        echo "<p class='error'>✗ Error preparing order INSERT: " . htmlspecialchars($e->getMessage()) . "</p>";
                        $issues[] = "Error preparing order INSERT: " . $e->getMessage();
                    }
                } else {
                    echo "<p class='error'>✗ Order class connection is null</p>";
                    $issues[] = "Order class connection is null";
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Order creation test error: " . htmlspecialchars($e->getMessage()) . "</p>";
                $issues[] = "Order creation test error: " . $e->getMessage();
            }
        } else {
            echo "<p class='error'>✗ Customer ID $customer_id does not exist in database</p>";
            $issues[] = "Customer ID $customer_id does not exist";
        }
    } else {
        echo "<p class='warning'>⚠ Not logged in - cannot test order creation</p>";
        $warnings[] = "Not logged in";
    }
    echo "</div>";
    
    // 6. Test Cart Clearing
    echo "<div class='section'><h2>6. Cart Clearing Test</h2>";
    if (is_logged_in()) {
        $customer_id = get_user_id();
        
        // Check current cart
        $cart_items = get_cart_items_ctr($customer_id);
        $cart_count = count($cart_items);
        echo "<p class='info'>Current cart items: $cart_count</p>";
        
        if ($cart_count > 0) {
            echo "<p class='info'>Cart items:</p><pre>" . print_r($cart_items, true) . "</pre>";
        }
        
        // Test cart clearing function
        try {
            $cart = new cart_class();
            $test_result = $cart->clear_cart($customer_id);
            if ($test_result !== false) {
                echo "<p class='success'>✓ Cart clear function executed (returned: " . var_export($test_result, true) . ")</p>";
                
                // Check if cart is actually empty
                $cart_after = get_cart_items_ctr($customer_id);
                if (empty($cart_after)) {
                    echo "<p class='success'>✓ Cart is now empty</p>";
                    echo "<p class='warning'>⚠ Note: Cart was cleared during test. Items will need to be re-added.</p>";
                } else {
                    echo "<p class='error'>✗ Cart clear returned success but cart still has items</p>";
                    $issues[] = "Cart clear function not working properly";
                }
            } else {
                echo "<p class='error'>✗ Cart clear function returned false</p>";
                $issues[] = "Cart clear function returned false";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Cart clear test error: " . htmlspecialchars($e->getMessage()) . "</p>";
            $issues[] = "Cart clear error: " . $e->getMessage();
        }
    } else {
        echo "<p class='warning'>⚠ Not logged in - cannot test cart clearing</p>";
    }
    echo "</div>";
    
    // 7. Check Recent Orders
    echo "<div class='section'><h2>7. Recent Orders (Last 5)</h2>";
    try {
        $recent_orders = $db->fetchAll("SELECT order_id, customer_id, total_amount, order_status, order_date, invoice_no FROM orders ORDER BY order_id DESC LIMIT 5");
        if ($recent_orders) {
            echo "<table><tr><th>Order ID</th><th>Customer ID</th><th>Amount</th><th>Status</th><th>Date</th><th>Invoice</th></tr>";
            foreach ($recent_orders as $order) {
                echo "<tr>
                    <td>{$order['order_id']}</td>
                    <td>{$order['customer_id']}</td>
                    <td>₵" . number_format($order['total_amount'], 2) . "</td>
                    <td>{$order['order_status']}</td>
                    <td>{$order['order_date']}</td>
                    <td>{$order['invoice_no']}</td>
                </tr>";
            }
            echo "</table>";
            echo "<p class='success'>✓ Found " . count($recent_orders) . " recent orders</p>";
        } else {
            echo "<p class='warning'>⚠ No orders found in database</p>";
            $warnings[] = "No orders in database";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error fetching recent orders: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    // 8. Check Error Logs
    echo "<div class='section'><h2>8. Error Log Check</h2>";
    $error_log_path = ini_get('error_log');
    if ($error_log_path && file_exists($error_log_path)) {
        $log_lines = file($error_log_path);
        $recent_logs = array_slice($log_lines, -20); // Last 20 lines
        echo "<p class='info'>Recent error log entries (last 20 lines):</p>";
        echo "<pre>" . htmlspecialchars(implode('', $recent_logs)) . "</pre>";
    } else {
        echo "<p class='warning'>⚠ Error log not found or not configured</p>";
    }
    echo "</div>";
    
    // Summary
    echo "<div class='section'><h2>📊 Diagnostic Summary</h2>";
    echo "<p><strong>Issues Found:</strong> " . count($issues) . "</p>";
    if (!empty($issues)) {
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li class='error'>$issue</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>Warnings:</strong> " . count($warnings) . "</p>";
    if (!empty($warnings)) {
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li class='warning'>$warning</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>Successful Checks:</strong> " . count($successes) . "</p>";
    if (!empty($successes)) {
        echo "<ul>";
        foreach ($successes as $success) {
            echo "<li class='success'>$success</li>";
        }
        echo "</ul>";
    }
    
    if (empty($issues)) {
        echo "<p class='success'><strong>✓ No critical issues found!</strong></p>";
        echo "<p>If checkout is still failing, check:</p>";
        echo "<ul>";
        echo "<li>JavaScript console for frontend errors</li>";
        echo "<li>Network tab for failed API requests</li>";
        echo "<li>Paystack dashboard for payment status</li>";
        echo "<li>Session data (ensure user is logged in)</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'><strong>✗ Critical issues found that need to be fixed!</strong></p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'><h2 class='error'>Fatal Error</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>




