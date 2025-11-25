# Image Upload Directory Guide

## Where Your Images Are Being Uploaded

### Directory Structure

Your images are uploaded to the following locations:

```
ecommerce-authent/
└── uploads/
    ├── temp/                          # For new products (product_id = 0)
    └── u{user_id}/                    # For existing products
        └── p{product_id}/            # Product-specific folder
            └── image_*.jpg/png/etc   # Actual image files
```

### Full Paths

**Base Directory:**
- **Absolute Path:** `C:\xampp\htdocs\ecommerce-authent\uploads\`
- **Relative Path:** `uploads/` (from project root)

**For New Products (temporary):**
- **Path:** `uploads/temp/`
- **Full Path:** `C:\xampp\htdocs\ecommerce-authent\uploads\temp\`
- **When Used:** When creating a new product (product_id = 0)

**For Existing Products:**
- **Path:** `uploads/u{user_id}/p{product_id}/`
- **Example:** `uploads/u5/p11/` (User ID 5, Product ID 11)
- **Full Path:** `C:\xampp\htdocs\ecommerce-authent\uploads\u5\p11\`
- **When Used:** When uploading images to an existing product

### Current Upload Status

Based on your directory structure, I can see:
- ✅ `uploads/temp/` exists (for new products)
- ✅ `uploads/u5/p1/` exists (User 5, Product 1 - has 1 image)
- ✅ `uploads/u5/p11/` exists (User 5, Product 11 - has 7 images)

**Your images ARE being uploaded successfully!**

## How to Check Upload Directory

### Method 1: Diagnostic Tool

1. Navigate to: `http://your-domain/actions/check_upload_directory.php`
2. This will show:
   - Directory paths
   - Permissions
   - Write capabilities
   - Any issues

### Method 2: Manual Check

1. Open File Explorer
2. Navigate to: `C:\xampp\htdocs\ecommerce-authent\uploads\`
3. Check if folders exist:
   - `temp/` folder
   - `u{your_user_id}/` folder
   - `u{your_user_id}/p{product_id}/` folders

### Method 3: Check via PHP

Run this in your browser console or create a test file:

```php
<?php
$uploads_base = __DIR__ . '/uploads';
echo "Base path: " . $uploads_base . "<br>";
echo "Exists: " . (is_dir($uploads_base) ? 'YES' : 'NO') . "<br>";
echo "Writable: " . (is_writable($uploads_base) ? 'YES' : 'NO') . "<br>";
?>
```

## Common Issues and Solutions

### Issue 1: "Failed to create uploads directory"

**Solution:**
1. Manually create the `uploads` folder in your project root
2. Right-click → Properties → Security
3. Add "Everyone" or your PHP user with "Write" permissions
4. Or set permissions to 755/777 (Linux/Mac)

### Issue 2: "Directory is not writable"

**Solution (Windows):**
1. Right-click `uploads` folder
2. Properties → Security tab
3. Click "Edit"
4. Add your PHP user (or "Everyone")
5. Check "Write" permission
6. Click OK

**Solution (Linux/Mac):**
```bash
chmod 755 uploads
# or
chmod 777 uploads
```

### Issue 3: Images upload but don't show in database

**This is the issue you're experiencing!**

**Cause:** The file uploads successfully, but the database insert fails.

**Solution:** 
- We've already fixed this in `product_class.php` with better error handling
- Check PHP error logs for specific database errors
- Verify the `product_images` table exists

### Issue 4: "Failed to save uploaded file"

**Possible causes:**
- Directory doesn't exist
- Directory not writable
- Disk space full
- File permissions issue

**Solution:**
1. Run the diagnostic tool: `/actions/check_upload_directory.php`
2. Check directory permissions
3. Verify disk space
4. Check PHP error logs

## File Naming Convention

Images are saved with this format:
- **New products:** `product_{timestamp}_{random}.{ext}`
- **Existing products:** `image_{index}_{timestamp}_{random}.{ext}`

Example:
- `image_1_1762962622_3bf46e06.jpeg`
- `product_11_1762428669.jpeg`

## Database Storage

The **relative path** is stored in the database:
- `uploads/temp/filename.jpg` (for new products)
- `uploads/u5/p11/filename.jpg` (for existing products)

When displaying images, use:
```php
$image_url = BASE_URL . '/' . $image_path;
// or
$image_url = url($image_path);
```

## Testing Upload

1. **Check Directory:**
   - Visit: `http://your-domain/actions/check_upload_directory.php`
   - Verify all checks pass

2. **Try Uploading:**
   - Go to product management
   - Upload an image
   - Check browser console for errors
   - Check PHP error logs

3. **Verify File:**
   - Check `uploads/u{user_id}/p{product_id}/` folder
   - File should appear immediately after upload

4. **Check Database:**
   - Query `product_images` table
   - Verify `image_url` column has the path

## Quick Fix Checklist

- [ ] `uploads/` directory exists
- [ ] `uploads/temp/` directory exists
- [ ] Directory is writable (check permissions)
- [ ] PHP has write access
- [ ] `product_images` table exists in database
- [ ] Database connection is working
- [ ] Check PHP error logs for specific errors

## Next Steps

1. **Run Diagnostic:**
   ```
   http://your-domain/actions/check_upload_directory.php
   ```

2. **Check Error Logs:**
   - PHP error log: `C:\xampp\php\logs\php_error_log`
   - Look for messages starting with "Upload action:"

3. **Verify Database:**
   - Check if `product_images` table exists
   - Verify the insert query is working

4. **Test Upload:**
   - Try uploading an image
   - Check if file appears in `uploads/` folder
   - Check if record appears in database

## Need More Help?

If images still aren't uploading:

1. Share the output from: `/actions/check_upload_directory.php`
2. Check browser console for JavaScript errors
3. Check PHP error logs
4. Verify the specific error message you're seeing

