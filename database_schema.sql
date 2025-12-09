-- =====================================================
-- Ecommerce Database Schema
-- Complete SQL file with all table definitions
-- =====================================================

-- Database: dbforlab
-- Charset: utf8mb4
-- Engine: InnoDB

-- =====================================================
-- 1. CART TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product` (`user_id`, `product_id`),
    INDEX `user_id` (`user_id`),
    INDEX `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `order_number` VARCHAR(50) UNIQUE,
    `order_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `order_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `shipping_address` TEXT,
    `billing_address` TEXT,
    `payment_method` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `user_id` (`user_id`),
    INDEX `status` (`status`),
    INDEX `order_status` (`order_status`),
    INDEX `order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. ORDER_ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `order_id` (`order_id`),
    INDEX `product_id` (`product_id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. PRODUCTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `products` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- NOTES:
-- =====================================================
-- 1. The application also references these tables (may exist in your database):
--    - users / customers (for user accounts)
--    - producers / designers / artisans (for seller accounts)
--
-- 2. The orders table has both 'order_total' and 'total_amount' columns
--    for compatibility with different naming conventions
--
-- 3. The orders table has both 'order_status' and 'status' columns
--    for compatibility with different naming conventions
--
-- 4. Foreign key relationships:
--    - order_items.order_id -> orders.id (CASCADE DELETE)
--    - cart.product_id -> products.id (logical relationship)
--    - order_items.product_id -> products.id (logical relationship)
--
-- 5. To add missing columns to existing tables, run:
--    ALTER TABLE `orders` ADD COLUMN `order_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`;
--    ALTER TABLE `orders` ADD COLUMN `order_status` VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER `status`;

