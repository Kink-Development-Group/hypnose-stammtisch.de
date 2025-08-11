<?php

declare(strict_types=1);

/**
 * Allgemeines Setup-Skript
 * Führt initiales Setup aus: Datenbank anlegen (falls nicht vorhanden), Migrationen ausführen, Seed-Daten einspielen.
 * Nutzung (CLI):
 *   php setup.php                # DB anlegen falls nötig, Migrationen, Seed
 *   php setup.php --no-seed       # Ohne Seeding
 *   php setup.php --seed          # Erzwingt Seeding auch wenn bereits Daten vorhanden
 *   php setup.php --admin-email admin@example.com --admin-pass GeheimesPasswort  # Head-Admin anlegen/erzwingen
 *   php setup.php --force         # Erzwingt bestimmte Schritte (z.B. Admin Update)
 *
 * Hinweise:
 * - Erwartet gültige DB_* Variablen in backend/.env (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)
 * - Der angegebene Benutzer muss CREATE DATABASE Rechte besitzen wenn DB noch nicht existiert.
 * - Sicher nach Produktion anpassen: APP_ENV, APP_DEBUG=false, starke Passwörter / Secrets.
 */

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

$IS_CLI = php_sapi_name() === 'cli';
$LOG = [];
$SUCCESS = true;
$ADMIN_CREATED = false;

// Optionen parsen (CLI oder Web Query)
$options = [
  'seed' => true,
  'force' => false,
  'admin-email' => null,
  'admin-pass' => null,
];

if ($IS_CLI) {
  $argvInput = $argv ?? [];
  array_shift($argvInput); // script name
  for ($i = 0; $i < count($argvInput); $i++) {
    $arg = $argvInput[$i];
    if ($arg === '--no-seed') {
      $options['seed'] = false;
    } elseif ($arg === '--seed') {
      $options['seed'] = true;
    } elseif ($arg === '--force') {
      $options['force'] = true;
    } elseif ($arg === '--admin-email' && isset($argvInput[$i + 1])) {
      $options['admin-email'] = $argvInput[++$i];
    } elseif ($arg === '--admin-pass' && isset($argvInput[$i + 1])) {
      $options['admin-pass'] = $argvInput[++$i];
    }
  }
} else {
  // Web: Parameter aus GET/POST
  $gp = $_GET + $_POST;
  if (isset($gp['seed'])) {
    $options['seed'] = !in_array(strtolower((string)$gp['seed']), ['0', 'false', 'no'], true);
  }
  if (!empty($gp['no_seed'])) {
    $options['seed'] = false;
  }
  if (!empty($gp['force'])) {
    $options['force'] = true;
  }
  if (!empty($gp['admin_email'])) {
    $options['admin-email'] = trim((string)$gp['admin_email']);
  }
  if (!empty($gp['admin_pass'])) {
    $options['admin-pass'] = (string)$gp['admin_pass'];
  }
  // Security Token Prüfung
  $expectedToken = $_ENV['SETUP_TOKEN'] ?? $_ENV['INSTALL_TOKEN'] ?? '';
  $providedToken = (string)($gp['token'] ?? '');
  $env = $_ENV['APP_ENV'] ?? 'production';
  $lockFile = __DIR__ . '/.setup.lock';
  if ($env === 'production') {
    if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
      http_response_code(403);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => 'Forbidden (token)']);
      return;
    }
    if (is_file($lockFile) && !$options['force']) {
      http_response_code(403);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => 'Setup locked']);
      return;
    }
  }
}

function out(string $msg, string $level = 'info'): void
{
  global $IS_CLI, $LOG;
  $record = ['level' => $level, 'message' => $msg];
  $LOG[] = $record;
  if ($IS_CLI) {
    $colors = [
      'info' => "\033[36m",
      'ok' => "\033[32m",
      'warn' => "\033[33m",
      'err' => "\033[31m",
    ];
    $reset = "\033[0m";
    $c = $colors[$level] ?? $colors['info'];
    echo $c . $msg . $reset . "\n";
  }
}

try {
  out('== Allgemeines Initial-Setup ==', 'info');

  // 1. Datenbank anlegen falls nicht vorhanden
  $host = Config::get('db.host');
  $port = Config::get('db.port');
  $name = Config::get('db.name');
  $user = Config::get('db.user');
  $pass = Config::get('db.pass');

  try {
    out('Prüfe ob Datenbank existiert ...', 'info');
    $dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
    $pdo = new PDO($dsnNoDb, $user, $pass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . str_replace("'", "''", $name) . "'");
    $exists = (bool)$stmt->fetchColumn();
    if (!$exists) {
      out("Datenbank '$name' nicht gefunden – erstelle...", 'warn');
      $pdo->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
      out('Datenbank erstellt.', 'ok');
    } else {
      out('Datenbank existiert bereits.', 'ok');
    }
  } catch (Throwable $e) {
    throw new RuntimeException('Fehler bei DB-Erstellung: ' . $e->getMessage());
  }

  // 2. Migrationen & Seeding Funktionen laden
  require_once __DIR__ . '/migrations/migrate.php';

  // 3. Migrationen ausführen
  out('Führe Migrationen aus ...', 'info');
  runMigrations();

  // 4. Seeding falls gewünscht
  if ($options['seed']) {
    $already = false;
    try {
      $countRow = Database::fetchOne('SELECT COUNT(*) AS c FROM events');
      if ($countRow && (int)$countRow['c'] > 0) {
        $already = true;
      }
    } catch (Throwable $e) {
      // Ignorieren
    }
    if ($already && !$options['force']) {
      out('Seed übersprungen (Events vorhanden). Nutze --force für erneutes Seed.', 'warn');
    } else {
      out('Seede Beispiel-Daten ...', 'info');
      seedDatabase();
    }
  } else {
    out('Seeding deaktiviert (--no-seed).', 'warn');
  }

  // 5. Optional Head-Admin anlegen/erzwingen
  if ($options['admin-email'] !== null) {
    out('Erstelle/Aktualisiere Head-Admin ...', 'info');
    try {
      $email = $options['admin-email'];
      $password = $options['admin-pass'] ?? null;
      if (!$password) {
        $password = bin2hex(random_bytes(8));
        out('Kein admin_pass angegeben – generiere temporäres Passwort: ' . $password, 'warn');
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Ungültige Admin Email');
      }
      if (strlen($password) < 8) {
        throw new InvalidArgumentException('Admin Passwort zu kurz (>=8)');
      }
      $existingHead = Database::fetchOne("SELECT id FROM users WHERE role='head' LIMIT 1");
      if ($existingHead && !$options['force']) {
        out('Head-Admin existiert – kein Update (nutze force=1 für Überschreiben).', 'warn');
      } else {
        $existingUser = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($existingUser) {
          Database::execute('UPDATE users SET password_hash=?, role="head", is_active=1 WHERE email=?', [$hash, $email]);
          out('Bestehender Benutzer aktualisiert zum Head-Admin.', 'ok');
        } else {
          Database::execute('INSERT INTO users (username, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, "head", 1, NOW())', [explode('@', $email)[0], $email, $hash]);
          out('Head-Admin angelegt.', 'ok');
        }
        out('Admin Zugangsdaten:', 'info');
        out('  Email: ' . $email, 'info');
        out('  Passwort: ' . $password, 'warn');
        $ADMIN_CREATED = true;
      }
    } catch (Throwable $e) {
      out('Fehler bei Admin-Anlage: ' . $e->getMessage(), 'err');
      $SUCCESS = false;
    }
  }

  out('Setup abgeschlossen.', 'ok');

  // Lock in Produktion
  if (!$IS_CLI) {
    $env = $_ENV['APP_ENV'] ?? 'production';
    if ($env === 'production') {
      @file_put_contents(__DIR__ . '/.setup.lock', date('c'));
    }
  }
} catch (Throwable $e) {
  $SUCCESS = false;
  out('Setup FEHLGESCHLAGEN: ' . $e->getMessage(), 'err');
}

if (!$IS_CLI) {
  header('Content-Type: application/json');
  echo json_encode([
    'success' => $SUCCESS,
    'admin_created' => $ADMIN_CREATED,
    'options' => $options,
    'messages' => $LOG,
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
