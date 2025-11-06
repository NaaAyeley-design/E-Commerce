# Requirements Status Report

## Part 1 – Brand Management (Add, View, Edit, Delete)

### ✅ Completed Requirements:

1. **✅ Make sure the admin can log in.**
   - Status: Implemented - Login functionality exists in `actions/process_login.php`
   - Location: `public_html/view/user/login.php`

2. **✅ Check if the person using the page is an admin.**
   - Status: Implemented - `is_admin()` check in `public_html/view/admin/brands.php` (line 22-25)
   - Location: `public_html/view/admin/brands.php`

3. **⚠️ Show all the brands created by the logged-in user.**
   - Status: **PARTIALLY IMPLEMENTED** - Currently shows ALL brands in category for admin users
   - Issue: In `class/brand_class.php` (line 168-175), admin users see all brands in category, not just their own
   - Current behavior: Admin sees all brands; regular users see only their brands
   - Required behavior: Admin should see only brands they created
   - Location: `class/brand_class.php::get_brands_by_category()`

4. **✅ Group brands under their categories.**
   - Status: Implemented - Brands are grouped by category in `$brands_by_category` array
   - Location: `public_html/view/admin/brands.php` (line 44-66, 154-159)

5. **✅ Create a form to add a new brand.**
   - Status: Implemented - Form exists with brand name, category, description, and logo fields
   - Location: `public_html/view/admin/brands.php` (line 96-129)

6. **✅ Let the admin choose the category for the brand.**
   - Status: Implemented - Category dropdown in add brand form
   - Location: `public_html/view/admin/brands.php` (line 105-115)

7. **✅ Each brand name and category pair must be unique.**
   - Status: Implemented - `brand_name_exists()` checks uniqueness per user, category, and brand name
   - Location: `class/brand_class.php::brand_name_exists()` (line 329-340)

8. **✅ Allow editing only the brand name (not the ID).**
   - Status: Implemented - Edit form only allows editing brand name, description, and logo (not ID)
   - Location: `public_html/view/admin/brands.php` (line 201-221)

9. **✅ Allow deleting a brand.**
   - Status: Implemented - Delete button and action exist
   - Location: `public_html/view/admin/brands.php` (line 175), `actions/delete_brand_action.php`

10. **✅ Show a clear success or failure message after each action.**
    - Status: Implemented - Toast notifications used for all actions
    - Location: `public_html/assets/js/brands.js` (uses Toast system)

11. **✅ Add pop-ups or small messages when actions are successful or fail.**
    - Status: Implemented - Toast notification system implemented
    - Location: `public_html/assets/js/brands.js` (line 580-612), `public_html/assets/css/toast.css`

12. **✅ Update the main menu:**
    - Status: Implemented - Menu structure matches requirements
    - Not logged in → "Register" and "Login" ✅
    - Logged in as admin → "Logout," "Category," and "Brand" ✅
    - Logged in as normal user → "Logout" ✅
    - Location: `public_html/view/templates/header.php` (line 112-196)

---

## Part 2 – Product Management (Add and Edit)

### ✅ Completed Requirements:

1. **✅ Make sure the admin is logged in.**
   - Status: Implemented - `is_admin()` check in `public_html/view/admin/products.php` (line 23-26)
   - Location: `public_html/view/admin/products.php`

2. **✅ Show all products arranged by category and brand.**
   - Status: Implemented - Products grouped by category, then by brand within each category
   - Location: `public_html/view/admin/products.php` (line 78-108, 256-322)

3. **✅ Add a form to create a new product.**
   - Status: Implemented - Form exists with all required fields
   - Location: `public_html/view/admin/products.php` (line 152-246)

4. **✅ Allow the admin to edit an existing product.**
   - Status: Implemented - Edit button and functionality exist
   - Location: `public_html/view/admin/products.php` (line 284), `actions/update_product_action.php`, `public_html/assets/js/products.js` (line 636-650)

5. **✅ The form should include:**
   - **✅ Category (choose from a list)** - Implemented (line 159-173)
   - **✅ Brand (choose from a list)** - Implemented (line 176-205)
   - **✅ Product name** - Implemented (line 208-211)
   - **✅ Price** - Implemented (line 213-216)
   - **✅ Description** - Implemented (line 218-221)
   - **✅ Image upload** - Implemented (line 229-240)
   - **✅ Keyword** - Implemented (line 223-227)
   - Location: `public_html/view/admin/products.php`

6. **✅ The product ID should be created automatically.**
   - Status: Implemented - `product_id INT(11) NOT NULL AUTO_INCREMENT` in schema
   - Location: `db/schema.sql` (line 122)

7. **✅ Save product images only inside the "uploads" folder.**
   - Status: Implemented - Images saved to `uploads/u{user_id}/p{product_id}/` structure
   - Location: `actions/add_product_action.php` (line 239-258)

8. **✅ Organize images by user and product inside the folder.**
   - Status: Implemented - Structure: `uploads/u{user_id}/p{product_id}/filename`
   - Location: `actions/add_product_action.php` (line 240-241, 258)

9. **✅ Show a message after each product action (added or edited).**
   - Status: Implemented - Toast notifications used for all actions
   - Location: `public_html/assets/js/products.js` (line 621, 626)

10. **⚠️ Optional: allow bulk image uploads.**
    - Status: **PARTIALLY IMPLEMENTED** - Code exists for multiple file handling
    - Location: `public_html/assets/js/products.js` (line 434-458, 843-870)
    - Note: Functionality exists but may need testing/verification

11. **✅ Update the main menu:**
    - Status: Implemented - Menu structure matches requirements
    - Not logged in → "Register" and "Login" ✅
    - Logged in as admin → "Logout," "Category," "Brand," and "Add Product" ✅
    - Logged in as normal user → "Logout" ✅
    - Location: `public_html/view/templates/header.php` (line 125-150)

---

## Issues Found:

### Issue 1: Brand Filtering for Admin Users
- **Requirement**: "Show all the brands created by the logged-in user"
- **Current Behavior**: Admin users see ALL brands in a category, not just their own
- **Location**: `class/brand_class.php::get_brands_by_category()` (line 168-175)
- **Fix Needed**: Change admin query to filter by `user_id` as well

### Issue 2: Bulk Image Upload (Optional)
- **Status**: Code exists but needs verification
- **Location**: `public_html/assets/js/products.js`
- **Note**: This is optional, so it may not be critical

---

## Summary:

- **Part 1**: 11/12 requirements fully met, 1 needs fix (brand filtering for admin)
- **Part 2**: 10/11 requirements fully met, 1 optional feature needs verification

**Total**: 21/23 requirements fully implemented (91% complete)


