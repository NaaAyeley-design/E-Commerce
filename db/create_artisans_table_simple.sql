-- ============================================
-- Create Artisans Table (Simple Version)
-- ============================================
-- This version works even if business_name and bio columns
-- don't exist in the customer table
-- ============================================

-- Create artisans table
CREATE TABLE IF NOT EXISTS artisans (
    artisan_id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    business_name VARCHAR(200) DEFAULT NULL,
    artisan_name VARCHAR(100) NOT NULL,
    bio TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    email VARCHAR(50) NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    city VARCHAR(30) DEFAULT NULL,
    country VARCHAR(30) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    instagram VARCHAR(100) DEFAULT NULL,
    facebook VARCHAR(100) DEFAULT NULL,
    twitter VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    featured TINYINT(1) DEFAULT 0,
    total_products INT(11) DEFAULT 0,
    total_sales INT(11) DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (artisan_id),
    UNIQUE KEY unique_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_city (city),
    INDEX idx_country (country),
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Populate artisans table with existing role 3 users
-- This version only uses columns that definitely exist in customer table
INSERT INTO artisans (
    customer_id,
    artisan_name,
    business_name,
    bio,
    profile_image,
    email,
    phone,
    city,
    country,
    status,
    created_at
)
SELECT 
    c.customer_id,
    c.customer_name as artisan_name,
    c.customer_name as business_name,  -- Use customer_name as business_name if business_name column doesn't exist
    NULL as bio,  -- Set bio to NULL if column doesn't exist
    c.customer_image,
    c.customer_email,
    c.customer_contact,
    c.customer_city,
    c.customer_country,
    'active' as status,
    NOW() as created_at
FROM customer c
WHERE c.user_role = 3
AND NOT EXISTS (
    SELECT 1 FROM artisans a WHERE a.customer_id = c.customer_id
);

-- Update total_products count for each artisan
-- This counts products linked to the artisan through brands they own
UPDATE artisans a
SET total_products = (
    SELECT COUNT(DISTINCT p.product_id) 
    FROM products p
    WHERE p.product_brand IN (
        SELECT brand_id 
        FROM brands 
        WHERE user_id = a.customer_id
    )
);

-- ============================================
-- Optional: Update business_name and bio if columns exist
-- ============================================
-- Run this separately if you have business_name and bio columns
-- ============================================

-- Uncomment and run this if business_name column exists:
/*
UPDATE artisans a
INNER JOIN customer c ON a.customer_id = c.customer_id
SET a.business_name = COALESCE(c.business_name, c.customer_name)
WHERE EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customer' 
    AND COLUMN_NAME = 'business_name'
);
*/

-- Uncomment and run this if bio column exists:
/*
UPDATE artisans a
INNER JOIN customer c ON a.customer_id = c.customer_id
SET a.bio = c.bio
WHERE EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customer' 
    AND COLUMN_NAME = 'bio'
);
*/

-- ============================================
-- Migration Complete!
-- ============================================

