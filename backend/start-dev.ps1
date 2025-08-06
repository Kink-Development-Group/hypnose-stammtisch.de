# Hypnose Stammtisch Backend - Development Server
# PowerShell script to start the PHP development server

Write-Host "🚀 Starting Hypnose Stammtisch Backend..." -ForegroundColor Green
Write-Host "📍 Server will be available at: http://localhost:8000" -ForegroundColor Cyan
Write-Host "📁 Document root: $PSScriptRoot\api" -ForegroundColor Yellow
Write-Host "🛑 Press Ctrl+C to stop the server" -ForegroundColor Red
Write-Host ("-" * 50) -ForegroundColor Gray

# Check if PHP is available
try {
    $phpVersion = php -v 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP not found"
    }

    # Extract version number
    $versionMatch = [regex]::Match($phpVersion, "PHP (\d+\.\d+\.\d+)")
    if ($versionMatch.Success) {
        $currentVersion = $versionMatch.Groups[1].Value
        Write-Host "✅ PHP version: $currentVersion" -ForegroundColor Green

        # Check minimum version
        $minVersion = [Version]"8.1.0"
        $currentVersionObj = [Version]$currentVersion

        if ($currentVersionObj -lt $minVersion) {
            Write-Host "❌ Error: PHP version 8.1.0 or higher is required." -ForegroundColor Red
            Write-Host "   Current version: $currentVersion" -ForegroundColor Red
            exit 1
        }
    }
} catch {
    Write-Host "❌ Error: PHP is not installed or not in PATH." -ForegroundColor Red
    Write-Host "   Please install PHP 8.1 or higher." -ForegroundColor Red
    exit 1
}

# Check if Composer dependencies are installed
$vendorPath = Join-Path $PSScriptRoot "vendor\autoload.php"
if (-not (Test-Path $vendorPath)) {
    Write-Host "❌ Error: Composer dependencies not installed." -ForegroundColor Red
    Write-Host "   Please run: composer install" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Dependencies: installed" -ForegroundColor Green

# Check if .env file exists
$envPath = Join-Path $PSScriptRoot ".env"
if (-not (Test-Path $envPath)) {
    Write-Host "⚠️  Warning: .env file not found." -ForegroundColor Yellow
    Write-Host "   Using default configuration." -ForegroundColor Yellow
} else {
    Write-Host "✅ Configuration: loaded" -ForegroundColor Green
}

Write-Host ("-" * 50) -ForegroundColor Gray

# Change to backend directory
Set-Location $PSScriptRoot

# Start the PHP development server
$host = "localhost"
$port = 8000
$docroot = "api"

Write-Host "🎯 Starting server..." -ForegroundColor Green
Write-Host "   Host: $host" -ForegroundColor Cyan
Write-Host "   Port: $port" -ForegroundColor Cyan
Write-Host "   Document Root: $docroot" -ForegroundColor Cyan
Write-Host ("-" * 50) -ForegroundColor Gray

try {
    php -S "${host}:${port}" -t $docroot
} catch {
    Write-Host "❌ Error starting server: $_" -ForegroundColor Red
    exit 1
}
