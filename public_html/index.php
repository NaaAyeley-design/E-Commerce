<?php
/**
 * Main Entry Point - Homepage
 * 
 * This is the main landing page for the e-commerce platform.
 * It serves as the homepage and entry point for all users.
 */

// Suppress error reporting to prevent code from showing
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../settings/core.php';

// Set page variables
$page_title = 'Home';
$page_description = 'Welcome to KenteKart. Discover amazing products with secure shopping experience.';
$body_class = 'home-page';
$additional_css = ['homepage.css']; // Specific CSS for homepage

// Include header
include __DIR__ . '/view/templates/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">Welcome shsisishis to <?php echo APP_NAME; ?></h1>
                <p class="hero-subtitle">
                    Discover amazing products with KenteKart - your secure and modern shopping platform. 
                    Shop with confidence and enjoy a seamless shopping experience.
                </p>
                <div class="hero-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo url('view/user/dashboard.php'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                        <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-outline btn-lg">
                            <i class="fas fa-shopping-bag"></i> Browse Products
                        </a>
                    <?php else: ?>
                        <a href="<?php echo url('view/user/register.php'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                        <a href="<?php echo url('view/user/login.php'); ?>" class="btn btn-outline btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-graphic">
                    <div class="graphic-circle circle-1"></div>
                    <div class="graphic-circle circle-2"></div>
                    <div class="graphic-circle circle-3"></div>
                    <div class="hero-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose Our Platform?</h2>
            <p>Experience the best in online shopping with our feature-rich platform</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Shopping</h3>
                <p>Your data and transactions are protected with industry-standard security measures.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3>Fast Delivery</h3>
                <p>Quick and reliable shipping options to get your products delivered on time.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Our customer support team is always ready to help you with any questions.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>Easy Returns</h3>
                <p>Hassle-free return policy to ensure your complete satisfaction with every purchase.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number" data-count="10000">0</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="5000">0</div>
                <div class="stat-label">Products Available</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="50">0</div>
                <div class="stat-label">Countries Served</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="99">0</div>
                <div class="stat-label">% Satisfaction Rate</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php if (!is_logged_in()): ?>
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Start Shopping?</h2>
            <p>Join thousands of satisfied customers and discover amazing products today!</p>
            <div class="cta-actions">
                <a href="<?php echo url('view/user/register.php'); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket"></i> Get Started Now
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Additional CSS for home page -->
<style>
.hero-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    overflow: hidden;
    position: relative;
}

.hero-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.2rem;
    margin-bottom: 40px;
    opacity: 0.9;
    line-height: 1.6;
}

.hero-actions {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.btn-lg {
    padding: 16px 32px;
    font-size: 1.1rem;
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--white);
    color: var(--white);
}

.btn-outline:hover {
    background: var(--white);
    color: var(--dark);
}

.hero-graphic {
    position: relative;
    width: 300px;
    height: 300px;
    margin: 0 auto;
}

.graphic-circle {
    position: absolute;
    border-radius: 50%;
    opacity: 0.1;
    animation: float 6s ease-in-out infinite;
}

.circle-1 {
    width: 100px;
    height: 100px;
    background: var(--white);
    top: 20px;
    left: 20px;
    animation-delay: 0s;
}

.circle-2 {
    width: 60px;
    height: 60px;
    background: var(--white);
    top: 50px;
    right: 30px;
    animation-delay: 2s;
}

.circle-3 {
    width: 80px;
    height: 80px;
    background: var(--white);
    bottom: 30px;
    left: 50px;
    animation-delay: 4s;
}

.hero-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 4rem;
    color: var(--white);
    animation: pulse 2s ease-in-out infinite;
}

.features-section {
    padding: 80px 0;
    background: var(--white);
}

.section-header {
    text-align: center;
    margin-bottom: 60px;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.section-header p {
    font-size: 1.1rem;
    color: var(--gray);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
}

.feature-card {
    text-align: center;
    padding: 40px 20px;
    border-radius: var(--border-radius);
    transition: var(--transition);
    border: 1px solid #e9ecef;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-hover);
}

.feature-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--white);
}

.feature-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--dark);
}

.feature-card p {
    color: var(--gray);
    line-height: 1.6;
}

.stats-section {
    padding: 60px 0;
    background: var(--light-gray);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 40px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 1.1rem;
    color: var(--gray);
    font-weight: 500;
}

.cta-section {
    padding: 80px 0;
    background: var(--primary-gradient);
    color: var(--white);
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.cta-content p {
    font-size: 1.2rem;
    margin-bottom: 40px;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .hero-content {
        grid-template-columns: 1fr;
        gap: 40px;
        text-align: center;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-actions {
        justify-content: center;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-lg {
        padding: 14px 24px;
        font-size: 1rem;
    }
}
</style>

<script>
// Animate counters
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.stat-number');
    
    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-count'));
        const increment = target / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current).toLocaleString();
            }
        }, 20);
    };
    
    // Intersection Observer for counter animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/view/templates/footer.php';
?>