<?php

declare(strict_types=1);

/**
 * DEPRECATED: install.php wurde durch setup.php ersetzt.
 *
 * Bitte nutze stattdessen:
 *   - Web: /api/setup.php?action=full&token=xxx
 *   - CLI: php cli/cli.php setup
 *
 * @deprecated since 2.0.0 - Use setup.php instead
 */

http_response_code(301);
header('Content-Type: application/json');
header('Location: setup.php?action=help');
echo json_encode([
  'success' => false,
  'error' => 'DEPRECATED: install.php wurde durch setup.php ersetzt',
  'redirect' => 'setup.php?action=help',
  'alternatives' => [
    'web' => '/api/setup.php?action=full&token=YOUR_TOKEN',
    'cli' => 'php cli/cli.php setup',
  ],
]);
