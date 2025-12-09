<?php
require_once("db.php");

class ProductController {
    private $db;
    
    public function __construct() {
        $db_conn = new db_connection();
        $this->db = $db_conn->db;
    }
    
    /**
     * Get all products with images
     */
    public function getAllProducts($limit = 50, $offset = 0) {
        $tables = ['products', 'product', 'items', 'item'];
        $imageColumns = ['image', 'image_url', 'photo', 'picture', 'img', 'thumbnail'];
        
        foreach ($tables as $table) {
            // Check if table exists
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            // Get all columns
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            
            if (!$columnsResult) {
                continue;
            }
            
            $columns = [];
            $imageColumn = null;
            
            while ($col = mysqli_fetch_assoc($columnsResult)) {
                $columns[] = $col['Field'];
                // Check if this is an image column
                foreach ($imageColumns as $imgCol) {
                    if (stripos($col['Field'], $imgCol) !== false) {
                        $imageColumn = $col['Field'];
                        break 2;
                    }
                }
            }
            
            // Build SELECT query with all columns
            $selectColumns = implode(', ', array_map(function($col) {
                return "`$col`";
            }, $columns));
            
            $query = "SELECT $selectColumns FROM `$table` ORDER BY id DESC LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $products = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Ensure image path is properly formatted
                if ($imageColumn && isset($row[$imageColumn])) {
                    $row['image_url'] = $this->formatImagePath($row[$imageColumn]);
                    $row['image_column'] = $imageColumn;
                } else {
                    $row['image_url'] = null;
                }
                $products[] = $row;
            }
            
            if (!empty($products)) {
                return $products;
            }
        }
        
        return [];
    }
    
    /**
     * Get single product by ID
     */
    public function getProductById($productId) {
        $tables = ['products', 'product', 'items', 'item'];
        $imageColumns = ['image', 'image_url', 'photo', 'picture', 'img', 'thumbnail'];
        
        foreach ($tables as $table) {
            $tableCheck = mysqli_query($this->db, "SHOW TABLES LIKE '$table'");
            if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
                continue;
            }
            
            $columnsQuery = "SHOW COLUMNS FROM `$table`";
            $columnsResult = mysqli_query($this->db, $columnsQuery);
            
            if (!$columnsResult) {
                continue;
            }
            
            $columns = [];
            $imageColumn = null;
            
            while ($col = mysqli_fetch_assoc($columnsResult)) {
                $columns[] = $col['Field'];
                foreach ($imageColumns as $imgCol) {
                    if (stripos($col['Field'], $imgCol) !== false) {
                        $imageColumn = $col['Field'];
                        break 2;
                    }
                }
            }
            
            $selectColumns = implode(', ', array_map(function($col) {
                return "`$col`";
            }, $columns));
            
            $query = "SELECT $selectColumns FROM `$table` WHERE id = ?";
            $stmt = mysqli_prepare($this->db, $query);
            mysqli_stmt_bind_param($stmt, "i", $productId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($product = mysqli_fetch_assoc($result)) {
                if ($imageColumn && isset($product[$imageColumn])) {
                    $product['image_url'] = $this->formatImagePath($product[$imageColumn]);
                    $product['image_column'] = $imageColumn;
                } else {
                    $product['image_url'] = null;
                }
                return $product;
            }
        }
        
        return null;
    }
    
    /**
     * Format image path to ensure it's accessible
     * Handles both relative and absolute paths
     */
    private function formatImagePath($imagePath) {
        if (empty($imagePath)) {
            return null;
        }
        
        // If it's already a full URL, return as is
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        
        // If it starts with /, it's an absolute path from root
        if (substr($imagePath, 0, 1) === '/') {
            return $imagePath;
        }
        
        // Check common upload directories
        $uploadDirs = [
            'uploads/',
            'images/',
            'img/',
            'products/',
            'assets/images/',
            '../uploads/',
            '../images/'
        ];
        
        // If path doesn't start with upload directory, try to find it
        $found = false;
        foreach ($uploadDirs as $dir) {
            if (strpos($imagePath, $dir) === 0) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            // Try prepending common upload directories
            foreach ($uploadDirs as $dir) {
                $fullPath = $dir . $imagePath;
                if (file_exists($fullPath) || file_exists('../' . $fullPath)) {
                    return '/' . ltrim($fullPath, '/');
                }
            }
        }
        
        // Return with leading slash for absolute path
        return '/' . ltrim($imagePath, '/');
    }
    
    /**
     * Get total product count
     */
    public function getTotalProducts() {
        $tables = ['products', 'product', 'items', 'item'];
        foreach ($tables as $table) {
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
}
?>

