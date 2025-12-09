<?php
/**
 * Dashboard API Endpoint
 * Returns JSON data for AJAX requests to update dashboard without page reload
 */
header('Content-Type: application/json');
require_once("dashboard_controller.php");

// Initialize dashboard controller
$dashboard = new DashboardController();

// Get the requested action
$action = $_GET['action'] ?? 'all';

try {
    switch ($action) {
        case 'all':
            // Return all statistics
            $stats = $dashboard->getAllStats();
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'stats':
            // Return only main statistics
            $stats = $dashboard->getAllStats();
            unset($stats['recent_orders']); // Remove orders array for stats-only request
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'recent_orders':
            // Return only recent orders
            $orders = $dashboard->getRecentOrders(5);
            echo json_encode([
                'success' => true,
                'data' => $orders
            ]);
            break;
            
        case 'chart':
            // Return chart data
            $chartData = $dashboard->getRevenueChartData();
            echo json_encode([
                'success' => true,
                'data' => $chartData
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

