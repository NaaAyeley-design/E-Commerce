<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? escape($page_title) . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/fontawesome/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo isset($page_description) ? escape($page_description) : 'Modern e-commerce platform with secure authentication'; ?>">
    <meta name="keywords" content="<?php echo isset($page_keywords) ? escape($page_keywords) : 'ecommerce, shopping, online store, authentication'; ?>">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? escape($page_title) . ' - ' . APP_NAME : APP_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? escape($page_description) : 'Modern e-commerce platform with secure authentication'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo current_url(); ?>">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/images/og-image.jpg">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? escape($page_title) . ' - ' . APP_NAME : APP_NAME; ?>">
    <meta name="twitter:description" content="<?php echo isset($page_description) ? escape($page_description) : 'Modern e-commerce platform with secure authentication'; ?>">
    <meta name="twitter:image" content="<?php echo ASSETS_URL; ?>/images/og-image.jpg">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    
    <!-- Additional Head Content -->
    <?php if (isset($additional_head_content)): ?>
        <?php echo $additional_head_content; ?>
    <?php endif; ?>
</head>
<body class="<?php echo isset($body_class) ? escape($body_class) : ''; ?>">
    
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Flash Messages -->
    <?php $flash_messages = get_flash_messages(); ?>
    <?php if (!empty($flash_messages)): ?>
        <div class="flash-messages">
            <?php foreach ($flash_messages as $message): ?>
                <div class="flash-message flash-<?php echo escape($message['type']); ?>">
                    <span class="message-text"><?php echo escape($message['message']); ?></span>
                    <button class="close-flash" onclick="this.parentElement.remove();">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Navigation Header (if not a standalone page) -->
    <?php if (!isset($standalone_page) || !$standalone_page): ?>
        <header class="header" role="banner">
            <div class="container">
                <div class="header-content">
                    <!-- Logo -->
                    <div class="logo">
                        <a href="<?php echo BASE_URL; ?>/public/index.php" class="logo-link">
                            <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="<?php echo APP_NAME; ?>" class="logo-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <span class="logo-text"><?php echo APP_NAME; ?></span>
                        </a>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <nav class="main-nav" role="navigation" aria-label="Main navigation">
                        <ul class="nav-menu">
                            <?php if (is_logged_in()): ?>
                                <!-- Authenticated User Menu -->
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL; ?>/view/user/dashboard.php" class="nav-link">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL; ?>/view/product/product_list.php" class="nav-link">
                                        <i class="fas fa-shopping-bag"></i> Products
                                    </a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                                        <i class="fas fa-user"></i> <?php echo escape($_SESSION['customer_name']); ?>
                                        <i class="fas fa-chevron-down"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a href="<?php echo BASE_URL; ?>/view/user/profile.php" class="dropdown-link">Profile</a></li>
                                        <li><a href="<?php echo BASE_URL; ?>/view/order/order_history.php" class="dropdown-link">Order History</a></li>
                                        <li><a href="<?php echo BASE_URL; ?>/view/user/settings.php" class="dropdown-link">Settings</a></li>
                                        <li class="dropdown-divider"></li>
                                        <li><a href="<?php echo BASE_URL; ?>/actions/logout_action.php" class="dropdown-link">Logout</a></li>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <!-- Guest User Menu -->
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL; ?>/view/product/product_list.php" class="nav-link">
                                        <i class="fas fa-shopping-bag"></i> Products
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL; ?>/view/user/login.php" class="nav-link">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL; ?>/view/user/register.php" class="nav-link btn-primary">
                                        <i class="fas fa-user-plus"></i> Register
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        
                        <!-- Mobile Menu Toggle -->
                        <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                        </button>
                    </nav>
                </div>
            </div>
        </header>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main id="main-content" role="main" class="<?php echo isset($main_class) ? escape($main_class) : 'main-content'; ?>">
