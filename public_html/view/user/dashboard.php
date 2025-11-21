<?php
/**
 * KenteKart User Dashboard - Cultural Heritage Redesign
 * 
 * A culturally rich dashboard connecting Ghanaian fashion designers 
 * with global customers while celebrating African cultural heritage.
 */

// Suppress error reporting to prevent code from showing
$suppress_errors = true;
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Include core settings
require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/order_controller.php';

// Set page variables
$page_title = 'My Cultural Journey - KenteKart';
$page_description = 'Connect with Ghanaian artisans, discover your heritage, and see the impact of your purchases.';
$body_class = 'dashboard-page cultural-dashboard';
$additional_css = ['dashboard.css']; // Custom dashboard page styles

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=login_required');
    exit;
}

// Get current user data
$user = new user_class();
$customer = $user->get_customer_by_id($_SESSION['user_id']);
if (!$customer) {
    header('Location: ' . BASE_URL . '/view/user/login.php?error=session_expired');
    exit;
}

// Check if user is admin and redirect to admin dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
    header('Location: ' . BASE_URL . '/view/admin/dashboard.php');
    exit;
}

// TODO: Replace with actual database queries when backend is ready
// Get user orders (placeholder - will be replaced with actual data)
$order_result = get_customer_orders_ctr($_SESSION['user_id'], 1, 5);
$recent_orders = isset($order_result['orders']) ? $order_result['orders'] : [];

// Placeholder Impact Metrics (TODO: Calculate from actual order/product data)
$impact_metrics = [
    'designers_supported' => count($recent_orders) > 0 ? 12 : 0,
    'communities_helped' => count($recent_orders) > 0 ? 5 : 0,
    'women_entrepreneurs' => count($recent_orders) > 0 ? 75 : 0,
    'cultural_stories' => count($recent_orders) > 0 ? 8 : 0,
];

// Placeholder Designer Data (TODO: Fetch from database)
$supported_designers = [
    [
        'id' => 1,
        'name' => 'Ama Serwah',
        'region' => 'Kumasi',
        'specialty' => 'Kente Weaving',
        'photo' => 'https://via.placeholder.com/300x200/d4a373/ffffff?text=Ama+Serwah',
        'story' => 'Third-generation Kente weaver preserving traditional Asante patterns...',
        'women_owned' => true,
        'followers' => 1240
    ],
    [
        'id' => 2,
        'name' => 'Kwame Adinkra',
        'region' => 'Accra',
        'specialty' => 'Adinkra Symbols',
        'photo' => 'https://via.placeholder.com/300x200/8b6f47/ffffff?text=Kwame+Adinkra',
        'story' => 'Master craftsman creating contemporary designs with ancient symbols...',
        'women_owned' => false,
        'followers' => 890
    ],
    [
        'id' => 3,
        'name' => 'Efua Mensah',
        'region' => 'Tamale',
        'specialty' => 'Batik & Tie-Dye',
        'photo' => 'https://via.placeholder.com/300x200/b8860b/ffffff?text=Efua+Mensah',
        'story' => 'Empowering women through traditional textile techniques...',
        'women_owned' => true,
        'followers' => 2100
    ],
];

// Placeholder Recommended Designers
$recommended_designers = [
    [
        'id' => 4,
        'name' => 'Yaa Asantewaa Designs',
        'region' => 'Cape Coast',
        'specialty' => 'Contemporary Kente',
        'photo' => 'https://via.placeholder.com/300x200/d4a373/ffffff?text=Yaa+Asantewaa',
        'match_reason' => 'Based on your love for traditional patterns'
    ],
    [
        'id' => 5,
        'name' => 'Kofi Adom',
        'region' => 'Kumasi',
        'specialty' => 'Modern Adinkra',
        'photo' => 'https://via.placeholder.com/300x200/8b6f47/ffffff?text=Kofi+Adom',
        'match_reason' => 'Similar to designers you follow'
    ],
];

// Placeholder AI Recommendations
$ai_recommendations = [
    [
        'product_id' => 1,
        'title' => 'Handwoven Kente Scarf',
        'designer' => 'Ama Serwah',
        'price' => 89.99,
        'image' => 'https://via.placeholder.com/240x200/d4a373/ffffff?text=Kente+Scarf',
        'reason' => 'Because you loved traditional Kente patterns'
    ],
    [
        'product_id' => 2,
        'title' => 'Adinkra Symbol Tote Bag',
        'designer' => 'Kwame Adinkra',
        'price' => 45.99,
        'image' => 'https://via.placeholder.com/240x200/8b6f47/ffffff?text=Tote+Bag',
        'reason' => 'Matches your cultural exploration journey'
    ],
];

// Ghanaian Proverbs (rotating)
$proverbs = [
    "Sankofa - It is not wrong to go back for that which you have forgotten.",
    "Wo nsa da mu a, wo nko ara na wo be hu di - If your hands are in the dish, you must eat with others.",
    "Se wo were fi na wosan kofa a yenkyi - It is not taboo to go back and fetch what you forgot.",
];

$current_proverb = $proverbs[array_rand($proverbs)];

// Pattern of the Week
$pattern_of_week = [
    'name' => 'Sankofa',
    'meaning' => 'Go back and fetch it',
    'symbolism' => 'Learning from the past to build the future',
    'image' => 'https://via.placeholder.com/180x180/d4a373/ffffff?text=Sankofa',
    'description' => 'The Sankofa bird symbolizes the importance of learning from the past. This pattern reminds us that wisdom comes from understanding our history and heritage.'
];

// Cultural Regions Explored
$regions_explored = ['Kumasi', 'Accra', 'Tamale', 'Cape Coast'];
$cultural_badges = ['Kente Explorer', 'Adinkra Scholar', 'Heritage Keeper'];

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="cultural-dashboard-container">
    
    <!-- CULTURAL HERO SECTION -->
    <div class="cultural-hero">
        <div class="hero-greeting">
            <h1>Akwaaba, <span class="user-name"><?php echo escape_html($customer['customer_name']); ?>!</span></h1>
            <p>Welcome to your cultural journey with KenteKart</p>
        </div>
        
        <div class="proverb-section">
            <?php echo escape_html($current_proverb); ?>
        </div>
        
        <div class="pattern-of-week">
            <img src="<?php echo escape_html($pattern_of_week['image']); ?>" alt="<?php echo escape_html($pattern_of_week['name']); ?>" class="pattern-image">
            <div class="pattern-info">
                <h3>Pattern of the Week: <span class="pattern-name"><?php echo escape_html($pattern_of_week['name']); ?></span></h3>
                <p class="pattern-meaning"><strong>Meaning:</strong> <?php echo escape_html($pattern_of_week['meaning']); ?></p>
                <p class="pattern-meaning"><strong>Symbolism:</strong> <?php echo escape_html($pattern_of_week['symbolism']); ?></p>
                <p class="pattern-desc"><?php echo escape_html($pattern_of_week['description']); ?></p>
            </div>
        </div>
    </div>

    <!-- IMPACT DASHBOARD -->
    <div class="impact-dashboard">
        <div class="impact-card">
            <div class="impact-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="impact-number"><?php echo $impact_metrics['designers_supported']; ?></div>
            <div class="impact-label">Designers Supported</div>
            <div class="impact-progress">
                <div class="impact-progress-bar"></div>
            </div>
        </div>
        
        <div class="impact-card">
            <div class="impact-icon">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <div class="impact-number"><?php echo $impact_metrics['communities_helped']; ?></div>
            <div class="impact-label">Communities Helped</div>
            <div class="impact-progress">
                <div class="impact-progress-bar"></div>
            </div>
        </div>
        
        <div class="impact-card">
            <div class="impact-icon">
                <i class="fas fa-female"></i>
            </div>
            <div class="impact-number"><?php echo $impact_metrics['women_entrepreneurs']; ?>%</div>
            <div class="impact-label">Women Entrepreneurs</div>
            <div class="impact-progress">
                <div class="impact-progress-bar"></div>
            </div>
        </div>
        
        <div class="impact-card">
            <div class="impact-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="impact-number"><?php echo $impact_metrics['cultural_stories']; ?></div>
            <div class="impact-label">Cultural Stories</div>
            <div class="impact-progress">
                <div class="impact-progress-bar"></div>
            </div>
        </div>
    </div>

    <!-- PERSONALIZED DESIGNER SHOWCASE -->
    <div class="designer-showcase">
        <div class="section-header">
            <h2><i class="fas fa-heart icon"></i> Designers You Support</h2>
            <a href="<?php echo url('view/product/all_product.php'); ?>">View All →</a>
        </div>
        
        <div class="designer-grid">
            <?php foreach ($supported_designers as $designer): ?>
            <div class="designer-card">
                <img src="<?php echo escape_html($designer['photo']); ?>" alt="<?php echo escape_html($designer['name']); ?>" class="designer-photo">
                <div class="designer-name"><?php echo escape_html($designer['name']); ?></div>
                <div class="designer-region"><i class="fas fa-map-marker-alt"></i> <?php echo escape_html($designer['region']); ?></div>
                <div class="designer-specialty"><?php echo escape_html($designer['specialty']); ?></div>
                <div class="designer-story"><?php echo escape_html($designer['story']); ?></div>
                <div class="designer-badges">
                    <?php if ($designer['women_owned']): ?>
                    <span class="badge badge-women"><i class="fas fa-female"></i> Women-Owned</span>
                    <?php endif; ?>
                    <span class="badge badge-region"><?php echo escape_html($designer['region']); ?></span>
                </div>
                <button class="btn-follow"><i class="fas fa-heart"></i> Following</button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section-header">
            <h2><i class="fas fa-star icon"></i> Recommended Artisans</h2>
        </div>
        
        <div class="designer-grid">
            <?php foreach ($recommended_designers as $designer): ?>
            <div class="designer-card">
                <img src="<?php echo escape_html($designer['photo']); ?>" alt="<?php echo escape_html($designer['name']); ?>" class="designer-photo">
                <div class="designer-name"><?php echo escape_html($designer['name']); ?></div>
                <div class="designer-region"><i class="fas fa-map-marker-alt"></i> <?php echo escape_html($designer['region']); ?></div>
                <div class="designer-specialty"><?php echo escape_html($designer['specialty']); ?></div>
                <div class="recommendation-reason"><?php echo escape_html($designer['match_reason']); ?></div>
                <button class="btn-follow"><i class="fas fa-plus"></i> Discover</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- CULTURAL JOURNEY TRACKER -->
    <div class="journey-tracker">
        <div class="section-header">
            <h2><i class="fas fa-route icon"></i> Your Cultural Journey</h2>
        </div>
        
        <div class="regions-map">
            <?php foreach ($regions_explored as $region): ?>
            <div class="region-badge">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo escape_html($region); ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cultural-badges-section">
            <h3>Your Achievements</h3>
            <div class="badges-grid">
                <?php foreach ($cultural_badges as $badge): ?>
                <div class="achievement-badge">
                    <div class="badge-icon"><i class="fas fa-trophy"></i></div>
                    <div class="badge-name"><?php echo escape_html($badge); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- AI-POWERED RECOMMENDATIONS -->
    <div class="ai-recommendations">
        <div class="section-header">
            <h2><i class="fas fa-magic icon"></i> Curated For You</h2>
            <span>Powered by AI</span>
        </div>
        
        <div class="recommendations-grid">
            <?php foreach ($ai_recommendations as $rec): ?>
            <div class="recommendation-card">
                <img src="<?php echo escape_html($rec['image']); ?>" alt="<?php echo escape_html($rec['title']); ?>" class="recommendation-image">
                <div class="recommendation-reason"><?php echo escape_html($rec['reason']); ?></div>
                <div class="recommendation-title"><?php echo escape_html($rec['title']); ?></div>
                <div class="recommendation-designer">by <?php echo escape_html($rec['designer']); ?></div>
                <div class="recommendation-price">$<?php echo number_format($rec['price'], 2); ?></div>
                <a href="<?php echo url('view/product/single_product.php?id=' . $rec['product_id']); ?>" class="btn-learn">View Product</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- RECENT ORDERS -->
    <div class="recent-orders">
        <div class="section-header">
            <h2><i class="fas fa-shopping-bag icon"></i> Recent Orders</h2>
            <a href="<?php echo url('view/order/order_history.php'); ?>">View All →</a>
        </div>
        
        <?php if (empty($recent_orders)): ?>
        <div>
            <i class="fas fa-shopping-bag"></i>
            <p>No orders yet. Start your cultural journey by exploring our artisans!</p>
            <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn-learn">Discover Products</a>
        </div>
        <?php else: ?>
        <?php foreach (array_slice($recent_orders, 0, 3) as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div class="order-info">
                    <h4>Order #<?php echo escape_html($order['order_id']); ?></h4>
                    <div class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                </div>
                <span class="order-status status-<?php echo escape_html($order['order_status']); ?>">
                    <?php echo ucfirst(escape_html($order['order_status'])); ?>
                </span>
            </div>
            <div class="cultural-significance">
                <h5><i class="fas fa-info-circle"></i> Cultural Significance</h5>
                <p>This purchase supports traditional craftsmanship and helps preserve Ghanaian cultural heritage.</p>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- CULTURAL EDUCATION HUB -->
    <div class="education-hub">
        <div class="section-header">
            <h2><i class="fas fa-graduation-cap icon"></i> Learn About Ghanaian Fashion</h2>
        </div>
        
        <div class="education-grid">
            <div class="education-card">
                <div class="education-icon"><i class="fas fa-book"></i></div>
                <div class="education-title">Kente Weaving Traditions</div>
                <div class="education-desc">Discover the rich history and techniques behind traditional Kente cloth weaving.</div>
                <a href="#" class="btn-learn">Read Article</a>
            </div>
            
            <div class="education-card">
                <div class="education-icon"><i class="fas fa-video"></i></div>
                <div class="education-title">Virtual Designer Showcase</div>
                <div class="education-desc">Join our upcoming virtual event featuring live demonstrations from master artisans.</div>
                <a href="#" class="btn-learn">Register Now</a>
            </div>
            
            <div class="education-card">
                <div class="education-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="education-title">Cultural Calendar</div>
                <div class="education-desc">Stay connected with Ghanaian festivals and cultural celebrations throughout the year.</div>
                <a href="#" class="btn-learn">View Calendar</a>
            </div>
        </div>
    </div>

    <!-- COMMUNITY ENGAGEMENT -->
    <div class="community-feed">
        <div class="section-header">
            <h2><i class="fas fa-users icon"></i> Community Updates</h2>
        </div>
        
        <div class="feed-item">
            <div class="feed-header">
                <img src="https://via.placeholder.com/44/d4a373/ffffff?text=AS" alt="Designer" class="feed-avatar">
                <div>
                    <div class="feed-author">Ama Serwah</div>
                    <div class="feed-time">2 hours ago</div>
                </div>
            </div>
            <div class="feed-content">
                Just finished a beautiful new Kente design inspired by the Sankofa pattern. Can't wait to share it with the KenteKart community! 🎨✨
            </div>
        </div>
        
        <div class="feed-item">
            <div class="feed-header">
                <img src="https://via.placeholder.com/44/8b6f47/ffffff?text=SM" alt="Customer" class="feed-avatar">
                <div>
                    <div class="feed-author">Sarah M.</div>
                    <div class="feed-time">1 day ago</div>
                </div>
            </div>
            <div class="feed-content">
                Wore my KenteKart scarf to a family gathering and everyone asked where I got it! So proud to support Ghanaian artisans. 💚
            </div>
        </div>
    </div>
    
    <!-- QUICK ACTIONS -->
    <div class="quick-actions-revised">
        <div class="section-header">
            <h2><i class="fas fa-bolt icon"></i> Quick Actions</h2>
        </div>
        
        <div class="actions-grid-revised">
            <a href="<?php echo url('view/product/all_product.php'); ?>" class="action-btn">
                <div class="action-icon"><i class="fas fa-search"></i></div>
                <div class="action-text">Discover Designers</div>
            </a>
            
            <a href="<?php echo url('view/product/all_product.php?filter=region'); ?>" class="action-btn">
                <div class="action-icon"><i class="fas fa-map"></i></div>
                <div class="action-text">Explore by Region</div>
            </a>
            
            <a href="#" class="action-btn">
                <div class="action-icon"><i class="fas fa-chart-line"></i></div>
                <div class="action-text">Impact Report</div>
            </a>
            
            <a href="<?php echo url('view/product/all_product.php?filter=ethical'); ?>" class="action-btn">
                <div class="action-icon"><i class="fas fa-leaf"></i></div>
                <div class="action-text">Ethical Collections</div>
            </a>
            
            <a href="#" class="action-btn">
                <div class="action-icon"><i class="fas fa-video"></i></div>
                <div class="action-text">Virtual Showcase</div>
            </a>
            
            <a href="#" class="action-btn">
                <div class="action-icon"><i class="fas fa-book-open"></i></div>
                <div class="action-text">Designer Stories</div>
            </a>
            
            <a href="<?php echo url('view/user/profile.php'); ?>" class="action-btn">
                <div class="action-icon"><i class="fas fa-user-edit"></i></div>
                <div class="action-text">Edit Profile</div>
            </a>
            
            <a href="<?php echo url('view/user/change_password.php'); ?>" class="action-btn">
                <div class="action-icon"><i class="fas fa-key"></i></div>
                <div class="action-text">Change Password</div>
            </a>
        </div>
    </div>
    
</div>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>