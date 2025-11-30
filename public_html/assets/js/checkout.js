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
                    Qty: ${item.quantity || item.qty || 1} × ₵${parseFloat(item.product_price || item.price || 0).toFixed(2)}
                </div>
            </div>
            <div style="font-family: \'Cormorant Garamond\', serif; font-weight: 600; color: var(--terracotta); font-size: 1.25rem;">
                ₵${parseFloat(item.subtotal || (item.product_price || item.price || 0) * (item.quantity || item.qty || 1)).toFixed(2)}
            </div>
        `;
        
        container.appendChild(itemDiv);
    });
    
    const totalElement = document.getElementById('checkoutTotal');
    if (totalElement) {
        const totalValue = parseFloat(total) || 0;
        if (isNaN(totalValue)) {
            console.error('Cart total is NaN. Items:', items);
            // Recalculate from items
            let calculatedTotal = 0;
            items.forEach(item => {
                const qty = parseFloat(item.quantity || item.qty || 1) || 1;
                const price = parseFloat(item.product_price || item.price || 0) || 0;
                calculatedTotal += qty * price;
            });
            totalElement.textContent = '₵' + calculatedTotal.toFixed(2);
            window.checkoutTotal = calculatedTotal.toFixed(2);
        } else {
            totalElement.textContent = '₵' + totalValue.toFixed(2);
            window.checkoutTotal = totalValue.toFixed(2);
        }
    } else {
        // Fallback: calculate from items if total is invalid
        let calculatedTotal = 0;
        items.forEach(item => {
            const qty = parseFloat(item.quantity || item.qty || 1) || 1;
            const price = parseFloat(item.product_price || item.price || 0) || 0;
            calculatedTotal += qty * price;
        });
        window.checkoutTotal = calculatedTotal.toFixed(2);
    }
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
 * Supports both Inline (popup) and Standard (redirect) methods
 */
function processCheckout() {
    const confirmBtn = document.getElementById('confirmPaymentBtn');
    if (!confirmBtn) return;
    
    const originalText = confirmBtn.textContent;
    
    // Disable button and show loading
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Initializing Payment...';
    
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
    
    // Get amount - ensure it's a valid number
    let amount = parseFloat(window.checkoutTotal || 0);
    
    // If amount is invalid, try to recalculate from cart items
    if (isNaN(amount) || amount <= 0) {
        console.warn('Invalid checkout total, recalculating from cart items...');
        if (window.cartItems && Array.isArray(window.cartItems) && window.cartItems.length > 0) {
            let calculatedTotal = 0;
            window.cartItems.forEach(item => {
                const qty = parseFloat(item.quantity || item.qty || 1) || 1;
                const price = parseFloat(item.product_price || item.price || 0) || 0;
                calculatedTotal += qty * price;
            });
            amount = calculatedTotal;
            window.checkoutTotal = calculatedTotal.toFixed(2);
            console.log('Recalculated amount:', amount);
        }
    }
    
    // Final validation
    if (isNaN(amount) || amount <= 0) {
        const errorMsg = 'Invalid payment amount. Please refresh the page and try again.';
        console.error('Payment amount validation failed:', {
            checkoutTotal: window.checkoutTotal,
            amount: amount,
            cartItems: window.cartItems
        });
        if (typeof Toast !== 'undefined') {
            Toast.error(errorMsg);
        } else {
            alert(errorMsg);
        }
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        return;
    }
    
    // Ensure amount is at least 1 GHS (100 pesewas)
    if (amount < 1) {
        const errorMsg = 'Minimum payment amount is ₵1.00';
        if (typeof Toast !== 'undefined') {
            Toast.error(errorMsg);
        } else {
            alert(errorMsg);
        }
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        return;
    }
    
    // Wait for PaystackPop to be available (with timeout)
    function waitForPaystackPop(callback, maxAttempts = 10, attempt = 0) {
        if (typeof PaystackPop !== 'undefined' && PaystackPop) {
            callback(true);
        } else if (attempt < maxAttempts) {
            setTimeout(() => waitForPaystackPop(callback, maxAttempts, attempt + 1), 200);
        } else {
            console.warn('PaystackPop not available after waiting. Using redirect method.');
            callback(false);
        }
    }
    
    waitForPaystackPop((isAvailable) => {
        const useInline = isAvailable && 
                          window.PAYSTACK_PUBLIC_KEY && 
                          window.PAYSTACK_PUBLIC_KEY !== 'pk_test_YOUR_PUBLIC_KEY_HERE' &&
                          window.PAYSTACK_PUBLIC_KEY !== '';
        
        if (useInline) {
            // Use Paystack Inline (popup) method
            processCheckoutInline(amount, customerEmail, confirmBtn, originalText);
        } else {
            // Use Paystack Standard (redirect) method
            processCheckoutStandard(amount, customerEmail, confirmBtn, originalText);
        }
    });
}

/**
 * Process checkout using Paystack Inline (popup) method
 */
function processCheckoutInline(amount, customerEmail, confirmBtn, originalText) {
    console.log('=== PAYSTACK INLINE INITIALIZATION ===');
    console.log('Amount:', amount);
    console.log('Email:', customerEmail);
    console.log('Public Key:', window.PAYSTACK_PUBLIC_KEY ? window.PAYSTACK_PUBLIC_KEY.substring(0, 20) + '...' : 'NOT SET');
    
    // Initialize Paystack transaction to get reference
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/paystack_init_transaction.php';
    console.log('Initialization URL:', actionUrl);
    
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Preparing Payment...';
    
    console.log('Sending request to:', actionUrl);
    console.log('Request payload:', { amount: amount, email: customerEmail });
    
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            amount: amount,
            email: customerEmail
        })
    })
    .then(response => {
        console.log('Response received. Status:', response.status);
        console.log('Response OK:', response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('=== PAYSTACK INIT RESPONSE ===');
        console.log('Full response:', JSON.stringify(data, null, 2));
        
        // Check for errors first
        if (data.status === 'error') {
            console.error('Payment initialization error:', data.message);
            const errorMsg = data.message || 'Failed to initialize payment. Please try again.';
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert('Payment Error: ' + errorMsg);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            return;
        }
        
        if (data.status === 'success' && data.reference) {
            console.log('✓ Transaction initialized successfully');
            console.log('Reference:', data.reference);
            console.log('Authorization URL:', data.authorization_url);
            
            // Convert amount to pesewas (kobo) for Paystack
            // Ensure amount is a number and round properly
            const amountInPesewas = Math.round(parseFloat(amount) * 100);
            console.log('Amount in GHS:', amount);
            console.log('Amount in pesewas:', amountInPesewas);
            
            // Validate pesewas amount (minimum 100 pesewas = 1 GHS)
            if (amountInPesewas < 100) {
                console.error('Amount too small:', amountInPesewas, 'pesewas');
                const errorMsg = 'Payment amount is too small. Minimum is ₵1.00';
                if (typeof Toast !== 'undefined') {
                    Toast.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
                return;
            }
            
            // Close modal
            closePaymentModal();
            
            // Initialize Paystack popup
            console.log('Initializing Paystack popup...');
            console.log('Using public key:', window.PAYSTACK_PUBLIC_KEY ? window.PAYSTACK_PUBLIC_KEY.substring(0, 20) + '...' : 'NOT SET');
            
            try {
                if (typeof PaystackPop === 'undefined') {
                    throw new Error('PaystackPop is not defined. Script may not have loaded.');
                }
                
                const handler = PaystackPop.setup({
                    key: window.PAYSTACK_PUBLIC_KEY,
                    email: customerEmail,
                    amount: amountInPesewas,
                    ref: data.reference,
                    currency: 'GHS',
                    callback: function(response) {
                        // Payment successful on Paystack - show success immediately
                        console.log('=== PAYSTACK CALLBACK RECEIVED ===');
                        console.log('Payment response:', response);
                        console.log('✓ Payment successful on Paystack');
                        
                        // Show success message
                        if (typeof Toast !== 'undefined') {
                            Toast.success('Payment successful! Creating order...', { duration: 2000 });
                        }
                        
                        // Create order directly (no verification needed)
                        createOrderAfterPayment(response.reference, amount);
                    },
                    onClose: function() {
                        // User closed popup
                        console.log('Payment popup closed by user');
                        if (typeof Toast !== 'undefined') {
                            Toast.info('Payment cancelled');
                        }
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = originalText;
                    }
                });
                
                console.log('Paystack handler created:', handler);
                console.log('Opening Paystack popup...');
                
                // Open Paystack popup
                handler.openIframe();
                
                console.log('✓ Paystack popup should be opening now');
                
            } catch (error) {
                console.error('=== ERROR INITIALIZING PAYSTACK POPUP ===');
                console.error('Error type:', error.constructor.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                console.error('PaystackPop available:', typeof PaystackPop !== 'undefined');
                console.error('Public key available:', !!window.PAYSTACK_PUBLIC_KEY);
                
                // Fallback to Standard method
                console.log('Falling back to Standard (redirect) method');
                processCheckoutStandard(amount, customerEmail, confirmBtn, originalText);
            }
        } else {
            console.error('=== PAYMENT INITIALIZATION FAILED ===');
            console.error('Response status:', data.status);
            console.error('Response message:', data.message);
            console.error('Response data:', data.data);
            console.error('Full response:', data);
            
            // Provide more helpful error messages
            let errorMsg = data.message || 'Failed to initialize payment';
            
            // Check for specific error types
            if (data.message && data.message.includes('gateway not configured')) {
                errorMsg = 'Payment gateway is not properly configured. Please contact support.';
            } else if (data.message && data.message.includes('Invalid')) {
                errorMsg = 'Invalid payment information. Please check your details and try again.';
            } else if (data.message && data.message.includes('API')) {
                errorMsg = 'Payment gateway connection error. Please try again in a moment.';
            }
            
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert('Payment Error: ' + errorMsg);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('=== FETCH ERROR ===');
        console.error('Error type:', error.constructor.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        // Check if it's a network error
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            const errorMsg = 'Network error. Please check your internet connection and try again.';
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert(errorMsg);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            return;
        }
        
        // For other errors, try fallback to Standard method
        console.log('Falling back to Standard (redirect) method');
        processCheckoutStandard(amount, customerEmail, confirmBtn, originalText);
    });
}

/**
 * Process checkout using Paystack Standard (redirect) method
 */
function processCheckoutStandard(amount, customerEmail, confirmBtn, originalText) {
    console.log('=== PAYSTACK STANDARD (REDIRECT) INITIALIZATION ===');
    console.log('Amount:', amount);
    console.log('Email:', customerEmail);
    
    // Initialize Paystack transaction
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/paystack_init_transaction.php';
    console.log('Initialization URL:', actionUrl);
    
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Redirecting to Paystack...';
    
    console.log('Sending request to:', actionUrl);
    console.log('Request payload:', { amount: amount, email: customerEmail });
    
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            amount: amount,
            email: customerEmail
        })
    })
    .then(response => {
        console.log('Response received. Status:', response.status);
        console.log('Response OK:', response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('=== PAYSTACK INIT RESPONSE (STANDARD) ===');
        console.log('Full response:', JSON.stringify(data, null, 2));
        
        // Check for errors first
        if (data.status === 'error') {
            console.error('Payment initialization error:', data.message);
            const errorMsg = data.message || 'Failed to initialize payment. Please try again.';
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert('Payment Error: ' + errorMsg);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            return;
        }
        
        if (data.status === 'success' && data.authorization_url) {
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
            console.error('=== PAYMENT INITIALIZATION FAILED (STANDARD) ===');
            console.error('Response status:', data.status);
            console.error('Response message:', data.message);
            console.error('Full response:', data);
            
            // Provide more helpful error messages
            let errorMsg = data.message || 'Failed to initialize payment';
            
            // Check for specific error types
            if (data.message && data.message.includes('gateway not configured')) {
                errorMsg = 'Payment gateway is not properly configured. Please contact support.';
            } else if (data.message && data.message.includes('Invalid')) {
                errorMsg = 'Invalid payment information. Please check your details and try again.';
            } else if (data.message && data.message.includes('API')) {
                errorMsg = 'Payment gateway connection error. Please try again in a moment.';
            }
            
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert('Payment Error: ' + errorMsg);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('=== FETCH ERROR (STANDARD) ===');
        console.error('Error type:', error.constructor.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        // Check if it's a network error
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            const errorMsg = 'Network error. Please check your internet connection and try again.';
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert(errorMsg);
            }
        } else {
            const errorMsg = 'Payment initialization failed. Please try again.';
            if (typeof Toast !== 'undefined') {
                Toast.error(errorMsg);
            } else {
                alert(errorMsg);
            }
        }
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

/**
 * Create order after successful payment
 * No verification - if Paystack says success, we trust it and create the order
 */
function createOrderAfterPayment(reference, amount) {
    console.log('=== CREATING ORDER AFTER PAYMENT ===');
    console.log('Reference:', reference);
    console.log('Amount:', amount);
    
    // Show loading message
    if (typeof Toast !== 'undefined') {
        Toast.info('Processing order...', { duration: 0 });
    }
    
    // Construct the correct URL for the payment verification endpoint
    let orderUrl;
    if (typeof BASE_URL !== 'undefined') {
        // Remove /public_html if present, then add the actions path
        orderUrl = BASE_URL.replace('/public_html', '') + '/actions/paystack_verify_payment.php';
    } else {
        // Fallback: try to construct from current location
        const currentPath = window.location.pathname;
        const basePath = currentPath.substring(0, currentPath.indexOf('/view/') || currentPath.indexOf('/public_html/'));
        orderUrl = basePath + '/actions/paystack_verify_payment.php';
    }
    
    console.log('Order creation URL:', orderUrl);
    
    fetch(orderUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            reference: reference,
            total_amount: amount
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        // Check if response is ok
        if (!response.ok) {
            console.error('Response not OK:', response.status, response.statusText);
            // Still try to parse JSON to get error message
        }
        
        return response.json().catch(err => {
            console.error('JSON parse error:', err);
            // Return error structure if JSON parsing fails
            return { 
                status: 'error', 
                message: 'Failed to parse server response',
                order_created: false 
            };
        });
    })
    .then(data => {
        console.log('Order creation response:', data);
        
        // Check if order was successfully created
        if (data.status === 'success' && data.order_id) {
            console.log('✓ Order created successfully:', data.order_id);
            
            if (typeof Toast !== 'undefined') {
                Toast.success('Payment successful! Order created.');
            }
            
            // Redirect to success page with order details
            setTimeout(() => {
                const successUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 
                                 '/view/payment/payment_success.php?reference=' + 
                                 encodeURIComponent(reference) + 
                                 '&invoice=' + encodeURIComponent(data.invoice_no || reference) +
                                 '&order_id=' + encodeURIComponent(data.order_id);
                console.log('Redirecting to:', successUrl);
                window.location.href = successUrl;
            }, 1000);
            
        } else if (data.status === 'success' && !data.order_id) {
            // Order creation may have partially succeeded
            console.warn('Order creation response indicates success but no order_id:', data);
            
            if (typeof Toast !== 'undefined') {
                Toast.warning('Payment successful! Please check your order status. Reference: ' + reference);
            }
            
            // Redirect to success page
            setTimeout(() => {
                const successUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 
                                 '/view/payment/payment_success.php?reference=' + 
                                 encodeURIComponent(reference) + 
                                 '&invoice=' + encodeURIComponent(data.invoice_no || reference);
                window.location.href = successUrl;
            }, 2000);
            
        } else {
            // Order creation failed
            console.error('Order creation failed:', data.message || 'Unknown error');
            
            // Log error details for debugging
            console.error('Error details:', data);
            
            // Show error but still redirect since payment was successful
            if (typeof Toast !== 'undefined') {
                Toast.error('Payment successful but order creation failed. Please contact support with reference: ' + reference);
            }
            
            // Still redirect to success page since payment went through
            setTimeout(() => {
                const successUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 
                                 '/view/payment/payment_success.php?reference=' + 
                                 encodeURIComponent(reference) + 
                                 '&invoice=' + encodeURIComponent(reference) +
                                 '&error=order_creation_failed';
                window.location.href = successUrl;
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Order creation network error:', error);
        
        // Network error - payment was successful on Paystack
        console.error('Full error:', error);
        
        if (typeof Toast !== 'undefined') {
            Toast.error('Payment successful but could not create order. Please contact support with reference: ' + reference);
        }
        
        // Redirect to success page with error flag
        setTimeout(() => {
            const successUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 
                             '/view/payment/payment_success.php?reference=' + 
                             encodeURIComponent(reference) + 
                             '&invoice=' + encodeURIComponent(reference) +
                             '&error=network_error';
            window.location.href = successUrl;
        }, 2000);
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

