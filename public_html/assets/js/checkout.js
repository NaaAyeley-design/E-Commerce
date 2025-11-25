/**
 * Checkout JavaScript
 * Handles Paystack payment integration for checkout page
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load checkout summary
    if (typeof window.cartItems !== 'undefined' && window.cartItems) {
        displayCheckoutItems(window.cartItems, window.checkoutTotal);
    } else {
        loadCheckoutSummary();
    }
});

/**
 * Load checkout summary from server
 */
function loadCheckoutSummary() {
    const container = document.getElementById('checkoutItemsContainer');
    
    if (!container) {
        console.error('Container not found: checkoutItemsContainer');
        return;
    }
    
    // Show loading state
    container.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-light);"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 15px;"></i><p>Loading order summary...</p></div>';
    
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/get_cart_action.php';
    
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.items && data.items.length > 0) {
            displayCheckoutItems(data.items, data.total);
        } else {
            // Cart is empty, redirect back
            window.location.href = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/view/cart/view_cart.php';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        container.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--terracotta);">Failed to load order summary. Please try again.</div>';
    });
}

/**
 * Display checkout items
 */
function displayCheckoutItems(items, total) {
    const container = document.getElementById('checkoutItemsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    items.forEach((item, index) => {
        // Build image path - handle both relative and absolute paths
        let imagePath;
        if (item.product_image) {
            if (item.product_image.startsWith('http')) {
                imagePath = item.product_image;
            } else if (item.product_image.startsWith('/')) {
                imagePath = item.product_image;
            } else {
                imagePath = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/' + item.product_image;
            }
        } else {
            // Use placeholder - try to get correct path
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
            imagePath = baseUrl + '/assets/images/placeholder-product.png';
        }
        
        // Fallback placeholder path
        const placeholderPath = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/assets/images/placeholder-product.png';
        
        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = 'display: flex; gap: 20px; padding: 20px 0; border-bottom: 1px solid rgba(198, 125, 92, 0.15); align-items: center;';
        
        itemDiv.innerHTML = `
            <img src="${escapeHtml(imagePath)}" 
                 alt="${escapeHtml(item.product_title)}" 
                 style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-md); flex-shrink: 0; background: var(--warm-beige);"
                 onerror="this.onerror=null; this.src='${escapeHtml(placeholderPath)}'; this.style.background='var(--warm-beige)';">
            <div style="flex: 1;">
                <div style="font-family: \'Cormorant Garamond\', serif; font-size: 1.125rem; font-weight: 400; color: var(--text-dark); margin-bottom: 8px;">
                    ${escapeHtml(item.product_title)}
                </div>
                <div style="font-family: \'Spectral\', serif; font-size: 0.875rem; color: var(--text-light);">
                    Qty: ${item.qty} × ₵${parseFloat(item.product_price || item.price || 0).toFixed(2)}
                </div>
            </div>
            <div style="font-family: \'Cormorant Garamond\', serif; font-weight: 600; color: var(--terracotta); font-size: 1.25rem;">
                ₵${parseFloat(item.subtotal || (item.product_price || item.price || 0) * item.qty).toFixed(2)}
            </div>
        `;
        
        container.appendChild(itemDiv);
    });
    
    const totalElement = document.getElementById('checkoutTotal');
    if (totalElement) {
        totalElement.textContent = '₵' + parseFloat(total).toFixed(2);
    }
    
    // Store total for payment modal
    window.checkoutTotal = parseFloat(total).toFixed(2);
}

/**
 * Show payment modal
 */
function showPaymentModal() {
    const modal = document.getElementById('paymentModal');
    const amountDisplay = document.getElementById('paymentAmount');
    
    if (!modal || !amountDisplay) {
        console.error('Payment modal elements not found');
        return;
    }
    
    amountDisplay.textContent = '₵' + (window.checkoutTotal || '0.00');
    
    modal.style.display = 'flex';
    
    // Add animation
    setTimeout(() => {
        modal.style.opacity = '1';
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transform = 'scale(1)';
        }
    }, 10);
}

/**
 * Close payment modal
 */
function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    if (!modal) return;
    
    modal.style.opacity = '0';
    
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.transform = 'scale(0.9)';
    }
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

/**
 * Process checkout via Paystack
 */
function processCheckout() {
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    if (!confirmBtn) return;
    
    const originalText = confirmBtn.textContent;
    
    // Disable button and show loading
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Redirecting to Paystack...';
    
    // Get customer email
    let customerEmail = window.customerEmail || '';
    
    // If no email in session, prompt user
    if (!customerEmail) {
        customerEmail = prompt('Please enter your email for payment:', '');
        
        if (!customerEmail || !customerEmail.includes('@')) {
            if (typeof Toast !== 'undefined') {
                Toast.error('Valid email is required for payment');
            } else {
                alert('Valid email is required for payment');
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            return;
        }
    }
    
    // Get amount
    const amount = parseFloat(window.checkoutTotal || 0);
    
    if (amount <= 0) {
        if (typeof Toast !== 'undefined') {
            Toast.error('Invalid amount');
        } else {
            alert('Invalid amount');
        }
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        return;
    }
    
    // Initialize Paystack transaction
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/paystack_init_transaction.php';
    
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            amount: amount,
            email: customerEmail
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Paystack init response:', data);
        
        if (data.status === 'success') {
            // Store data for verification after payment
            window.paymentReference = data.reference;
            window.cartItems = window.cartItems || null;
            window.totalAmount = amount;
            
            // Show success message
            if (typeof Toast !== 'undefined') {
                Toast.success('Redirecting to secure payment...');
            }
            
            // Close modal
            closePaymentModal();
            
            // Redirect to Paystack payment page
            setTimeout(() => {
                window.location.href = data.authorization_url;
            }, 500);
        } else {
            const errorMsg = data.message || 'Failed to initialize payment';
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert(errorMsg);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMsg = 'Payment initialization failed. Please try again.';
        if (typeof Toast !== 'undefined') {
            Toast.error(errorMsg);
        } else {
            alert(errorMsg);
        }
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const paymentModal = document.getElementById('paymentModal');
    if (event.target === paymentModal) {
        closePaymentModal();
    }
};

