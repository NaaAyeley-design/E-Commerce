<?php
/**
 * Create Orders Tables
 * 
 * This script creates the orders and order_items tables if they don't exist
 */

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../class/db_class.php';

try {
    $db = new db_class();
    
    // Create orders table
    $orders_sql = "CREATE TABLE IF NOT EXISTS orders (
        order_id INT(11) NOT NULL AUTO_INCREMENT,
        customer_id INT(11) NOT NULL,
        invoice_no VARCHAR(50) DEFAULT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        shipping_address TEXT NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'pending',
        order_status VARCHAR(50) DEFAULT 'pending',
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (order_id),
        FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
        UNIQUE KEY idx_invoice_no (invoice_no),
        INDEX idx_customer_id (customer_id),
        INDEX idx_order_status (order_status),
        INDEX idx_order_date (order_date),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    
    $result = $db->execute($orders_sql);
    if ($result !== false) {
        echo "Orders table created successfully or already exists.\n";
        
        // Add invoice_no and order_date columns if they don't exist (for existing tables)
        try {
            // Check if invoice_no column exists
            $check_invoice = $db->fetchRow("SHOW COLUMNS FROM orders LIKE 'invoice_no'");
            if (!$check_invoice) {
                $db->execute("ALTER TABLE orders ADD COLUMN invoice_no VARCHAR(50) DEFAULT NULL AFTER customer_id");
                // Add UNIQUE index (NULL values are allowed and don't violate uniqueness)
                try {
                    $db->execute("ALTER TABLE orders ADD UNIQUE KEY idx_invoice_no (invoice_no)");
                } catch (Exception $e) {
                    // If unique key already exists or fails, try adding regular index
                    try {
                        $db->execute("ALTER TABLE orders ADD INDEX idx_invoice_no (invoice_no)");
                    } catch (Exception $e2) {
                        // Index might already exist, continue
                    }
                }
                echo "Added invoice_no column to orders table.\n";
            }
            
            // Check if order_date column exists
            $check_order_date = $db->fetchRow("SHOW COLUMNS FROM orders LIKE 'order_date'");
            if (!$check_order_date) {
                $db->execute("ALTER TABLE orders ADD COLUMN order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER order_status");
                $db->execute("ALTER TABLE orders ADD INDEX idx_order_date (order_date)");
                echo "Added order_date column to orders table.\n";
            }
        } catch (Exception $e) {
            echo "Note: " . $e->getMessage() . " (columns may already exist)\n";
        }
    } else {
        echo "Error creating orders table.\n";
    }
    
    // Create order_items table
    $order_items_sql = "CREATE TABLE IF NOT EXISTS order_items (
        item_id INT(11) NOT NULL AUTO_INCREMENT,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (item_id),
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        INDEX idx_order_id (order_id),
        INDEX idx_product_id (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    
    $result = $db->execute($order_items_sql);
    if ($result !== false) {
        echo "Order items table created successfully or already exists.\n";
    } else {
        echo "Error creating order_items table.\n";
    }
    
    echo "\nDone! Orders tables are ready.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

