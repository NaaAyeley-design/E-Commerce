<?php
/**
 * Paystack Payment Callback Handler
 * This page is called after Paystack payment process
 */

require_once __DIR__ . '/../../../settings/core.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

// Get reference from URL
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : null;

if (!$reference) {
    // Payment cancelled or reference missing
    header('Location: ' . BASE_URL . '/view/payment/checkout.php?error=cancelled');
    exit;
}

// Set page variables
$page_title = 'Processing Payment';
$page_description = 'Verifying your payment';
$body_class = 'payment-callback-page';
$additional_css = [];

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container" style="max-width: 600px; margin: 100px auto; padding: 0 20px;">
    <div style="background: var(--white); padding: 60px 40px; border: var(--border-thin); border-radius: var(--radius-md); text-align: center; box-shadow: 0 20px 60px rgba(139, 111, 71, 0.08);">
        <div class="spinner" id="spinner" style="display: inline-block; width: 50px; height: 50px; border: 4px solid var(--warm-beige); border-top: 4px solid var(--terracotta); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 30px;"></div>
        
        <h1 style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 400; color: var(--text-dark); margin-bottom: 15px;">
            Verifying Payment
        </h1>
        <p style="font-family: 'Spectral', serif; font-size: 1rem; color: var(--text-light); line-height: 1.8; margin-bottom: 30px;">
            Please wait while we verify your payment with Paystack...
        </p>
        
        <div style="background: var(--warm-beige); padding: 20px; border-radius: var(--radius-md); margin: 25px 0; word-break: break-all;">
            <div style="font-family: 'Spectral', serif; font-size: 0.75rem; color: var(--text-light); margin-bottom: 8px; letter-spacing: 0.05em; text-transform: uppercase;">
                Payment Reference
            </div>
            <div style="font-family: monospace; font-size: 0.875rem; color: var(--text-dark); font-weight: 400;">
                <?php echo htmlspecialchars($reference); ?>
            </div>
        </div>
        
        <div id="errorBox" class="error-box" style="display: none; color: var(--terracotta); background: rgba(198, 125, 92, 0.1); border: 1px solid var(--terracotta); padding: 20px; border-radius: var(--radius-md); margin: 20px 0;">
            <strong style="font-family: 'Spectral', serif; font-size: 0.875rem;">Error:</strong>
            <span id="errorMessage" style="font-family: 'Spectral', serif; font-size: 0.875rem; margin-left: 8px;"></span>
        </div>
        
        <div id="successBox" class="success-box" style="display: none; color: var(--deep-brown); background: rgba(139, 111, 71, 0.1); border: 1px solid var(--deep-brown); padding: 20px; border-radius: var(--radius-md); margin: 20px 0;">
            <strong style="font-family: 'Spectral', serif; font-size: 0.875rem;">Success!</strong>
            <span style="font-family: 'Spectral', serif; font-size: 0.875rem; margin-left: 8px;">Your payment has been verified. Redirecting...</span>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
/**
 * Verify payment with backend
 */
async function verifyPayment() {
    const reference = <?php echo json_encode($reference); ?>;
    
    try {
        const response = await fetch('<?php echo url('actions/paystack_verify_payment.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                reference: reference,
                cart_items: null, // Will be fetched from backend
                total_amount: null // Will be calculated from cart
            })
        });
        
        const data = await response.json();
        console.log('=== VERIFICATION RESPONSE ===');
        console.log('Status:', data.status);
        console.log('Verified:', data.verified);
        console.log('Message:', data.message);
        console.log('Full response:', data);
        
        // Hide spinner
        document.getElementById('spinner').style.display = 'none';
        
        if (data.status === 'success' && data.verified) {
            // Payment verified successfully
            console.log('✓ Payment verified successfully');
            document.getElementById('successBox').style.display = 'block';
            
            // Redirect to success page
            setTimeout(() => {
                window.location.replace('<?php echo url('view/payment/payment_success.php'); ?>?reference=' + encodeURIComponent(reference) + '&invoice=' + encodeURIComponent(data.invoice_no || ''));
            }, 1000);
            
        } else {
            // Payment verification failed
            const errorMsg = data.message || 'Payment verification failed';
            console.error('✗ Payment verification failed:', errorMsg);
            console.error('Response data:', data);
            
            // Show detailed error message
            let detailedError = errorMsg;
            if (data.debug && typeof data.debug === 'object') {
                detailedError += '\n\nDebug info: ' + JSON.stringify(data.debug, null, 2);
            }
            if (data.expected && data.paid) {
                detailedError += `\n\nExpected: ${data.expected} GHS, Paid: ${data.paid} GHS`;
            }
            
            showError(detailedError);
            
            // Redirect after 8 seconds (give user time to read error)
            setTimeout(() => {
                window.location.href = '<?php echo url('view/payment/checkout.php'); ?>?error=verification_failed&ref=' + encodeURIComponent(reference);
            }, 8000);
        }
        
    } catch (error) {
        console.error('Verification error:', error);
        showError('Connection error. Please try again or contact support.');
        
        // Redirect after 5 seconds
        setTimeout(() => {
            window.location.href = '<?php echo url('view/payment/checkout.php'); ?>?error=connection_error';
        }, 5000);
    }
}

/**
 * Show error message
 */
function showError(message) {
    document.getElementById('errorBox').style.display = 'block';
    document.getElementById('errorMessage').textContent = message;
}

// Start verification when page loads
window.addEventListener('load', verifyPayment);
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

