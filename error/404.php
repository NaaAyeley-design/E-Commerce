<?php
/**
 * 404 Error Page
 * 
 * Page not found error with helpful navigation
 */

// Include core settings
require_once __DIR__ . '/../settings/core.php';

// Set page variables
$page_title = '404 - Page Not Found';
$page_description = 'The page you are looking for could not be found.';
$standalone_page = true;
$body_class = 'error-page error-404';

// Set HTTP status code
http_response_code(404);

// Include header
include VIEW_PATH . '/templates/header.php';
?>

<div class="error-container">
    <div class="error-content">
        <div class="error-graphic">
            <div class="error-number">404</div>
            <div class="error-icon">
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <div class="error-text">
            <h1>Page Not Found</h1>
            <p>Sorry, the page you are looking for could not be found. It might have been moved, deleted, or you entered the wrong URL.</p>
        </div>
        
        <div class="error-actions">
            <a href="<?php echo BASE_URL; ?>/public/index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Go Home
            </a>
            <a href="javascript:history.back()" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
        </div>
        
        <div class="helpful-links">
            <h3>You might be looking for:</h3>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/view/product/product_list.php">Browse Products</a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="<?php echo BASE_URL; ?>/view/user/dashboard.php">Your Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/view/order/order_history.php">Order History</a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>/view/user/login.php">Login</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/view/user/register.php">Create Account</a></li>
                <?php endif; ?>
                <li><a href="#">Contact Support</a></li>
            </ul>
        </div>
    </div>
</div>

<style>
.error-page {
    background: var(--bg-gradient);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.error-container {
    background: var(--white);
    padding: 60px 40px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    text-align: center;
    max-width: 600px;
    width: 100%;
    animation: slideInUp 0.6s ease-out;
}

.error-graphic {
    position: relative;
    margin-bottom: 40px;
}

.error-number {
    font-size: 8rem;
    font-weight: 700;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 20px;
}

.error-icon {
    font-size: 3rem;
    color: var(--gray);
    opacity: 0.5;
}

.error-text h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 20px;
}

.error-text p {
    font-size: 1.1rem;
    color: var(--gray);
    line-height: 1.6;
    margin-bottom: 40px;
}

.error-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.helpful-links {
    text-align: left;
    background: var(--light-gray);
    padding: 30px;
    border-radius: var(--border-radius);
    margin-top: 40px;
}

.helpful-links h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 15px;
}

.helpful-links ul {
    list-style: none;
    padding: 0;
}

.helpful-links li {
    margin-bottom: 10px;
}

.helpful-links a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.helpful-links a:hover {
    color: #764ba2;
    text-decoration: underline;
}

@media (max-width: 768px) {
    .error-container {
        padding: 40px 20px;
    }
    
    .error-number {
        font-size: 6rem;
    }
    
    .error-text h1 {
        font-size: 2rem;
    }
    
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .error-actions .btn {
        width: 100%;
        max-width: 250px;
    }
}
</style>

<?php
// Include footer
include VIEW_PATH . '/templates/footer.php';
?>
