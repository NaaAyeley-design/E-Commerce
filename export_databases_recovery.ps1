# Export Databases in Recovery Mode
# This script helps export your databases when MySQL is in recovery mode

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Database Export in Recovery Mode" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$mysqlPath = "C:\xampp\mysql\bin"
$backupDir = "C:\xampp\mysql_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"

# Check if MySQL is running
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Write-Host "ERROR: MySQL is not running!" -ForegroundColor Red
    Write-Host "Please start MySQL in XAMPP Control Panel first." -ForegroundColor Yellow
    exit 1
}

Write-Host "MySQL is running. Creating backup directory..." -ForegroundColor Green
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Write-Host "Backup directory: $backupDir" -ForegroundColor White
Write-Host ""

# Get list of databases
Write-Host "Getting list of databases..." -ForegroundColor Yellow
$databases = & "$mysqlPath\mysql.exe" -u root -e "SHOW DATABASES;" 2>&1 | Where-Object { $_ -notmatch "Database|information_schema|performance_schema|mysql|sys" } | ForEach-Object { $_.Trim() }

if ($databases.Count -eq 0) {
    Write-Host "No databases found to export." -ForegroundColor Yellow
    exit 0
}

Write-Host "Found $($databases.Count) database(s) to export:" -ForegroundColor Green
foreach ($db in $databases) {
    Write-Host "  - $db" -ForegroundColor White
}
Write-Host ""

# Export each database
foreach ($db in $databases) {
    Write-Host "Exporting database: $db..." -ForegroundColor Cyan
    $outputFile = "$backupDir\$db.sql"
    
    $result = & "$mysqlPath\mysqldump.exe" -u root "$db" > $outputFile 2>&1
    
    if ($LASTEXITCODE -eq 0 -and (Test-Path $outputFile) -and (Get-Item $outputFile).Length -gt 0) {
        $size = [math]::Round((Get-Item $outputFile).Length / 1KB, 2)
        Write-Host "  ✓ Exported: $outputFile ($size KB)" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Failed to export: $db" -ForegroundColor Red
        Write-Host "  Error: $result" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Export Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Backup location: $backupDir" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Verify the SQL files in the backup directory" -ForegroundColor White
Write-Host "  2. Once verified, we can rebuild MySQL cleanly" -ForegroundColor White
Write-Host ""



