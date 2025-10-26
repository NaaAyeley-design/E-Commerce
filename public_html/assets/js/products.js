/**
 * Product Management JavaScript
 * Handles AJAX form submissions, validation, file uploads, and product management
 */

// Global variables
let productsData = [];
let categoriesData = [];
let brandsData = [];
let currentProductId = null;
let uploadedImagePath = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize product management
    initializeProductManagement();
    
    // Add refresh button
    addRefreshButton();
    
    // Load initial data
    loadProductsData();
});

function initializeProductManagement() {
    // Product form submission
    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', handleProductSubmit);
    }
    
    // Cancel button
    const cancelBtn = document.getElementById('cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', handleCancelEdit);
    }
    
    // Image preview
    const imageInput = document.getElementById('product_image');
    if (imageInput) {
        imageInput.addEventListener('change', handleImagePreview);
    }
    
    // Remove image button
    const removeImageBtn = document.getElementById('remove-image');
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', handleRemoveImage);
    }
    
    // Category change handler
    const categorySelect = document.getElementById('cat_id');
    if (categorySelect) {
        categorySelect.addEventListener('change', handleCategoryChange);
    }
    
    // Edit product buttons
    const editButtons = document.querySelectorAll('.edit-product-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', handleEditProduct);
    });
    
    // Delete product buttons
    const deleteButtons = document.querySelectorAll('.delete-product-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', handleDeleteProduct);
    });
    
    // Real-time validation
    setupRealTimeValidation();
}

// Enhanced validation functions
function validateProductForm(formData) {
    const errors = [];
    
    // Validate required fields
    const requiredFields = ['cat_id', 'brand_id', 'title', 'price', 'desc', 'keyword'];
    requiredFields.forEach(field => {
        const value = formData.get(field);
        if (!value || value.trim() === '') {
            errors.push(`${field.replace('_', ' ')} is required`);
        }
    });
    
    // Validate title
    const title = formData.get('title');
    if (title) {
        if (title.length < 2) {
            errors.push('Product title must be at least 2 characters long');
        } else if (title.length > 200) {
            errors.push('Product title must not exceed 200 characters');
        } else if (!/^[a-zA-Z0-9\s\-_.]+$/.test(title)) {
            errors.push('Product title can only contain letters, numbers, spaces, hyphens, underscores, and periods');
        }
    }
    
    // Validate price
    const price = formData.get('price');
    if (price) {
        if (!isNumeric(price)) {
            errors.push('Price must be a valid number');
        } else if (parseFloat(price) < 0) {
            errors.push('Price must be a positive number');
        } else if (parseFloat(price) > 999999.99) {
            errors.push('Price must not exceed $999,999.99');
        }
    }
    
    // Validate compare price
    const comparePrice = formData.get('compare_price');
    if (comparePrice && comparePrice !== '') {
        if (!isNumeric(comparePrice)) {
            errors.push('Compare price must be a valid number');
        } else if (parseFloat(comparePrice) < 0) {
            errors.push('Compare price must be a positive number');
        } else if (parseFloat(comparePrice) > 999999.99) {
            errors.push('Compare price must not exceed $999,999.99');
        }
    }
    
    // Validate cost price
    const costPrice = formData.get('cost_price');
    if (costPrice && costPrice !== '') {
        if (!isNumeric(costPrice)) {
            errors.push('Cost price must be a valid number');
        } else if (parseFloat(costPrice) < 0) {
            errors.push('Cost price must be a positive number');
        } else if (parseFloat(costPrice) > 999999.99) {
            errors.push('Cost price must not exceed $999,999.99');
        }
    }
    
    // Validate stock quantity
    const stockQuantity = formData.get('stock_quantity');
    if (stockQuantity && stockQuantity !== '') {
        if (!isNumeric(stockQuantity)) {
            errors.push('Stock quantity must be a valid number');
        } else if (parseInt(stockQuantity) < 0) {
            errors.push('Stock quantity must be a positive number');
        } else if (parseInt(stockQuantity) > 999999) {
            errors.push('Stock quantity must not exceed 999,999');
        }
    }
    
    // Validate weight
    const weight = formData.get('weight');
    if (weight && weight !== '') {
        if (!isNumeric(weight)) {
            errors.push('Weight must be a valid number');
        } else if (parseFloat(weight) < 0) {
            errors.push('Weight must be a positive number');
        } else if (parseFloat(weight) > 999.99) {
            errors.push('Weight must not exceed 999.99 lbs');
        }
    }
    
    // Validate description
    const desc = formData.get('desc');
    if (desc) {
        if (desc.length < 10) {
            errors.push('Product description must be at least 10 characters long');
        } else if (desc.length > 2000) {
            errors.push('Product description must not exceed 2000 characters');
        }
    }
    
    // Validate keywords
    const keyword = formData.get('keyword');
    if (keyword) {
        if (keyword.length < 3) {
            errors.push('Keywords must be at least 3 characters long');
        } else if (keyword.length > 500) {
            errors.push('Keywords must not exceed 500 characters');
        }
    }
    
    // Validate SKU
    const sku = formData.get('sku');
    if (sku && sku !== '') {
        if (sku.length > 100) {
            errors.push('SKU must not exceed 100 characters');
        } else if (!/^[a-zA-Z0-9\-_]+$/.test(sku)) {
            errors.push('SKU can only contain letters, numbers, hyphens, and underscores');
        }
    }
    
    // Validate meta fields
    const metaTitle = formData.get('meta_title');
    if (metaTitle && metaTitle.length > 200) {
        errors.push('Meta title must not exceed 200 characters');
    }
    
    const metaDescription = formData.get('meta_description');
    if (metaDescription && metaDescription.length > 500) {
        errors.push('Meta description must not exceed 500 characters');
    }
    
    return errors;
}

function isNumeric(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

function setupRealTimeValidation() {
    const inputs = document.querySelectorAll('#product-form input, #product-form textarea, #product-form select');
    inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearFieldError);
    });
}

function validateField(e) {
    const input = e.target;
    const value = input.value.trim();
    
    // Clear previous errors
    clearFieldError(e);
    
    // Validate based on field type
    switch (input.name) {
        case 'title':
            if (value && (value.length < 2 || value.length > 200)) {
                showFieldError(input, 'Product title must be between 2 and 200 characters');
            }
            break;
        case 'price':
            if (value && (!isNumeric(value) || parseFloat(value) < 0)) {
                showFieldError(input, 'Price must be a valid positive number');
            }
            break;
        case 'desc':
            if (value && (value.length < 10 || value.length > 2000)) {
                showFieldError(input, 'Product description must be between 10 and 2000 characters');
            }
            break;
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

function handleProductSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    // Validate form data
    const validationErrors = validateProductForm(formData);
    if (validationErrors.length > 0) {
        showModal('Validation Error', validationErrors.join('<br>'), 'error');
        return;
    }
    
    // Add AJAX flag
    formData.append('ajax', '1');
    
    const isEdit = currentProductId !== null;
    
    // Handle image upload first if there are new images
    const imageFiles = document.getElementById('product_image').files;
    if (imageFiles && imageFiles.length > 0) {
        uploadProductImages(imageFiles, isEdit)
            .then(imagePaths => {
                if (imagePaths && imagePaths.length > 0) {
                    // Set the first image as primary
                    formData.set('image_path', imagePaths[0]);
                    // Store all image paths for potential future use
                    formData.set('all_image_paths', JSON.stringify(imagePaths));
                    submitProductForm(formData, isEdit);
                } else {
                    showModal('Error', 'Failed to upload images. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Image upload error:', error);
                showModal('Error', 'Failed to upload images. Please try again.', 'error');
            });
    } else {
        // No new images, submit form directly
        submitProductForm(formData, isEdit);
    }
}

function uploadProductImages(imageFiles, isEdit) {
    return new Promise((resolve, reject) => {
        // Validate all files first
        const validFiles = [];
        const errors = [];
        
        for (let i = 0; i < imageFiles.length; i++) {
            const file = imageFiles[i];
            if (!validateImageFile(file)) {
                errors.push(`File ${i + 1} (${file.name}): Invalid file`);
            } else {
                validFiles.push(file);
            }
        }
        
        if (errors.length > 0) {
            showModal('Validation Error', errors.join('<br>'), 'error');
            reject(new Error('Invalid files detected'));
            return;
        }
        
        if (validFiles.length === 0) {
            reject(new Error('No valid files to upload'));
            return;
        }
        
        const formData = new FormData();
        formData.append('product_id', currentProductId || '0');
        formData.append('is_primary', '1');
        formData.append('ajax', '1');
        
        // Append all files
        for (let i = 0; i < validFiles.length; i++) {
            formData.append('images[]', validFiles[i]);
        }
        
        showLoading();
        
        fetch('../../actions/upload_product_image_action.php', {
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
                const imagePaths = data.data.uploaded_files.map(file => file.file_path);
                uploadedImagePath = imagePaths[0]; // Set first image as primary
                
                // Show detailed results
                if (data.data.error_count > 0) {
                    const errorMessages = data.data.upload_results
                        .filter(result => !result.success)
                        .map(result => `${result.original_name}: ${result.error}`)
                        .join('<br>');
                    
                    showModal('Partial Success', 
                        `${data.data.success_count} image(s) uploaded successfully.<br><br>Errors:<br>${errorMessages}`, 
                        'warning');
                } else {
                    showModal('Success', `All ${data.data.success_count} image(s) uploaded successfully!`, 'success');
                }
                
                resolve(imagePaths);
            } else {
                reject(new Error(data.message || 'Failed to upload images'));
            }
        })
        .catch(error => {
            hideLoading();
            reject(error);
        });
    });
}

function uploadProductImage(imageFile, isEdit) {
    return new Promise((resolve, reject) => {
        // Validate file
        if (!validateImageFile(imageFile)) {
            reject(new Error('Invalid image file'));
            return;
        }
        
        const formData = new FormData();
        formData.append('product_id', currentProductId || '0');
        formData.append('image', imageFile);
        formData.append('is_primary', '1');
        formData.append('ajax', '1');
        
        showLoading();
        
        fetch('../../actions/upload_product_image_action.php', {
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
                uploadedImagePath = data.data.file_path;
                resolve(data.data.file_path);
            } else {
                reject(new Error(data.message || 'Failed to upload image'));
            }
        })
        .catch(error => {
            hideLoading();
            reject(error);
        });
    });
}

function validateImageFile(file) {
    // Check file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showModal('Error', 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 'error');
        return false;
    }
    
    // Check file size (max 5MB)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        showModal('Error', 'File size must not exceed 5MB.', 'error');
        return false;
    }
    
    return true;
}

function submitProductForm(formData, isEdit) {
    const actionUrl = isEdit ? '../../actions/update_product_action.php' : '../../actions/add_product_action.php';
    
    showLoading();
    
    fetch(actionUrl, {
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
            showModal('Success', isEdit ? 'Product updated successfully!' : 'Product added successfully!', 'success');
            resetForm();
            // Refresh product list dynamically
            refreshProductList();
        } else {
            showModal('Error', data.message || 'Failed to process product', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showModal('Error', 'An error occurred while processing the product. Please try again.', 'error');
    });
}

function handleEditProduct(e) {
    const productId = e.target.getAttribute('data-product-id');
    currentProductId = productId;
    
    // Fetch complete product data from server
    fetchProductData(productId)
        .then(productData => {
            if (productData) {
                populateEditForm(productData);
                document.getElementById('form-title').textContent = 'Edit Product';
                document.getElementById('submit-btn').textContent = 'Update Product';
                document.getElementById('cancel-btn').style.display = 'inline-block';
                
                // Scroll to form
                document.getElementById('product-form').scrollIntoView({ behavior: 'smooth' });
            } else {
                showModal('Error', 'Failed to load product data', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching product data:', error);
            showModal('Error', 'Failed to load product data', 'error');
        });
}

function fetchProductData(productId) {
    return fetch(`../../actions/get_product_action.php?product_id=${productId}&ajax=1`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                return data.data;
            } else {
                throw new Error(data.message || 'Failed to fetch product data');
            }
        });
}

function populateEditForm(productData) {
    // Populate form fields with product data
    document.getElementById('product_id').value = productData.product_id;
    document.getElementById('cat_id').value = productData.cat_id;
    document.getElementById('brand_id').value = productData.brand_id;
    document.getElementById('title').value = productData.product_name;
    document.getElementById('price').value = productData.price;
    document.getElementById('compare_price').value = productData.compare_price || '';
    document.getElementById('cost_price').value = productData.cost_price || '';
    document.getElementById('sku').value = productData.sku || '';
    document.getElementById('stock_quantity').value = productData.stock_quantity || '';
    document.getElementById('weight').value = productData.weight || '';
    document.getElementById('desc').value = productData.product_description;
    document.getElementById('keyword').value = productData.meta_keywords;
    document.getElementById('dimensions').value = productData.dimensions || '';
    document.getElementById('meta_title').value = productData.meta_title || '';
    document.getElementById('meta_description').value = productData.meta_description || '';
    
    // Update brand dropdown based on selected category
    handleCategoryChange({ target: document.getElementById('cat_id') });
    
    // Clear image preview
    document.getElementById('image-preview').style.display = 'none';
    document.getElementById('product_image').value = '';
}

function handleCancelEdit() {
    resetForm();
}

function resetForm() {
    document.getElementById('product-form').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('form-title').textContent = 'Add New Product';
    document.getElementById('submit-btn').textContent = 'Add Product';
    document.getElementById('cancel-btn').style.display = 'none';
    document.getElementById('image-preview').style.display = 'none';
    
    // Reset global variables
    currentProductId = null;
    uploadedImagePath = null;
    
    // Clear any field errors
    const errorFields = document.querySelectorAll('.form-input.error');
    errorFields.forEach(field => {
        field.classList.remove('error');
        const errorDiv = field.closest('.form-group').querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    });
}

function handleDeleteProduct(e) {
    const productId = e.target.getAttribute('data-product-id');
    const productTitle = e.target.getAttribute('data-product-title');
    
    showConfirmModal(
        'Delete Product',
        `Are you sure you want to delete the product "${productTitle}"? This action cannot be undone.`,
        'warning',
        () => {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('ajax', '1');
            
            showLoading();
            
            fetch('../../actions/delete_product_action.php', {
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
                    showModal('Success', 'Product deleted successfully!', 'success');
                    // Refresh product list dynamically
                    refreshProductList();
                } else {
                    showModal('Error', data.message || 'Failed to delete product', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showModal('Error', 'An error occurred while deleting the product. Please try again.', 'error');
            });
        }
    );
}

function handleImagePreview(e) {
    const files = e.target.files;
    if (files && files.length > 0) {
        // Validate all files
        const validFiles = [];
        const errors = [];
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                errors.push(`${file.name}: Invalid file type`);
                continue;
            }
            
            // Validate file size (max 5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                errors.push(`${file.name}: File size must not exceed 5MB`);
                continue;
            }
            
            validFiles.push(file);
        }
        
        if (errors.length > 0) {
            showModal('File Validation Error', errors.join('<br>'), 'error');
            e.target.value = '';
            return;
        }
        
        if (validFiles.length === 0) {
            e.target.value = '';
            return;
        }
        
        // Show preview for multiple files
        showMultipleImagePreview(validFiles);
    }
}

function showMultipleImagePreview(files) {
    const previewContainer = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (files.length === 1) {
        // Single file preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.alt = files[0].name;
            previewContainer.style.display = 'block';
            
            // Update preview info
            const previewInfo = document.getElementById('preview-info');
            if (previewInfo) {
                previewInfo.textContent = `Selected: ${files[0].name} (${formatFileSize(files[0].size)})`;
            }
        };
        reader.readAsDataURL(files[0]);
    } else {
        // Multiple files preview
        previewImg.src = '';
        previewContainer.style.display = 'block';
        
        // Create or update multiple files info
        let multipleFilesInfo = document.getElementById('multiple-files-info');
        if (!multipleFilesInfo) {
            multipleFilesInfo = document.createElement('div');
            multipleFilesInfo.id = 'multiple-files-info';
            multipleFilesInfo.className = 'multiple-files-preview';
            previewContainer.appendChild(multipleFilesInfo);
        }
        
        // Show first image as preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.alt = `${files.length} images selected`;
        };
        reader.readAsDataURL(files[0]);
        
        // Update multiple files info
        const totalSize = files.reduce((sum, file) => sum + file.size, 0);
        multipleFilesInfo.innerHTML = `
            <div class="files-count">${files.length} images selected</div>
            <div class="files-list">
                ${files.map((file, index) => `
                    <div class="file-item">
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">(${formatFileSize(file.size)})</span>
                    </div>
                `).join('')}
            </div>
            <div class="total-size">Total size: ${formatFileSize(totalSize)}</div>
        `;
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function handleRemoveImage() {
    document.getElementById('product_image').value = '';
    document.getElementById('image-preview').style.display = 'none';
    
    // Remove multiple files info if it exists
    const multipleFilesInfo = document.getElementById('multiple-files-info');
    if (multipleFilesInfo) {
        multipleFilesInfo.remove();
    }
}

function handleCategoryChange(e) {
    const categoryId = e.target.value;
    const brandSelect = document.getElementById('brand_id');
    
    // Clear brand selection
    brandSelect.value = '';
    
    // Show/hide brands based on selected category
    const brandOptions = brandSelect.querySelectorAll('option[data-cat-id]');
    brandOptions.forEach(option => {
        if (categoryId === '' || option.getAttribute('data-cat-id') === categoryId) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

// Dynamic refresh functionality
function loadProductsData() {
    showLoading();
    
    fetch('../../actions/fetch_products_action.php?ajax=1')
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            productsData = data.data;
            updateProductsDisplay();
        } else {
            showModal('Error', data.message || 'Failed to load products', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showModal('Error', 'An error occurred while loading products', 'error');
    });
}

function refreshProductList() {
    // Show a brief loading indicator
    const refreshBtn = document.querySelector('.refresh-btn');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;
    }
    
    // Reload the page to show updated data
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function updateProductsDisplay() {
    // This would update the products display dynamically
    // For now, we'll reload the page to show updated data
    setTimeout(() => {
        location.reload();
    }, 1500);
}

// Add refresh button functionality
function addRefreshButton() {
    const productsCard = document.querySelector('.card:last-child');
    if (productsCard) {
        const header = productsCard.querySelector('h3');
        if (header && !header.querySelector('.refresh-btn')) {
            const refreshBtn = document.createElement('button');
            refreshBtn.className = 'btn btn-sm btn-outline refresh-btn';
            refreshBtn.innerHTML = '<i class="fa fa-refresh"></i> Refresh';
            refreshBtn.onclick = refreshProductList;
            header.appendChild(refreshBtn);
        }
    }
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
