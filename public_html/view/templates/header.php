<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? escape_html($page_title) . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/sleep.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/header_footer.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/toast.css">
    
    <!-- Additional CSS Files -->
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/<?php echo $css_file; ?>?v=<?php echo time(); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
    
    <!-- Sidebar Navigation (if not a standalone page) -->
    <?php if (!isset($standalone_page) || !$standalone_page): ?>
        <!-- Mobile Menu Toggle Button (only visible on mobile) -->
        <button class="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="sidebar-navigation">
            <svg class="mobile-menu-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        
        <!-- Overlay Backdrop (mobile only) -->
        <div class="sidebar-overlay" aria-hidden="true"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar-navigation" role="navigation" aria-label="Main navigation">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <a href="<?php echo url('index.php'); ?>" class="sidebar-logo">
                    <img src="<?php echo ASSETS_URL; ?>/images/logo.svg" alt="<?php echo APP_NAME; ?>" class="sidebar-logo-image" onerror="this.style.display='none';">
                    <span class="sidebar-logo-text"><?php echo APP_NAME; ?></span>
                </a>
                <button class="sidebar-toggle" aria-expanded="true" aria-controls="sidebar-navigation" aria-label="Toggle sidebar">
                    <svg class="sidebar-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>
            
            <!-- Sidebar Navigation -->
            <nav class="sidebar-nav">
                <ul class="sidebar-nav-list">
                    <li class="sidebar-nav-item">
                        <a href="<?php echo url('view/product/all_product.php'); ?>" class="sidebar-nav-link" data-tooltip="All Products">
                            <span class="sidebar-nav-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </span>
                            <span class="sidebar-nav-label">All Products</span>
                        </a>
                    </li>
                    
                    <?php if (is_logged_in()): ?>
                        <?php if (!is_admin()): ?>
                            <!-- Regular User Cart (not for admins) -->
                            <li class="sidebar-nav-item">
                                <a href="<?php echo url('view/cart/view_cart.php'); ?>" class="sidebar-nav-link" data-tooltip="Shopping Cart">
                                    <span class="sidebar-nav-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </span>
                                    <span class="sidebar-nav-label">Cart</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (is_admin()): ?>
                            <!-- Admin User Menu -->
                            <li class="sidebar-nav-item">
                                <a href="<?php echo url('view/admin/categories.php'); ?>" class="sidebar-nav-link" data-tooltip="Categories">
                                    <span class="sidebar-nav-icon">
                                        <i class="fas fa-tags"></i>
                                    </span>
                                    <span class="sidebar-nav-label">Category</span>
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a href="<?php echo url('view/admin/brands.php'); ?>" class="sidebar-nav-link" data-tooltip="Brands">
                                    <span class="sidebar-nav-icon">
                                        <i class="fas fa-trademark"></i>
                                    </span>
                                    <span class="sidebar-nav-label">Brand</span>
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a href="<?php echo url('view/admin/products.php'); ?>" class="sidebar-nav-link" data-tooltip="Add Product">
                                    <span class="sidebar-nav-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </span>
                                    <span class="sidebar-nav-label">Add Product</span>
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a href="<?php echo url('view/admin/orders.php'); ?>" class="sidebar-nav-link" data-tooltip="Orders">
                                    <span class="sidebar-nav-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </span>
                                    <span class="sidebar-nav-label">Orders</span>
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a href="<?php echo str_replace('/public_html', '', BASE_URL) . '/actions/logout_action.php'; ?>" class="sidebar-nav-link" data-tooltip="Logout">
                                    <span class="sidebar-nav-icon">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </span>
                                    <span class="sidebar-nav-label">Logout</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- Regular User Menu -->
                            <li class="sidebar-nav-item">
                                <a href="<?php echo str_replace('/public_html', '', BASE_URL) . '/actions/logout_action.php'; ?>" class="sidebar-nav-link" data-tooltip="Logout">
                                    <span class="sidebar-nav-icon">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </span>
                                    <span class="sidebar-nav-label">Logout</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Guest User Menu -->
                        <li class="sidebar-nav-item">
                            <a href="<?php echo url('view/user/register.php'); ?>" class="sidebar-nav-link" data-tooltip="Register">
                                <span class="sidebar-nav-icon">
                                    <i class="fas fa-user-plus"></i>
                                </span>
                                <span class="sidebar-nav-label">Register</span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a href="<?php echo url('view/user/login.php'); ?>" class="sidebar-nav-link" data-tooltip="Login">
                                <span class="sidebar-nav-icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </span>
                                <span class="sidebar-nav-label">Login</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        
        <!-- Top Bar with Search (replaces horizontal header) -->
        <div class="top-bar">
            <div class="top-bar-content">
                <!-- Search Box -->
                <div class="top-bar-search">
                    <form action="<?php echo url('view/product/product_search_result.php'); ?>" method="GET" class="search-form" id="header-search-form">
                        <input type="text" 
                               name="query" 
                               id="header-search-input"
                               placeholder="Search products..." 
                               class="search-input"
                               value="<?php echo isset($_GET['query']) ? escape_html($_GET['query']) : ''; ?>">
                        <button type="submit" class="search-btn" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main id="main-content" role="main" class="<?php echo isset($main_class) ? escape_html($main_class) : 'main-content'; ?>">
