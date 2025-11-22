# Analyze MySQL Error Log
# This script analyzes the MySQL error log to find patterns

$logFile = "C:\xampp\mysql\data\mysql_error.log"

if (-not (Test-Path $logFile)) {
    Write-Host "Error log not found: $logFile" -ForegroundColor Red
    exit
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MySQL Error Log Analysis" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$logContent = Get-Content $logFile

# Count restarts
$restarts = $logContent | Select-String "Starting MariaDB"
Write-Host "Total MySQL restarts: $($restarts.Count)" -ForegroundColor Yellow
Write-Host ""

# Check for errors
$errors = $logContent | Select-String -Pattern "ERROR|FATAL" -CaseSensitive:$false
if ($errors) {
    Write-Host "ERRORS FOUND:" -ForegroundColor Red
    $errors | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
    Write-Host ""
} else {
    Write-Host "No ERROR or FATAL messages found" -ForegroundColor Green
    Write-Host ""
}

# Check for warnings
$warnings = $logContent | Select-String -Pattern "Warning|WARN" -CaseSensitive:$false
if ($warnings) {
    Write-Host "WARNINGS FOUND:" -ForegroundColor Yellow
    $warnings | Select-Object -Last 10 | ForEach-Object { Write-Host "  $_" -ForegroundColor Yellow }
    Write-Host ""
}

# Check for crash recovery
$crashRecovery = $logContent | Select-String "crash recovery"
Write-Host "Crash recovery events: $($crashRecovery.Count)" -ForegroundColor $(if ($crashRecovery.Count -gt 5) { 'Red' } else { 'Yellow' })
Write-Host ""

# Analyze restart pattern
Write-Host "Recent restart pattern:" -ForegroundColor Cyan
$recentRestarts = $restarts | Select-Object -Last 10
foreach ($restart in $recentRestarts) {
    $time = ($restart -split '\s+')[0..1] -join ' '
    Write-Host "  $time" -ForegroundColor White
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ANALYSIS:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if ($errors) {
    Write-Host "❌ MySQL has actual errors - check the ERROR messages above" -ForegroundColor Red
} elseif ($crashRecovery.Count -gt 5) {
    Write-Host "⚠️  MySQL is crashing frequently (crash recovery $($crashRecovery.Count) times)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Possible causes:" -ForegroundColor Yellow
    Write-Host "  1. MySQL is being killed by Windows or antivirus" -ForegroundColor White
    Write-Host "  2. Memory/resource issues" -ForegroundColor White
    Write-Host "  3. Corrupted data files" -ForegroundColor White
    Write-Host "  4. Port conflicts" -ForegroundColor White
    Write-Host "  5. Windows Service conflict" -ForegroundColor White
    Write-Host ""
    Write-Host "Recommended fixes:" -ForegroundColor Yellow
    Write-Host "  1. Run XAMPP as Administrator" -ForegroundColor White
    Write-Host "  2. Disable MySQL Windows Service" -ForegroundColor White
    Write-Host "  3. Check Windows Event Viewer for system errors" -ForegroundColor White
    Write-Host "  4. Add XAMPP to antivirus exclusions" -ForegroundColor White
} else {
    Write-Host "✅ MySQL appears to be running normally" -ForegroundColor Green
}

Write-Host ""


