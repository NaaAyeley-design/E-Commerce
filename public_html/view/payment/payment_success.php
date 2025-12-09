<?php
/**
 * Payment Success Page
 * 
 * Displays order confirmation after successful payment
 */

require_once __DIR__ . '/../../settings/core.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

// Get parameters from URL
$invoice_no = isset($_GET['invoice']) ? htmlspecialchars($_GET['invoice']) : '';
$reference = isset($_GET['reference']) ? htmlspecialchars($_GET['reference']) : '';

// Set page variables
$page_title = 'Payment Successful';
$page_description = 'Your order has been confirmed';
$body_class = 'payment-success-page';
$additional_css = [];

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container" style="max-width: 800px; margin: 80px auto; padding: 0 20px;">
    <div style="background: linear-gradient(135deg, rgba(139, 111, 71, 0.1) 0%, rgba(198, 125, 92, 0.1) 100%); border: 2px solid var(--terracotta); border-radius: var(--radius-md); padding: 60px 50px; text-align: center;">
        <div class="success-icon" style="font-size: 80px; margin-bottom: 30px; animation: bounce 1s ease-in-out;">
            🎉
        </div>
        
        <h1 style="font-family: 'Cormorant Garamond', serif; font-size: 3rem; font-weight: 400; color: var(--text-dark); margin-bottom: 15px; letter-spacing: -0.01em;">
            Order Successful!
        </h1>
        <p style="font-family: 'Spectral', serif; font-size: 1.125rem; color: var(--text-light); margin-bottom: 40px; line-height: 1.8;">
            Your payment has been processed successfully
        </p>
        
        <div style="background: var(--white); padding: 40px; border-radius: var(--radius-md); margin: 40px 0; text-align: left; box-shadow: 0 10px 40px rgba(139, 111, 71, 0.08);">
            <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(198, 125, 92, 0.15);">
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; font-weight: 400; color: var(--text-dark);">
                    Invoice Number
                </span>
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); word-break: break-all;">
                    <?php echo $invoice_no ?: 'N/A'; ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(198, 125, 92, 0.15);">
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; font-weight: 400; color: var(--text-dark);">
                    Payment Reference
                </span>
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); word-break: break-all;">
                    <?php echo $reference ?: 'N/A'; ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(198, 125, 92, 0.15);">
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; font-weight: 400; color: var(--text-dark);">
                    Order Date
                </span>
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light);">
                    <?php echo date('F j, Y'); ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 15px 0;">
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; font-weight: 400; color: var(--text-dark);">
                    Status
                </span>
                <span style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--terracotta); font-weight: 400;">
                    Paid ✓
                </span>
            </div>
        </div>
        
        <div style="background: rgba(139, 111, 71, 0.1); border: 1px solid var(--deep-brown); padding: 25px; border-radius: var(--radius-md); margin-bottom: 40px;">
            <strong style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--deep-brown); display: block; margin-bottom: 8px;">
                ✓ Payment Confirmed
            </strong>
            <p style="font-family: 'Spectral', serif; font-size: 0.875rem; color: var(--text-light); line-height: 1.8; margin: 0;">
                Thank you for your purchase! Your order has been confirmed and will be processed shortly.
            </p>
        </div>
        
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 40px;">
            <a href="<?php echo url('view/user/dashboard.php'); ?>" class="btn btn-primary" style="padding: 15px 40px; border: 1px solid var(--terracotta); background: var(--terracotta); color: var(--white); font-family: 'Spectral', serif; font-size: 0.875rem; letter-spacing: 0.1em; text-transform: uppercase; text-decoration: none; border-radius: var(--radius-md); transition: all var(--transition-base); display: inline-block;">
                <i class="fas fa-box" style="margin-right: 8px;"></i>
                View My Orders
            </a>
            <a href="<?php echo url('view/product/all_product.php'); ?>" class="btn btn-outline" style="padding: 15px 40px; border: 1px solid var(--terracotta); background: transparent; color: var(--terracotta); font-family: 'Spectral', serif; font-size: 0.875rem; letter-spacing: 0.1em; text-transform: uppercase; text-decoration: none; border-radius: var(--radius-md); transition: all var(--transition-base); display: inline-block;">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<style>
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
</style>

<script>
/**
 * Confetti effect for celebration
 */
function createConfetti() {
    const colors = ['var(--terracotta)', 'var(--deep-brown)', '#CC8B3C', '#B7410E'];
    const confettiCount = 50;
    
    for (let i = 0; i < confettiCount; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: fixed;
                width: 10px;
                height: 10px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                left: ${Math.random() * 100}%;
                top: -10px;
                opacity: 1;
                transform: rotate(${Math.random() * 360}deg);
                z-index: 10001;
                pointer-events: none;
                border-radius: 50%;
            `;
            
            document.body.appendChild(confetti);
            
            const duration = 2000 + Math.random() * 1000;
            const startTime = Date.now();
            
            function animateConfetti() {
                const elapsed = Date.now() - startTime;
                const progress = elapsed / duration;
                
                if (progress < 1) {
                    const top = progress * (window.innerHeight + 50);
                    const wobble = Math.sin(progress * 10) * 50;
                    
                    confetti.style.top = top + 'px';
                    confetti.style.left = `calc(${confetti.style.left} + ${wobble}px)`;
                    confetti.style.opacity = 1 - progress;
                    confetti.style.transform = `rotate(${progress * 720}deg)`;
                    
                    requestAnimationFrame(animateConfetti);
                } else {
                    confetti.remove();
                }
            }
            
            animateConfetti();
        }, i * 30);
    }
}

// Trigger confetti on page load
window.addEventListener('load', createConfetti);
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

