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
let selectedFiles = []; // Array to store selected files for multiple upload

document.addEventListener('DOMContentLoaded', function() {
    // Initialize product management
    initializeProductManagement();
    
    // Add refresh button
    addRefreshButton();
    
    // Debug: Check if category dropdown exists and is populated
    const categorySelect = document.getElementById('cat_id');
    if (categorySelect) {
        console.log('Category dropdown found. Options:', categorySelect.options.length);
        console.log('Category dropdown disabled?', categorySelect.disabled);
        console.log('Category dropdown readonly?', categorySelect.readOnly);
        
        // Check for any overlays blocking the dropdown
        const overlays = document.querySelectorAll('.modal-overlay, #loading-overlay, .overlay');
        if (overlays.length > 0) {
            console.warn('Found overlays that might be blocking:', overlays);
            overlays.forEach(overlay => {
                if (overlay.style.display !== 'none') {
                    overlay.style.display = 'none';
                    console.log('Hidden overlay:', overlay);
                }
            });
        }
        
        // Ensure the dropdown is enabled and not readonly
        categorySelect.disabled = false;
        categorySelect.removeAttribute('readonly');
        categorySelect.style.pointerEvents = 'auto';
        
        // Test if dropdown is actually clickable
        setTimeout(() => {
            const rect = categorySelect.getBoundingClientRect();
            const elementAtPoint = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
            console.log('Element at dropdown center:', elementAtPoint);
            if (elementAtPoint && elementAtPoint !== categorySelect && !categorySelect.contains(elementAtPoint)) {
                console.warn('Something is blocking the dropdown!', elementAtPoint);
            } else if (!elementAtPoint) {
                // Element might not be visible yet, but that's okay
                console.log('Dropdown not yet visible in viewport (this is normal during page load)');
            }
        }, 100);
        
        // Initialize category-based brand filtering on page load if a category is already selected
        if (categorySelect.value) {
            console.log('Initializing with existing category:', categorySelect.value);
            handleCategoryChange({ target: categorySelect });
        } else {
            // Initialize brand dropdown state even when no category is selected
            const brandSelect = document.getElementById('brand_id');
            if (brandSelect) {
                console.log('No category selected on page load - initializing brand dropdown as disabled');
                brandSelect.disabled = true;
                brandSelect.style.pointerEvents = 'none';
                brandSelect.style.cursor = 'not-allowed';
                
                // Log all brand options for debugging
                const allBrandOptions = brandSelect.querySelectorAll('option[data-cat-id]');
                console.log('Brand options available:', allBrandOptions.length);
                allBrandOptions.forEach((option, index) => {
                    if (index < 5) { // Log first 5 only
                        console.log(`Brand option ${index + 1}:`, {
                            value: option.value,
                            text: option.textContent,
                            catId: option.getAttribute('data-cat-id')
                        });
                    }
                });
            }
        }
    } else {
        console.error('Category dropdown (cat_id) not found!');
    }
    
    // Load initial data (only if needed - page already displays products server-side)
    // loadProductsData();
});

function initializeProductManagement() {
    // Product form submission - use specific form ID to avoid conflicts
    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleProductSubmit(e);
        }, true); // Use capture phase to ensure it runs first
    }
    
    // Cancel button
    const cancelBtn = document.getElementById('cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', handleCancelEdit);
    }
    
    // Initialize multiple file upload
    initializeMultipleFileUpload();
    
    // Category change handler - ensure it's not blocked
    const categorySelect = document.getElementById('cat_id');
    if (categorySelect) {
        // Ensure it's not disabled or blocked
        categorySelect.disabled = false;
        categorySelect.removeAttribute('readonly');
        categorySelect.style.pointerEvents = 'auto';
        categorySelect.style.cursor = 'pointer';
        categorySelect.style.position = 'relative';
        categorySelect.style.zIndex = '1';
        
        // Add change event listener
        categorySelect.addEventListener('change', function(e) {
            console.log('Category changed to:', e.target.value);
            handleCategoryChange(e);
        });
        
        // Ensure clicks work - don't prevent default
        categorySelect.addEventListener('click', function(e) {
            // Don't stop propagation - let the native select behavior work
            console.log('Category dropdown clicked');
        });
        
        // Test if dropdown is functional
        console.log('Category dropdown initialized:', {
            element: categorySelect,
            disabled: categorySelect.disabled,
            readonly: categorySelect.readOnly,
            options: categorySelect.options.length,
            parent: categorySelect.parentElement
        });
    } else {
        console.error('Category dropdown element not found!');
    }
    
    // Brand dropdown handler - ensure it's always accessible when enabled
    const brandSelect = document.getElementById('brand_id');
    if (brandSelect) {
        // Initialize brand dropdown as disabled (needs category first)
        brandSelect.disabled = true;
        brandSelect.removeAttribute('readonly');
        brandSelect.style.pointerEvents = 'none';
        brandSelect.style.cursor = 'not-allowed';
        brandSelect.style.position = 'relative';
        brandSelect.style.zIndex = '10';
        
        // Log all brand options for debugging
        console.log('Brand dropdown initialized. Total options:', brandSelect.options.length);
        
        // Log ALL options first to see what we have
        console.log('All brand options:');
        for (let i = 0; i < brandSelect.options.length; i++) {
            const option = brandSelect.options[i];
            console.log(`Option ${i}:`, {
                value: option.value,
                text: option.textContent,
                hasDataCatId: option.hasAttribute('data-cat-id'),
                dataCatId: option.getAttribute('data-cat-id'),
                outerHTML: option.outerHTML.substring(0, 100)
            });
        }
        
        // Now check for options with data-cat-id
        const allBrandOptions = brandSelect.querySelectorAll('option[data-cat-id]');
        console.log('Brand options with data-cat-id attribute:', allBrandOptions.length);
        
        // Also try alternative query
        const allOptionsWithCatId = Array.from(brandSelect.options).filter(opt => opt.hasAttribute('data-cat-id'));
        console.log('Brand options with data-cat-id (alternative method):', allOptionsWithCatId.length);
        
        allBrandOptions.forEach((option, index) => {
            console.log(`Brand ${index + 1}:`, {
                value: option.value,
                text: option.textContent,
                catId: option.getAttribute('data-cat-id')
            });
        });
        
        // Add click handler to ensure it works
        brandSelect.addEventListener('click', function(e) {
            console.log('Brand dropdown clicked. Disabled:', brandSelect.disabled);
            if (brandSelect.disabled) {
                console.warn('Brand dropdown is disabled! Current category:', categorySelect ? categorySelect.value : 'none');
            }
        });
        
        // Add change handler
        brandSelect.addEventListener('change', function(e) {
            console.log('Brand changed to:', e.target.value);
        });
        
        // Add mousedown handler to ensure clicks register
        brandSelect.addEventListener('mousedown', function(e) {
            console.log('Brand dropdown mousedown. Disabled:', brandSelect.disabled);
            if (!brandSelect.disabled) {
                console.log('Brand dropdown is enabled and should work');
            }
        });
        
        console.log('Brand dropdown initialized:', {
            element: brandSelect,
            disabled: brandSelect.disabled,
            readonly: brandSelect.readOnly,
            options: brandSelect.options.length,
            brandOptionsWithDataAttr: allBrandOptions.length
        });
    } else {
        console.error('Brand dropdown element not found!');
    }
    
    // Edit product buttons - use event delegation to handle dynamically added buttons
    document.addEventListener('click', function(e) {
        // Check if the clicked element is an edit product button
        const editBtn = e.target.closest('.edit-product-btn');
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            handleEditProduct(e);
        }
    });
    
    // Also attach directly to existing buttons
    const editButtons = document.querySelectorAll('.edit-product-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleEditProduct(e);
        });
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
    
    // Validate that brand belongs to selected category
    const categoryId = formData.get('cat_id');
    const brandId = formData.get('brand_id');
    if (categoryId && brandId) {
        const brandSelect = document.getElementById('brand_id');
        const selectedBrandOption = brandSelect.querySelector(`option[value="${brandId}"]`);
        if (selectedBrandOption) {
            const brandCatId = selectedBrandOption.getAttribute('data-cat-id');
            if (brandCatId !== categoryId) {
                errors.push('The selected brand does not belong to the selected category. Please select a brand that matches the category.');
            }
        }
    }
    
    // Validate title
    const title = formData.get('title');
    if (title) {
        if (title.length < 2) {
            errors.push('Product title must be at least 2 characters long');
        } else if (title.length > 200) {
            errors.push('Product title must not exceed 200 characters');
        } else if (!/^[\p{L}\p{N}\s'\-_.&,()]+$/u.test(title)) {
            errors.push('Product title contains invalid characters');
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
    
    
    return errors;
}

function isNumeric(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

function setupRealTimeValidation() {
    const inputs = document.querySelectorAll('#product-form input, #product-form textarea, #product-form select');
    inputs.forEach(input => {
        // Don't add input event to select elements (use change instead)
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', clearFieldError);
            input.addEventListener('blur', validateField);
        } else {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        }
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
    e.stopPropagation();
    
    // Ensure we're handling the product form, not any other form
    const form = e.target;
    if (!form || form.id !== 'product-form') {
        console.error('Form submission handler called for wrong form:', form);
        return;
    }
    
    const formData = new FormData(form);
    
    // Debug: Check if file is in FormData
    // Use selectedFiles array for multiple file uploads
    const fileInput = document.getElementById('product_image');
    const imageFiles = selectedFiles.length > 0 ? selectedFiles : (fileInput ? Array.from(fileInput.files) : []);
    console.log('Image files selected:', imageFiles.length);
    if (imageFiles.length > 0) {
        console.log('First file:', imageFiles[0].name, imageFiles[0].size, 'bytes');
    }
    
    const isEdit = currentProductId !== null;
    
    // Handle image uploads
    if (imageFiles && imageFiles.length > 0) {
        // For editing existing products, upload images separately first
        if (isEdit) {
            // Editing existing product - upload images first
            uploadProductImages(imageFiles, isEdit)
                .then(imagePaths => {
                    if (imagePaths && imagePaths.length > 0) {
                        const uploadedImagePath = imagePaths[0];
                        
                        // Update image preview immediately
                        updateImagePreview(uploadedImagePath);
                        
                        // Create new FormData with the uploaded image path
                        // Get all form fields explicitly to ensure nothing is missing
                        const updateFormData = new FormData();
                        
                        // Get all form inputs
                        const formInputs = form.querySelectorAll('input, select, textarea');
                        formInputs.forEach(input => {
                            // Skip file inputs (we handle image separately)
                            if (input.type === 'file') {
                                return;
                            }
                            
                            // Get the value
                            let value = input.value;
                            
                            // Handle checkboxes and radios
                            if (input.type === 'checkbox' || input.type === 'radio') {
                                if (!input.checked) {
                                    return; // Skip unchecked checkboxes/radios
                                }
                            }
                            
                            // Add to FormData
                            if (input.name) {
                                updateFormData.append(input.name, value);
                            }
                        });
                        
                        // Ensure product_id is included
                        const productIdInput = form.querySelector('input[name="product_id"]');
                        if (productIdInput && productIdInput.value) {
                            updateFormData.set('product_id', productIdInput.value);
                        } else if (currentProductId) {
                            updateFormData.set('product_id', currentProductId);
                        }
                        
                        // Set the image path
                        updateFormData.set('image_path', uploadedImagePath);
                        updateFormData.append('ajax', '1');
                        
                        // Log FormData contents for debugging
                        console.log('FormData entries for update:', Array.from(updateFormData.entries()).map(([key, val]) => [key, val instanceof File ? val.name : val]));
                        
                        // Submit form update
                        submitProductForm(updateFormData, isEdit, uploadedImagePath);
                    } else {
                        showModal('Error', 'Failed to upload images. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Image upload error:', error);
                    showModal('Error', 'Failed to upload images: ' + (error.message || 'Unknown error'), 'error');
                });
            return; // Exit early, will continue after image upload
        }
        // For new products, image will be included in the form submission
    }
    
    // Validate form data
    const validationErrors = validateProductForm(formData);
    if (validationErrors.length > 0) {
        showModal('Validation Error', validationErrors.join('<br>'), 'error');
        return;
    }
    
    // Add AJAX flag
    formData.append('ajax', '1');
    
    // For new products, add all selected files to FormData
    if (!isEdit && imageFiles && imageFiles.length > 0) {
        // Remove any existing product_image entry
        formData.delete('product_image');
        
        // Add all files as images[] array
        imageFiles.forEach((file, index) => {
            formData.append('images[]', file);
        });
        
        console.log(`Added ${imageFiles.length} files to FormData for new product`);
    }
    
    // New product or no new image - submit form directly (image will be handled server-side)
    console.log('Submitting form with FormData. Has product_image:', formData.has('product_image'), 'Has images[]:', Array.from(formData.entries()).some(([key]) => key === 'images[]'));
    submitProductForm(formData, isEdit);
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
        
        // Use absolute path with BASE_URL (actions folder is at root, not in public_html)
        let uploadActionUrl;
        if (typeof BASE_URL !== 'undefined' && BASE_URL) {
            // Remove /public_html from BASE_URL to get root, then add /actions/
            uploadActionUrl = BASE_URL.replace('/public_html', '') + '/actions/upload_product_image_action.php';
        } else {
            uploadActionUrl = '../../actions/upload_product_image_action.php';
        }
        
        console.log('Uploading images. FormData entries:', Array.from(formData.entries()).map(([key, val]) => [key, val instanceof File ? val.name : val]));
        
        fetch(uploadActionUrl, {
            method: 'POST',
            body: formData,
            // Don't set Content-Type header - let browser set it with boundary for multipart/form-data
            credentials: 'same-origin'
        })
        .then(response => {
            // Try to parse JSON even if status is not OK to get error message
            return response.text().then(text => {
                console.log('Upload response status:', response.status);
                console.log('Upload response text:', text.substring(0, 500)); // First 500 chars
                
                try {
                    const data = JSON.parse(text);
                    // If status is not OK, include error info
                    if (!response.ok) {
                        data.httpStatus = response.status;
                    }
                    return data;
                } catch (e) {
                    // If not JSON, return error object with the text
                    console.error('Failed to parse JSON:', e, 'Response text:', text);
                    if (response.status === 500) {
                        throw new Error('Server error (500): ' + (text.substring(0, 200) || response.statusText));
                    }
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 200) || response.statusText}`);
                }
            });
        })
        .then(data => {
            hideLoading();
            
            if (data.success) {
                const imagePaths = data.data.uploaded_files.map(file => file.file_path);
                uploadedImagePath = imagePaths[0]; // Set first image as primary
                
                // Update image preview immediately after upload
                if (imagePaths.length > 0) {
                    updateImagePreview(imagePaths[0]);
                }
                
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
                    // Don't show modal for single image upload during edit - it will show after update
                    if (!isEdit || imageFiles.length > 1) {
                        showModal('Success', `All ${data.data.success_count} image(s) uploaded successfully!`, 'success');
                    }
                }
                
                resolve(imagePaths);
            } else {
                const errorMsg = data.message || 'Failed to upload images';
                if (data.debug) {
                    console.error('Upload error details:', data.debug);
                }
                showModal('Upload Error', errorMsg, 'error');
                reject(new Error(errorMsg));
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Upload error:', error);
            const errorMsg = error.message || 'Failed to upload images. Please check your connection and try again.';
            showModal('Upload Error', errorMsg, 'error');
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
        
        // Use absolute path with BASE_URL (actions folder is at root, not in public_html)
        let uploadActionUrl;
        if (typeof BASE_URL !== 'undefined' && BASE_URL) {
            // Remove /public_html from BASE_URL to get root, then add /actions/
            uploadActionUrl = BASE_URL.replace('/public_html', '') + '/actions/upload_product_image_action.php';
        } else {
            uploadActionUrl = '../../actions/upload_product_image_action.php';
        }
        
        console.log('Uploading single image. FormData entries:', Array.from(formData.entries()).map(([key, val]) => [key, val instanceof File ? val.name : val]));
        
        fetch(uploadActionUrl, {
            method: 'POST',
            body: formData,
            // Don't set Content-Type header - let browser set it with boundary for multipart/form-data
            credentials: 'same-origin'
        })
        .then(response => {
            // Try to parse JSON even if status is not OK to get error message
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    // If not JSON, return error object
                    throw new Error(`HTTP ${response.status}: ${text || response.statusText}`);
                }
            });
        })
        .then(data => {
            hideLoading();
            
            if (data.success) {
                uploadedImagePath = data.data.file_path || (data.data.uploaded_files && data.data.uploaded_files[0] && data.data.uploaded_files[0].file_path);
                resolve(uploadedImagePath);
            } else {
                const errorMsg = data.message || 'Failed to upload image';
                showModal('Upload Error', errorMsg, 'error');
                reject(new Error(errorMsg));
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Upload error:', error);
            const errorMsg = error.message || 'Failed to upload image. Please check your connection and try again.';
            showModal('Upload Error', errorMsg, 'error');
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

function submitProductForm(formData, isEdit, uploadedImagePath = null) {
    // Use absolute path with BASE_URL (actions folder is at root, not in public_html)
    let actionUrl;
    if (typeof BASE_URL !== 'undefined' && BASE_URL) {
        // Remove /public_html from BASE_URL to get root, then add /actions/
        const rootUrl = BASE_URL.replace('/public_html', '');
        actionUrl = isEdit ? rootUrl + '/actions/update_product_action.php' : rootUrl + '/actions/add_product_action.php';
    } else {
        actionUrl = isEdit ? '../../actions/update_product_action.php' : '../../actions/add_product_action.php';
    }
    
    console.log('Submitting to:', actionUrl);
    console.log('FormData entries:', Array.from(formData.entries()).map(([key, val]) => [key, val instanceof File ? val.name : val]));
    showLoading();
    
    fetch(actionUrl, {
        method: 'POST',
        body: formData,
        // Don't set Content-Type header - let browser set it with boundary for multipart/form-data
        credentials: 'same-origin'
    })
    .then(response => {
        // Try to parse JSON even if status is not OK
        return response.text().then(text => {
            console.log('Response text:', text.substring(0, 500));
            try {
                return JSON.parse(text);
            } catch (e) {
                // If response is not JSON, check if it's a success message
                if (text.includes('successfully') || text.includes('success')) {
                    return { success: true, message: text };
                }
                throw new Error(`HTTP ${response.status}: ${text || response.statusText}`);
            }
        });
    })
    .then(data => {
        hideLoading();
        
        console.log('Product submission response:', data);
        
        if (data.success) {
            // If image was uploaded, ensure preview is updated
            if (uploadedImagePath) {
                updateImagePreview(uploadedImagePath);
            }
            
            showModal('Success', isEdit ? 'Product updated successfully!' : 'Product added successfully!', 'success');
            
            // Only reset form if it's a new product
            if (!isEdit) {
                resetForm();
            }
            
            // Refresh product list dynamically
            refreshProductList();
        } else {
            // Even if update failed, if image was uploaded, keep the preview
            if (uploadedImagePath) {
                updateImagePreview(uploadedImagePath);
                showModal('Warning', 'Image uploaded successfully, but product update had issues: ' + (data.message || 'Unknown error') + '. Please refresh the page to see changes.', 'warning');
            } else {
                showModal('Error', data.message || 'Failed to process product', 'error');
            }
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        
        // Even if there's an error, if image was uploaded, keep the preview
        if (uploadedImagePath) {
            updateImagePreview(uploadedImagePath);
            showModal('Warning', 'Image uploaded successfully, but product update failed. Please refresh the page to see changes.', 'warning');
        } else {
            showModal('Error', 'An error occurred while processing the product. Please try again.', 'error');
        }
    });
}

/**
 * Update image preview with uploaded image path
 */
function updateImagePreview(imagePath) {
    const previewDiv = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (previewDiv && previewImg && imagePath) {
        // Construct full image URL
        let imageUrl = imagePath;
        if (!imagePath.startsWith('http://') && !imagePath.startsWith('https://')) {
            // If path doesn't start with http, construct from BASE_URL
            if (typeof BASE_URL !== 'undefined' && BASE_URL) {
                const rootUrl = BASE_URL.replace('/public_html', '');
                imageUrl = rootUrl + '/' + imagePath.replace(/^\//, '');
            } else {
                imageUrl = '/' + imagePath.replace(/^\//, '');
            }
        }
        
        previewImg.src = imageUrl;
        previewImg.alt = 'Uploaded product image';
        previewDiv.style.display = 'block';
        previewDiv.classList.add('active');
        
        console.log('Image preview updated with:', imageUrl);
    }
}

function handleEditProduct(e) {
    // Prevent any default behavior and stop propagation
    if (e && e.preventDefault) {
        e.preventDefault();
    }
    if (e && e.stopPropagation) {
        e.stopPropagation();
    }
    
    // Get the button element (could be e.target or e.currentTarget)
    const button = e.target.closest('.edit-product-btn') || e.target;
    const productId = button.getAttribute('data-product-id');
    
    if (!productId) {
        console.error('No product ID found on edit button');
        showModal('Error', 'Product ID not found. Please try again.', 'error');
        return;
    }
    
    console.log('Edit product clicked for product ID:', productId);
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
            showModal('Error', 'Failed to load product data: ' + (error.message || 'Unknown error'), 'error');
        });
}

function fetchProductData(productId) {
    // Use absolute path with BASE_URL (actions folder is at root, not in public_html)
    let fetchUrl;
    if (typeof BASE_URL !== 'undefined' && BASE_URL) {
        // Remove /public_html from BASE_URL to get root, then add /actions/
        const rootUrl = BASE_URL.replace('/public_html', '');
        fetchUrl = `${rootUrl}/actions/get_product_action.php?product_id=${productId}&ajax=1`;
    } else {
        fetchUrl = `../../actions/get_product_action.php?product_id=${productId}&ajax=1`;
    }
    
    return fetch(fetchUrl)
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
    document.getElementById('product_id').value = productData.product_id || '';
    document.getElementById('cat_id').value = productData.product_cat || '';
    document.getElementById('brand_id').value = productData.product_brand || '';
    document.getElementById('title').value = productData.product_title || '';
    document.getElementById('price').value = productData.product_price || '';
    document.getElementById('desc').value = productData.product_desc || '';
    document.getElementById('keyword').value = productData.product_keywords || '';
    
    // Update brand dropdown based on selected category
    if (productData.product_cat) {
        handleCategoryChange({ target: document.getElementById('cat_id') });
    }
    
    // Display image if available
    if (productData.product_image) {
        const previewDiv = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        if (previewDiv && previewImg) {
            previewImg.src = productData.product_image;
            previewDiv.style.display = 'block';
        }
    } else {
        document.getElementById('image-preview').style.display = 'none';
    }
    
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
    
    // Clear multiple file upload
    clearAllFiles();
    
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
            
            // Use absolute path with BASE_URL (actions folder is at root, not in public_html)
            let deleteActionUrl;
            if (typeof BASE_URL !== 'undefined' && BASE_URL) {
                // Remove /public_html from BASE_URL to get root, then add /actions/
                const rootUrl = BASE_URL.replace('/public_html', '');
                deleteActionUrl = rootUrl + '/actions/delete_product_action.php';
            } else {
                deleteActionUrl = '../../actions/delete_product_action.php';
            }
            
            fetch(deleteActionUrl, {
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

/**
 * Initialize Multiple File Upload System
 */
function initializeMultipleFileUpload() {
    const fileInput = document.getElementById('product_image');
    const fileUploadArea = document.getElementById('file-upload-area');
    const fileCountDisplay = document.getElementById('file-count-display');
    const fileCountText = document.getElementById('file-count-text');
    const clearAllBtn = document.getElementById('clear-all-files');
    
    if (!fileInput || !fileUploadArea) return;
    
    // Click on upload area to trigger file input
    fileUploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    // Drag and drop functionality
    fileUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.classList.add('drag-over');
    });
    
    fileUploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.classList.remove('drag-over');
    });
    
    fileUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadArea.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        handleFileSelection(files);
    });
    
    // File input change event
    fileInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        handleFileSelection(files);
    });
    
    // Clear all files button
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            clearAllFiles();
        });
    }
}

/**
 * Handle file selection
 */
function handleFileSelection(files) {
    if (!files || files.length === 0) return;
    
    // Validate files
    const validationResult = validateFiles(files);
    
    if (validationResult.errors.length > 0) {
        showModal('File Validation Error', validationResult.errors.join('<br>'), 'error');
        return;
    }
    
    if (validationResult.validFiles.length === 0) {
        return;
    }
    
    // Add new files to selected files array (avoid duplicates)
    validationResult.validFiles.forEach(file => {
        // Check if file already exists (by name and size)
        const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
        if (!exists) {
            selectedFiles.push(file);
        }
    });
    
    // Limit to 10 files
    if (selectedFiles.length > 10) {
        selectedFiles = selectedFiles.slice(0, 10);
        showModal('File Limit', 'Maximum 10 files allowed. Only the first 10 files will be kept.', 'warning');
    }
    
    // Update UI
    displayFilePreviews();
    updateFileCount();
}

/**
 * Validate files
 */
function validateFiles(files) {
    const validFiles = [];
    const errors = [];
    const maxFileSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    const maxTotalSize = 50 * 1024 * 1024; // 50MB total
    
    let totalSize = selectedFiles.reduce((sum, f) => sum + f.size, 0);
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Check file type
        if (!allowedTypes.includes(file.type)) {
            errors.push(`${file.name}: Invalid file type. Only JPG, PNG, GIF, WEBP, and SVG are allowed.`);
            continue;
        }
        
        // Check file size
        if (file.size > maxFileSize) {
            errors.push(`${file.name}: File size (${formatFileSize(file.size)}) exceeds 5MB limit.`);
            continue;
        }
        
        // Check total size
        if (totalSize + file.size > maxTotalSize) {
            errors.push(`${file.name}: Adding this file would exceed 50MB total size limit.`);
            continue;
        }
        
        totalSize += file.size;
        validFiles.push(file);
    }
    
    return { validFiles, errors };
}

/**
 * Display file previews in gallery
 */
function displayFilePreviews() {
    const gallery = document.getElementById('file-preview-gallery');
    const galleryGrid = document.getElementById('preview-gallery-grid');
    
    if (!gallery || !galleryGrid) return;
    
    if (selectedFiles.length === 0) {
        gallery.style.display = 'none';
        return;
    }
    
    gallery.style.display = 'block';
    galleryGrid.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const previewItem = createFilePreviewItem(file, index);
        galleryGrid.appendChild(previewItem);
    });
}

/**
 * Create file preview item
 */
function createFilePreviewItem(file, index) {
    const item = document.createElement('div');
    item.className = 'file-preview-item';
    item.dataset.index = index;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        item.innerHTML = `
            <div class="file-preview-image">
                <img src="${e.target.result}" alt="${escapeHtml(file.name)}">
                <button type="button" class="file-remove-btn" data-index="${index}" aria-label="Remove file">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="file-preview-info">
                <div class="file-preview-name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
                <div class="file-preview-size">${formatFileSize(file.size)}</div>
            </div>
        `;
        
        // Add remove button event listener
        const removeBtn = item.querySelector('.file-remove-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeFile(index);
            });
        }
    };
    reader.readAsDataURL(file);
    
    return item;
}

/**
 * Remove file from selection
 */
function removeFile(index) {
    if (index >= 0 && index < selectedFiles.length) {
        selectedFiles.splice(index, 1);
        displayFilePreviews();
        updateFileCount();
        
        // Update file input (create new FileList)
        const fileInput = document.getElementById('product_image');
        if (fileInput) {
            // Create a new DataTransfer to update the file input
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;
        }
    }
}

/**
 * Clear all files
 */
function clearAllFiles() {
    selectedFiles = [];
    displayFilePreviews();
    updateFileCount();
    
    const fileInput = document.getElementById('product_image');
    if (fileInput) {
        fileInput.value = '';
    }
}

/**
 * Update file count display
 */
function updateFileCount() {
    const fileCountDisplay = document.getElementById('file-count-display');
    const fileCountText = document.getElementById('file-count-text');
    
    if (!fileCountDisplay || !fileCountText) return;
    
    if (selectedFiles.length > 0) {
        fileCountText.textContent = `${selectedFiles.length} file${selectedFiles.length !== 1 ? 's' : ''} selected`;
        fileCountDisplay.style.display = 'block';
    } else {
        fileCountDisplay.style.display = 'none';
    }
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Legacy function for backward compatibility
 */
function handleImagePreview(e) {
    const files = Array.from(e.target.files);
    handleFileSelection(files);
}

/**
 * Legacy function for backward compatibility
 */
function handleRemoveImage() {
    clearAllFiles();
}

function handleCategoryChange(e) {
    const categoryId = e.target ? e.target.value : e;
    const brandSelect = document.getElementById('brand_id');
    const brandHelp = document.getElementById('brand-help');
    
    if (!brandSelect) {
        console.error('Brand dropdown not found!');
        return;
    }
    
    console.log('handleCategoryChange called with categoryId:', categoryId);
    
    // Always ensure brand dropdown is not blocked - enable it first
    brandSelect.disabled = false;
    brandSelect.removeAttribute('readonly');
    brandSelect.style.pointerEvents = 'auto';
    brandSelect.style.cursor = 'pointer';
    brandSelect.style.position = 'relative';
    brandSelect.style.zIndex = '10';
    
    // Clear brand selection when category changes
    brandSelect.value = '';
    
    // Get all brand options (excluding the placeholder option)
    const brandOptions = brandSelect.querySelectorAll('option[data-cat-id]');
    const placeholderOption = brandSelect.querySelector('option:not([data-cat-id])');
    
    console.log('Total brand options found:', brandOptions.length);
    
    // If no category selected, disable brand dropdown
    if (!categoryId || categoryId === '') {
        console.log('No category selected - disabling brand dropdown');
        brandOptions.forEach(option => {
            option.style.display = 'none';
            option.disabled = true;
            option.hidden = true;
        });
        if (placeholderOption) {
            placeholderOption.style.display = 'block';
            placeholderOption.textContent = 'Select a category first';
            placeholderOption.disabled = false;
        }
        brandSelect.disabled = true;
        if (brandHelp) {
            brandHelp.textContent = 'Select a category first to filter available brands';
            brandHelp.style.color = '';
        }
        return;
    }
    
    // Category is selected - enable the brand dropdown
    console.log('Category selected:', categoryId, '- enabling brand dropdown');
    brandSelect.disabled = false;
    
    // Show only brands that belong to the selected category
    // Convert both to strings for comparison to avoid type mismatch issues
    const selectedCatId = String(categoryId);
    let hasBrandsInCategory = false;
    let brandCount = 0;
    
    brandOptions.forEach(option => {
        const brandCatId = String(option.getAttribute('data-cat-id'));
        console.log('Comparing brand category:', brandCatId, 'with selected:', selectedCatId);
        
        if (brandCatId === selectedCatId) {
            option.style.display = 'block';
            option.disabled = false;
            option.hidden = false;
            option.removeAttribute('hidden');
            hasBrandsInCategory = true;
            brandCount++;
            console.log('Brand enabled:', option.textContent);
        } else {
            option.style.display = 'none';
            option.disabled = true;
            option.hidden = true;
            option.setAttribute('hidden', 'hidden');
        }
    });
    
    console.log(`Filtered brands: ${brandCount} available for category ${selectedCatId}`);
    
    // Update placeholder text
    if (placeholderOption) {
        placeholderOption.style.display = 'block';
        placeholderOption.disabled = false;
        if (hasBrandsInCategory) {
            placeholderOption.textContent = `Select a brand (${brandCount} available)`;
        } else {
            placeholderOption.textContent = 'No brands available for this category';
        }
    }
    
    // Update help text
    if (brandHelp) {
        if (hasBrandsInCategory) {
            brandHelp.textContent = `${brandCount} brand(s) available for this category`;
            brandHelp.style.color = '';
        } else {
            brandHelp.textContent = 'No brands found for this category. Please add brands to this category first.';
            brandHelp.style.color = '#dc3545';
        }
    }
    
    // Final check - ensure dropdown is enabled
    if (hasBrandsInCategory) {
        brandSelect.disabled = false;
        brandSelect.style.pointerEvents = 'auto';
        console.log('Brand dropdown enabled with', brandCount, 'brands available');
    } else {
        console.warn('No brands available for the selected category. Please add brands to this category first.');
        brandSelect.disabled = true;
    }
}

// Dynamic refresh functionality
function loadProductsData() {
    // Disabled - fetch_products_action.php doesn't exist
    // The page already displays products server-side, so no need to fetch via AJAX
    console.log('loadProductsData called but disabled - products are loaded server-side');
    return;
    
    // NOTE: This entire function is disabled because:
    // 1. fetch_products_action.php doesn't exist (causes 404 errors)
    // 2. Products are already loaded server-side on page load
    // 3. No need for AJAX reload unless we implement product management features later
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

// Modal functions - Use toast notifications instead
function showModal(title, message, type = 'info') {
    if (typeof Toast !== 'undefined') {
        const toastType = type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info';
        Toast[toastType](message || title);
    } else {
        console.log(`Modal [${type.toUpperCase()}]: ${title} - ${message}`);
    }
}

function showConfirmModal(title, message, type = 'warning', onConfirm) {
    console.log(`Confirm Modal [${type.toUpperCase()}]: ${title} - ${message}`);
    // Modal functionality disabled to prevent obstruction
    // Execute the confirm action directly
    if (onConfirm && typeof onConfirm === 'function') {
        onConfirm();
    }
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
