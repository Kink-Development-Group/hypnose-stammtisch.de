<?php

declare(strict_types=1);

/**
 * Unified Setup API Endpoint
 *
 * This is the single entry point for all web-based setup operations.
 * Replaces the previously scattered install.php, setup.php, and setup-admin.php.
 *
 * Usage:
 *   GET /api/setup.php?action=status&token=xxx     - Show status
 *   GET /api/setup.php?action=validate&token=xxx   - Validate requirements
 *   GET /api/setup.php?action=migrate&token=xxx    - Run migrations
 *   GET /api/setup.php?action=seed&token=xxx       - Seed sample data
 *   GET /api/setup.php?action=fresh&token=xxx      - Migrate + Seed
 *   GET /api/setup.php?action=full&token=xxx       - Complete setup
 *   GET /api/setup.php?action=admin&admin_email=...&admin_pass=...&token=xxx
 *
 * Security:
 *   - Requires SETUP_TOKEN in .env for production environments
 *   - Automatically locks after successful setup in production
 *   - Delete or disable after deployment is complete
 *
 * @package HypnoseStammtisch
 * @since 2.0.0
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Load autoloader
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'error' => 'Dependencies not installed. Run: composer install',
  ]);
  exit;
}

require_once $autoloadPath;

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Controllers\SetupController;

// Load configuration
try {
  Config::load(dirname(__DIR__));

  if (Config::get('app.debug')) {
    ini_set('display_errors', '1');
  }
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'error' => 'Configuration error: ' . $e->getMessage(),
  ]);
  exit;
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? 'help';

// Dispatch to controller
$controller = new SetupController();
$controller->handle($action);
