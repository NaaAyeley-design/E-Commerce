<?php
/**
 * Sample Data Seeder
 * 
 * Inserts sample data into the database for testing and demonstration
 * 
 * Usage: php db/seeders/insert_sample_data.php
 */

// Include database configuration
require_once __DIR__ . '/../../settings/core.php';
require_once __DIR__ . '/../../class/db_class.php';

// Initialize database connection
$db = new db_class();

try {
    echo "Starting sample data insertion...\n\n";
    
    // Step 1: Create admin user (if not exists)
    echo "1. Creating admin user...\n";
    $admin_email = 'admin@test.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Check if admin exists
    $check_admin = $db->fetchRow(
        "SELECT customer_id FROM customer WHERE customer_email = ?",
        [$admin_email]
    );
    
    if (!$check_admin) {
        $db->execute(
            "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            ['Admin User', $admin_email, $admin_password, 'US', 'New York', '1234567890', 1]
        );
        $admin_id = $db->lastInsertId();
        echo "   ✓ Admin user created with ID: $admin_id\n";
    } else {
        $admin_id = $check_admin['customer_id'];
        echo "   ✓ Admin user already exists with ID: $admin_id\n";
    }
    
    // Step 2: Insert Categories
    echo "\n2. Inserting categories...\n";
    $categories = [
        ['Footwear', $admin_id],
        ['Electronics', $admin_id],
        ['Clothing', $admin_id],
        ['Home Appliances', $admin_id],
        ['Beauty & Care', $admin_id]
    ];
    
    $category_ids = [];
    foreach ($categories as $index => $category) {
        $cat_name = $category[0];
        $user_id = $category[1];
        
        // Check if category exists
        $existing = $db->fetchRow(
            "SELECT cat_id FROM categories WHERE cat_name = ? AND user_id = ?",
            [$cat_name, $user_id]
        );
        
        if (!$existing) {
            $db->execute(
                "INSERT INTO categories (cat_name, user_id) VALUES (?, ?)",
                [$cat_name, $user_id]
            );
            $cat_id = $db->lastInsertId();
            echo "   ✓ Category '$cat_name' created with ID: $cat_id\n";
        } else {
            $cat_id = $existing['cat_id'];
            echo "   ✓ Category '$cat_name' already exists with ID: $cat_id\n";
        }
        
        $category_ids[$index + 1] = $cat_id; // Store with 1-based index
    }
    
    // Step 3: Insert Brands
    echo "\n3. Inserting brands...\n";
    $brands = [
        ['Nike', 'Sports and casual shoes', $category_ids[1], $admin_id], // Footwear
        ['Adidas', 'Athletic footwear and apparel', $category_ids[1], $admin_id], // Footwear
        ['Samsung', 'Electronics and smart devices', $category_ids[2], $admin_id], // Electronics
        ['Apple', 'Phones, tablets, and accessories', $category_ids[2], $admin_id], // Electronics
        ['Zara', 'Trendy fashion clothing', $category_ids[3], $admin_id], // Clothing
        ['LG', 'Home and kitchen appliances', $category_ids[4], $admin_id], // Home Appliances
        ['Philips', 'Home gadgets and electronics', $category_ids[4], $admin_id], // Home Appliances
        ['Nivea', 'Skin care and personal hygiene', $category_ids[5], $admin_id], // Beauty & Care
        ['L\'Oréal', 'Beauty and cosmetics products', $category_ids[5], $admin_id] // Beauty & Care
    ];
    
    $brand_ids = [];
    foreach ($brands as $index => $brand) {
        $brand_name = $brand[0];
        $brand_desc = $brand[1];
        $cat_id = $brand[2];
        $user_id = $brand[3];
        
        // Check if brand exists
        $existing = $db->fetchRow(
            "SELECT brand_id FROM brands WHERE brand_name = ? AND cat_id = ? AND user_id = ?",
            [$brand_name, $cat_id, $user_id]
        );
        
        if (!$existing) {
            $db->execute(
                "INSERT INTO brands (brand_name, brand_description, cat_id, user_id, is_active) 
                 VALUES (?, ?, ?, ?, ?)",
                [$brand_name, $brand_desc, $cat_id, $user_id, 1]
            );
            $brand_id = $db->lastInsertId();
            echo "   ✓ Brand '$brand_name' created with ID: $brand_id\n";
        } else {
            $brand_id = $existing['brand_id'];
            echo "   ✓ Brand '$brand_name' already exists with ID: $brand_id\n";
        }
        
        $brand_ids[$index + 1] = $brand_id; // Store with 1-based index
    }
    
    // Step 4: Insert Products
    echo "\n4. Inserting products...\n";
    $products = [
        ['Nike Air Max 270', $brand_ids[1], $category_ids[1], 120.00, 'Lightweight sneakers for everyday wear', 'uploads/u1/p1/airmax.png', 'shoes, running'],
        ['Adidas Ultraboost 22', $brand_ids[2], $category_ids[1], 150.00, 'High-performance running shoes', 'uploads/u1/p2/ultraboost.png', 'running, sport'],
        ['Samsung Galaxy S23', $brand_ids[3], $category_ids[2], 999.00, 'Flagship Android smartphone', 'uploads/u1/p3/s23.png', 'phone, android'],
        ['Apple iPhone 15 Pro', $brand_ids[4], $category_ids[2], 1199.00, 'Latest iPhone with A17 chip', 'uploads/u1/p4/iphone15.png', 'phone, ios'],
        ['Zara Summer Dress', $brand_ids[5], $category_ids[3], 80.00, 'Light and stylish cotton summer dress', 'uploads/u1/p5/dress.png', 'dress, fashion'],
        ['LG Smart Refrigerator', $brand_ids[6], $category_ids[4], 1350.00, 'Energy-efficient smart refrigerator', 'uploads/u1/p6/fridge.png', 'fridge, kitchen'],
        ['Philips Air Fryer', $brand_ids[7], $category_ids[4], 200.00, 'Compact air fryer for healthy cooking', 'uploads/u1/p7/airfryer.png', 'cooking, kitchen'],
        ['Nivea Body Lotion', $brand_ids[8], $category_ids[5], 15.00, 'Deep moisturizing body lotion', 'uploads/u1/p8/lotion.png', 'skincare, body'],
        ['L\'Oréal Face Cream', $brand_ids[9], $category_ids[5], 25.00, 'Brightening cream for daily use', 'uploads/u1/p9/facecream.png', 'beauty, cream']
    ];
    
    foreach ($products as $product) {
        $title = $product[0];
        $brand_id = $product[1];
        $cat_id = $product[2];
        $price = $product[3];
        $desc = $product[4];
        $image = $product[5];
        $keywords = $product[6];
        
        // Check if product exists
        $existing = $db->fetchRow(
            "SELECT product_id FROM products WHERE product_title = ? AND product_brand = ?",
            [$title, $brand_id]
        );
        
        if (!$existing) {
            $db->execute(
                "INSERT INTO products (product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$cat_id, $brand_id, $title, $price, $desc, $image, $keywords]
            );
            $product_id = $db->lastInsertId();
            echo "   ✓ Product '$title' created with ID: $product_id\n";
        } else {
            echo "   ✓ Product '$title' already exists with ID: {$existing['product_id']}\n";
        }
    }
    
    // Step 5: Create additional test users (optional)
    echo "\n5. Creating additional test users...\n";
    $test_users = [
        ['Seller User', 'seller1@test.com', 'seller123', 'US', 'Los Angeles', '0987654321', 2],
        ['Buyer User', 'buyer1@test.com', 'buyer123', 'US', 'Chicago', '1122334455', 2]
    ];
    
    foreach ($test_users as $user) {
        $name = $user[0];
        $email = $user[1];
        $password = password_hash($user[2], PASSWORD_DEFAULT);
        $country = $user[3];
        $city = $user[4];
        $contact = $user[5];
        $role = $user[6];
        
        // Check if user exists
        $existing = $db->fetchRow(
            "SELECT customer_id FROM customer WHERE customer_email = ?",
            [$email]
        );
        
        if (!$existing) {
            $db->execute(
                "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$name, $email, $password, $country, $city, $contact, $role]
            );
            $user_id = $db->lastInsertId();
            echo "   ✓ User '$name' created with ID: $user_id\n";
        } else {
            echo "   ✓ User '$name' already exists with ID: {$existing['customer_id']}\n";
        }
    }
    
    echo "\n✅ Sample data insertion completed successfully!\n\n";
    echo "Login credentials:\n";
    echo "  Admin: admin@test.com / admin123\n";
    echo "  Seller: seller1@test.com / seller123\n";
    echo "  Buyer: buyer1@test.com / buyer123\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

