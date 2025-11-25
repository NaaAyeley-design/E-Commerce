<?php
/**
 * Debug Orders - Check why orders aren't showing up
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/order_class.php';
require_once __DIR__ . '/../class/db_class.php';

header('Content-Type: application/json');

// Only allow logged-in admin users
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$debug_results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

$order = new order_class();
$db = new db_class();
$conn = $db->getConnection();

// Test 1: Check if orders table exists and count orders
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug_results['tests']['total_orders_in_db'] = [
        'result' => $result['total'] ?? 0
    ];
} catch (Exception $e) {
    $debug_results['tests']['total_orders_in_db'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 2: Get recent orders without JOIN
try {
    $stmt = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
    $orders_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug_results['tests']['orders_without_join'] = [
        'count' => count($orders_raw),
        'orders' => $orders_raw
    ];
} catch (Exception $e) {
    $debug_results['tests']['orders_without_join'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 3: Check if customers exist for these orders
if (isset($debug_results['tests']['orders_without_join']['orders'])) {
    $customer_check = [];
    foreach ($debug_results['tests']['orders_without_join']['orders'] as $order_data) {
        $customer_id = $order_data['customer_id'] ?? null;
        if ($customer_id) {
            try {
                $stmt = $conn->prepare("SELECT customer_id, customer_name, customer_email FROM customer WHERE customer_id = ?");
                $stmt->execute([$customer_id]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                $customer_check[] = [
                    'order_id' => $order_data['order_id'],
                    'customer_id' => $customer_id,
                    'customer_exists' => $customer ? true : false,
                    'customer_data' => $customer
                ];
            } catch (Exception $e) {
                $customer_check[] = [
                    'order_id' => $order_data['order_id'],
                    'customer_id' => $customer_id,
                    'customer_exists' => 'ERROR',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    $debug_results['tests']['customer_check'] = $customer_check;
}

// Test 4: Try the JOIN query directly
try {
    $sql = "SELECT o.*, c.customer_name, c.customer_email 
            FROM orders o 
            JOIN customer c ON o.customer_id = c.customer_id
            ORDER BY o.created_at DESC LIMIT 5";
    $stmt = $conn->query($sql);
    $orders_with_join = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug_results['tests']['orders_with_join'] = [
        'count' => count($orders_with_join),
        'orders' => $orders_with_join
    ];
} catch (Exception $e) {
    $debug_results['tests']['orders_with_join'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage(),
        'sql' => $sql
    ];
}

// Test 5: Try using get_all_orders method
try {
    $orders_method = $order->get_all_orders(5, 0, null);
    $debug_results['tests']['get_all_orders_method'] = [
        'count' => is_array($orders_method) ? count($orders_method) : 0,
        'orders' => $orders_method
    ];
} catch (Exception $e) {
    $debug_results['tests']['get_all_orders_method'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
}

// Test 6: Check parameter binding for LIMIT and OFFSET
try {
    $sql = "SELECT o.*, c.customer_name, c.customer_email 
            FROM orders o 
            JOIN customer c ON o.customer_id = c.customer_id
            ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, 5, PDO::PARAM_INT);
    $stmt->bindValue(2, 0, PDO::PARAM_INT);
    $stmt->execute();
    $orders_with_params = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug_results['tests']['orders_with_bound_params'] = [
        'count' => count($orders_with_params),
        'orders' => $orders_with_params
    ];
} catch (Exception $e) {
    $debug_results['tests']['orders_with_bound_params'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

echo json_encode($debug_results, JSON_PRETTY_PRINT);

