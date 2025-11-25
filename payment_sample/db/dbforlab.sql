-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 27, 2025 at 12:48 AM
-- Server version: 8.0.43-0ubuntu0.24.04.2
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_2025A_elorm_ameyibor`
--
CREATE DATABASE IF NOT EXISTS `ecommerce_2025A_elorm_ameyibor` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `ecommerce_2025A_elorm_ameyibor`;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_id`, `brand_name`, `created_by`, `created_date`) VALUES
(1, 'Nike', 1, '2025-10-25 23:06:37'),
(2, 'Adidas', 1, '2025-10-25 23:06:37');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `p_id` int NOT NULL,
  `ip_add` varchar(50) NOT NULL,
  `c_id` int DEFAULT NULL,
  `qty` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `cat_id` int NOT NULL,
  `cat_name` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`cat_id`, `cat_name`, `created_by`, `created_date`) VALUES
(1, 'Shoes', 1, '2025-10-02 13:37:05');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(50) NOT NULL,
  `customer_pass` varchar(150) NOT NULL,
  `customer_country` varchar(30) NOT NULL,
  `customer_city` varchar(30) NOT NULL,
  `customer_contact` varchar(15) NOT NULL,
  `customer_image` varchar(100) DEFAULT NULL,
  `user_role` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_email`, `customer_pass`, `customer_country`, `customer_city`, `customer_contact`, `customer_image`, `user_role`) VALUES
(1, 'Peggy', 'test3@gmail.com', '$2y$10$jcgx3EvNpy/kRlPsUhelluqTxtdsK1QzZoN6eMjzbnuEZsoq7Xiy6', 'USA', 'Houston', '2938048496', NULL, 1),
(9, 'Test One', 'test@gmail.com', '$2y$10$YWr6/97Ha1hraF2X3WfbNOvxQnASXxvmZiDeKbwiKlCsEqaLN3px.', 'Canada', 'Ontario', '0394029394', NULL, 1),
(10, 'Test Two', 'test2@gmail.com', '$2y$10$vJ72O9Sf9TT9b.KZi3VaHuuvZfxfqwpOU/hS8VOPrv.Dk3f9mJY9W', 'Ghana', 'Tema', '0495038495', NULL, 2),
(12, 'Elikem GaleZoyiku', 'egalezoyiku@gmail.com', '$2y$10$u92ghzlmemoVlFCjKjWBJ.XSFQWVRM9jTqW5Vl0zMVoucDnnxPx7q', 'Ghana', 'Aburi', '+233507586382', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orderdetails`
--

CREATE TABLE `orderdetails` (
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `qty` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `invoice_no` int NOT NULL,
  `order_date` date NOT NULL,
  `order_status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `pay_id` int NOT NULL,
  `amt` double NOT NULL,
  `customer_id` int NOT NULL,
  `order_id` int NOT NULL,
  `currency` text NOT NULL,
  `payment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `product_cat` int NOT NULL,
  `product_brand` int NOT NULL,
  `product_title` varchar(200) NOT NULL,
  `product_price` double NOT NULL,
  `product_desc` varchar(500) DEFAULT NULL,
  `product_image` varchar(100) DEFAULT NULL,
  `product_keywords` varchar(100) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_cat`, `product_brand`, `product_title`, `product_price`, `product_desc`, `product_image`, `product_keywords`, `created_by`, `created_date`) VALUES
(5, 1, 1, 'Nike Air Max', 120, 'Comfortable running shoes', 'uploads/u1/p5/img_68fe7553c6a5d9.77457821.jpg', 'nike, shoes, running', 1, '2025-10-25 23:10:21'),
(6, 1, 2, 'Adidas Ultraboost', 150, 'Premium running shoes', NULL, 'adidas, shoes, running', 1, '2025-10-25 23:10:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD KEY `p_id` (`p_id`),
  ADD KEY `c_id` (`c_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`cat_id`),
  ADD UNIQUE KEY `unique_category_per_user` (`cat_name`,`created_by`),
  ADD KEY `fk_categories_customer` (`created_by`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_email` (`customer_email`);

--
-- Indexes for table `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`pay_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `product_cat` (`product_cat`),
  ADD KEY `product_brand` (`product_brand`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `cat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `pay_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`p_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`c_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_customer` FOREIGN KEY (`created_by`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD CONSTRAINT `orderdetails_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `orderdetails_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`product_cat`) REFERENCES `categories` (`cat_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`product_brand`) REFERENCES `brands` (`brand_id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
