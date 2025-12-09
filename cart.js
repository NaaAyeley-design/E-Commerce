/**
 * Cart JavaScript - Frontend cart operations
 * Handles all cart interactions with the backend API
 */

class CartManager {
    constructor() {
        this.apiUrl = 'cart_api.php';
        this.init();
    }
    
    init() {
        // Load cart on page load
        this.loadCart();
        
        // Set up event listeners if cart UI exists
        this.setupEventListeners();
    }
    
    /**
     * Setup event listeners for cart buttons
     */
    setupEventListeners() {
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId || e.target.closest('[data-product-id]')?.dataset.productId;
                const quantity = parseInt(e.target.dataset.quantity || 1);
                if (productId) {
                    this.addItem(productId, quantity);
                }
            });
        });
        
        // Update quantity buttons
        document.querySelectorAll('.update-cart-quantity').forEach(button => {
            button.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                const quantity = parseInt(e.target.value || e.target.closest('input')?.value || 1);
                if (productId) {
                    this.updateQuantity(productId, quantity);
                }
            });
        });
        
        // Remove item buttons
        document.querySelectorAll('.remove-from-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                if (productId) {
                    this.removeItem(productId);
                }
            });
        });
        
        // Empty cart button
        const emptyCartBtn = document.getElementById('empty-cart');
        if (emptyCartBtn) {
            emptyCartBtn.addEventListener('click', () => {
                this.emptyCart();
            });
        }
    }
    
    /**
     * Add item to cart
     */
    async addItem(productId, quantity = 1) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Item added to cart', 'success');
                this.loadCart();
                this.updateCartBadge();
            } else {
                this.showMessage(data.message || 'Failed to add item', 'error');
            }
            
            return data;
        } catch (error) {
            console.error('Error adding item to cart:', error);
            this.showMessage('An error occurred while adding item to cart', 'error');
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Update item quantity in cart
     */
    async updateQuantity(productId, quantity) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.loadCart();
                this.updateCartBadge();
            } else {
                this.showMessage(data.message || 'Failed to update cart', 'error');
            }
            
            return data;
        } catch (error) {
            console.error('Error updating cart:', error);
            this.showMessage('An error occurred while updating cart', 'error');
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Remove item from cart
     */
    async removeItem(productId) {
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?product_id=${productId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Item removed from cart', 'success');
                this.loadCart();
                this.updateCartBadge();
            } else {
                this.showMessage(data.message || 'Failed to remove item', 'error');
            }
            
            return data;
        } catch (error) {
            console.error('Error removing item from cart:', error);
            this.showMessage('An error occurred while removing item', 'error');
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Empty entire cart
     */
    async emptyCart() {
        if (!confirm('Are you sure you want to empty your cart?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=empty`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Cart emptied', 'success');
                this.loadCart();
                this.updateCartBadge();
            } else {
                this.showMessage(data.message || 'Failed to empty cart', 'error');
            }
            
            return data;
        } catch (error) {
            console.error('Error emptying cart:', error);
            this.showMessage('An error occurred while emptying cart', 'error');
            return { success: false, message: error.message };
        }
    }
    
    /**
     * Load cart items and display them
     */
    async loadCart() {
        try {
            const response = await fetch(`${this.apiUrl}?action=items`);
            const data = await response.json();
            
            if (data.success !== false) {
                this.renderCart(data.items || []);
                this.updateCartTotal(data.total || 0);
            }
            
            return data;
        } catch (error) {
            console.error('Error loading cart:', error);
            return { success: false, items: [], total: 0 };
        }
    }
    
    /**
     * Get cart item count
     */
    async getItemCount() {
        try {
            const response = await fetch(`${this.apiUrl}?action=count`);
            const data = await response.json();
            return data.count || 0;
        } catch (error) {
            console.error('Error getting cart count:', error);
            return 0;
        }
    }
    
    /**
     * Update cart badge/counter
     */
    async updateCartBadge() {
        const count = await this.getItemCount();
        const badge = document.getElementById('cart-badge') || document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }
    
    /**
     * Render cart items in the UI
     */
    renderCart(items) {
        const cartContainer = document.getElementById('cart-items') || document.querySelector('.cart-items');
        if (!cartContainer) return;
        
        if (items.length === 0) {
            cartContainer.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
            return;
        }
        
        let html = '<div class="cart-items-list">';
        items.forEach(item => {
            html += `
                <div class="cart-item" data-product-id="${item.product_id}">
                    <div class="cart-item-info">
                        <h4>${item.product_name || 'Product #' + item.product_id}</h4>
                        <p class="cart-item-price">$${parseFloat(item.product_price || 0).toFixed(2)} each</p>
                    </div>
                    <div class="cart-item-controls">
                        <input type="number" 
                               class="cart-quantity-input" 
                               value="${item.quantity}" 
                               min="1" 
                               data-product-id="${item.product_id}"
                               onchange="cartManager.updateQuantity(${item.product_id}, this.value)">
                        <button class="remove-from-cart" data-product-id="${item.product_id}">Remove</button>
                    </div>
                    <div class="cart-item-total">
                        $${parseFloat(item.item_total || 0).toFixed(2)}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        cartContainer.innerHTML = html;
        
        // Re-setup event listeners for new elements
        this.setupEventListeners();
    }
    
    /**
     * Update cart total display
     */
    updateCartTotal(total) {
        const totalElement = document.getElementById('cart-total') || document.querySelector('.cart-total');
        if (totalElement) {
            totalElement.textContent = `$${parseFloat(total).toFixed(2)}`;
        }
    }
    
    /**
     * Show message to user
     */
    showMessage(message, type = 'info') {
        // Create or update message element
        let messageEl = document.getElementById('cart-message');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.id = 'cart-message';
            messageEl.className = `cart-message cart-message-${type}`;
            document.body.appendChild(messageEl);
        }
        
        messageEl.textContent = message;
        messageEl.className = `cart-message cart-message-${type} show`;
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            messageEl.classList.remove('show');
        }, 3000);
    }
}

// Initialize cart manager when DOM is ready
let cartManager;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        cartManager = new CartManager();
    });
} else {
    cartManager = new CartManager();
}

// Make cartManager globally available
window.cartManager = cartManager;

