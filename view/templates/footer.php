    </main>
    
    <!-- Footer (if not a standalone page) -->
    <?php if (!isset($standalone_page) || !$standalone_page): ?>
        <footer class="footer" role="contentinfo">
            <div class="container">
                <div class="footer-content">
                    <!-- Footer Top -->
                    <div class="footer-top">
                        <div class="footer-grid">
                            <!-- Company Info -->
                            <div class="footer-section">
                                <h3 class="footer-title"><?php echo APP_NAME; ?></h3>
                                <p class="footer-description">
                                    Your trusted e-commerce platform for all your shopping needs. 
                                    Secure, fast, and reliable online shopping experience.
                                </p>
                                <div class="social-links">
                                    <a href="#" class="social-link" aria-label="Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="#" class="social-link" aria-label="Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="#" class="social-link" aria-label="Instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                    <a href="#" class="social-link" aria-label="LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Quick Links -->
                            <div class="footer-section">
                                <h4 class="footer-subtitle">Quick Links</h4>
                                <ul class="footer-links">
                                    <li><a href="<?php echo url('public/index.php'); ?>" class="footer-link">Home</a></li>
                                    <li><a href="<?php echo url('view/product/product_list.php'); ?>" class="footer-link">Products</a></li>
                                    <li><a href="#" class="footer-link">About Us</a></li>
                                    <li><a href="#" class="footer-link">Contact</a></li>
                                    <li><a href="#" class="footer-link">FAQ</a></li>
                                </ul>
                            </div>
                            
                            <!-- Customer Service -->
                            <div class="footer-section">
                                <h4 class="footer-subtitle">Customer Service</h4>
                                <ul class="footer-links">
                                    <?php if (is_logged_in()): ?>
                                        <li><a href="<?php echo url('view/user/dashboard.php'); ?>" class="footer-link">My Account</a></li>
                                        <li><a href="<?php echo url('view/order/order_history.php'); ?>" class="footer-link">Order History</a></li>
                                    <?php else: ?>
                                        <li><a href="<?php echo url('view/user/login.php'); ?>" class="footer-link">Login</a></li>
                                        <li><a href="<?php echo url('view/user/register.php'); ?>" class="footer-link">Register</a></li>
                                    <?php endif; ?>
                                    <li><a href="#" class="footer-link">Shipping Info</a></li>
                                    <li><a href="#" class="footer-link">Returns</a></li>
                                    <li><a href="#" class="footer-link">Support</a></li>
                                </ul>
                            </div>
                            
                            <!-- Contact Info -->
                            <div class="footer-section">
                                <h4 class="footer-subtitle">Contact Info</h4>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>123 E-Commerce Street<br>Digital City, DC 12345</span>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <span>+1 (555) 123-4567</span>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <span>support@ecommerce-platform.com</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer Bottom -->
                    <div class="footer-bottom">
                        <div class="footer-bottom-content">
                            <div class="copyright">
                                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                            </div>
                            <div class="footer-bottom-links">
                                <a href="#" class="footer-bottom-link">Privacy Policy</a>
                                <a href="#" class="footer-bottom-link">Terms of Service</a>
                                <a href="#" class="footer-bottom-link">Cookie Policy</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>
    
    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" aria-label="Back to top" title="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <!-- JavaScript Files -->
    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
    <?php if (isset($additional_js) && is_array($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo ASSETS_URL; ?>/js/<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Additional Footer Content -->
    <?php if (isset($additional_footer_content)): ?>
        <?php echo $additional_footer_content; ?>
    <?php endif; ?>
    
    
    <script>
        // Initialize application
        document.addEventListener('DOMContentLoaded', function() {
            // Back to top functionality
            const backToTopBtn = document.getElementById('backToTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopBtn.classList.add('visible');
                } else {
                    backToTopBtn.classList.remove('visible');
                }
            });
            
            backToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Mobile menu functionality
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileToggle && navMenu) {
                mobileToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    this.classList.toggle('active');
                });
            }
            
            // Dropdown functionality
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = this.parentElement;
                    dropdown.classList.toggle('active');
                    
                    // Close other dropdowns
                    dropdownToggles.forEach(otherToggle => {
                        if (otherToggle !== this) {
                            otherToggle.parentElement.classList.remove('active');
                        }
                    });
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    dropdownToggles.forEach(toggle => {
                        toggle.parentElement.classList.remove('active');
                    });
                }
            });
            
            // Flash message auto-hide
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.remove();
                    }, 300);
                }, 5000);
            });
            
            // CSRF token setup for AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                // Set up default CSRF token for fetch requests
                const originalFetch = window.fetch;
                window.fetch = function(url, options = {}) {
                    if (options.method && options.method.toUpperCase() !== 'GET') {
                        options.headers = options.headers || {};
                        if (options.body instanceof FormData) {
                            options.body.append('csrf_token', csrfToken.getAttribute('content'));
                        } else {
                            options.headers['X-CSRF-Token'] = csrfToken.getAttribute('content');
                        }
                    }
                    return originalFetch(url, options);
                };
            }
        });
    </script>
</body>
</html>
