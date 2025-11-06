/**
 * Cart Management JavaScript
 * 
 * Handles cart operations: update quantity, remove items, etc.
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeCart();
});

/**
 * Initialize cart functionality
 */
function initializeCart() {
    // Quantity decrease buttons
    document.querySelectorAll('.decrease-qty').forEach(button => {
        button.addEventListener('click', handleDecreaseQuantity);
    });

    // Quantity increase buttons
    document.querySelectorAll('.increase-qty').forEach(button => {
        button.addEventListener('click', handleIncreaseQuantity);
    });

    // Quantity input changes
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', handleQuantityChange);
    });

    // Remove item buttons
    document.querySelectorAll('.remove-cart-item').forEach(button => {
        button.addEventListener('click', handleRemoveItem);
    });

    // Checkout button
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        // Enable checkout button if cart has items
        const cartTable = document.querySelector('.cart-table tbody');
        const cartRows = cartTable ? cartTable.querySelectorAll('tr[data-cart-id]') : [];
        if (cartRows.length > 0) {
            checkoutBtn.disabled = false;
        }
        
        checkoutBtn.addEventListener('click', handleCheckout);
    }
}

/**
 * Handle decrease quantity
 */
function handleDecreaseQuantity(e) {
    const button = e.target.closest('.decrease-qty');
    const cartId = button.getAttribute('data-cart-id');
    const input = button.parentElement.querySelector('.quantity-input');
    const currentQty = parseInt(input.value) || 1;
    
    if (currentQty <= 1) {
        return; // Already at minimum
    }

    const newQty = currentQty - 1;
    updateCartItem(cartId, newQty);
}

/**
 * Handle increase quantity
 */
function handleIncreaseQuantity(e) {
    const button = e.target.closest('.increase-qty');
    const cartId = button.getAttribute('data-cart-id');
    const input = button.parentElement.querySelector('.quantity-input');
    const currentQty = parseInt(input.value) || 1;
    
    const newQty = currentQty + 1;
    updateCartItem(cartId, newQty);
}

/**
 * Handle quantity input change
 */
function handleQuantityChange(e) {
    const input = e.target;
    const cartId = input.getAttribute('data-cart-id');
    const newQty = parseInt(input.value) || 1;
    
    if (newQty < 1) {
        input.value = 1;
        return;
    }

    updateCartItem(cartId, newQty);
}

/**
 * Update cart item quantity
 */
function updateCartItem(cartId, quantity) {
    if (!cartId || quantity < 1) {
        return;
    }

    const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
    if (!row) return;

    // Disable controls during update
    const controls = row.querySelectorAll('.quantity-btn, .quantity-input, .remove-cart-item');
    controls.forEach(control => control.disabled = true);

    // Get action URL
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/update_cart_action.php';

    // Update via AJAX
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            cart_id: cartId,
            quantity: quantity
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Reload page to show updated cart
            window.location.reload();
        } else {
            if (typeof Toast !== 'undefined') {
                Toast.error(data.message || 'Failed to update cart item.');
            } else {
                alert(data.message || 'Failed to update cart item.');
            }
            // Re-enable controls
            controls.forEach(control => control.disabled = false);
        }
    })
    .catch(error => {
        console.error('Update cart item error:', error);
        if (typeof Toast !== 'undefined') {
            Toast.error('An error occurred while updating cart item. Please try again.');
        } else {
            alert('An error occurred while updating cart item. Please try again.');
        }
        // Re-enable controls
        controls.forEach(control => control.disabled = false);
    });
}

/**
 * Handle remove item from cart
 */
function handleRemoveItem(e) {
    const button = e.target.closest('.remove-cart-item');
    const cartId = button.getAttribute('data-cart-id');
    
    if (!cartId) {
        return;
    }

    // Confirm removal
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    // Disable button during removal
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Get action URL
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/remove_from_cart_action.php';

    // Remove via AJAX
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            cart_id: cartId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Toast !== 'undefined') {
                Toast.success(data.message || 'Item removed from cart!');
            } else {
                alert(data.message || 'Item removed from cart!');
            }
            // Reload page to show updated cart
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            if (typeof Toast !== 'undefined') {
                Toast.error(data.message || 'Failed to remove item from cart.');
            } else {
                alert(data.message || 'Failed to remove item from cart.');
            }
            // Re-enable button
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Remove cart item error:', error);
        if (typeof Toast !== 'undefined') {
            Toast.error('An error occurred while removing item from cart. Please try again.');
        } else {
            alert('An error occurred while removing item from cart. Please try again.');
        }
        // Re-enable button
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}

/**
 * Handle checkout
 */
function handleCheckout(e) {
    e.preventDefault();
    
    const checkoutBtn = e.target.closest('#checkout-btn');
    if (!checkoutBtn || checkoutBtn.disabled) {
        return;
    }

    // Confirm checkout
    if (!confirm('Are you sure you want to proceed with checkout? This will create an order from your cart items.')) {
        return;
    }

    // Disable button during checkout
    checkoutBtn.disabled = true;
    const originalHTML = checkoutBtn.innerHTML;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    // Get action URL
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/checkout_action.php';

    // Process checkout via AJAX
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin'
    })
    .then(async response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response received:', text);
            throw new Error('Invalid response format');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Toast !== 'undefined') {
                Toast.success(data.message || 'Order placed successfully!');
            } else {
                alert(data.message || 'Order placed successfully!');
            }
            
            // Redirect to dashboard or order confirmation page
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/view/user/dashboard.php';
                }
            }, 1500);
        } else {
            if (typeof Toast !== 'undefined') {
                Toast.error(data.message || 'Failed to process checkout. Please try again.');
            } else {
                alert(data.message || 'Failed to process checkout. Please try again.');
            }
            // Re-enable button
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Checkout error:', error);
        if (typeof Toast !== 'undefined') {
            Toast.error('An error occurred during checkout. Please try again.');
        } else {
            alert('An error occurred during checkout. Please try again.');
        }
        // Re-enable button
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = originalHTML;
    });
}

