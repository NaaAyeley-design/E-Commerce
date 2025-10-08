
-- E-Commerce Authentication System Database Schema
-- This file contains the SQL statements for the existing shoppn database

-- Customer table (existing structure)
CREATE TABLE IF NOT EXISTS customer (
    customer_id INT(11) NOT NULL AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(50) NOT NULL,
    customer_pass VARCHAR(150) NOT NULL,
    customer_country VARCHAR(30) NOT NULL,
    customer_city VARCHAR(30) NOT NULL,
    customer_contact VARCHAR(15) NOT NULL,
    customer_image VARCHAR(100) DEFAULT NULL,
    user_role INT(11) NOT NULL,
    PRIMARY KEY (customer_id),
    UNIQUE KEY customer_email (customer_email)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer_token (customer_id),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- User roles table (optional, for future expansion)
CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL,
    role_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT IGNORE INTO user_roles (role_id, role_name, role_description) VALUES
(1, 'Admin', 'Administrator with full access'),
(2, 'Customer', 'Regular customer account');

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    cat_id INT(11) NOT NULL AUTO_INCREMENT,
    cat_name VARCHAR(100) NOT NULL,
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cat_id),
    FOREIGN KEY (user_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, cat_name),
    INDEX idx_user_id (user_id),
    INDEX idx_cat_name (cat_name)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Sessions table (optional, for session management)
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    customer_id INT NOT NULL,
    session_data TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_expires (expires_at)
);

-- Audit log table (optional, for tracking user actions)
CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Create indexes for better performance
CREATE INDEX idx_customer_email ON customer(customer_email);
CREATE INDEX idx_customer_role ON customer(user_role);
CREATE INDEX idx_customer_created ON customer(created_at);

-- Clean up expired password reset tokens (run this periodically)
-- DELETE FROM password_resets WHERE expires_at < NOW();

-- Clean up expired sessions (run this periodically)
-- DELETE FROM user_sessions WHERE expires_at < NOW();
