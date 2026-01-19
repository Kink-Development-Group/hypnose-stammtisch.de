<?php

declare(strict_types=1);

/**
 * DEPRECATED: Backend root setup.php
 *
 * Dieser Endpunkt wurde zu api/setup.php verschoben.
 * Diese Datei leitet zur neuen Position weiter.
 *
 * Für CLI-Nutzung: php cli/cli.php setup
 * Für Web-Nutzung: /api/setup.php?action=full&token=xxx
 *
 * @deprecated since 2.0.0 - Use api/setup.php or CLI instead
 */

// Bei CLI-Aufruf: Hinweis auf CLI-Tool
if (php_sapi_name() === 'cli') {
  echo "\n";
  echo "╔════════════════════════════════════════════════════════════════╗\n";
  echo "║  DEPRECATED: setup.php wurde verschoben                       ║\n";
  echo "║                                                               ║\n";
  echo "║  Bitte nutze stattdessen das CLI-Tool:                        ║\n";
  echo "║  php cli/cli.php setup                                        ║\n";
  echo "║                                                               ║\n";
  echo "║  Verfügbare Befehle:                                          ║\n";
  echo "║    php cli/cli.php setup           # Vollständiges Setup      ║\n";
  echo "║    php cli/cli.php setup database  # Nur Datenbank            ║\n";
  echo "║    php cli/cli.php setup admin     # Nur Admin erstellen      ║\n";
  echo "║    php cli/cli.php migrate         # Migrationen              ║\n";
  echo "╚════════════════════════════════════════════════════════════════╝\n";
  echo "\n";
  exit(1);
}

// Bei Web-Aufruf: Weiterleitung zu api/setup.php
http_response_code(301);
header('Content-Type: application/json');

// Query-String beibehalten
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = 'api/setup.php' . ($queryString ? '?' . $queryString : '?action=help');

header('Location: ' . $redirectUrl);

echo json_encode([
  'success' => false,
  'error' => 'DEPRECATED: setup.php wurde zu api/setup.php verschoben',
  'redirect' => $redirectUrl,
  'alternatives' => [
    'web' => '/api/setup.php?action=full&token=YOUR_TOKEN',
    'cli' => 'php cli/cli.php setup',
  ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
