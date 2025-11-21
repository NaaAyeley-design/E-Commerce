# XAMPP Setup Guide

## Problem
After reinstalling XAMPP, your project is not accessible because it's located outside XAMPP's default document root.

## Solution Options

### Option 1: Move Project to XAMPP htdocs (RECOMMENDED - Easiest)

1. **Move your project folder:**
   - From: `C:\Users\nayel\Downloads\htdocs\ecommerce-authent`
   - To: `C:\xampp\htdocs\ecommerce-authent`

2. **Access your site:**
   - Open browser and go to: `http://localhost/ecommerce-authent/public_html/`
   - Or: `http://localhost/ecommerce-authent/` (will redirect to public_html)

### Option 2: Create a Virtual Host (Better for Development)

1. **Open XAMPP Control Panel** and make sure Apache is running

2. **Edit Apache httpd.conf:**
   - Location: `C:\xampp\apache\conf\httpd.conf`
   - Find the line: `#Include conf/extra/httpd-vhosts.conf`
   - Remove the `#` to uncomment it: `Include conf/extra/httpd-vhosts.conf`
   - Save the file

3. **Edit httpd-vhosts.conf:**
   - Location: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
   - Add this configuration at the end:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/Users/nayel/Downloads/htdocs/ecommerce-authent/public_html"
    ServerName ecommerce-authent.local
    ServerAlias www.ecommerce-authent.local
    <Directory "C:/Users/nayel/Downloads/htdocs/ecommerce-authent/public_html">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

4. **Edit Windows hosts file:**
   - Location: `C:\Windows\System32\drivers\etc\hosts`
   - Open as Administrator
   - Add this line: `127.0.0.1    ecommerce-authent.local`
   - Save the file

5. **Restart Apache** in XAMPP Control Panel

6. **Access your site:**
   - Open browser and go to: `http://ecommerce-authent.local/`

### Option 3: Change XAMPP Document Root (Not Recommended)

1. **Edit httpd.conf:**
   - Location: `C:\xampp\apache\conf\httpd.conf`
   - Find: `DocumentRoot "C:/xampp/htdocs"`
   - Change to: `DocumentRoot "C:/Users/nayel/Downloads/htdocs/ecommerce-authent/public_html"`
   - Also change: `<Directory "C:/xampp/htdocs">` to `<Directory "C:/Users/nayel/Downloads/htdocs/ecommerce-authent/public_html">`
   - Save and restart Apache

## Verify Setup

1. Make sure **Apache** and **MySQL** are running in XAMPP Control Panel
2. Check that your database credentials in `settings/db_cred.php` are correct
3. Try accessing the site using one of the URLs above

## Troubleshooting

- **403 Forbidden Error:** Check file permissions and Directory settings in Apache config
- **404 Not Found:** Verify the document root path is correct
- **Database Connection Error:** Check `settings/db_cred.php` and ensure MySQL is running


