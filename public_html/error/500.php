<?php
/**
 * 500 Error Page
 * 
 * Internal server error page
 */

// Include core settings
require_once __DIR__ . '/../settings/core.php';

// Set page variables
$page_title = '500 - Internal Server Error';
$page_description = 'An internal server error occurred. Please try again later.';
$standalone_page = true;
$body_class = 'error-page error-500';

// Set HTTP status code
http_response_code(500);

// Include header
include VIEW_PATH . '/templates/header.php';
?>

<div class="error-container">
    <div class="error-content">
        <div class="error-graphic">
            <div class="error-number">500</div>
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        
        <div class="error-text">
            <h1>Internal Server Error</h1>
            <p>Something went wrong on our end. We're working to fix this issue. Please try again in a few moments.</p>
        </div>
        
        <div class="error-actions">
            <a href="<?php echo BASE_URL; ?>/public/index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Go Home
            </a>
            <a href="javascript:location.reload()" class="btn btn-outline">
                <i class="fas fa-redo"></i> Try Again
            </a>
        </div>
        
        <div class="error-info">
            <p><strong>Error Code:</strong> 500</p>
            <p><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <?php if (APP_ENV === 'development'): ?>
                <p><strong>Environment:</strong> Development</p>
            <?php endif; ?>
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
    background: var(--error-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 20px;
}

.error-icon {
    font-size: 3rem;
    color: #fa709a;
    opacity: 0.7;
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

.error-info {
    background: var(--light-gray);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-top: 40px;
    text-align: left;
}

.error-info p {
    margin-bottom: 10px;
    font-size: 0.9rem;
    color: var(--gray);
}

.error-info p:last-child {
    margin-bottom: 0;
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
