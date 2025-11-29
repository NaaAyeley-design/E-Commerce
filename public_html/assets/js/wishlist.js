/**
 * KenteKart Wishlist System
 * Complete wishlist functionality with localStorage and UI management
 */

// Wishlist storage key
const WISHLIST_STORAGE_KEY = 'kentekart_wishlist';

/**
 * Initialize wishlist system
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeWishlist();
    
    // If on dashboard and wishlist section is active, load it
    if (document.getElementById('section-wishlist')) {
        const wishlistSection = document.getElementById('section-wishlist');
        if (wishlistSection && wishlistSection.classList.contains('active')) {
            setTimeout(() => {
                loadWishlistPage();
            }, 100);
        }
    }
});

/**
 * Initialize wishlist UI on page load
 */
function initializeWishlist() {
    // Update all heart icons on the page
    updateAllHeartIcons();
    
    // Update wishlist count in navigation
    updateWishlistCount();
    
    // Set up event listeners for wishlist buttons
    setupWishlistButtons();
    
    // Initialize wishlist page if on wishlist page or dashboard wishlist section
    if (document.getElementById('wishlist-page')) {
        loadWishlistPage();
    } else if (document.getElementById('dashboard-wishlist-grid')) {
        // Dashboard wishlist section - will be loaded when section is activated
        // This is handled by the dashboard's own initialization
    }
}

/**
 * Setup event listeners for wishlist buttons
 */
function setupWishlistButtons() {
    // Product card heart buttons
    const wishlistButtons = document.querySelectorAll('.wishlist-btn, .btn-wishlist, .save-for-later-btn');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', handleWishlistToggle);
    });
    
    // Remove buttons on wishlist page
    const removeButtons = document.querySelectorAll('.remove-wishlist-btn');
    removeButtons.forEach(button => {
        button.addEventListener('click', handleRemoveFromWishlist);
    });
    
    // Clear all wishlist button (standalone page)
    const clearAllBtn = document.getElementById('clear-wishlist');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', handleClearWishlist);
    }
    
    // Clear all wishlist button (dashboard)
    const dashboardClearBtn = document.getElementById('dashboard-clear-wishlist');
    if (dashboardClearBtn) {
        dashboardClearBtn.addEventListener('click', handleClearWishlist);
    }
    
    // Add all to cart button (standalone page)
    const addAllToCartBtn = document.getElementById('add-all-to-cart');
    if (addAllToCartBtn) {
        addAllToCartBtn.addEventListener('click', handleAddAllToCart);
    }
    
    // Add all to cart button (dashboard)
    const dashboardAddAllBtn = document.getElementById('dashboard-add-all-to-cart');
    if (dashboardAddAllBtn) {
        dashboardAddAllBtn.addEventListener('click', handleAddAllToCart);
    }
}

/**
 * Handle wishlist toggle (add/remove)
 */
function handleWishlistToggle(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = e.currentTarget;
    const productId = button.getAttribute('data-product-id');
    
    if (!productId) {
        console.error('Product ID not found');
        return;
    }
    
    // Get product data from button or card
    const productData = getProductData(button);
    
    if (isInWishlist(productId)) {
        removeFromWishlist(productId);
    } else {
        addToWishlist(productId, productData);
    }
}

/**
 * Get product data from DOM element
 */
function getProductData(element) {
    const card = element.closest('.product-card, .wishlist-item, .cart-item');
    if (!card) return null;
    
    const productId = element.getAttribute('data-product-id');
    const name = card.querySelector('.product-title, .product-name')?.textContent?.trim() || '';
    const brand = card.querySelector('.product-brand, .brand-name')?.textContent?.trim() || '';
    const priceText = card.querySelector('.product-price')?.textContent?.trim() || '';
    const price = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;
    const image = card.querySelector('.product-image img')?.src || '';
    const category = card.querySelector('.product-category')?.textContent?.trim() || '';
    
    return {
        id: productId,
        name: name,
        brand: brand,
        price: price,
        image: image,
        category: category,
        inStock: true, // Default to true, can be enhanced
        dateAdded: new Date().toISOString()
    };
}

/**
 * Add product to wishlist
 */
function addToWishlist(productId, productData = null) {
    // Get current wishlist
    let wishlist = getWishlistItems();
    
    // Check if already in wishlist
    if (isInWishlist(productId)) {
        showToast('Item already in wishlist', 'info');
        return false;
    }
    
    // If product data not provided, try to get it
    if (!productData) {
        productData = getProductData(document.querySelector(`[data-product-id="${productId}"]`));
    }
    
    // Create wishlist item
    const wishlistItem = {
        id: productId,
        name: productData?.name || 'Product',
        brand: productData?.brand || '',
        price: productData?.price || 0,
        image: productData?.image || '',
        category: productData?.category || '',
        inStock: productData?.inStock !== false,
        dateAdded: new Date().toISOString()
    };
    
    // Add to wishlist
    wishlist.push(wishlistItem);
    
    // Save to localStorage
    saveWishlist(wishlist);
    
    // Update UI
    updateHeartIcon(productId, true);
    updateWishlistCount();
    
    // Animate heart
    animateHeart(productId);
    
    // Show notification
    showToast('Added to Wishlist ❤️', 'success');
    
    return true;
}

/**
 * Remove product from wishlist
 */
function removeFromWishlist(productId) {
    // Get current wishlist
    let wishlist = getWishlistItems();
    
    // Filter out the product
    wishlist = wishlist.filter(item => item.id !== productId);
    
    // Save updated wishlist
    saveWishlist(wishlist);
    
    // Update UI
    updateHeartIcon(productId, false);
    updateWishlistCount();
    
    // If on wishlist page or dashboard, remove the item from DOM
    const wishlistItem = document.querySelector(`.wishlist-item[data-product-id="${productId}"]`);
    if (wishlistItem) {
        animateItemRemoval(wishlistItem);
        setTimeout(() => {
            wishlistItem.remove();
            updateWishlistPage();
        }, 400);
    }
    
    // Show notification
    showToast('Removed from Wishlist', 'info');
    
    return true;
}

/**
 * Handle remove from wishlist page
 */
function handleRemoveFromWishlist(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = e.currentTarget;
    const productId = button.getAttribute('data-product-id');
    
    if (productId) {
        removeFromWishlist(productId);
    }
}

/**
 * Check if product is in wishlist
 */
function isInWishlist(productId) {
    const wishlist = getWishlistItems();
    return wishlist.some(item => item.id === productId);
}

/**
 * Get all wishlist items
 */
function getWishlistItems() {
    try {
        const stored = localStorage.getItem(WISHLIST_STORAGE_KEY);
        return stored ? JSON.parse(stored) : [];
    } catch (error) {
        console.error('Error reading wishlist:', error);
        return [];
    }
}

/**
 * Save wishlist to localStorage
 */
function saveWishlist(wishlist) {
    try {
        localStorage.setItem(WISHLIST_STORAGE_KEY, JSON.stringify(wishlist));
    } catch (error) {
        console.error('Error saving wishlist:', error);
        showToast('Error saving wishlist', 'error');
    }
}

/**
 * Get wishlist count
 */
function getWishlistCount() {
    return getWishlistItems().length;
}

/**
 * Update all heart icons on the page
 */
function updateAllHeartIcons() {
    const wishlist = getWishlistItems();
    const wishlistIds = wishlist.map(item => item.id);
    
    document.querySelectorAll('.wishlist-btn, .btn-wishlist').forEach(button => {
        const productId = button.getAttribute('data-product-id');
        if (productId && wishlistIds.includes(productId)) {
            updateHeartIcon(productId, true);
        }
    });
}

/**
 * Update heart icon state
 */
function updateHeartIcon(productId, inWishlist) {
    const buttons = document.querySelectorAll(`[data-product-id="${productId}"].wishlist-btn, [data-product-id="${productId}"].btn-wishlist`);
    
    buttons.forEach(button => {
        const heartIcon = button.querySelector('.heart-icon');
        const buttonText = button.querySelector('.btn-text');
        
        if (inWishlist) {
            button.classList.add('in-wishlist');
            button.setAttribute('aria-pressed', 'true');
            button.setAttribute('aria-label', 'Remove from wishlist');
            
            if (heartIcon) {
                heartIcon.classList.add('filled');
            }
            
            if (buttonText) {
                buttonText.textContent = 'In Wishlist ❤️';
            }
        } else {
            button.classList.remove('in-wishlist');
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute('aria-label', 'Add to wishlist');
            
            if (heartIcon) {
                heartIcon.classList.remove('filled');
            }
            
            if (buttonText) {
                buttonText.textContent = 'Add to Wishlist';
            }
        }
    });
}

/**
 * Animate heart on click
 */
function animateHeart(productId) {
    const buttons = document.querySelectorAll(`[data-product-id="${productId}"].wishlist-btn, [data-product-id="${productId}"].btn-wishlist`);
    
    buttons.forEach(button => {
        const heartIcon = button.querySelector('.heart-icon');
        if (heartIcon) {
            heartIcon.classList.add('animate');
            setTimeout(() => {
                heartIcon.classList.remove('animate');
            }, 500);
        }
    });
}

/**
 * Animate item removal
 */
function animateItemRemoval(element) {
    element.style.transition = 'all 0.4s ease';
    element.style.opacity = '0';
    element.style.transform = 'scale(0.8) translateY(-20px)';
}

/**
 * Update wishlist count in navigation
 */
function updateWishlistCount() {
    const count = getWishlistCount();
    const countElements = document.querySelectorAll('.wishlist-count, #wishlist-count');
    
    countElements.forEach(element => {
        if (count > 0) {
            element.textContent = count;
            element.style.display = 'flex';
        } else {
            element.textContent = '';
            element.style.display = 'none';
        }
    });
}

/**
 * Clear entire wishlist
 */
function handleClearWishlist() {
    const wishlist = getWishlistItems();
    
    if (wishlist.length === 0) {
        showToast('Wishlist is already empty', 'info');
        return;
    }
    
    // Show confirmation
    if (confirm(`Are you sure you want to remove all ${wishlist.length} items from your wishlist?`)) {
        // Clear wishlist
        saveWishlist([]);
        
        // Update all heart icons
        wishlist.forEach(item => {
            updateHeartIcon(item.id, false);
        });
        
        // Update count
        updateWishlistCount();
        
        // Reload wishlist page if on it (standalone or dashboard)
        if (document.getElementById('wishlist-page') || document.getElementById('dashboard-wishlist-grid')) {
            loadWishlistPage();
        }
        
        // Show notification
        showToast('Wishlist Cleared', 'success');
    }
}

/**
 * Add all wishlist items to cart
 */
function handleAddAllToCart() {
    const wishlist = getWishlistItems();
    
    if (wishlist.length === 0) {
        showToast('Wishlist is empty', 'info');
        return;
    }
    
    let addedCount = 0;
    let failedCount = 0;
    
    wishlist.forEach(item => {
        if (addToCart(item.id, 1)) {
            addedCount++;
        } else {
            failedCount++;
        }
    });
    
    if (addedCount > 0) {
        showToast(`Added ${addedCount} item${addedCount !== 1 ? 's' : ''} to Cart`, 'success');
    }
    
    if (failedCount > 0) {
        showToast(`Failed to add ${failedCount} item${failedCount !== 1 ? 's' : ''}`, 'error');
    }
}

/**
 * Add single item to cart from wishlist
 */
function handleAddToCartFromWishlist(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = e.currentTarget;
    const productId = button.getAttribute('data-product-id');
    
    if (productId) {
        if (addToCart(productId, 1)) {
            showToast('Added to Cart', 'success');
        } else {
            showToast('Failed to add to cart', 'error');
        }
    }
}

/**
 * Add product to cart (helper function)
 */
function addToCart(productId, quantity) {
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/add_to_cart_action.php';
    
    return fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin',
        body: new URLSearchParams({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(async response => {
        if (!response.ok) {
            return false;
        }
        const data = await response.json();
        return data.success === true;
    })
    .catch(error => {
        console.error('Add to cart error:', error);
        return false;
    });
}

/**
 * Load wishlist page content
 * Works with both standalone wishlist page and dashboard wishlist section
 */
function loadWishlistPage() {
    const wishlist = getWishlistItems();
    
    // Check for standalone wishlist page elements
    let container = document.getElementById('wishlist-grid');
    let emptyState = document.getElementById('empty-wishlist');
    let itemCount = document.getElementById('item-count');
    
    // If not found, check for dashboard wishlist section elements
    if (!container) {
        container = document.getElementById('dashboard-wishlist-grid');
        emptyState = document.getElementById('dashboard-empty-wishlist');
        itemCount = document.getElementById('dashboard-item-count');
    }
    
    if (itemCount) {
        itemCount.textContent = wishlist.length;
    }
    
    if (wishlist.length === 0) {
        if (container) container.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        return;
    }
    
    if (container) container.style.display = 'grid';
    if (emptyState) emptyState.style.display = 'none';
    
    if (container) {
        container.innerHTML = wishlist.map(item => createWishlistItemHTML(item)).join('');
        
        // Re-attach event listeners
        container.querySelectorAll('.remove-wishlist-btn').forEach(btn => {
            btn.addEventListener('click', handleRemoveFromWishlist);
        });
        
        container.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', handleAddToCartFromWishlist);
        });
    }
}

/**
 * Create wishlist item HTML
 */
function createWishlistItemHTML(item) {
    const stockStatus = item.inStock ? 
        '<p class="stock-status in-stock">In Stock</p>' : 
        '<p class="stock-status out-of-stock">Out of Stock</p>';
    
    return `
        <div class="wishlist-item" data-product-id="${item.id}">
            <button class="remove-wishlist-btn" data-product-id="${item.id}" aria-label="Remove from wishlist">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="product-image">
                <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" onerror="this.src='${typeof ASSETS_URL !== 'undefined' ? ASSETS_URL : ''}/images/placeholder-product.svg'">
            </div>
            <div class="product-info">
                <p class="product-brand">${escapeHtml(item.brand || 'Brand')}</p>
                <h3 class="product-name">${escapeHtml(item.name)}</h3>
                <p class="product-price">₵${parseFloat(item.price).toFixed(2)}</p>
                ${stockStatus}
            </div>
            <button class="btn btn-sm btn-primary add-to-cart-btn" data-product-id="${item.id}">
                Add to Cart
            </button>
        </div>
    `;
}

/**
 * Update wishlist page
 * Works with both standalone wishlist page and dashboard wishlist section
 */
function updateWishlistPage() {
    loadWishlistPage();
    updateWishlistCount();
    
    // Also update dashboard if it exists
    if (typeof loadDashboardWishlist === 'function') {
        loadDashboardWishlist();
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => toast.remove());
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    
    const icon = type === 'success' ? '❤️' : type === 'error' ? '⚠️' : 'ℹ️';
    
    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" aria-label="Close">×</button>
    `;
    
    document.body.appendChild(toast);
    
    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.remove();
    });
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export functions for use in other scripts
window.wishlist = {
    add: addToWishlist,
    remove: removeFromWishlist,
    isInWishlist: isInWishlist,
    getItems: getWishlistItems,
    getCount: getWishlistCount,
    clear: handleClearWishlist
};

