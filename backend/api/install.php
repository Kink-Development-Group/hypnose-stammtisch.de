<?php

declare(strict_types=1);

/**
 * Web Installer for Shared Hosting (Hetzner / Apache)
 *
 * Provides a secure way to run migrations and (optionally) seed data on
 * environments where CLI access is limited.
 *
 * SECURITY:
 * - Requires INSTALL_TOKEN in environment (e.g. via .env) or set below as fallback.
 * - Denies execution if APP_ENV=production AND no valid token provided.
 * - Disable or delete this file after successful deployment.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Utils\Response;

// Load config
Config::load(__DIR__ . '/..');

$env = $_ENV['APP_ENV'] ?? 'production';
$providedToken = $_GET['token'] ?? '';
$expectedToken = $_ENV['INSTALL_TOKEN'] ?? '';

if ($expectedToken === '' && $env !== 'local') {
  http_response_code(403);
  echo 'INSTALL_TOKEN not configured.';
  exit;
}

if (!hash_equals($expectedToken, $providedToken)) {
  http_response_code(403);
  echo 'Invalid installation token.';
  exit;
}

$action = $_GET['action'] ?? 'status';

// Optionaler Schutz: In Production nur einmalige Nutzung zulassen mittels Marker-Datei
$marker = dirname(__DIR__) . '/.install.lock';
if ($env === 'production' && file_exists($marker) && $action !== 'status') {
  http_response_code(403);
  echo 'Installer locked. Remove .install.lock to re-enable (NOT recommended).';
  exit;
}

require_once __DIR__ . '/../migrations/migrate.php';

ob_start();

switch ($action) {
  case 'migrate':
    runMigrations();
    if ($env === 'production') {
      @file_put_contents($marker, date('c'));
    }
    break;
  case 'seed':
    seedDatabase();
    if ($env === 'production') {
      @file_put_contents($marker, date('c'));
    }
    break;
  case 'fresh':
    runMigrations();
    seedDatabase();
    if ($env === 'production') {
      @file_put_contents($marker, date('c'));
    }
    break;
  case 'status':
  default:
    echo "Installer ready. Actions: migrate, seed, fresh. Use ?action=migrate&token=...";
}

$output = nl2br(htmlentities(ob_get_clean() ?? ''));
?>
<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="utf-8" />
  <title>Installer</title>
  <meta name="robots" content="noindex,nofollow" />
  <style>
    body {
      font-family: system-ui, Arial, sans-serif;
      background: #111;
      color: #eee;
      padding: 2rem;
    }

    pre {
      background: #222;
      padding: 1rem;
      border-radius: 6px;
      overflow: auto;
    }

    a {
      color: #6cf;
    }

    .ok {
      color: #5f5;
    }

    .warn {
      color: #fd5;
    }

    .err {
      color: #f55;
    }
  </style>
</head>

<body>
  <h1>Backend Installer</h1>
  <p>Environment: <strong><?= htmlspecialchars($env) ?></strong></p>
  <p>Action: <strong><?= htmlspecialchars($action) ?></strong></p>
  <pre><?= $output ?></pre>
  <p><strong>Sicherheitshinweis:</strong> Diese Datei nach Abschluss <em>löschen</em> oder INSTALL_TOKEN ändern.</p>
</body>

</html>
