# Designer/Producer Registration Implementation

## Summary
Successfully implemented designer/producer registration functionality alongside existing customer registration. Users can now choose their account type during registration.

## Changes Made

### 1. Registration Form (`public_html/view/user/register.php`)
- ✅ Added role selection with radio buttons
  - Customer/Buyer (Role 2) - "Shop authentic Ghanaian fashion"
  - Designer/Producer (Role 3) - "Sell my products on KenteKart"
- ✅ Added designer-specific fields (shown when Designer is selected):
  - Business Name (optional)
  - Brief Description/Bio (optional)
- ✅ Added JavaScript to show/hide designer fields based on role selection
- ✅ Visual styling with icons and hover effects

### 2. Registration Action (`actions/register_customer_action.php`)
- ✅ Captures `user_role` from form (validates only 2 or 3)
- ✅ Captures `business_name` and `bio` for designers
- ✅ Validates role selection before submission
- ✅ Prevents role 1 (admin) from being set via registration
- ✅ Updated success message based on role

### 3. User Controller (`controller/user_controller.php`)
- ✅ Updated `register_user_ctr()` to accept:
  - `$user_role` parameter (defaults to 2)
  - `$business_name` parameter (optional)
  - `$bio` parameter (optional)
- ✅ Validates role (only allows 2 or 3)

### 4. User Class (`class/user_class.php`)
- ✅ Updated `add_customer()` method to:
  - Accept role, business_name, and bio parameters
  - Check if designer columns exist in database
  - Conditionally insert designer fields if columns exist
  - Maintain backward compatibility if columns don't exist
- ✅ Added `column_exists()` helper method

### 5. Database Schema
- ✅ Created migration script: `db/add_designer_fields.php`
  - Adds `business_name` VARCHAR(200) column
  - Adds `bio` TEXT column
  - Adds Designer role (3) to user_roles table
- ✅ Updated `db/schema.sql` with new columns and role

### 6. Login System (`actions/process_login.php`)
- ✅ Updated redirect logic to handle all three roles:
  - Role 1 (Admin) → Admin dashboard
  - Role 2 (Customer) → Customer dashboard
  - Role 3 (Designer) → Designer dashboard (if exists, otherwise customer dashboard)

### 7. Dashboard Access (`public_html/view/user/dashboard.php`)
- ✅ Added role check to redirect designers to their dashboard
- ✅ Maintains access for customers

## User Role System

| Role ID | Role Name | Description | Dashboard |
|---------|-----------|------------|-----------|
| 1 | Admin | Administrator with full access | `/view/admin/dashboard.php` |
| 2 | Customer | Regular customer account | `/view/user/dashboard.php` |
| 3 | Designer/Producer | Designer/Producer account | `/view/designer/dashboard.php` |

## Database Migration

Run the migration script to add designer fields:
```bash
php db/add_designer_fields.php
```

This will:
1. Add `business_name` column to customer table
2. Add `bio` column to customer table
3. Add Designer role (3) to user_roles table

## Security Features

1. **Role Validation**: Only roles 2 and 3 can be selected during registration
2. **Admin Protection**: Role 1 (admin) cannot be set through registration form
3. **Input Sanitization**: All inputs are sanitized before database insertion
4. **CSRF Protection**: Registration form includes CSRF token validation
5. **Rate Limiting**: Registration attempts are rate-limited

## Registration Flow

1. User visits registration page
2. Selects account type (Customer or Designer/Producer)
3. If Designer selected, additional fields appear
4. Fills in required information
5. Submits form
6. System validates and creates account with appropriate role
7. Redirects to login page
8. After login, user is redirected to appropriate dashboard based on role

## Next Steps (Optional)

1. **Create Designer Dashboard**: Create `/view/designer/dashboard.php` for designer-specific features
2. **Designer Profile Page**: Allow designers to edit their business information
3. **Product Management**: Add product creation/management interface for designers
4. **Designer Verification**: Add verification process for designer accounts

## Testing Checklist

- [ ] Customer registration works (Role 2)
- [ ] Designer registration works (Role 3)
- [ ] Designer fields appear when Designer is selected
- [ ] Designer fields are hidden when Customer is selected
- [ ] Role validation prevents invalid roles
- [ ] Admin role (1) cannot be set via registration
- [ ] Login redirects customers to customer dashboard
- [ ] Login redirects designers to designer dashboard (or customer dashboard if designer dashboard doesn't exist)
- [ ] Database migration script runs successfully
- [ ] Designer fields are saved to database
- [ ] Existing users remain functional

## Files Modified

1. `public_html/view/user/register.php` - Registration form
2. `actions/register_customer_action.php` - Registration processing
3. `controller/user_controller.php` - User registration controller
4. `class/user_class.php` - User database operations
5. `actions/process_login.php` - Login redirect logic
6. `public_html/view/user/dashboard.php` - Dashboard access control
7. `db/schema.sql` - Database schema
8. `db/add_designer_fields.php` - Migration script (NEW)

## Notes

- The system maintains backward compatibility - if designer columns don't exist, registration still works
- Designer dashboard can be created later - system will redirect to customer dashboard as fallback
- All existing customer accounts remain functional
- Phone number is already required for all users (including designers)

