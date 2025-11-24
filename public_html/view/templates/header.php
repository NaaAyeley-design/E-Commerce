<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/favicon_io/favicon-16x16.png">
    <link rel="icon" href="<?php echo BASE_URL; ?>/favicon_io/favicon.ico">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/favicon_io/site.webmanifest">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? escape_html($page_title) . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/sleep.css?v=<?php echo get_css_version('sleep.css'); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/header_footer.css?v=<?php echo get_css_version('header_footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/sidebar.css?v=<?php echo get_css_version('sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/toast.css?v=<?php echo get_css_version('toast.css'); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/african-savanna-theme.css?v=<?php echo get_css_version('african-savanna-theme.css'); ?>">
    
    <!-- Additional CSS Files -->
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/<?php echo $css_file; ?>?v=<?php echo get_css_version($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo isset($page_description) ? escape_html($page_description) : 'KenteKart - Modern e-commerce platform with secure authentication'; ?>">
    <meta name="keywords" content="<?php echo isset($page_keywords) ? escape_html($page_keywords) : 'kentekart, ecommerce, shopping, online store, authentication'; ?>">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? escape_html($page_title) . ' - ' . APP_NAME : APP_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? escape_html($page_description) : 'KenteKart - Modern e-commerce platform with secure authentication'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo current_url(); ?>">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/images/og-image.jpg">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? escape_html($page_title) . ' - ' . APP_NAME : APP_NAME; ?>">
    <meta name="twitter:description" content="<?php echo isset($page_description) ? escape_html($page_description) : 'KenteKart - Modern e-commerce platform with secure authentication'; ?>">
    <meta name="twitter:image" content="<?php echo ASSETS_URL; ?>/images/og-image.jpg">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    
    <!-- Additional Head Content -->
    <?php if (isset($additional_head_content)): ?>
        <?php echo $additional_head_content; ?>
    <?php endif; ?>
</head>
<body class="no-js <?php echo isset($body_class) ? escape_html($body_class) : ''; ?>">
    
    <!-- Flash Messages -->
    <?php $flash_messages = get_flash_messages(); ?>
    <?php if (!empty($flash_messages)): ?>
        <div class="flash-messages">
            <?php foreach ($flash_messages as $message): ?>
                <div class="flash-message flash-<?php echo escape_html($message['type']); ?>">
                    <span class="message-text"><?php echo escape_html($message['message']); ?></span>
                    <button class="close-flash" onclick="this.parentElement.remove();">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php
    // Determine if this is an admin page
    $is_admin_page = false;
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $script_path = $_SERVER['SCRIPT_NAME'];
        $is_admin_page = (strpos($script_path, '/admin/') !== false) || 
                        (strpos($script_path, '/view/admin/') !== false);
    }
    // Also check if user is admin and explicitly set admin layout
    if (isset($use_admin_layout) && $use_admin_layout) {
        $is_admin_page = true;
    }
    
    // Add admin-page class to body for CSS targeting
    if ($is_admin_page) {
        $body_class = (isset($body_class) ? $body_class . ' ' : '') . 'admin-page';
    }
    ?>
    
    <?php if (!isset($standalone_page) || !$standalone_page): ?>
        <!-- Top Navigation Bar (for non-admin pages) -->
        <nav class="top-navbar" role="navigation" aria-label="Main navigation">
            <div class="top-navbar-container">
                <!-- Logo -->
                <div class="top-navbar-logo">
                    <a href="<?php echo url('index.php'); ?>" class="top-navbar-logo-link">
                        <span class="top-navbar-logo-text"><?php echo APP_NAME; ?></span>
                    </a>
                </div>
                
                <!-- Navigation Links -->
                <ul class="top-navbar-menu" id="top-navbar-menu">
                    <li class="top-navbar-item">
                        <a href="<?php echo url('index.php'); ?>" class="top-navbar-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <?php if (!is_logged_in() || !is_admin()): ?>
                    <li class="top-navbar-item">
                        <a href="<?php echo url('view/product/all_product.php'); ?>" class="top-navbar-link">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (is_logged_in()): ?>
                        <?php if (!is_admin()): ?>
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/cart/view_cart.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Cart</span>
                                </a>
                            </li>
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/user/dashboard.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-user"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- Admin Navigation Links -->
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/admin/dashboard.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/admin/categories.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-tags"></i>
                                    <span>Categories</span>
                                </a>
                            </li>
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/admin/brands.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-trademark"></i>
                                    <span>Brands</span>
                                </a>
                            </li>
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/admin/products.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-box"></i>
                                    <span>Products</span>
                                </a>
                            </li>
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/admin/orders.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Orders</span>
                                </a>
                            </li>
                            <li class="top-navbar-item">
                                <a href="<?php echo url('view/admin/users.php'); ?>" class="top-navbar-link">
                                    <i class="fas fa-users"></i>
                                    <span>Users</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="top-navbar-item">
                            <a href="<?php echo url('actions/logout_action.php'); ?>" class="top-navbar-link">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="top-navbar-item">
                            <a href="<?php echo url('view/user/login.php'); ?>" class="top-navbar-link">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                            </a>
                        </li>
                        <li class="top-navbar-item">
                            <a href="<?php echo url('view/user/register.php'); ?>" class="top-navbar-link top-navbar-link-primary">
                                <i class="fas fa-user-plus"></i>
                                <span>Register</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Box -->
                <div class="top-navbar-search">
                    <form action="<?php echo url('view/product/all_product.php'); ?>" method="GET" class="top-navbar-search-form">
                        <input type="text" 
                               name="query" 
                               placeholder="Search products..." 
                               class="top-navbar-search-input"
                               value="<?php echo isset($_GET['query']) ? escape_html($_GET['query']) : ''; ?>">
                        <button type="submit" class="top-navbar-search-btn" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="top-navbar-mobile-toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="top-navbar-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main id="main-content" role="main" class="<?php echo isset($main_class) ? escape_html($main_class) : 'main-content'; ?>">
