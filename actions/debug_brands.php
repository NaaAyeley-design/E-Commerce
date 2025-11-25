<?php
/**
 * Debug Brands - Check why brands aren't showing up
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/brand_class.php';
require_once __DIR__ . '/../class/db_class.php';

header('Content-Type: application/json');

// Only allow logged-in admin users
if (!is_logged_in() || !is_admin()) {
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$user_id = get_user_id();
$debug_results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'user_id' => $user_id,
    'is_admin' => is_admin(),
    'tests' => []
];

$brand = new brand_class();
$db = new db_class();
$conn = $db->getConnection();

// Test 1: Check if brands table exists and count brands
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM brands");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug_results['tests']['total_brands_in_db'] = [
        'result' => $result['total'] ?? 0
    ];
} catch (Exception $e) {
    $debug_results['tests']['total_brands_in_db'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 2: Get all brands without any filters
try {
    $stmt = $conn->query("SELECT b.*, c.cat_name FROM brands b LEFT JOIN categories c ON b.cat_id = c.cat_id ORDER BY b.brand_name ASC LIMIT 10");
    $brands_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug_results['tests']['brands_without_filters'] = [
        'count' => count($brands_raw),
        'brands' => $brands_raw
    ];
} catch (Exception $e) {
    $debug_results['tests']['brands_without_filters'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 3: Get brands by user_id
try {
    $stmt = $conn->prepare("SELECT b.*, c.cat_name FROM brands b LEFT JOIN categories c ON b.cat_id = c.cat_id WHERE b.user_id = ? ORDER BY b.brand_name ASC LIMIT 10");
    $stmt->bindValue(1, (int)$user_id, PDO::PARAM_INT);
    $stmt->execute();
    $brands_by_user = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug_results['tests']['brands_by_user_id'] = [
        'count' => count($brands_by_user),
        'user_id' => $user_id,
        'brands' => $brands_by_user
    ];
} catch (Exception $e) {
    $debug_results['tests']['brands_by_user_id'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 4: Get all categories
try {
    $stmt = $conn->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug_results['tests']['categories'] = [
        'count' => count($categories),
        'categories' => $categories
    ];
} catch (Exception $e) {
    $debug_results['tests']['categories'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Test 5: Get brands by category using the method
if (isset($debug_results['tests']['categories']['categories']) && count($debug_results['tests']['categories']['categories']) > 0) {
    $first_cat = $debug_results['tests']['categories']['categories'][0];
    $cat_id = $first_cat['cat_id'];
    
    try {
        $brands_by_cat = $brand->get_brands_by_category($user_id, $cat_id);
        $debug_results['tests']['get_brands_by_category_method'] = [
            'category_id' => $cat_id,
            'category_name' => $first_cat['cat_name'],
            'count' => is_array($brands_by_cat) ? count($brands_by_cat) : 0,
            'brands' => $brands_by_cat
        ];
    } catch (Exception $e) {
        $debug_results['tests']['get_brands_by_category_method'] = [
            'result' => 'ERROR',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Test 6: Try get_all_brands method
try {
    $all_brands = $brand->get_all_brands(10, 0);
    $debug_results['tests']['get_all_brands_method'] = [
        'count' => is_array($all_brands) ? count($all_brands) : 0,
        'brands' => $all_brands
    ];
} catch (Exception $e) {
    $debug_results['tests']['get_all_brands_method'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
}

// Test 7: Check user role
try {
    $stmt = $conn->prepare("SELECT customer_id, customer_name, user_role FROM customer WHERE customer_id = ?");
    $stmt->bindValue(1, (int)$user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug_results['tests']['user_role_check'] = [
        'user_data' => $user_data,
        'is_admin_role' => isset($user_data['user_role']) && $user_data['user_role'] == 1
    ];
} catch (Exception $e) {
    $debug_results['tests']['user_role_check'] = [
        'result' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

echo json_encode($debug_results, JSON_PRETTY_PRINT);

