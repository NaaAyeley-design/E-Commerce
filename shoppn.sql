-- Create Database
CREATE DATABASE IF NOT EXISTS shoppn;
USE shoppn;

-- Table: brands
CREATE TABLE IF NOT EXISTS brands (
  brand_id INT(11) NOT NULL AUTO_INCREMENT,
  brand_name VARCHAR(100) NOT NULL,
  PRIMARY KEY (brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: categories
CREATE TABLE IF NOT EXISTS categories (
  cat_id INT(11) NOT NULL AUTO_INCREMENT,
  cat_name VARCHAR(100) NOT NULL,
  PRIMARY KEY (cat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: customer
CREATE TABLE IF NOT EXISTS customer (
  customer_id INT(11) NOT NULL AUTO_INCREMENT,
  customer_name VARCHAR(100) NOT NULL,
  customer_email VARCHAR(50) NOT NULL UNIQUE,
  customer_pass VARCHAR(150) NOT NULL,
  customer_country VARCHAR(30) NOT NULL,
  customer_city VARCHAR(30) NOT NULL,
  customer_contact VARCHAR(15) NOT NULL,
  customer_image VARCHAR(100) DEFAULT NULL,
  user_role INT(11) NOT NULL,
  PRIMARY KEY (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: products
CREATE TABLE IF NOT EXISTS products (
  product_id INT(11) NOT NULL AUTO_INCREMENT,
  product_cat INT(11) NOT NULL,
  product_brand INT(11) NOT NULL,
  product_title VARCHAR(200) NOT NULL,
  product_price DOUBLE NOT NULL,
  product_desc VARCHAR(500) DEFAULT NULL,
  product_image VARCHAR(100) DEFAULT NULL,
  product_keywords VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (product_id),
  KEY (product_cat),
  KEY (product_brand),
  CONSTRAINT fk_product_cat FOREIGN KEY (product_cat) REFERENCES categories (cat_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_product_brand FOREIGN KEY (product_brand) REFERENCES brands (brand_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: cart
CREATE TABLE IF NOT EXISTS cart (
  p_id INT(11) NOT NULL,
  ip_add VARCHAR(50) NOT NULL,
  c_id INT(11) DEFAULT NULL,
  qty INT(11) NOT NULL,
  KEY (p_id),
  KEY (c_id),
  CONSTRAINT fk_cart_product FOREIGN KEY (p_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cart_customer FOREIGN KEY (c_id) REFERENCES customer (customer_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: orders
CREATE TABLE IF NOT EXISTS orders (
  order_id INT(11) NOT NULL AUTO_INCREMENT,
  customer_id INT(11) NOT NULL,
  invoice_no INT(11) NOT NULL,
  order_date DATE NOT NULL,
  order_status VARCHAR(100) NOT NULL,
  PRIMARY KEY (order_id),
  KEY (customer_id),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: orderdetails
CREATE TABLE IF NOT EXISTS orderdetails (
  order_id INT(11) NOT NULL,
  product_id INT(11) NOT NULL,
  qty INT(11) NOT NULL,
  KEY (order_id),
  KEY (product_id),
  CONSTRAINT fk_orderdetails_order FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_orderdetails_product FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: payment
CREATE TABLE IF NOT EXISTS payment (
  pay_id INT(11) NOT NULL AUTO_INCREMENT,
  amt DOUBLE NOT NULL,
  customer_id INT(11) NOT NULL,
  order_id INT(11) NOT NULL,
  currency TEXT NOT NULL,
  payment_date DATE NOT NULL,
  PRIMARY KEY (pay_id),
  KEY (customer_id),
  KEY (order_id),
  CONSTRAINT fk_payment_customer FOREIGN KEY (customer_id) REFERENCES customer (customer_id),
  CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
