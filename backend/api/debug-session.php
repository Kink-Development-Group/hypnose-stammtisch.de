<?php

declare(strict_types=1);

/**
 * Debug-Seite für Session-Monitoring
 * NUR FÜR ENTWICKLUNG - NICHT IN PRODUKTION VERWENDEN!
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\Response;

// Load configuration
Config::load(__DIR__ . '/..');

// Handle CORS
header('Access-Control-Allow-Origin: http://localhost:5174');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Start session
AdminAuth::startSession();

?>
<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Session Debug - Admin System</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
      background-color: #f5f5f5;
    }

    .debug-section {
      background: white;
      margin: 20px 0;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .debug-section h2 {
      margin-top: 0;
      color: #333;
      border-bottom: 2px solid #007cba;
      padding-bottom: 10px;
    }

    .key-value {
      display: grid;
      grid-template-columns: 200px 1fr;
      gap: 10px;
      margin: 10px 0;
      padding: 8px;
      background: #f8f9fa;
      border-radius: 4px;
    }

    .key {
      font-weight: bold;
      color: #495057;
    }

    .value {
      color: #212529;
      word-break: break-all;
    }

    .status-ok {
      color: #28a745;
    }

    .status-error {
      color: #dc3545;
    }

    .json-data {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      padding: 15px;
      font-family: 'Courier New', monospace;
      white-space: pre-wrap;
      overflow-x: auto;
    }

    button {
      background: #007cba;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      margin: 5px;
    }

    button:hover {
      background: #005a87;
    }

    .warning {
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      color: #856404;
      padding: 15px;
      border-radius: 5px;
      margin: 20px 0;
    }
  </style>
</head>

<body>
  <h1>🔍 Session Debug - Admin System</h1>

  <div class="warning">
    <strong>⚠️ Warnung:</strong> Diese Seite ist nur für die Entwicklung gedacht und zeigt sensible Session-Daten an.
    In der Produktion sollte sie nicht verfügbar sein!
  </div>

  <div class="debug-section">
    <h2>📊 Session Status</h2>

    <div class="key-value">
      <div class="key">Session Status:</div>
      <div class="value <?php echo session_status() === PHP_SESSION_ACTIVE ? 'status-ok' : 'status-error'; ?>">
        <?php
        switch (session_status()) {
          case PHP_SESSION_DISABLED:
            echo "❌ DISABLED";
            break;
          case PHP_SESSION_NONE:
            echo "⚪ NONE";
            break;
          case PHP_SESSION_ACTIVE:
            echo "✅ ACTIVE";
            break;
          default:
            echo "❓ UNKNOWN";
        }
        ?>
      </div>
    </div>

    <div class="key-value">
      <div class="key">Session ID:</div>
      <div class="value"><?php echo session_id(); ?></div>
    </div>

    <div class="key-value">
      <div class="key">Session Name:</div>
      <div class="value"><?php echo session_name(); ?></div>
    </div>

    <div class="key-value">
      <div class="key">Ist authentifiziert:</div>
      <div class="value <?php echo AdminAuth::isAuthenticated() ? 'status-ok' : 'status-error'; ?>">
        <?php echo AdminAuth::isAuthenticated() ? '✅ JA' : '❌ NEIN'; ?>
      </div>
    </div>
  </div>

  <div class="debug-section">
    <h2>🎯 Session Daten</h2>

    <?php if (empty($_SESSION)): ?>
      <p class="status-error">❌ Keine Session-Daten vorhanden</p>
    <?php else: ?>
      <div class="json-data"><?php echo json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></div>
    <?php endif; ?>
  </div>

  <div class="debug-section">
    <h2>🍪 Cookies</h2>

    <?php if (empty($_COOKIE)): ?>
      <p class="status-error">❌ Keine Cookies vorhanden</p>
    <?php else: ?>
      <?php foreach ($_COOKIE as $name => $value): ?>
        <div class="key-value">
          <div class="key"><?php echo htmlspecialchars($name); ?>:</div>
          <div class="value"><?php echo htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : ''); ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="debug-section">
    <h2>👤 Aktueller Benutzer</h2>

    <?php
    $currentUser = AdminAuth::getCurrentUser();
    if ($currentUser):
    ?>
      <div class="json-data"><?php echo json_encode($currentUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></div>
    <?php else: ?>
      <p class="status-error">❌ Kein authentifizierter Benutzer</p>
    <?php endif; ?>
  </div>

  <div class="debug-section">
    <h2>⚙️ Session Konfiguration</h2>

    <div class="key-value">
      <div class="key">Cookie Lifetime:</div>
      <div class="value"><?php echo ini_get('session.cookie_lifetime'); ?> Sekunden</div>
    </div>

    <div class="key-value">
      <div class="key">Cookie Path:</div>
      <div class="value"><?php echo ini_get('session.cookie_path'); ?></div>
    </div>

    <div class="key-value">
      <div class="key">Cookie Domain:</div>
      <div class="value"><?php echo ini_get('session.cookie_domain') ?: 'nicht gesetzt'; ?></div>
    </div>

    <div class="key-value">
      <div class="key">Cookie Secure:</div>
      <div class="value"><?php echo ini_get('session.cookie_secure') ? 'JA' : 'NEIN'; ?></div>
    </div>

    <div class="key-value">
      <div class="key">Cookie HttpOnly:</div>
      <div class="value"><?php echo ini_get('session.cookie_httponly') ? 'JA' : 'NEIN'; ?></div>
    </div>

    <div class="key-value">
      <div class="key">Cookie SameSite:</div>
      <div class="value"><?php echo ini_get('session.cookie_samesite') ?: 'nicht gesetzt'; ?></div>
    </div>
  </div>

  <div class="debug-section">
    <h2>🔧 Aktionen</h2>

    <button onclick="location.reload()">🔄 Aktualisieren</button>

    <form method="POST" style="display: inline;">
      <button type="submit" name="action" value="destroy_session">🗑️ Session zerstören</button>
    </form>

    <form method="POST" style="display: inline;">
      <button type="submit" name="action" value="regenerate_id">🔄 Session ID neu generieren</button>
    </form>

    <a href="/admin/login" style="text-decoration: none;">
      <button type="button">🚪 Zur Login-Seite</button>
    </a>
  </div>

  <?php
  // Handle POST actions
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'destroy_session':
        AdminAuth::logout();
        echo '<script>setTimeout(() => location.reload(), 1000);</script>';
        echo '<div class="warning">Session wurde zerstört. Seite wird neu geladen...</div>';
        break;

      case 'regenerate_id':
        session_regenerate_id(true);
        echo '<script>setTimeout(() => location.reload(), 1000);</script>';
        echo '<div class="warning">Session ID wurde neu generiert. Seite wird neu geladen...</div>';
        break;
    }
  }
  ?>

  <div class="debug-section">
    <h2>📝 Debug Log</h2>
    <p><em>Timestamp: <?php echo date('Y-m-d H:i:s'); ?></em></p>
    <p><em>Server: <?php echo $_SERVER['HTTP_HOST'] ?? 'localhost'; ?></em></p>
    <p><em>Request URI: <?php echo $_SERVER['REQUEST_URI'] ?? '/'; ?></em></p>
    <p><em>User Agent: <?php echo substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 100); ?></em></p>
  </div>

</body>

</html>
