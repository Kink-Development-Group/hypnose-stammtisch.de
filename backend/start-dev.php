#!/usr/bin/env php
<?php
/**
 * Backend Development Server Starter
 *
 * This script starts the PHP development server for the Hypnose Stammtisch API
 */

echo "🚀 Starting Hypnose Stammtisch Backend...\n";
echo "📍 Server will be available at: http://localhost:8000\n";
echo "📁 Document root: " . __DIR__ . "/api\n";
echo "🛑 Press Ctrl+C to stop the server\n";
echo str_repeat("-", 50) . "\n";

// Check if PHP version is sufficient
$requiredVersion = '8.1.0';
$currentVersion = PHP_VERSION;

if (version_compare($currentVersion, $requiredVersion, '<')) {
    echo "❌ Error: PHP version {$requiredVersion} or higher is required.\n";
    echo "   Current version: {$currentVersion}\n";
    exit(1);
}

// Check if Composer dependencies are installed
$vendorPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendorPath)) {
    echo "❌ Error: Composer dependencies not installed.\n";
    echo "   Please run: composer install\n";
    exit(1);
}

// Check if .env file exists
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    echo "⚠️  Warning: .env file not found.\n";
    echo "   Using default configuration.\n";
}

echo "✅ PHP version: {$currentVersion}\n";
echo "✅ Dependencies: installed\n";
echo "✅ Configuration: ready\n";
echo str_repeat("-", 50) . "\n";

// Change to the backend directory
chdir(__DIR__);

// Start the PHP development server
$host = 'localhost';
$port = 8000;
$docroot = 'api';

$command = "php -S {$host}:{$port} -t {$docroot}";

echo "🎯 Starting server with command: {$command}\n";
echo str_repeat("-", 50) . "\n";

// Execute the server
passthru($command);
