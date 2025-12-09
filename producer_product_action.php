<?php
session_start();
require_once("db.php");

// Check if user is logged in
if (!isset($_SESSION['producer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['designer_id']) && !isset($_SESSION['artisan_id'])) {
    header('Location: login.php');
    exit;
}

$producerId = $_SESSION['producer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['designer_id'] ?? $_SESSION['artisan_id'];
$action = $_POST['action'] ?? 'add';
$error = '';
$success = '';

$db_conn = new db_connection();
$db = $db_conn->db;

// Find products table
$productsTable = null;
$tables = ['products', 'product'];
foreach ($tables as $table) {
    $tableCheck = mysqli_query($db, "SHOW TABLES LIKE '$table'");
    if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
        $productsTable = $table;
        break;
    }
}

if (!$productsTable) {
    // Create products table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS `products` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `price` DECIMAL(10,2) NOT NULL,
        `stock` INT(11) NOT NULL DEFAULT 0,
        `image` VARCHAR(255),
        `status` VARCHAR(50) DEFAULT 'active',
        `producer_id` INT(11) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `producer_id` (`producer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($db, $createTable);
    $productsTable = 'products';
}

// Find producer column
$producerColumns = ['producer_id', 'user_id', 'designer_id', 'artisan_id', 'seller_id', 'created_by'];
$producerColumn = 'producer_id';

$columnsQuery = "SHOW COLUMNS FROM `$productsTable`";
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

// Handle file upload
$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($_FILES['image']['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        $imagePath = $targetPath;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if (empty($name)) {
        $error = 'Product name is required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } else {
        if ($action === 'add') {
            // Insert new product
            $query = "INSERT INTO `$productsTable` (name, description, price, stock, image, status, $producerColumn) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "ssdissi", $name, $description, $price, $stock, $imagePath, $status, $producerId);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Product added successfully!';
                header('Location: producer_products.php?success=1');
                exit;
            } else {
                $error = 'Failed to add product: ' . mysqli_error($db);
            }
        } elseif ($action === 'edit' && $productId > 0) {
            // SECURITY: Verify product belongs to producer before allowing edit
            $checkQuery = "SELECT id FROM `$productsTable` WHERE id = ? AND $producerColumn = ?";
            $checkStmt = mysqli_prepare($db, $checkQuery);
            mysqli_stmt_bind_param($checkStmt, "ii", $productId, $producerId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_fetch_assoc($checkResult)) {
                // Product belongs to this producer - safe to update
                if ($imagePath) {
                    $query = "UPDATE `$productsTable` SET name = ?, description = ?, price = ?, stock = ?, image = ?, status = ? WHERE id = ?";
                    $stmt = mysqli_prepare($db, $query);
                    mysqli_stmt_bind_param($stmt, "ssdissi", $name, $description, $price, $stock, $imagePath, $status, $productId);
                } else {
                    $query = "UPDATE `$productsTable` SET name = ?, description = ?, price = ?, stock = ?, status = ? WHERE id = ?";
                    $stmt = mysqli_prepare($db, $query);
                    mysqli_stmt_bind_param($stmt, "ssdisi", $name, $description, $price, $stock, $status, $productId);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Product updated successfully!';
                    header('Location: producer_products.php?updated=1');
                    exit;
                } else {
                    $error = 'Failed to update product: ' . mysqli_error($db);
                }
            } else {
                $error = 'Product not found or you do not have permission to edit it';
            }
        }
    }
}

// If there's an error, redirect back with error message
if ($error) {
    header('Location: producer_products.php?error=' . urlencode($error));
    exit;
}
?>

