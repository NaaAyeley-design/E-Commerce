# How to Access Your Site

## URLs to Try:

1. **Main URL (recommended):**
   ```
   http://localhost/ecommerce-authent/
   ```
   This will automatically redirect to `public_html/`

2. **Direct public_html access:**
   ```
   http://localhost/ecommerce-authent/public_html/
   ```

3. **If you get 404 errors, try:**
   ```
   http://localhost/ecommerce-authent/public_html/index.php
   ```

## Troubleshooting Steps:

1. **Make sure Apache is running** in XAMPP Control Panel
2. **Make sure MySQL is running** (for database)
3. **Check Apache error log:**
   - Location: `C:\xampp\apache\logs\error.log`
   - Look for any error messages

4. **Verify file permissions:**
   - Make sure Apache can read the files
   - In Windows, this is usually not an issue, but check if files are accessible

5. **Clear browser cache** and try again

6. **Check database connection:**
   - Verify `settings/db_cred.php` has correct database credentials
   - Make sure the database exists in phpMyAdmin

## Common Issues:

- **403 Forbidden:** Check Apache configuration and file permissions
- **404 Not Found:** Verify the URL path is correct
- **500 Internal Server Error:** Check PHP error logs and database connection
- **Database Connection Error:** Verify MySQL is running and credentials are correct


