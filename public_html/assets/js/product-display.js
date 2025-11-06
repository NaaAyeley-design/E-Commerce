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
    
    if (catId == '0') {
        // Remove category filter
        delete currentFilters.cat_id;
    } else {
        currentFilters.cat_id = catId;
    }
    
    // Load brands for selected category
    loadBrandsByCategory(catId);
    
    // Apply filters
    applyFilters();
}

/**
 * Handle brand filter change
 */
function handleBrandFilter(e) {
    const brandId = e.target.value;
    
    if (brandId == '0') {
        delete currentFilters.brand_id;
    } else {
        currentFilters.brand_id = brandId;
    }
    
    applyFilters();
}

/**
 * Load brands by category for dynamic dropdown
 */
function loadBrandsByCategory(catId) {
    const brandFilter = document.getElementById('filter-brand');
    if (!brandFilter || !catId || catId == '0') {
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
    const originalHTML = brandFilter.innerHTML;

    fetch(actionUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Clear existing options except first
                brandFilter.innerHTML = '<option value="0">All Brands</option>';
                
                // Add brands for this category
                data.data.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.brand_id;
                    option.textContent = brand.brand_name;
                    brandFilter.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading brands:', error);
            brandFilter.innerHTML = originalHTML;
        })
        .finally(() => {
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
        const imageUrl = product.product_image 
            ? (typeof BASE_URL !== 'undefined' ? BASE_URL + '/' + product.product_image : product.product_image)
            : (typeof ASSETS_URL !== 'undefined' ? ASSETS_URL + '/images/placeholder-product.png' : '');
        
        const productUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/view/product/single_product.php?id=' + product.product_id;
        
        html += `
            <div class="product-card" data-product-id="${product.product_id}">
                <div class="product-image">
                    <img src="${imageUrl}" 
                         alt="${escapeHtml(product.product_title)}"
                         onerror="this.src='${typeof ASSETS_URL !== 'undefined' ? ASSETS_URL : ''}/images/placeholder-product.png'">
                </div>
                <div class="product-info">
                    <h3 class="product-title">
                        <a href="${productUrl}">${escapeHtml(product.product_title)}</a>
                    </h3>
                    <div class="product-meta">
                        <span class="product-category">${escapeHtml(product.cat_name || 'N/A')}</span>
                        <span class="product-brand">${escapeHtml(product.brand_name || 'N/A')}</span>
                    </div>
                    <div class="product-price">$${parseFloat(product.product_price).toFixed(2)}</div>
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
                                   '/view/product/product_search_result.php?query=' + 
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
    }
    
    // Reload page or apply filters
    window.location.href = window.location.pathname;
}

/**
 * Handle add to cart (placeholder)
 */
function handleAddToCart(e) {
    e.preventDefault();
    const productId = e.target.getAttribute('data-product-id');
    
    // Placeholder - implement cart functionality later
    if (typeof Toast !== 'undefined') {
        Toast.info('Add to Cart functionality will be implemented. Product ID: ' + productId);
    } else {
        alert('Add to Cart functionality will be implemented. Product ID: ' + productId);
    }
    
    // You can add AJAX call here to add product to cart
    // Example:
    // fetch(BASE_URL + '/actions/add_to_cart_action.php', {
    //     method: 'POST',
    //     body: JSON.stringify({ product_id: productId })
    // })
    // .then(response => response.json())
    // .then(data => {
    //     if (data.success) {
    //         showNotification('Product added to cart!', 'success');
    //     }
    // });
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


