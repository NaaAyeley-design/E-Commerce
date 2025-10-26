/**
 * Brand Management JavaScript
 * Handles AJAX form submissions, validation, and brand management
 */

// Global variables
let brandsData = [];
let categoriesData = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize brand management
    initializeBrandManagement();
    
    // Load initial data
    loadBrandsData();
});

function initializeBrandManagement() {
    // Add brand form submission
    const addBrandForm = document.getElementById('add-brand-form');
    if (addBrandForm) {
        addBrandForm.addEventListener('submit', handleAddBrand);
    }
    
    // Edit brand buttons
    const editButtons = document.querySelectorAll('.edit-brand-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', handleEditBrand);
    });
    
    // Cancel edit buttons
    const cancelButtons = document.querySelectorAll('.cancel-edit-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', handleCancelEdit);
    });
    
    // Delete brand buttons
    const deleteButtons = document.querySelectorAll('.delete-brand-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', handleDeleteBrand);
    });
    
    // Edit form submissions
    const editForms = document.querySelectorAll('.edit-brand-form');
    editForms.forEach(form => {
        form.addEventListener('submit', handleUpdateBrand);
    });
    
    // Real-time validation
    setupRealTimeValidation();
}

// Validation functions
function validateBrandForm(formData) {
    const errors = [];
    
    // Validate brand name
    const brandName = formData.get('brand_name')?.trim();
    if (!brandName) {
        errors.push('Brand name is required');
    } else if (brandName.length < 2) {
        errors.push('Brand name must be at least 2 characters long');
    } else if (brandName.length > 100) {
        errors.push('Brand name must not exceed 100 characters');
    } else if (!/^[a-zA-Z0-9\s\-_]+$/.test(brandName)) {
        errors.push('Brand name can only contain letters, numbers, spaces, hyphens, and underscores');
    }
    
    // Validate category selection
    const categoryId = formData.get('cat_id');
    if (!categoryId || categoryId === '') {
        errors.push('Please select a category');
    }
    
    // Validate description length if provided
    const description = formData.get('brand_description')?.trim();
    if (description && description.length > 1000) {
        errors.push('Brand description must not exceed 1000 characters');
    }
    
    // Validate logo URL if provided
    const logoUrl = formData.get('brand_logo')?.trim();
    if (logoUrl && !isValidUrl(logoUrl)) {
        errors.push('Please enter a valid logo URL');
    }
    
    return errors;
}

function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

function setupRealTimeValidation() {
    const brandNameInput = document.getElementById('brand_name');
    const categorySelect = document.getElementById('cat_id');
    const descriptionInput = document.getElementById('brand_description');
    const logoInput = document.getElementById('brand_logo');
    
    if (brandNameInput) {
        brandNameInput.addEventListener('blur', validateBrandName);
        brandNameInput.addEventListener('input', clearFieldError);
    }
    
    if (categorySelect) {
        categorySelect.addEventListener('change', clearFieldError);
    }
    
    if (descriptionInput) {
        descriptionInput.addEventListener('blur', validateDescription);
        descriptionInput.addEventListener('input', clearFieldError);
    }
    
    if (logoInput) {
        logoInput.addEventListener('blur', validateLogoUrl);
        logoInput.addEventListener('input', clearFieldError);
    }
}

function validateBrandName(e) {
    const input = e.target;
    const value = input.value.trim();
    
    if (!value) {
        showFieldError(input, 'Brand name is required');
    } else if (value.length < 2) {
        showFieldError(input, 'Brand name must be at least 2 characters long');
    } else if (value.length > 100) {
        showFieldError(input, 'Brand name must not exceed 100 characters');
    } else if (!/^[a-zA-Z0-9\s\-_]+$/.test(value)) {
        showFieldError(input, 'Brand name can only contain letters, numbers, spaces, hyphens, and underscores');
    } else {
        clearFieldError(e);
    }
}

function validateDescription(e) {
    const input = e.target;
    const value = input.value.trim();
    
    if (value && value.length > 1000) {
        showFieldError(input, 'Brand description must not exceed 1000 characters');
    } else {
        clearFieldError(e);
    }
}

function validateLogoUrl(e) {
    const input = e.target;
    const value = input.value.trim();
    
    if (value && !isValidUrl(value)) {
        showFieldError(input, 'Please enter a valid logo URL');
    } else {
        clearFieldError(e);
    }
}

function showFieldError(input, message) {
    clearFieldError({ target: input });
    input.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    
    const formGroup = input.closest('.form-group');
    if (formGroup) {
        formGroup.appendChild(errorDiv);
    }
}

function clearFieldError(e) {
    const input = e.target;
    input.classList.remove('error');
    
    const formGroup = input.closest('.form-group');
    if (formGroup) {
        const errorDiv = formGroup.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
}

function handleAddBrand(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    // Validate form data
    const validationErrors = validateBrandForm(formData);
    if (validationErrors.length > 0) {
        showModal('Validation Error', validationErrors.join('<br>'), 'error');
        return;
    }
    
    // Add AJAX flag
    formData.append('ajax', '1');
    
    showLoading();
    
    fetch('actions/add_brand_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showModal('Success', 'Brand added successfully!', 'success');
            form.reset();
            // Refresh brand list dynamically
            refreshBrandList();
        } else {
            showModal('Error', data.message || 'Failed to add brand', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showModal('Error', 'An error occurred while adding the brand. Please try again.', 'error');
    });
}

function handleEditBrand(e) {
    const brandId = e.target.getAttribute('data-brand-id');
    const brandCard = document.querySelector(`[data-brand-id="${brandId}"]`);
    const editForm = brandCard.querySelector('.edit-brand-form');
    const brandName = brandCard.querySelector('.brand-name');
    const brandActions = brandCard.querySelector('.brand-actions');
    
    // Hide brand name and actions
    brandName.style.display = 'none';
    brandActions.style.display = 'none';
    
    // Show edit form
    editForm.style.display = 'block';
    
    // Focus on the first input
    const firstInput = editForm.querySelector('input');
    if (firstInput) {
        firstInput.focus();
    }
}

function handleCancelEdit(e) {
    const brandId = e.target.getAttribute('data-brand-id');
    const brandCard = document.querySelector(`[data-brand-id="${brandId}"]`);
    const editForm = brandCard.querySelector('.edit-brand-form');
    const brandName = brandCard.querySelector('.brand-name');
    const brandActions = brandCard.querySelector('.brand-actions');
    
    // Show brand name and actions
    brandName.style.display = 'block';
    brandActions.style.display = 'flex';
    
    // Hide edit form
    editForm.style.display = 'none';
}

function handleUpdateBrand(e) {
    e.preventDefault();
    
    const form = e.target;
    const brandId = form.id.replace('edit-form-', '');
    const formData = new FormData(form);
    
    // Validate form data
    const validationErrors = validateBrandForm(formData);
    if (validationErrors.length > 0) {
        showModal('Validation Error', validationErrors.join('<br>'), 'error');
        return;
    }
    
    formData.append('brand_id', brandId);
    formData.append('ajax', '1');
    
    showLoading();
    
    fetch('actions/update_brand_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showModal('Success', 'Brand updated successfully!', 'success');
            // Refresh brand list dynamically
            refreshBrandList();
        } else {
            showModal('Error', data.message || 'Failed to update brand', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showModal('Error', 'An error occurred while updating the brand. Please try again.', 'error');
    });
}

function handleDeleteBrand(e) {
    const brandId = e.target.getAttribute('data-brand-id');
    const brandName = e.target.getAttribute('data-brand-name');
    
    showConfirmModal(
        'Delete Brand',
        `Are you sure you want to delete the brand "${brandName}"? This action cannot be undone.`,
        'warning',
        () => {
            const formData = new FormData();
            formData.append('brand_id', brandId);
            formData.append('ajax', '1');
            
            showLoading();
            
            fetch('actions/delete_brand_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showModal('Success', 'Brand deleted successfully!', 'success');
                    // Refresh brand list dynamically
                    refreshBrandList();
                } else {
                    showModal('Error', data.message || 'Failed to delete brand', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showModal('Error', 'An error occurred while deleting the brand. Please try again.', 'error');
            });
        }
    );
}

// Dynamic refresh functionality
function loadBrandsData() {
    showLoading();
    
    fetch('actions/fetch_brand_action.php?ajax=1')
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            brandsData = data.data;
            updateBrandsDisplay();
        } else {
            showModal('Error', data.message || 'Failed to load brands', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showModal('Error', 'An error occurred while loading brands', 'error');
    });
}

function refreshBrandList() {
    loadBrandsData();
}

function updateBrandsDisplay() {
    // Group brands by category
    const brandsByCategory = {};
    
    brandsData.forEach(brand => {
        const catId = brand.cat_id;
        if (!brandsByCategory[catId]) {
            brandsByCategory[catId] = {
                category: { cat_id: catId, cat_name: brand.cat_name },
                brands: []
            };
        }
        brandsByCategory[catId].brands.push(brand);
    });
    
    // Update the brands display
    updateBrandsHTML(brandsByCategory);
}

function updateBrandsHTML(brandsByCategory) {
    const brandsContainer = document.querySelector('.card:last-child');
    if (!brandsContainer) return;
    
    let html = '<h3>Your Brands</h3>';
    
    if (Object.keys(brandsByCategory).length === 0) {
        html += '<p>No brands found. Add your first brand above.</p>';
    } else {
        Object.values(brandsByCategory).forEach(categoryData => {
            html += `
                <div class="category-section">
                    <h4 class="category-title">
                        ${escapeHtml(categoryData.category.cat_name)}
                        <span class="brand-count">(${categoryData.brands.length} brands)</span>
                    </h4>
            `;
            
            if (categoryData.brands.length === 0) {
                html += '<p class="no-brands">No brands in this category yet.</p>';
            } else {
                html += '<div class="brands-grid">';
                
                categoryData.brands.forEach(brand => {
                    html += generateBrandCardHTML(brand);
                });
                
                html += '</div>';
            }
            
            html += '</div>';
        });
    }
    
    brandsContainer.innerHTML = html;
    
    // Re-initialize event listeners for new elements
    initializeBrandManagement();
}

function generateBrandCardHTML(brand) {
    return `
        <div class="brand-card" data-brand-id="${brand.brand_id}">
            <div class="brand-header">
                <h5 class="brand-name" id="brand-name-${brand.brand_id}">
                    ${escapeHtml(brand.brand_name)}
                </h5>
                <div class="brand-actions">
                    <button class="btn btn-sm btn-outline edit-brand-btn" data-brand-id="${brand.brand_id}">
                        Edit
                    </button>
                    <button class="btn btn-sm btn-danger delete-brand-btn" data-brand-id="${brand.brand_id}" data-brand-name="${escapeHtml(brand.brand_name)}">
                        Delete
                    </button>
                </div>
            </div>
            
            ${brand.brand_description ? `<p class="brand-description">${escapeHtml(brand.brand_description)}</p>` : ''}
            
            ${brand.brand_logo ? `
                <div class="brand-logo">
                    <img src="${escapeHtml(brand.brand_logo)}" alt="${escapeHtml(brand.brand_name)} logo" onerror="this.style.display='none'">
                </div>
            ` : ''}
            
            <div class="brand-meta">
                <small class="brand-status ${brand.is_active ? 'active' : 'inactive'}">
                    ${brand.is_active ? 'Active' : 'Inactive'}
                </small>
                <small class="brand-date">
                    Created: ${new Date(brand.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                </small>
            </div>
            
            <!-- Edit Form (Hidden by default) -->
            <form class="edit-brand-form" id="edit-form-${brand.brand_id}" style="display: none;">
                <div class="form-group">
                    <label>Brand Name:</label>
                    <input type="text" name="brand_name" value="${escapeHtml(brand.brand_name)}" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="brand_description" class="form-input" rows="2">${escapeHtml(brand.brand_description || '')}</textarea>
                </div>
                
                <div class="form-group">
                    <label>Logo URL:</label>
                    <input type="url" name="brand_logo" value="${escapeHtml(brand.brand_logo || '')}" class="form-input">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    <button type="button" class="btn btn-outline btn-sm cancel-edit-btn" data-brand-id="${brand.brand_id}">Cancel</button>
                </div>
            </form>
        </div>
    `;
}

function updateCategoryCounts() {
    // Update brand counts in category titles
    const categorySections = document.querySelectorAll('.category-section');
    categorySections.forEach(section => {
        const brandCards = section.querySelectorAll('.brand-card');
        const countElement = section.querySelector('.brand-count');
        if (countElement) {
            countElement.textContent = `(${brandCards.length} brands)`;
        }
    });
}

// Modal functions
function showModal(title, message, type = 'info') {
    // Remove existing modals
    const existingModals = document.querySelectorAll('.modal-overlay');
    existingModals.forEach(modal => modal.remove());
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title modal-${type}">${title}</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal()">OK</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Auto-close success modals after 3 seconds
    if (type === 'success') {
        setTimeout(() => {
            closeModal();
        }, 3000);
    }
}

function showConfirmModal(title, message, type = 'warning', onConfirm) {
    // Remove existing modals
    const existingModals = document.querySelectorAll('.modal-overlay');
    existingModals.forEach(modal => modal.remove());
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title modal-${type}">${title}</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" onclick="confirmAction()">Confirm</button>
                <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Store the confirm callback
    window.currentConfirmCallback = onConfirm;
}

function confirmAction() {
    if (window.currentConfirmCallback) {
        window.currentConfirmCallback();
        window.currentConfirmCallback = null;
    }
    closeModal();
}

function closeModal() {
    const modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(modal => modal.remove());
    window.currentConfirmCallback = null;
}

function showMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    
    // Insert at the top of the message container
    const messageContainer = document.getElementById('message-container');
    if (messageContainer) {
        messageContainer.insertBefore(messageDiv, messageContainer.firstChild);
    }
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

function showLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle Enter key in edit forms
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.closest('.edit-brand-form')) {
        e.preventDefault();
        e.target.closest('.edit-brand-form').dispatchEvent(new Event('submit'));
    }
});

// Handle Escape key to cancel edit
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const visibleEditForm = document.querySelector('.edit-brand-form[style*="block"]');
        if (visibleEditForm) {
            const brandId = visibleEditForm.id.replace('edit-form-', '');
            const cancelButton = document.querySelector(`[data-brand-id="${brandId}"].cancel-edit-btn`);
            if (cancelButton) {
                cancelButton.click();
            }
        }
    }
});
