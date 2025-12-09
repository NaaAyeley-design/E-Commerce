<?php
require_once("db.php");

class DashboardController {
    private $db;
    
    public function __construct() {
        $db_conn = new db_connection();
        $this->db = $db_conn->db;
    }
    
    /**
     * Get total number of customers/users
     */
    public function getTotalCustomers() {
        // Try different possible table names
        $tables = ['customers', 'users', 'customer', 'user'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            $query = "SELECT COUNT(*) as total FROM `$table`";
            $result = mysqli_query($this->db, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (int)$row['total'];
            }
        }
        return 0;
    }
    
    /**
     * Get total number of orders
     */
    public function getTotalOrders() {
        $tables = ['orders', 'order'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            $query = "SELECT COUNT(*) as total FROM `$table`";
            $result = mysqli_query($this->db, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (int)$row['total'];
            }
        }
        return 0;
    }
    
    /**
     * Get total revenue
     */
    public function getTotalRevenue() {
        $tables = ['orders', 'order', 'transactions', 'transaction'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Get all columns from the table
            $colResult = mysqli_query($this->db, "SHOW COLUMNS FROM `$table`");
            if ($colResult) {
                $amountColumn = null;
                while ($col = mysqli_fetch_assoc($colResult)) {
                    if (stripos($col['Field'], 'total') !== false || 
                        stripos($col['Field'], 'amount') !== false || 
                        stripos($col['Field'], 'price') !== false) {
                        $amountColumn = $col['Field'];
                        break;
                    }
                }
                if ($amountColumn) {
                    $query = "SELECT SUM(`$amountColumn`) as total FROM `$table`";
                    $result = mysqli_query($this->db, $query);
                    if ($result && mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        return (float)($row['total'] ?? 0);
                    }
                }
            }
        }
        return 0;
    }
    
    /**
     * Get total products
     */
    public function getTotalProducts() {
        $tables = ['products', 'product', 'items', 'item'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            $query = "SELECT COUNT(*) as total FROM `$table`";
            $result = mysqli_query($this->db, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (int)$row['total'];
            }
        }
        return 0;
    }
    
    /**
     * Get pending orders count
     */
    public function getPendingOrders() {
        $tables = ['orders', 'order'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Check if status column exists
            $query = "SHOW COLUMNS FROM `$table` LIKE 'status'";
            $result = mysqli_query($this->db, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $query = "SELECT COUNT(*) as total FROM `$table` WHERE status IN ('pending', 'processing', 'Pending', 'Processing')";
                $result = mysqli_query($this->db, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    return (int)$row['total'];
                }
            }
        }
        return 0;
    }
    
    /**
     * Get recent orders (last 5)
     */
    public function getRecentOrders($limit = 5) {
        $tables = ['orders', 'order'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Try different date column names
            $dateColumns = ['created_at', 'order_date', 'date', 'created'];
            $orderBy = 'id DESC';
            
            foreach ($dateColumns as $dateCol) {
                $colCheck = mysqli_query($this->db, "SHOW COLUMNS FROM `$table` LIKE '$dateCol'");
                if ($colCheck && mysqli_num_rows($colCheck) > 0) {
                    $orderBy = "$dateCol DESC, id DESC";
                    break;
                }
            }
            
            $query = "SELECT * FROM `$table` ORDER BY $orderBy LIMIT $limit";
            $result = mysqli_query($this->db, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $orders = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $orders[] = $row;
                }
                return $orders;
            }
        }
        return [];
    }
    
    /**
     * Get revenue for current month
     */
    public function getCurrentMonthRevenue() {
        $tables = ['orders', 'order', 'transactions', 'transaction'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            $query = "SHOW COLUMNS FROM `$table`";
            $result = mysqli_query($this->db, $query);
            if ($result) {
                $dateColumn = null;
                $amountColumn = null;
                while ($col = mysqli_fetch_assoc($result)) {
                    if (stripos($col['Field'], 'date') !== false || 
                        stripos($col['Field'], 'created') !== false) {
                        $dateColumn = $col['Field'];
                    }
                    if (stripos($col['Field'], 'total') !== false || 
                        stripos($col['Field'], 'amount') !== false || 
                        stripos($col['Field'], 'price') !== false) {
                        $amountColumn = $col['Field'];
                    }
                }
                if ($dateColumn && $amountColumn) {
                    $query = "SELECT SUM(`$amountColumn`) as total FROM `$table` 
                             WHERE MONTH(`$dateColumn`) = MONTH(CURRENT_DATE()) 
                             AND YEAR(`$dateColumn`) = YEAR(CURRENT_DATE())";
                    $result = mysqli_query($this->db, $query);
                    if ($result && mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        return (float)($row['total'] ?? 0);
                    }
                }
            }
        }
        return 0;
    }
    
    /**
     * Get new customers this month
     * Note: Since created_at column is removed from users table,
     * this will return total customers as fallback
     */
    public function getNewCustomersThisMonth() {
        $tables = ['customers', 'users', 'customer', 'user'];
        foreach ($tables as $table) {
            // Check if table exists first
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Since created_at column is removed, just return total count
            // This is a fallback when no date column is available
            $query = "SELECT COUNT(*) as total FROM `$table`";
            $result = mysqli_query($this->db, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (int)$row['total'];
            }
        }
        return 0;
    }
    
    /**
     * Get all dashboard statistics
     */
    public function getAllStats() {
        return [
            'total_customers' => $this->getTotalCustomers(),
            'total_orders' => $this->getTotalOrders(),
            'total_revenue' => $this->getTotalRevenue(),
            'total_products' => $this->getTotalProducts(),
            'pending_orders' => $this->getPendingOrders(),
            'current_month_revenue' => $this->getCurrentMonthRevenue(),
            'new_customers_this_month' => $this->getNewCustomersThisMonth(),
            'recent_orders' => $this->getRecentOrders(5)
        ];
    }
    
    /**
     * Get revenue chart data (last 6 months)
     */
    public function getRevenueChartData() {
        $tables = ['orders', 'order', 'transactions', 'transaction'];
        $chartData = [];
        $foundTable = null;
        $dateColumn = null;
        $amountColumn = null;
        
        // First, find a valid table with the right columns
        foreach ($tables as $table) {
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            $query = "SHOW COLUMNS FROM `$table`";
            $result = mysqli_query($this->db, $query);
            if ($result) {
                while ($col = mysqli_fetch_assoc($result)) {
                    if (stripos($col['Field'], 'date') !== false || 
                        stripos($col['Field'], 'created') !== false) {
                        $dateColumn = $col['Field'];
                    }
                    if (stripos($col['Field'], 'total') !== false || 
                        stripos($col['Field'], 'amount') !== false || 
                        stripos($col['Field'], 'price') !== false) {
                        $amountColumn = $col['Field'];
                    }
                }
                if ($dateColumn && $amountColumn) {
                    $foundTable = $table;
                    break;
                }
            }
        }
        
        // Generate chart data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime("-$i months"));
            
            if ($foundTable && $dateColumn && $amountColumn) {
                $query = "SELECT SUM(`$amountColumn`) as total FROM `$foundTable` 
                         WHERE DATE_FORMAT(`$dateColumn`, '%Y-%m') = '$month'";
                $result = mysqli_query($this->db, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    $chartData[] = [
                        'month' => $monthName,
                        'revenue' => (float)($row['total'] ?? 0)
                    ];
                } else {
                    $chartData[] = [
                        'month' => $monthName,
                        'revenue' => 0
                    ];
                }
            } else {
                $chartData[] = [
                    'month' => $monthName,
                    'revenue' => 0
                ];
            }
        }
        
        return $chartData;
    }
}
?>

