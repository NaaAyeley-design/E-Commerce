/**
 * Product Display JavaScript
 * Handles AJAX product search, filtering, and dynamic interactions
 */

// Global variables
let currentPage = 1;
let currentFilters = {};
let searchTimeout = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeProductDisplay();
    
    // Initialize brand filter based on current category selection
    const categoryFilter = document.getElementById('filter-category');
    const brandFilter = document.getElementById('filter-brand');
    
    if (categoryFilter && brandFilter) {
        const selectedCatId = categoryFilter.value;
        if (selectedCatId && selectedCatId != '0') {
            // Enable brand filter and load brands for the currently selected category
            brandFilter.disabled = false;
            loadBrandsByCategory(selectedCatId);
            
            // Restore selected brand after brands are loaded
            setTimeout(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const selectedBrandId = urlParams.get('brand_id');
                if (selectedBrandId && brandFilter) {
                    brandFilter.value = selectedBrandId;
                }
            }, 800);
        } else {
            // Disable brand filter if no category is selected
            brandFilter.disabled = true;
            brandFilter.innerHTML = '<option value="0">Select a category first</option>';
        }
    }
});

/**
 * Initialize product display functionality
 */
function initializeProductDisplay() {
    // Category filter dropdown
    const categoryFilter = document.getElementById('filter-category');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', handleCategoryFilter);
    }

    // Brand filter dropdown
    const brandFilter = document.getElementById('filter-brand');
    if (brandFilter) {
        brandFilter.addEventListener('change', handleBrandFilter);
    }

    // Clear filters button
    const clearFiltersBtn = document.getElementById('clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearFilters);
    }

    // Search form
    const searchForm = document.getElementById('product-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', handleSearchSubmit);
    }

    // Search input (real-time search)
    const searchInput = document.getElementById('product-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', handleSearchInput);
    }

    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', handleAddToCart);
    });

    // Load brands when category changes
    if (categoryFilter) {
        categoryFilter.addEventListener('change', loadBrandsByCategory);
    }
}

/**
 * Handle category filter change
 */
function handleCategoryFilter(e) {
    const catId = e.target.value;
    
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Update or remove category filter
    if (catId == '0' || catId == '') {
        urlParams.delete('cat_id');
    } else {
        urlParams.set('cat_id', catId);
    }
    
    // Reset to page 1 when filtering
    urlParams.set('page', '1');
    
    // Remove brand filter when category changes (brands are category-specific)
    urlParams.delete('brand_id');
    
    // Load brands for selected category
    loadBrandsByCategory(catId);
    
    // Reload page with new filters
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

/**
 * Handle brand filter change
 */
function handleBrandFilter(e) {
    const brandId = e.target.value;
    
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Update or remove brand filter
    if (brandId == '0' || brandId == '') {
        urlParams.delete('brand_id');
    } else {
        urlParams.set('brand_id', brandId);
    }
    
    // Reset to page 1 when filtering
    urlParams.set('page', '1');
    
    // Reload page with new filters
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

/**
 * Load brands by category for dynamic dropdown
 */
function loadBrandsByCategory(catId) {
    const brandFilter = document.getElementById('filter-brand');
    if (!brandFilter) return;
    
    if (!catId || catId == '0') {
        // Reset brand filter if no category selected
        brandFilter.innerHTML = '<option value="0">Select a category first</option>';
        brandFilter.disabled = true;
        return;
    }

    // Get action URL
    let actionUrl;
    if (typeof BASE_URL !== 'undefined' && BASE_URL) {
        actionUrl = BASE_URL.replace('/public_html', '') + '/actions/product_actions.php?action=get_brands_by_category&cat_id=' + catId;
    } else {
        actionUrl = '../../actions/product_actions.php?action=get_brands_by_category&cat_id=' + catId;
    }

    // Show loading state
    brandFilter.disabled = true;
    brandFilter.innerHTML = '<option value="0">Loading brands...</option>';

    fetch(actionUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && Array.isArray(data.data) && data.data.length > 0) {
                // Clear existing options
                brandFilter.innerHTML = '<option value="0">All Brands</option>';
                
                // Add brands for this category
                data.data.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.brand_id;
                    option.textContent = brand.brand_name;
                    brandFilter.appendChild(option);
                });
                brandFilter.disabled = false;
            } else {
                brandFilter.innerHTML = '<option value="0">No brands available</option>';
                brandFilter.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error loading brands:', error);
            brandFilter.innerHTML = '<option value="0">Error loading brands</option>';
            brandFilter.disabled = false;
        });
}

/**
 * Apply filters using AJAX
 */
function applyFilters() {
    const productsContainer = document.getElementById('products-container');
    const productsGrid = document.getElementById('products-grid');
    
    if (!productsContainer) return;

    // Show loading state
    showLoading(productsContainer);

    // Build query parameters
    const params = new URLSearchParams();
    params.append('action', 'composite_search');
    
    if (currentFilters.query) {
        params.append('query', currentFilters.query);
    }
    if (currentFilters.cat_id) {
        params.append('cat_id', currentFilters.cat_id);
    }
    if (currentFilters.brand_id) {
        params.append('brand_id', currentFilters.brand_id);
    }
    if (currentFilters.max_price) {
        params.append('max_price', currentFilters.max_price);
    }
    
    params.append('page', currentPage);
    params.append('limit', 10);

    // Get action URL
    let actionUrl;
    if (typeof BASE_URL !== 'undefined' && BASE_URL) {
        actionUrl = BASE_URL.replace('/public_html', '') + '/actions/product_actions.php?' + params.toString();
    } else {
        actionUrl = '../../actions/product_actions.php?' + params.toString();
    }

    // Fetch products
    fetch(actionUrl)
        .then(response => response.json())
        .then(data => {
            hideLoading(productsContainer);
            
            if (data.success && data.data) {
                displayProducts(data.data);
                updatePagination(data);
            } else {
                showNoProducts(productsContainer);
            }
        })
        .catch(error => {
            console.error('Error fetching products:', error);
            hideLoading(productsContainer);
            showError(productsContainer, 'An error occurred while loading products.');
        });
}

/**
 * Display products in grid
 */
function displayProducts(products) {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;

    if (products.length === 0) {
        productsGrid.innerHTML = '<div class="no-products"><p>No products found.</p></div>';
        return;
    }

    let html = '';
    products.forEach(product => {
        let imageUrl = '';
        if (product.product_image) {
            // If image path is already absolute, use it; otherwise construct from BASE_URL
            if (product.product_image.startsWith('http://') || product.product_image.startsWith('https://')) {
                imageUrl = product.product_image;
            } else {
                // Remove leading slash if present
                const cleanPath = product.product_image.startsWith('/') ? product.product_image.substring(1) : product.product_image;
                // If uploads path, remove /public_html from BASE_URL
                if (cleanPath.startsWith('uploads/')) {
                    const baseUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '');
                    imageUrl = baseUrl + '/' + cleanPath;
                } else {
                    imageUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL + '/' + cleanPath : '/' + cleanPath);
                }
            }
        } else {
            imageUrl = (typeof ASSETS_URL !== 'undefined' ? ASSETS_URL + '/images/placeholder-product.svg' : '/images/placeholder-product.svg');
        }
        
        const productUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/view/product/single_product.php?id=' + product.product_id;
        
        html += `
            <div class="product-card" data-product-id="${product.product_id}">
                <div class="product-image">
                    <img src="${imageUrl}" 
                         alt="${escapeHtml(product.product_title)}"
                         onerror="this.src='${typeof ASSETS_URL !== 'undefined' ? ASSETS_URL : ''}/images/placeholder-product.svg'">
                </div>
                <div class="product-info">
                    <h3 class="product-title">
                        <a href="${productUrl}">${escapeHtml(product.product_title)}</a>
                    </h3>
                    <div class="product-meta">
                        <span class="product-category">${escapeHtml(product.cat_name || 'N/A')}</span>
                        <span class="product-brand">${escapeHtml(product.brand_name || 'N/A')}</span>
                    </div>
                    <div class="product-price">₵${parseFloat(product.product_price).toFixed(2)}</div>
                    <div class="product-actions">
                        <a href="${productUrl}" class="btn btn-primary btn-sm">View Details</a>
                        <button class="btn btn-outline btn-sm add-to-cart-btn" 
                                data-product-id="${product.product_id}">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    productsGrid.innerHTML = html;

    // Re-attach event listeners for add to cart buttons
    const addToCartButtons = productsGrid.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', handleAddToCart);
    });
}

/**
 * Update pagination
 */
function updatePagination(data) {
    const paginationContainer = document.querySelector('.pagination');
    if (!paginationContainer || !data.total_pages) return;

    let html = '';
    
    if (data.page > 1) {
        const prevUrl = buildPaginationUrl(data.page - 1);
        html += `<a href="${prevUrl}" class="btn btn-outline">Previous</a>`;
    }
    
    html += `<span class="page-info">Page ${data.page} of ${data.total_pages}</span>`;
    
    if (data.page < data.total_pages) {
        const nextUrl = buildPaginationUrl(data.page + 1);
        html += `<a href="${nextUrl}" class="btn btn-outline">Next</a>`;
    }

    paginationContainer.innerHTML = html;
}

/**
 * Build pagination URL
 */
function buildPaginationUrl(page) {
    const params = new URLSearchParams();
    params.append('page', page);
    
    if (currentFilters.cat_id) {
        params.append('cat_id', currentFilters.cat_id);
    }
    if (currentFilters.brand_id) {
        params.append('brand_id', currentFilters.brand_id);
    }
    if (currentFilters.query) {
        params.append('query', currentFilters.query);
    }
    if (currentFilters.max_price) {
        params.append('max_price', currentFilters.max_price);
    }

    return window.location.pathname + '?' + params.toString();
}

/**
 * Handle search form submit
 */
function handleSearchSubmit(e) {
    e.preventDefault();
    const searchInput = document.getElementById('product-search-input');
    if (searchInput) {
        const query = searchInput.value.trim();
        if (query) {
            window.location.href = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 
                                   '/view/product/all_product.php?query=' + 
                                   encodeURIComponent(query);
        }
    }
}

/**
 * Handle search input (real-time search)
 */
function handleSearchInput(e) {
    const query = e.target.value.trim();
    
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // Debounce search
    searchTimeout = setTimeout(() => {
        if (query.length >= 2) {
            currentFilters.query = query;
            currentPage = 1;
            applyFilters();
        } else if (query.length === 0) {
            delete currentFilters.query;
            currentPage = 1;
            applyFilters();
        }
    }, 500);
}

/**
 * Clear all filters
 */
function clearFilters() {
    currentFilters = {};
    currentPage = 1;
    
    // Reset dropdowns
    const categoryFilter = document.getElementById('filter-category');
    if (categoryFilter) {
        categoryFilter.value = '0';
    }
    
    const brandFilter = document.getElementById('filter-brand');
    if (brandFilter) {
        brandFilter.value = '0';
        brandFilter.disabled = true;
        brandFilter.innerHTML = '<option value="0">Select a category first</option>';
    }
    
    // Reload page without filters
    window.location.href = window.location.pathname;
}

/**
 * Handle add to cart
 */
function handleAddToCart(e) {
    e.preventDefault();
    const button = e.target.closest('.add-to-cart-btn');
    if (!button) return;
    
    const productId = button.getAttribute('data-product-id');
    
    if (!productId) {
        if (typeof Toast !== 'undefined') {
            Toast.error('Product ID is missing.');
        } else {
            alert('Product ID is missing.');
        }
        return;
    }

    // Disable button during request
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

    // Get action URL
    const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/add_to_cart_action.php';

    // Add to cart via AJAX
    fetch(actionUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            product_id: productId,
            quantity: 1
        })
    })
    .then(async response => {
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Cart action error response:', errorText);
            throw new Error('HTTP error! status: ' + response.status + ' - ' + errorText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Toast !== 'undefined') {
                Toast.success(data.message || 'Product added to cart!');
            } else {
                alert(data.message || 'Product added to cart!');
            }
            // Optionally update cart count in header if it exists
            updateCartCount();
        } else {
            if (typeof Toast !== 'undefined') {
                Toast.error(data.message || 'Failed to add product to cart.');
            } else {
                alert(data.message || 'Failed to add product to cart.');
            }
        }
    })
    .catch(error => {
        console.error('Add to cart error:', error);
        console.error('Error details:', {
            message: error.message,
            stack: error.stack,
            actionUrl: actionUrl
        });
        if (typeof Toast !== 'undefined') {
            Toast.error('An error occurred while adding to cart. Please check the console for details.');
        } else {
            alert('An error occurred while adding to cart. Please check the console for details.');
        }
    })
    .finally(() => {
        // Re-enable button
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

/**
 * Update cart count in header (if cart count element exists)
 */
function updateCartCount() {
    const cartCountElement = document.querySelector('.cart-count, #cart-count, [data-cart-count]');
    if (cartCountElement) {
        // Fetch updated cart count
        const actionUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL.replace('/public_html', '') : '') + '/actions/get_cart_action.php';
        fetch(actionUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.count !== undefined) {
                    cartCountElement.textContent = data.count;
                    cartCountElement.style.display = data.count > 0 ? 'inline' : 'none';
                }
            })
            .catch(error => {
                console.error('Failed to update cart count:', error);
            });
    }
}

/**
 * Show loading state
 */
function showLoading(container) {
    if (container) {
        container.innerHTML = '<div class="loading"><p>Loading products...</p></div>';
    }
}

/**
 * Hide loading state
 */
function hideLoading(container) {
    // Loading state is replaced by content
}

/**
 * Show no products message
 */
function showNoProducts(container) {
    if (container) {
        container.innerHTML = '<div class="no-products"><p>No products found.</p></div>';
    }
}

/**
 * Show error message
 */
function showError(container, message) {
    if (container) {
        container.innerHTML = '<div class="error-message"><p>' + escapeHtml(message) + '</p></div>';
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


