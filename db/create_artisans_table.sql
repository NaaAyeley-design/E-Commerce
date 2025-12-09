-- ============================================
-- Create Artisans Table
-- ============================================
-- This SQL script creates the artisans table and populates it
-- with existing users who have user_role = 3 (Designer/Producer/Artisan)
--
-- Run this script on your live server to create the artisans table
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
-- Note: This handles cases where business_name and bio columns may not exist in customer table
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
    c.customer_name,
    -- Use business_name if column exists, otherwise use customer_name
    CASE 
        WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'customer' 
                     AND COLUMN_NAME = 'business_name')
        THEN COALESCE(c.business_name, c.customer_name)
        ELSE c.customer_name
    END as business_name,
    -- Use bio if column exists, otherwise NULL
    CASE 
        WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'customer' 
                     AND COLUMN_NAME = 'bio')
        THEN c.bio
        ELSE NULL
    END as bio,
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
-- Migration Complete!
-- ============================================
-- The artisans table has been created and populated
-- You can now query artisans using:
-- SELECT * FROM artisans WHERE status = 'active';
-- ============================================

