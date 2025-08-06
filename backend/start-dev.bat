@echo off
REM Hypnose Stammtisch Backend - Development Server
REM Batch script to start the PHP development server

echo.
echo 🚀 Starting Hypnose Stammtisch Backend...
echo 📍 Server will be available at: http://localhost:8000
echo 📁 Document root: %~dp0api
echo 🛑 Press Ctrl+C to stop the server
echo --------------------------------------------------

REM Check if PHP is available
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Error: PHP is not installed or not in PATH.
    echo    Please install PHP 8.1 or higher.
    pause
    exit /b 1
)

REM Get PHP version
for /f "tokens=2" %%i in ('php -v ^| findstr "PHP"') do set PHP_VERSION=%%i
echo ✅ PHP version: %PHP_VERSION%

REM Check if Composer dependencies are installed
if not exist "%~dp0vendor\autoload.php" (
    echo ❌ Error: Composer dependencies not installed.
    echo    Please run: composer install
    pause
    exit /b 1
)

echo ✅ Dependencies: installed

REM Check if .env file exists
if not exist "%~dp0.env" (
    echo ⚠️  Warning: .env file not found.
    echo    Using default configuration.
) else (
    echo ✅ Configuration: loaded
)

echo --------------------------------------------------

REM Change to backend directory
cd /d "%~dp0"

REM Start the PHP development server
echo 🎯 Starting server...
echo    Host: localhost
echo    Port: 8000
echo    Document Root: api
echo --------------------------------------------------

php -S localhost:8000 -t api

echo.
echo Server stopped.
pause
