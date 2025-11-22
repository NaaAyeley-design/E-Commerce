# XAMPP MySQL Keeps Turning Off - Troubleshooting Guide

## Quick Diagnosis

Run the troubleshooting script:
```powershell
.\fix_mysql_xampp.ps1
```

## Common Causes & Solutions

### 1. **Port 3306 Already in Use** (Most Common)

**Problem:** Another MySQL instance or application is using port 3306.

**Solution A - Stop the conflicting process:**
```powershell
# Find what's using port 3306
Get-NetTCPConnection -LocalPort 3306 | Select-Object OwningProcess

# Stop the process (replace PID with actual process ID)
Stop-Process -Id <PID>
```

**Solution B - Change XAMPP MySQL port:**
1. Edit `C:\xampp\mysql\bin\my.ini`
2. Find `port=3306`
3. Change to `port=3307`
4. Update your `settings/db_cred.php`:
   ```php
   define('DB_PORT', '3307');
   ```
5. Restart MySQL in XAMPP

### 2. **MySQL Running as Windows Service**

**Problem:** MySQL is installed as a Windows service, conflicting with XAMPP.

**Solution:**
```powershell
# Run PowerShell as Administrator, then:
Get-Service -Name "MySQL*" | Stop-Service
Get-Service -Name "MySQL*" | Set-Service -StartupType Disabled
```

### 3. **Corrupted MySQL Data Files**

**Problem:** MySQL data files are corrupted, causing crashes.

**Solution:**
1. **BACKUP YOUR DATABASES FIRST!** (Export via phpMyAdmin)
2. Stop MySQL in XAMPP
3. Delete these files:
   - `C:\xampp\mysql\data\ib_logfile0`
   - `C:\xampp\mysql\data\ib_logfile1`
4. Start MySQL again

### 4. **Insufficient Permissions**

**Problem:** XAMPP doesn't have permission to run MySQL.

**Solution:**
1. Close XAMPP Control Panel
2. Right-click XAMPP Control Panel
3. Select "Run as Administrator"
4. Start MySQL

### 5. **Antivirus/Firewall Blocking**

**Problem:** Security software is blocking MySQL.

**Solution:**
1. Add XAMPP to antivirus exclusions:
   - `C:\xampp\mysql\bin\mysqld.exe`
   - `C:\xampp\mysql\data\`
2. Allow MySQL through Windows Firewall

### 6. **Check Error Logs**

**Location:** `C:\xampp\mysql\data\*.err`

**How to read:**
1. Open the latest `.err` file
2. Look for lines with "ERROR" or "FATAL"
3. Google the error message for specific solutions

## Step-by-Step Fix Process

### Step 1: Run Diagnostics
```powershell
.\fix_mysql_xampp.ps1
```

### Step 2: Stop Conflicting Services
```powershell
# As Administrator
Get-Service -Name "MySQL*" | Stop-Service
Get-Service -Name "MySQL*" | Set-Service -StartupType Disabled
```

### Step 3: Check Port
```powershell
Get-NetTCPConnection -LocalPort 3306
```

### Step 4: Clean Start
1. Close XAMPP completely
2. Open XAMPP as Administrator
3. Start MySQL
4. Check if it stays running

### Step 5: If Still Failing
1. Check error log: `C:\xampp\mysql\data\*.err`
2. Look for specific error messages
3. Search online for that specific error

## Quick Commands Reference

```powershell
# Check if MySQL is running
Get-Process -Name "mysqld" -ErrorAction SilentlyContinue

# Check port 3306
Get-NetTCPConnection -LocalPort 3306

# Check MySQL services
Get-Service -Name "MySQL*"

# Stop MySQL service
Stop-Service -Name "MySQL80"  # Replace with your service name

# View error log
Get-Content "C:\xampp\mysql\data\*.err" -Tail 20
```

## Still Not Working?

1. **Check XAMPP version compatibility** - Update to latest XAMPP
2. **Reinstall MySQL in XAMPP** - Use XAMPP's setup to reinstall MySQL
3. **Check Windows Event Viewer** - Look for system errors
4. **Try different MySQL port** - Use 3307, 3308, etc.
5. **Check disk space** - MySQL needs free space for logs

## Prevention

1. **Always run XAMPP as Administrator**
2. **Don't install MySQL as Windows Service** if using XAMPP
3. **Keep XAMPP updated**
4. **Regular database backups**
5. **Monitor error logs regularly**

