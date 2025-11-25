<?php
/**
 * Homepage - KenteKart
 * Authentic Ghanaian Fashion Marketplace
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../settings/core.php';

$page_title = 'Home - Authentic Ghanaian Fashion';
$page_description = 'Discover authentic Ghanaian fashion from talented artisans. Support women-owned businesses and preserve African cultural heritage.';
$body_class = 'home-page';
$additional_css = ['homepage.css'];

include __DIR__ . '/view/templates/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <!-- Particles Container (Hero Section Only) -->
    <div class="particle-container hero-particles">
        <?php for ($i = 1; $i <= 15; $i++): ?>
            <div class="particle"></div>
        <?php endfor; ?>
    </div>
    
    <!-- Sparkles Container (Hero Section Only) -->
    <div class="sparkle-container hero-sparkles">
        <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="sparkle"></div>
        <?php endfor; ?>
    </div>
    
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-badge">
                    <i class="fas fa-heart"></i> Supporting 500+ Ghanaian Artisans
                </div>
                <h1 class="hero-title">Discover Authentic <span class="accent-gradient">Ghanaian Fashion</span></h1>
                <p class="hero-subtitle">
                    Connect with your heritage through handcrafted pieces. Every purchase empowers artisans and preserves cultural tradition.
                </p>
                <div class="hero-stats-inline">
                    <div class="hero-stat-item">
                        <i class="fas fa-users"></i> 70% Women-Owned
                    </div>
                    <div class="hero-stat-item">
                        <i class="fas fa-globe-africa"></i> 50+ Countries
                    </div>
                    <div class="hero-stat-item">
                        <i class="fas fa-leaf"></i> 100% Ethical
                    </div>
                </div>
                <div class="hero-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-bag"></i> Explore Collections
                        </a>
                        <a href="<?php echo url('view/user/dashboard.php'); ?>" class="btn btn-outline btn-lg">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?php echo url('view/user/register.php'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-star"></i> Get Started
                        </a>
                        <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-outline btn-lg">
                            <i class="fas fa-shopping-bag"></i> Browse
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-visual">
                    <div class="kente-pattern pattern-1"></div>
                    <div class="kente-pattern pattern-2"></div>
                    <div class="kente-pattern pattern-3"></div>
                    <div class="cultural-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Impact Section -->
<section class="impact-section">
    <div class="container">
        <div class="section-header">
            <h2>Our <span class="accent-gradient">Impact</span></h2>
            <p>Every purchase creates positive change across Ghana</p>
        </div>
        
        <div class="impact-grid">
            <div class="impact-card">
                <div class="impact-icon"><i class="fas fa-user-friends"></i></div>
                <div class="impact-number" data-count="500">500+</div>
                <div class="impact-label">Designers Empowered</div>
            </div>
            
            <div class="impact-card">
                <div class="impact-icon"><i class="fas fa-female"></i></div>
                <div class="impact-number">70%</div>
                <div class="impact-label">Women-Owned</div>
            </div>
            
            <div class="impact-card">
                <div class="impact-icon"><i class="fas fa-map-marked-alt"></i></div>
                <div class="impact-number" data-count="50">50+</div>
                <div class="impact-label">Countries Connected</div>
            </div>
            
            <div class="impact-card">
                <div class="impact-icon"><i class="fas fa-users"></i></div>
                <div class="impact-number" data-count="125000">125K+</div>
                <div class="impact-label">Lives Supported</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2>Why <span class="accent-gradient">KenteKart</span>?</h2>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-certificate"></i></div>
                <h3>Authenticity Guaranteed</h3>
                <p>Every designer verified. Every piece genuine Ghanaian craftsmanship.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-hand-holding-heart"></i></div>
                <h3>Direct Support</h3>
                <p>Purchases go directly to artisans, ensuring fair compensation.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-globe-africa"></i></div>
                <h3>Heritage Connection</h3>
                <p>Reconnect with roots through fashion that celebrates African identity.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                <h3>Sustainable</h3>
                <p>Quality handcrafted pieces that last, not fast fashion.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header">
            <h2>Customer <span class="accent-gradient">Stories</span></h2>
        </div>
        
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">
                    "KenteKart helps me stay connected to my roots. Quality is incredible and I'm supporting artisans back home."
                </p>
                <div class="testimonial-author">
                    <strong>Kwame M.</strong> <span>London, UK</span>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">
                    "Passionate about ethical fashion. KenteKart's transparency makes it my go-to for authentic African designs."
                </p>
                <div class="testimonial-author">
                    <strong>Sarah J.</strong> <span>New York, USA</span>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">
                    "Teaching my children about their heritage through beautiful pieces. The cultural stories make it special."
                </p>
                <div class="testimonial-author">
                    <strong>Abena O.</strong> <span>Toronto, Canada</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-overlay"></div>
    <div class="container">
        <div class="cta-content">
            <?php if (!is_logged_in()): ?>
                <h2>Begin Your Journey Today</h2>
                <p>Join thousands discovering authentic Ghanaian fashion</p>
                <div class="cta-actions">
                    <a href="<?php echo url('view/user/register.php'); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                    <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-shopping-bag"></i> Browse
                    </a>
                </div>
            <?php else: ?>
                <h2>Discover New Designers</h2>
                <p>Explore collections and support artisan communities</p>
                <div class="cta-actions">
                    <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag"></i> Explore
                    </a>
                    <a href="<?php echo url('view/user/dashboard.php'); ?>" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/view/templates/footer.php'; ?>
