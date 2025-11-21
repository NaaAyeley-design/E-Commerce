# PowerShell script to sync files from Downloads/htdocs to XAMPP htdocs
# Run this script after making changes to sync files to XAMPP

$source = "C:\Users\nayel\Downloads\htdocs\ecommerce-authent"
$destination = "C:\xampp\htdocs\ecommerce-authent"

Write-Host "Syncing files from:" -ForegroundColor Cyan
Write-Host $source -ForegroundColor Yellow
Write-Host "To:" -ForegroundColor Cyan
Write-Host $destination -ForegroundColor Yellow
Write-Host ""

# Check if source exists
if (-not (Test-Path $source)) {
    Write-Host "ERROR: Source directory does not exist!" -ForegroundColor Red
    exit 1
}

# Check if destination exists
if (-not (Test-Path $destination)) {
    Write-Host "Creating destination directory..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
}

# Exclude .git and other unnecessary files
$exclude = @('.git', 'node_modules', '.DS_Store', 'Thumbs.db')

Write-Host "Copying files..." -ForegroundColor Green

# Use Robocopy for better performance and control
$robocopyArgs = @(
    $source,
    $destination,
    "/E",           # Copy subdirectories including empty ones
    "/XD", ".git", "node_modules", "__pycache__", ".vscode", ".idea",  # Exclude directories
    "/XF", "*.log", "Thumbs.db", ".DS_Store",  # Exclude files
    "/R:3",         # Retry 3 times
    "/W:1",         # Wait 1 second between retries
    "/NP",          # No progress
    "/NFL",         # No file list
    "/NDL"          # No directory list
)

$result = & robocopy @robocopyArgs

# Robocopy returns exit codes 0-7 for success, 8+ for errors
if ($LASTEXITCODE -le 7) {
    Write-Host ""
    Write-Host "Sync completed successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Your changes should now be visible at:" -ForegroundColor Cyan
    Write-Host "http://localhost/ecommerce-authent/public_html/" -ForegroundColor Yellow
} else {
    Write-Host ""
    Write-Host "Sync completed with warnings (exit code: $LASTEXITCODE)" -ForegroundColor Yellow
    Write-Host "Some files may not have been copied. Check the output above." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

