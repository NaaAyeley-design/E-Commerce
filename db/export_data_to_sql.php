<?php
/**
 * Export Database Data to SQL
 * 
 * Exports all data from local database to SQL INSERT statements
 * that can be imported into the remote database via phpMyAdmin
 * 
 * Usage: php db/export_data_to_sql.php
 */

// Include database configuration
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/db_class.php';

// Initialize database connection
$db = new db_class();

try {
    echo "Starting database export...\n\n";
    
    $sql_output = "-- Database Export for Remote Server\n";
    $sql_output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_output .= "-- Database: " . DB_NAME . "\n\n";
    
    // Disable foreign key checks temporarily
    $sql_output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // 1. Export Categories
    echo "1. Exporting categories...\n";
    $categories = $db->fetchAll("SELECT cat_id, cat_name, user_id, created_at, updated_at FROM categories ORDER BY cat_id");
    if ($categories) {
        $sql_output .= "-- Categories\n";
        $sql_output .= "DELETE FROM categories;\n";
        foreach ($categories as $cat) {
            $cat_name = addslashes($cat['cat_name']);
            $user_id = (int)$cat['user_id'];
            $created_at = $cat['created_at'] ?? 'NOW()';
            $updated_at = $cat['updated_at'] ?? 'NOW()';
            
            if ($created_at !== 'NOW()') {
                $created_at = "'" . $created_at . "'";
            }
            if ($updated_at !== 'NOW()') {
                $updated_at = "'" . $updated_at . "'";
            }
            
            $sql_output .= "INSERT INTO categories (cat_id, cat_name, user_id, created_at, updated_at) VALUES ({$cat['cat_id']}, '{$cat_name}', {$user_id}, {$created_at}, {$updated_at});\n";
        }
        $sql_output .= "\n";
        echo "   ✓ Exported " . count($categories) . " categories\n";
    }
    
    // 2. Export Brands
    echo "2. Exporting brands...\n";
    $brands = $db->fetchAll("SELECT brand_id, brand_name, brand_description, cat_id, user_id, brand_logo, is_active, created_at, updated_at FROM brands ORDER BY brand_id");
    if ($brands) {
        $sql_output .= "-- Brands\n";
        $sql_output .= "DELETE FROM brands;\n";
        foreach ($brands as $brand) {
            $brand_name = addslashes($brand['brand_name']);
            $brand_desc = addslashes($brand['brand_description'] ?? '');
            $cat_id = (int)$brand['cat_id'];
            $user_id = (int)$brand['user_id'];
            $brand_logo = $brand['brand_logo'] ? "'" . addslashes($brand['brand_logo']) . "'" : 'NULL';
            $is_active = (int)($brand['is_active'] ?? 1);
            $created_at = $brand['created_at'] ?? 'NOW()';
            $updated_at = $brand['updated_at'] ?? 'NOW()';
            
            if ($created_at !== 'NOW()') {
                $created_at = "'" . $created_at . "'";
            }
            if ($updated_at !== 'NOW()') {
                $updated_at = "'" . $updated_at . "'";
            }
            
            $sql_output .= "INSERT INTO brands (brand_id, brand_name, brand_description, cat_id, user_id, brand_logo, is_active, created_at, updated_at) VALUES ({$brand['brand_id']}, '{$brand_name}', '{$brand_desc}', {$cat_id}, {$user_id}, {$brand_logo}, {$is_active}, {$created_at}, {$updated_at});\n";
        }
        $sql_output .= "\n";
        echo "   ✓ Exported " . count($brands) . " brands\n";
    }
    
    // 3. Export Products
    echo "3. Exporting products...\n";
    $products = $db->fetchAll("SELECT product_id, product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords FROM products ORDER BY product_id");
    if ($products) {
        $sql_output .= "-- Products\n";
        $sql_output .= "DELETE FROM products;\n";
        foreach ($products as $product) {
            $title = addslashes($product['product_title']);
            $desc = addslashes($product['product_desc'] ?? '');
            $image = $product['product_image'] ? "'" . addslashes($product['product_image']) . "'" : 'NULL';
            $keywords = $product['product_keywords'] ? "'" . addslashes($product['product_keywords']) . "'" : 'NULL';
            $cat_id = (int)$product['product_cat'];
            $brand_id = (int)$product['product_brand'];
            $price = (float)$product['product_price'];
            
            $sql_output .= "INSERT INTO products (product_id, product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords) VALUES ({$product['product_id']}, {$cat_id}, {$brand_id}, '{$title}', {$price}, '{$desc}', {$image}, {$keywords});\n";
        }
        $sql_output .= "\n";
        echo "   ✓ Exported " . count($products) . " products\n";
    }
    
    // 4. Export Customers (optional - only if you want to copy users)
    echo "4. Exporting customers...\n";
    $customers = $db->fetchAll("SELECT customer_id, customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, customer_image, user_role FROM customer ORDER BY customer_id");
    if ($customers) {
        $sql_output .= "-- Customers (optional - review before importing)\n";
        $sql_output .= "-- DELETE FROM customer;\n";
        foreach ($customers as $customer) {
            $name = addslashes($customer['customer_name']);
            $email = addslashes($customer['customer_email']);
            $pass = addslashes($customer['customer_pass']);
            $country = addslashes($customer['customer_country'] ?? '');
            $city = addslashes($customer['customer_city'] ?? '');
            $contact = addslashes($customer['customer_contact'] ?? '');
            $image = $customer['customer_image'] ? "'" . addslashes($customer['customer_image']) . "'" : 'NULL';
            $role = (int)($customer['user_role'] ?? 2);
            
            // Comment out the INSERT for customers - uncomment if you want to import
            $sql_output .= "-- INSERT INTO customer (customer_id, customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, customer_image, user_role) VALUES ({$customer['customer_id']}, '{$name}', '{$email}', '{$pass}', '{$country}', '{$city}', '{$contact}', {$image}, {$role});\n";
        }
        $sql_output .= "\n";
        echo "   ✓ Exported " . count($customers) . " customers (commented out - review before importing)\n";
    }
    
    // Re-enable foreign key checks
    $sql_output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Write to file
    $output_file = __DIR__ . '/exported_data.sql';
    file_put_contents($output_file, $sql_output);
    
    echo "\n✅ Database export completed successfully!\n";
    echo "📁 Export file saved to: " . $output_file . "\n";
    echo "\nNext steps:\n";
    echo "1. Open phpMyAdmin at: http://169.239.251.102:442/phpmyadmin/\n";
    echo "2. Select database: ecommerce_2025A_naa_aryee\n";
    echo "3. Click 'Import' tab\n";
    echo "4. Choose file: " . $output_file . "\n";
    echo "5. Click 'Go' to import\n";
    echo "\n⚠️  Note: This will DELETE and REPLACE existing data in the remote database!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}


