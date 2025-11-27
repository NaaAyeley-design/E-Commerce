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
                    <button class="wishlist-btn" 
                            data-product-id="${product.product_id}" 
                            aria-label="Add to wishlist"
                            aria-pressed="false"
                            title="Add to wishlist">
                        <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>
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

/**
 * Initialize Filter Sidebar Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeFilterSidebar();
    initializeSizeButtons();
    initializeColorSwatches();
    initializeTopFilters();
    initializeClearFilters();
});

/**
 * Initialize Filter Sidebar
 */
function initializeFilterSidebar() {
    const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
    const filterSidebar = document.getElementById('filter-sidebar');
    const filterSidebarClose = document.getElementById('filter-sidebar-close');
    
    // Create overlay for mobile
    let overlay = document.querySelector('.filter-sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'filter-sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // Mobile filter toggle
    if (mobileFilterToggle && filterSidebar) {
        mobileFilterToggle.addEventListener('click', function() {
            filterSidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close sidebar
    if (filterSidebarClose) {
        filterSidebarClose.addEventListener('click', function() {
            filterSidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close on overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            filterSidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Handle sidebar form changes
    const sidebarForm = document.getElementById('filter-sidebar-form');
    if (sidebarForm) {
        const radioInputs = sidebarForm.querySelectorAll('input[type="radio"]');
        const checkboxInputs = sidebarForm.querySelectorAll('input[type="checkbox"]');
        
        // Auto-submit on radio change
        radioInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Update top filters to match
                if (this.name === 'cat_id') {
                    const topCategoryFilter = document.getElementById('top-filter-category');
                    if (topCategoryFilter) {
                        topCategoryFilter.value = this.value;
                        if (topCategoryFilter.value !== '0') {
                            loadBrandsByCategory(this.value);
                        } else {
                            const topBrandFilter = document.getElementById('top-filter-brand');
                            if (topBrandFilter) {
                                topBrandFilter.disabled = true;
                                topBrandFilter.innerHTML = '<option value="0">Select a category first</option>';
                            }
                        }
                    }
                }
                
                // Handle price range
                if (this.name === 'price_range') {
                    const maxPriceInput = document.getElementById('max-price-input');
                    if (maxPriceInput) {
                        if (this.value === 'all') {
                            maxPriceInput.value = '0';
                        } else if (this.value === '200+') {
                            maxPriceInput.value = '9999';
                        } else {
                            maxPriceInput.value = this.value;
                        }
                    }
                }
                
                // Submit form after a short delay
                setTimeout(() => {
                    sidebarForm.submit();
                }, 300);
            });
        });
        
        // Auto-submit on checkbox change
        checkboxInputs.forEach(input => {
            input.addEventListener('change', function() {
                setTimeout(() => {
                    sidebarForm.submit();
                }, 300);
            });
        });
    }
}

/**
 * Initialize Size Buttons
 */
function initializeSizeButtons() {
    const sizeButtons = document.querySelectorAll('.size-button');
    const selectedSizesInput = document.getElementById('selected-sizes');
    let selectedSizes = [];
    
    sizeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const size = this.getAttribute('data-size');
            
            if (this.classList.contains('active')) {
                this.classList.remove('active');
                selectedSizes = selectedSizes.filter(s => s !== size);
            } else {
                this.classList.add('active');
                selectedSizes.push(size);
            }
            
            // Update hidden input
            if (selectedSizesInput) {
                selectedSizesInput.value = selectedSizes.join(',');
            }
            
            // Auto-submit form
            const sidebarForm = document.getElementById('filter-sidebar-form');
            if (sidebarForm) {
                setTimeout(() => {
                    sidebarForm.submit();
                }, 300);
            }
        });
    });
}

/**
 * Initialize Color Swatches
 */
function initializeColorSwatches() {
    const colorSwatches = document.querySelectorAll('.color-swatch');
    const selectedColorsInput = document.getElementById('selected-colors');
    let selectedColors = [];
    
    colorSwatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            const color = this.getAttribute('data-color');
            
            if (this.classList.contains('active')) {
                this.classList.remove('active');
                selectedColors = selectedColors.filter(c => c !== color);
            } else {
                this.classList.add('active');
                selectedColors.push(color);
            }
            
            // Update hidden input
            if (selectedColorsInput) {
                selectedColorsInput.value = selectedColors.join(',');
            }
            
            // Auto-submit form
            const sidebarForm = document.getElementById('filter-sidebar-form');
            if (sidebarForm) {
                setTimeout(() => {
                    sidebarForm.submit();
                }, 300);
            }
        });
    });
}

/**
 * Initialize Top Filters
 */
function initializeTopFilters() {
    const topCategoryFilter = document.getElementById('top-filter-category');
    const topBrandFilter = document.getElementById('top-filter-brand');
    const topFiltersForm = document.getElementById('top-filters-form');
    
    // Sync top category filter with sidebar
    if (topCategoryFilter) {
        topCategoryFilter.addEventListener('change', function() {
            const value = this.value;
            
            // Update sidebar radio
            const sidebarRadio = document.querySelector(`#filter-sidebar-form input[name="cat_id"][value="${value}"]`);
            if (sidebarRadio) {
                sidebarRadio.checked = true;
            }
            
            // Load brands for category
            if (value !== '0') {
                loadBrandsByCategory(value);
            } else {
                if (topBrandFilter) {
                    topBrandFilter.disabled = true;
                    topBrandFilter.innerHTML = '<option value="0">Select a category first</option>';
                }
            }
            
            // Submit form
            if (topFiltersForm) {
                topFiltersForm.submit();
            }
        });
    }
    
    // Sync top brand filter with sidebar
    if (topBrandFilter) {
        topBrandFilter.addEventListener('change', function() {
            const value = this.value;
            
            // Update sidebar checkboxes
            const sidebarCheckboxes = document.querySelectorAll('#filter-sidebar-form input[name="brand_ids[]"]');
            sidebarCheckboxes.forEach(checkbox => {
                checkbox.checked = (checkbox.value === value);
            });
            
            // Submit form
            if (topFiltersForm) {
                topFiltersForm.submit();
            }
        });
    }
}

/**
 * Initialize Clear Filters
 */
function initializeClearFilters() {
    const clearFiltersBtn = document.getElementById('clear-all-filters');
    
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            // Reset all filters
            const sidebarForm = document.getElementById('filter-sidebar-form');
            if (sidebarForm) {
                sidebarForm.reset();
            }
            
            // Reset size buttons
            const sizeButtons = document.querySelectorAll('.size-button');
            sizeButtons.forEach(btn => btn.classList.remove('active'));
            
            // Reset color swatches
            const colorSwatches = document.querySelectorAll('.color-swatch');
            colorSwatches.forEach(swatch => swatch.classList.remove('active'));
            
            // Reset hidden inputs
            const selectedSizesInput = document.getElementById('selected-sizes');
            const selectedColorsInput = document.getElementById('selected-colors');
            if (selectedSizesInput) selectedSizesInput.value = '';
            if (selectedColorsInput) selectedColorsInput.value = '';
            
            // Reset top filters
            const topCategoryFilter = document.getElementById('top-filter-category');
            const topBrandFilter = document.getElementById('top-filter-brand');
            if (topCategoryFilter) topCategoryFilter.value = '0';
            if (topBrandFilter) {
                topBrandFilter.value = '0';
                topBrandFilter.disabled = true;
                topBrandFilter.innerHTML = '<option value="0">Select a category first</option>';
            }
            
            // Redirect to clean URL
            window.location.href = window.location.pathname;
        });
    }
}

/**
 * Update products count
 */
function updateProductsCount(count) {
    const productsCountElement = document.getElementById('products-count');
    if (productsCountElement) {
        productsCountElement.textContent = count + ' product' + (count !== 1 ? 's' : '');
    }
}

/**
 * Initialize Product Detail Page Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeProductDetailPage();
});

function initializeProductDetailPage() {
    // Only initialize if we're on the product detail page
    if (!document.querySelector('.product-detail-page')) {
        return;
    }

    initializeThumbnails();
    initializeColorSelection();
    initializeSizeSelection();
    initializeQuantityControls();
    initializeAddToCart();
    initializeAddToWishlist();
}

/**
 * Initialize Thumbnail Gallery
 */
function initializeThumbnails() {
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const mainImage = document.getElementById('product-main-image');

    if (!thumbnails.length || !mainImage) return;

    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            // Remove active class from all thumbnails
            thumbnails.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            this.classList.add('active');
            
            // Update main image
            const imageUrl = this.getAttribute('data-image');
            if (imageUrl) {
                mainImage.style.opacity = '0';
                setTimeout(() => {
                    mainImage.src = imageUrl;
                    mainImage.style.opacity = '1';
                }, 150);
            }
        });
    });
}

/**
 * Initialize Color Selection
 */
function initializeColorSelection() {
    const colorSwatches = document.querySelectorAll('.color-swatch');
    const selectedColorInput = document.getElementById('selected-color');

    if (!colorSwatches.length) return;

    colorSwatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            // Remove active class from all swatches
            colorSwatches.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked swatch
            this.classList.add('active');
            
            // Update hidden input
            const color = this.getAttribute('data-color');
            if (selectedColorInput) {
                selectedColorInput.value = color;
            }
            
            // Optional: Update product image based on color (if different images available)
            // This would require product data with color-specific images
        });
    });
}

/**
 * Initialize Size Selection
 */
function initializeSizeSelection() {
    const sizeButtons = document.querySelectorAll('.size-btn');
    const selectedSizeInput = document.getElementById('selected-size');

    if (!sizeButtons.length) return;

    sizeButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            sizeButtons.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update hidden input
            const size = this.getAttribute('data-size');
            if (selectedSizeInput) {
                selectedSizeInput.value = size;
            }
        });
    });
}

/**
 * Initialize Quantity Controls
 */
function initializeQuantityControls() {
    const decreaseBtn = document.getElementById('quantity-decrease');
    const increaseBtn = document.getElementById('quantity-increase');
    const quantityInput = document.getElementById('quantity-input');

    if (!decreaseBtn || !increaseBtn || !quantityInput) return;

    decreaseBtn.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value) || 1;
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });

    increaseBtn.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value) || 1;
        const maxValue = parseInt(quantityInput.getAttribute('max')) || 99;
        if (currentValue < maxValue) {
            quantityInput.value = currentValue + 1;
        }
    });

    quantityInput.addEventListener('input', function() {
        let value = parseInt(this.value) || 1;
        const min = parseInt(this.getAttribute('min')) || 1;
        const max = parseInt(this.getAttribute('max')) || 99;
        
        if (value < min) {
            this.value = min;
        } else if (value > max) {
            this.value = max;
        }
    });

    quantityInput.addEventListener('blur', function() {
        if (!this.value || parseInt(this.value) < 1) {
            this.value = 1;
        }
    });
}

/**
 * Initialize Add to Cart
 */
function initializeAddToCart() {
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    
    if (!addToCartBtn) return;

    addToCartBtn.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        const selectedSize = document.getElementById('selected-size')?.value || 'M';
        const selectedColor = document.getElementById('selected-color')?.value || 'terracotta';
        const quantity = parseInt(document.getElementById('quantity-input')?.value) || 1;
        
        // Get product info
        const productTitle = document.querySelector('.product-detail-title')?.textContent || '';
        const productPrice = document.querySelector('.product-detail-price')?.textContent || '';
        const productImage = document.getElementById('product-main-image')?.src || '';

        // Validate size and color are selected
        if (!selectedSize || !selectedColor) {
            if (typeof Toast !== 'undefined') {
                Toast.error('Please select a size and color before adding to cart.');
            } else {
                alert('Please select a size and color before adding to cart.');
            }
            return;
        }

        // Disable button during request
        const originalText = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

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
                quantity: quantity
            })
        })
        .then(async response => {
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error('HTTP error! status: ' + response.status + ' - ' + errorText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success animation
                this.innerHTML = '<i class="fas fa-check"></i> Added!';
                this.style.background = 'var(--deep-brown)';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.background = '';
                }, 2000);

                if (typeof Toast !== 'undefined') {
                    Toast.success(data.message || 'Product added to cart!');
                } else {
                    alert(data.message || 'Product added to cart!');
                }
                
                // Update cart count
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
            if (typeof Toast !== 'undefined') {
                Toast.error('An error occurred while adding to cart. Please try again.');
            } else {
                alert('An error occurred while adding to cart. Please try again.');
            }
        })
        .finally(() => {
            // Re-enable button
            this.disabled = false;
        });
    });
}

/**
 * Initialize Add to Wishlist
 */
function initializeAddToWishlist() {
    const wishlistBtn = document.getElementById('add-to-wishlist-btn');
    
    if (!wishlistBtn) return;

    let isInWishlist = false;

    // Check if product is already in wishlist (from localStorage or database)
    // For now, using localStorage
    const productId = document.getElementById('add-to-cart-btn')?.getAttribute('data-product-id');
    if (productId) {
        const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        isInWishlist = wishlist.includes(parseInt(productId));
        if (isInWishlist) {
            wishlistBtn.classList.add('active');
            wishlistBtn.querySelector('i').classList.remove('far');
            wishlistBtn.querySelector('i').classList.add('fas');
        }
    }

    wishlistBtn.addEventListener('click', function() {
        if (!productId) return;

        const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        const productIdNum = parseInt(productId);
        const icon = this.querySelector('i');

        if (isInWishlist) {
            // Remove from wishlist
            const index = wishlist.indexOf(productIdNum);
            if (index > -1) {
                wishlist.splice(index, 1);
            }
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            this.classList.remove('active');
            icon.classList.remove('fas');
            icon.classList.add('far');
            isInWishlist = false;
            
            if (typeof Toast !== 'undefined') {
                Toast.success('Removed from wishlist');
            }
        } else {
            // Add to wishlist
            wishlist.push(productIdNum);
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            this.classList.add('active');
            icon.classList.remove('far');
            icon.classList.add('fas');
            isInWishlist = true;
            
            // Heart animation
            this.style.transform = 'scale(1.2)';
            setTimeout(() => {
                this.style.transform = '';
            }, 300);
            
            if (typeof Toast !== 'undefined') {
                Toast.success('Added to wishlist');
            }
        }
    });
}


