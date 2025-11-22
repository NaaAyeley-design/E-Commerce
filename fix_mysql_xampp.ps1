# XAMPP MySQL Troubleshooting Script
# This script helps diagnose and fix MySQL issues in XAMPP

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "XAMPP MySQL Troubleshooting Tool" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if MySQL is running
Write-Host "1. Checking MySQL status..." -ForegroundColor Yellow
$mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if ($mysqlProcess) {
    Write-Host "   MySQL is currently RUNNING (PID: $($mysqlProcess.Id))" -ForegroundColor Green
} else {
    Write-Host "   MySQL is NOT running" -ForegroundColor Red
}

Write-Host ""

# Check if port 3306 is in use
Write-Host "2. Checking port 3306 (MySQL default port)..." -ForegroundColor Yellow
$port3306 = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue
if ($port3306) {
    Write-Host "   Port 3306 is IN USE by process ID: $($port3306.OwningProcess)" -ForegroundColor Red
    $process = Get-Process -Id $port3306.OwningProcess -ErrorAction SilentlyContinue
    if ($process) {
        Write-Host "   Process name: $($process.Name)" -ForegroundColor Red
        Write-Host "   Process path: $($process.Path)" -ForegroundColor Red
    }
} else {
    Write-Host "   Port 3306 is AVAILABLE" -ForegroundColor Green
}

Write-Host ""

# Check if MySQL is installed as Windows Service
Write-Host "3. Checking for MySQL Windows Service..." -ForegroundColor Yellow
$mysqlService = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue
if ($mysqlService) {
    Write-Host "   Found MySQL Windows Service(s):" -ForegroundColor Yellow
    foreach ($service in $mysqlService) {
        Write-Host "   - $($service.Name): $($service.Status)" -ForegroundColor $(if ($service.Status -eq 'Running') { 'Green' } else { 'Yellow' })
        if ($service.Status -eq 'Running') {
            Write-Host "     WARNING: MySQL is running as a Windows Service!" -ForegroundColor Red
            Write-Host "     This conflicts with XAMPP's MySQL. You need to stop it." -ForegroundColor Red
        }
    }
} else {
    Write-Host "   No MySQL Windows Service found (Good for XAMPP)" -ForegroundColor Green
}

Write-Host ""

# Check XAMPP MySQL path
Write-Host "4. Checking XAMPP MySQL installation..." -ForegroundColor Yellow
$xamppPath = "C:\xampp"
$mysqlPath = "$xamppPath\mysql"
if (Test-Path $mysqlPath) {
    Write-Host "   XAMPP MySQL found at: $mysqlPath" -ForegroundColor Green
    
    # Check my.ini file
    $myIni = "$mysqlPath\bin\my.ini"
    if (Test-Path $myIni) {
        Write-Host "   Configuration file found: $myIni" -ForegroundColor Green
    } else {
        Write-Host "   WARNING: my.ini not found!" -ForegroundColor Red
    }
    
    # Check data directory
    $dataDir = "$mysqlPath\data"
    if (Test-Path $dataDir) {
        Write-Host "   Data directory found: $dataDir" -ForegroundColor Green
    } else {
        Write-Host "   WARNING: Data directory not found!" -ForegroundColor Red
    }
} else {
    Write-Host "   XAMPP MySQL not found at: $mysqlPath" -ForegroundColor Red
    Write-Host "   Please check your XAMPP installation path." -ForegroundColor Yellow
}

Write-Host ""

# Check error log
Write-Host "5. Checking MySQL error log..." -ForegroundColor Yellow
$errorLog = "$mysqlPath\data\*.err"
$errorFiles = Get-ChildItem -Path $errorLog -ErrorAction SilentlyContinue
if ($errorFiles) {
    $latestError = $errorFiles | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    Write-Host "   Latest error log: $($latestError.FullName)" -ForegroundColor Yellow
    Write-Host "   Last modified: $($latestError.LastWriteTime)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "   Last 10 lines of error log:" -ForegroundColor Cyan
    Get-Content $latestError.FullName -Tail 10 | ForEach-Object {
        Write-Host "   $_" -ForegroundColor $(if ($_ -match "ERROR|FATAL|failed") { 'Red' } else { 'White' })
    }
} else {
    Write-Host "   No error log files found" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "RECOMMENDED FIXES:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Provide recommendations
if ($port3306 -and $port3306.OwningProcess) {
    $process = Get-Process -Id $port3306.OwningProcess -ErrorAction SilentlyContinue
    if ($process -and $process.Name -ne "mysqld") {
        Write-Host "FIX 1: Port 3306 is in use by another process" -ForegroundColor Yellow
        Write-Host "   - Stop the process using port 3306 (PID: $($port3306.OwningProcess))" -ForegroundColor White
        Write-Host "   - Or change MySQL port in XAMPP (edit my.ini)" -ForegroundColor White
        Write-Host ""
    }
}

if ($mysqlService -and ($mysqlService | Where-Object { $_.Status -eq 'Running' })) {
    Write-Host "FIX 2: MySQL Windows Service is running" -ForegroundColor Yellow
    Write-Host "   Run these commands as Administrator:" -ForegroundColor White
    foreach ($service in ($mysqlService | Where-Object { $_.Status -eq 'Running' })) {
        Write-Host "   Stop-Service -Name '$($service.Name)'" -ForegroundColor Cyan
        Write-Host "   Set-Service -Name '$($service.Name)' -StartupType Disabled" -ForegroundColor Cyan
    }
    Write-Host ""
}

Write-Host "FIX 3: General troubleshooting steps:" -ForegroundColor Yellow
Write-Host "   1. Close XAMPP Control Panel completely" -ForegroundColor White
Write-Host "   2. Open XAMPP Control Panel as Administrator" -ForegroundColor White
Write-Host "   3. Try starting MySQL again" -ForegroundColor White
Write-Host "   4. Check the error log above for specific errors" -ForegroundColor White
Write-Host ""

Write-Host "FIX 4: If MySQL keeps crashing:" -ForegroundColor Yellow
Write-Host "   1. Backup your databases (export via phpMyAdmin)" -ForegroundColor White
Write-Host "   2. Stop MySQL in XAMPP" -ForegroundColor White
Write-Host "   3. Delete: C:\xampp\mysql\data\ib_logfile0" -ForegroundColor White
Write-Host "   4. Delete: C:\xampp\mysql\data\ib_logfile1" -ForegroundColor White
Write-Host "   5. Restart MySQL" -ForegroundColor White
Write-Host ""

Write-Host "FIX 5: Change MySQL port (if port conflict):" -ForegroundColor Yellow
Write-Host "   1. Edit: C:\xampp\mysql\bin\my.ini" -ForegroundColor White
Write-Host "   2. Find: port=3306" -ForegroundColor White
Write-Host "   3. Change to: port=3307" -ForegroundColor White
Write-Host "   4. Update your db_cred.php to use port 3307" -ForegroundColor White
Write-Host ""

Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")


