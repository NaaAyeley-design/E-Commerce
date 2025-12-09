<?php
/**
 * Producer Product API - Secure endpoint for fetching product data
 * Only returns products belonging to the logged-in designer/producer
 */
session_start();
header('Content-Type: application/json');
require_once("producer_controller.php");

// Check if user is logged in
if (!isset($_SESSION['producer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['designer_id']) && !isset($_SESSION['artisan_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$producerController = new ProducerController();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['product_id'])) {
                // Get single product - SECURE: Only if it belongs to producer
                $productId = (int)$_GET['product_id'];
                $product = $producerController->getProducerProduct($productId);
                
                if ($product) {
                    echo json_encode(['success' => true, 'product' => $product]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Product not found or you do not have permission to access it']);
                }
            } else {
                // Get all products for this producer
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                $products = $producerController->getProducerProducts($limit, $offset);
                echo json_encode(['success' => true, 'products' => $products]);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['product_id'])) {
                $productId = (int)$_GET['product_id'];
                
                // SECURITY: Verify ownership before deletion
                if ($producerController->verifyProductOwnership($productId)) {
                    require_once("db.php");
                    $db_conn = new db_connection();
                    $db = $db_conn->db;
                    
                    // Find products table and producer column
                    $tables = ['products', 'product'];
                    $deleted = false;
                    
                    foreach ($tables as $table) {
                        $tableCheck = mysqli_query($db, "SHOW TABLES LIKE '$table'");
                        if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
                            // Find producer column
                            $producerColumns = ['producer_id', 'user_id', 'designer_id', 'artisan_id'];
                            $producerColumn = null;
                            $columnsQuery = "SHOW COLUMNS FROM `$table`";
                            $columnsResult = mysqli_query($db, $columnsQuery);
                            if ($columnsResult) {
                                while ($col = mysqli_fetch_assoc($columnsResult)) {
                                    foreach ($producerColumns as $pc) {
                                        if (stripos($col['Field'], $pc) !== false) {
                                            $producerColumn = $col['Field'];
                                            break 2;
                                        }
                                    }
                                }
                            }
                            
                            if ($producerColumn) {
                                // Double-check ownership in DELETE query
                                $producerId = $_SESSION['producer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['designer_id'] ?? $_SESSION['artisan_id'];
                                $deleteQuery = "DELETE FROM `$table` WHERE id = ? AND `$producerColumn` = ?";
                                $deleteStmt = mysqli_prepare($db, $deleteQuery);
                                mysqli_stmt_bind_param($deleteStmt, "ii", $productId, $producerId);
                                
                                if (mysqli_stmt_execute($deleteStmt)) {
                                    $deleted = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    if ($deleted) {
                        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
                    }
                } else {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this product']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

