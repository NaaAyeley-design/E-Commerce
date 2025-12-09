-- ============================================
-- KenteKart Academy Database Schema
-- ============================================

-- Academy Courses Table
CREATE TABLE IF NOT EXISTS academy_courses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    category VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content_type ENUM('video', 'article', 'download') NOT NULL DEFAULT 'article',
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
    duration_minutes INT(11) NOT NULL DEFAULT 0,
    video_url VARCHAR(500) DEFAULT NULL,
    article_content TEXT DEFAULT NULL,
    download_url VARCHAR(500) DEFAULT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    view_count INT(11) DEFAULT 0,
    completion_count INT(11) DEFAULT 0,
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_category (category),
    INDEX idx_difficulty (difficulty_level),
    INDEX idx_content_type (content_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academy User Progress Table
CREATE TABLE IF NOT EXISTS academy_user_progress (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    progress_percentage INT(11) DEFAULT 0,
    status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    last_accessed TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_course (user_id, course_id),
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES academy_courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academy Certificates Table
CREATE TABLE IF NOT EXISTS academy_certificates (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    certificate_type ENUM('course', 'path', 'category', 'academy_graduate') NOT NULL,
    reference_id INT(11) NOT NULL,
    reference_name VARCHAR(255) NOT NULL,
    certificate_code VARCHAR(50) NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_certificate_code (certificate_code),
    INDEX idx_user_id (user_id),
    INDEX idx_certificate_type (certificate_type),
    FOREIGN KEY (user_id) REFERENCES customer(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academy Badges Table
CREATE TABLE IF NOT EXISTS academy_badges (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    badge_type VARCHAR(50) NOT NULL,
    badge_description TEXT,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_badge (user_id, badge_name),
    INDEX idx_user_id (user_id),
    INDEX idx_badge_type (badge_type),
    FOREIGN KEY (user_id) REFERENCES customer(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academy Resources Table
CREATE TABLE IF NOT EXISTS academy_resources (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('template', 'checklist', 'guide') NOT NULL,
    file_type VARCHAR(20) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_size VARCHAR(20) NOT NULL,
    download_count INT(11) DEFAULT 0,
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academy Resource Downloads Table
CREATE TABLE IF NOT EXISTS academy_resource_downloads (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    resource_id INT(11) NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_id (user_id),
    INDEX idx_resource_id (resource_id),
    FOREIGN KEY (user_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES academy_resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academy Learning Paths Table
CREATE TABLE IF NOT EXISTS academy_learning_paths (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    difficulty_level VARCHAR(50) NOT NULL,
    course_ids TEXT NOT NULL COMMENT 'JSON array of course IDs',
    total_duration INT(11) NOT NULL COMMENT 'Total duration in minutes',
    certificate_name VARCHAR(255) NOT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_difficulty (difficulty_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

