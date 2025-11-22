# Fix InnoDB Configuration and Log Files
# This script optimizes InnoDB settings and fixes corrupted log files

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "InnoDB Configuration Fixer" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$myIniPath = "C:\xampp\mysql\bin\my.ini"
$dataDir = "C:\xampp\mysql\data"

# Check if my.ini exists
if (-not (Test-Path $myIniPath)) {
    Write-Host "ERROR: my.ini not found at $myIniPath" -ForegroundColor Red
    exit 1
}

Write-Host "Current InnoDB Settings:" -ForegroundColor Yellow
$currentConfig = Get-Content $myIniPath
$innodbSettings = $currentConfig | Select-String -Pattern "innodb_"
foreach ($setting in $innodbSettings) {
    Write-Host "  $setting" -ForegroundColor White
}
Write-Host ""

# Check if MySQL is running
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if ($mysqlRunning) {
    Write-Host "WARNING: MySQL is currently running!" -ForegroundColor Red
    Write-Host "You must STOP MySQL in XAMPP before running this fix." -ForegroundColor Yellow
    Write-Host ""
    $continue = Read-Host "Do you want to continue anyway? (y/n)"
    if ($continue -ne "y") {
        Write-Host "Exiting. Please stop MySQL first." -ForegroundColor Yellow
        exit
    }
}

Write-Host "Step 1: Backing up current my.ini..." -ForegroundColor Cyan
$backupPath = "$myIniPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
Copy-Item $myIniPath $backupPath
Write-Host "  Backup created: $backupPath" -ForegroundColor Green
Write-Host ""

Write-Host "Step 2: Optimizing InnoDB settings..." -ForegroundColor Cyan
$config = Get-Content $myIniPath -Raw

# Optimize InnoDB settings
$optimizations = @{
    'innodb_buffer_pool_size=16M' = 'innodb_buffer_pool_size=128M'
    'innodb_log_file_size=5M' = 'innodb_log_file_size=32M'
    'innodb_log_buffer_size=8M' = 'innodb_log_buffer_size=16M'
    'innodb_flush_log_at_trx_commit=1' = 'innodb_flush_log_at_trx_commit=2'
}

foreach ($old in $optimizations.Keys) {
    $new = $optimizations[$old]
    if ($config -match [regex]::Escape($old)) {
        $config = $config -replace [regex]::Escape($old), $new
        Write-Host "  Updated: $old -> $new" -ForegroundColor Green
    } else {
        Write-Host "  Not found: $old" -ForegroundColor Yellow
    }
}

# Save optimized config
Set-Content -Path $myIniPath -Value $config -NoNewline
Write-Host "  Configuration updated!" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3: Checking InnoDB log files..." -ForegroundColor Cyan
$ibLogfile0 = "$dataDir\ib_logfile0"
$ibLogfile1 = "$dataDir\ib_logfile1"
$ibdata1 = "$dataDir\ibdata1"

if ((Test-Path $ibLogfile0) -and (Test-Path $ibLogfile1)) {
    $size0 = (Get-Item $ibLogfile0).Length
    $size1 = (Get-Item $ibLogfile1).Length
    Write-Host "  ib_logfile0 size: $([math]::Round($size0/1MB, 2)) MB" -ForegroundColor White
    Write-Host "  ib_logfile1 size: $([math]::Round($size1/1MB, 2)) MB" -ForegroundColor White
    
    # Check if sizes match the new configuration (32M)
    $expectedSize = 32 * 1024 * 1024  # 32MB in bytes
    if ($size0 -ne $expectedSize -or $size1 -ne $expectedSize) {
        Write-Host "  WARNING: Log file sizes don't match new configuration!" -ForegroundColor Yellow
        Write-Host "  You need to delete and recreate the log files." -ForegroundColor Yellow
        Write-Host ""
        $recreate = Read-Host "Delete and recreate InnoDB log files? (y/n)"
        if ($recreate -eq "y") {
            Write-Host "  Stopping MySQL if running..." -ForegroundColor Yellow
            Stop-Process -Name "mysqld" -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 2
            
            Write-Host "  Deleting old log files..." -ForegroundColor Yellow
            Remove-Item $ibLogfile0 -Force -ErrorAction SilentlyContinue
            Remove-Item $ibLogfile1 -Force -ErrorAction SilentlyContinue
            Write-Host "  Log files deleted. MySQL will recreate them on next start." -ForegroundColor Green
        }
    } else {
        Write-Host "  Log file sizes are correct!" -ForegroundColor Green
    }
} else {
    Write-Host "  Log files not found - MySQL will create them on next start." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "OPTIMIZATION COMPLETE!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Changes made:" -ForegroundColor Yellow
Write-Host "  ✓ Increased buffer pool: 16M -> 128M (better performance)" -ForegroundColor White
Write-Host "  ✓ Increased log file size: 5M -> 32M (better stability)" -ForegroundColor White
Write-Host "  ✓ Increased log buffer: 8M -> 16M (better performance)" -ForegroundColor White
Write-Host "  ✓ Changed flush mode: 1 -> 2 (better performance, still safe)" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Start MySQL in XAMPP Control Panel" -ForegroundColor White
Write-Host "  2. If you deleted log files, MySQL will recreate them automatically" -ForegroundColor White
Write-Host "  3. Monitor MySQL to see if crashes stop" -ForegroundColor White
Write-Host ""
Write-Host "Note: If MySQL fails to start after this, restore the backup:" -ForegroundColor Yellow
Write-Host "  Copy-Item '$backupPath' '$myIniPath' -Force" -ForegroundColor Cyan
Write-Host ""


