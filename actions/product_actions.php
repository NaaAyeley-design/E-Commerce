<?php
/**
 * Product Actions
 * 
 * Handles all backend logic for customer-facing product operations.
 * Returns JSON for AJAX requests.
 */

// Include core settings and product controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/product_controller.php';
require_once __DIR__ . '/../controller/category_controller.php';
require_once __DIR__ . '/../controller/brand_controller.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Handle CORS if needed
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Handle different actions
    switch ($action) {
        case 'view_all':
            // View all products with pagination
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $products = view_all_products_ctr($limit, $offset);
            $total = get_product_count_ctr();
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]);
            break;
            
        case 'view_single':
            // View single product
            $product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if (empty($product_id)) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
                exit;
            }
            
            $product = view_single_product_ctr($product_id);
            
            if ($product) {
                echo json_encode([
                    'success' => true,
                    'data' => $product
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product not found.'
                ]);
            }
            break;
            
        case 'search':
            // Search products
            $query = trim($_GET['query'] ?? $_POST['query'] ?? '');
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            if (empty($query)) {
                echo json_encode(['success' => false, 'message' => 'Search query is required.']);
                exit;
            }
            
            $products = search_products_ctr($query, $limit, $offset);
            $filters = ['query' => $query];
            $total = count_filtered_products_ctr($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit),
                'query' => $query
            ]);
            break;
            
        case 'filter_category':
            // Filter by category
            $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            if (empty($cat_id)) {
                echo json_encode(['success' => false, 'message' => 'Category ID is required.']);
                exit;
            }
            
            $products = filter_products_by_category_ctr($cat_id, $limit, $offset);
            $filters = ['cat_id' => $cat_id];
            $total = count_filtered_products_ctr($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit),
                'cat_id' => $cat_id
            ]);
            break;
            
        case 'filter_brand':
            // Filter by brand
            $brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            if (empty($brand_id)) {
                echo json_encode(['success' => false, 'message' => 'Brand ID is required.']);
                exit;
            }
            
            $products = filter_products_by_brand_ctr($brand_id, $limit, $offset);
            $filters = ['brand_id' => $brand_id];
            $total = count_filtered_products_ctr($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit),
                'brand_id' => $brand_id
            ]);
            break;
            
        case 'composite_search':
            // Composite search with multiple filters
            $filters = [];
            
            if (isset($_GET['query']) || isset($_POST['query'])) {
                $filters['query'] = trim($_GET['query'] ?? $_POST['query'] ?? '');
            }
            
            if (isset($_GET['cat_id']) || isset($_POST['cat_id'])) {
                $cat_id = (int)($_GET['cat_id'] ?? $_POST['cat_id'] ?? 0);
                if ($cat_id > 0) {
                    $filters['cat_id'] = $cat_id;
                }
            }
            
            if (isset($_GET['brand_id']) || isset($_POST['brand_id'])) {
                $brand_id = (int)($_GET['brand_id'] ?? $_POST['brand_id'] ?? 0);
                if ($brand_id > 0) {
                    $filters['brand_id'] = $brand_id;
                }
            }
            
            if (isset($_GET['max_price']) || isset($_POST['max_price'])) {
                $max_price = (float)($_GET['max_price'] ?? $_POST['max_price'] ?? 0);
                if ($max_price > 0) {
                    $filters['max_price'] = $max_price;
                }
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $products = composite_search_ctr($filters, $limit, $offset);
            $total = count_filtered_products_ctr($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit),
                'filters' => $filters
            ]);
            break;
            
        case 'get_categories':
            // Get all categories for dropdown
            $category_obj = new category_class();
            $categories = $category_obj->get_all_categories(1000, 0);
            
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            break;
            
        case 'get_brands':
            // Get all brands for dropdown
            $brand_obj = new brand_class();
            $brands = $brand_obj->get_all_brands(1000, 0);
            
            echo json_encode([
                'success' => true,
                'data' => $brands
            ]);
            break;
            
        case 'get_brands_by_category':
            // Get brands by category for dynamic dropdown
            $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
            
            if (empty($cat_id)) {
                echo json_encode(['success' => false, 'message' => 'Category ID is required.']);
                exit;
            }
            
            $brand_obj = new brand_class();
            $user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
            $brands = $brand_obj->get_brands_by_category($user_id, $cat_id);
            
            echo json_encode([
                'success' => true,
                'data' => $brands
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action. Available actions: view_all, view_single, search, filter_category, filter_brand, composite_search, get_categories, get_brands, get_brands_by_category'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Product actions error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request.'
    ]);
}
?>


