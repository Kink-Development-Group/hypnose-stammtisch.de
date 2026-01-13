<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;
use PDO;
use PDOException;
use Throwable;

/**
 * Setup Controller
 *
 * Unified controller for all setup and installation operations.
 * Consolidates functionality from previously scattered install.php, setup.php, and setup-admin.php.
 *
 * Security:
 * - Requires SETUP_TOKEN or INSTALL_TOKEN in production
 * - Automatically locks after successful setup in production
 * - All operations are logged
 *
 * @package HypnoseStammtisch\Controllers
 */
class SetupController
{
    private const DEFAULT_HEAD_EMAIL = 'head@hypnose-stammtisch.de';
    private const DEFAULT_HEAD_PASSWORD = 'admin123';
    private const LOCK_FILE = '.setup.lock';

    private bool $isProduction;
    private bool $isForced;
    private array $log = [];

    public function __construct()
    {
        $this->isProduction = ($_ENV['APP_ENV'] ?? 'production') === 'production';
        $this->isForced = $this->getParam('force', false);
    }

    /**
     * Main router for setup actions
     */
    public function handle(string $action): void
    {
        // Security check
        if (!$this->validateAccess()) {
            return;
        }

        try {
            match ($action) {
                'status' => $this->status(),
                'migrate' => $this->migrate(),
                'seed' => $this->seed(),
                'fresh' => $this->fresh(),
                'admin' => $this->createAdmin(),
                'full' => $this->fullSetup(),
                'validate' => $this->validate(),
                default => $this->showHelp(),
            };
        } catch (Throwable $e) {
            $this->log('Setup fehlgeschlagen: ' . $e->getMessage(), 'error');
            $this->sendResponse(false);
        }
    }

    /**
     * Validate access token and lock status
     */
    private function validateAccess(): bool
    {
        $expectedToken = $_ENV['SETUP_TOKEN'] ?? $_ENV['INSTALL_TOKEN'] ?? '';
        $providedToken = $this->getParam('token', '');

        // In production, token is required
        if ($this->isProduction) {
            if ($expectedToken === '') {
                Response::error('SETUP_TOKEN nicht konfiguriert. Setze SETUP_TOKEN in .env', 403);
                return false;
            }

            if (!hash_equals($expectedToken, $providedToken)) {
                Response::error('Ungültiger Setup-Token', 403);
                return false;
            }

            // Check lock file (except for status check)
            $action = $this->getParam('action', 'help');
            if ($action !== 'status' && $action !== 'validate' && $this->isLocked() && !$this->isForced) {
                Response::error('Setup gesperrt. Nutze force=1 zum Überschreiben oder lösche .setup.lock', 403);
                return false;
            }
        }

        return true;
    }

    /**
     * Show setup status and available actions
     */
    private function status(): void
    {
        $status = [
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'locked' => $this->isLocked(),
            'database' => $this->checkDatabaseStatus(),
            'migrations' => $this->checkMigrationStatus(),
            'admin_exists' => $this->checkAdminExists(),
            'available_actions' => [
                'status' => 'Status anzeigen (diese Ansicht)',
                'validate' => 'System-Anforderungen prüfen',
                'migrate' => 'Datenbank-Migrationen ausführen',
                'seed' => 'Beispieldaten einspielen',
                'fresh' => 'Migrationen + Seed',
                'admin' => 'Admin-Benutzer erstellen',
                'full' => 'Komplettes Setup (empfohlen für Erstinstallation)',
            ],
            'usage' => 'GET /api/setup?action=<action>&token=<token>',
        ];

        Response::success($status);
    }

    /**
     * Show help/usage information
     */
    private function showHelp(): void
    {
        Response::success([
            'message' => 'Hypnose Stammtisch Setup API',
            'version' => '2.0.0',
            'actions' => [
                'status' => 'Status und verfügbare Aktionen anzeigen',
                'validate' => 'System-Anforderungen prüfen',
                'migrate' => 'Datenbank-Migrationen ausführen',
                'seed' => 'Beispieldaten einspielen',
                'fresh' => 'Migrationen + Seed (clean install)',
                'admin' => 'Admin-Benutzer erstellen/aktualisieren',
                'full' => 'Komplettes Setup durchführen',
            ],
            'parameters' => [
                'action' => 'Auszuführende Aktion (required)',
                'token' => 'Setup-Token aus .env (required in production)',
                'force' => 'Erzwingt Ausführung auch wenn gesperrt',
                'admin_email' => 'E-Mail für Admin (bei action=admin)',
                'admin_pass' => 'Passwort für Admin (bei action=admin)',
                'no_seed' => 'Überspringe Seeding bei full Setup',
            ],
            'examples' => [
                '/api/setup?action=status&token=xxx',
                '/api/setup?action=full&token=xxx',
                '/api/setup?action=admin&admin_email=admin@example.com&admin_pass=secret&token=xxx',
            ],
        ]);
    }

    /**
     * Validate system requirements
     */
    private function validate(): void
    {
        $this->log('Prüfe System-Anforderungen...', 'info');

        $checks = [
            'php_version' => [
                'required' => '8.1.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.1.0', '>='),
            ],
            'extensions' => [],
            'directories' => [],
            'files' => [],
        ];

        // Required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
        foreach ($requiredExtensions as $ext) {
            $checks['extensions'][$ext] = extension_loaded($ext);
        }

        // Writable directories
        $backendDir = dirname(__DIR__, 2);
        $writableDirs = ['logs', 'cache', 'temp', 'uploads'];
        foreach ($writableDirs as $dir) {
            $path = $backendDir . '/' . $dir;
            $checks['directories'][$dir] = [
                'exists' => is_dir($path),
                'writable' => is_writable($path),
            ];
        }

        // Required files
        $checks['files'] = [
            '.env' => file_exists($backendDir . '/.env'),
            'composer.json' => file_exists($backendDir . '/composer.json'),
            'vendor/autoload.php' => file_exists($backendDir . '/vendor/autoload.php'),
        ];

        // Database connection
        $checks['database'] = $this->checkDatabaseStatus();

        // Overall status
        $allPassed = $checks['php_version']['passed']
            && !in_array(false, $checks['extensions'], true)
            && $checks['files']['.env']
            && $checks['files']['vendor/autoload.php'];

        $this->log($allPassed ? 'Alle Anforderungen erfüllt' : 'Einige Anforderungen nicht erfüllt', $allPassed ? 'ok' : 'warn');

        Response::success([
            'valid' => $allPassed,
            'checks' => $checks,
            'messages' => $this->log,
        ]);
    }

    /**
     * Run database migrations
     */
    private function migrate(): void
    {
        $this->log('Führe Migrationen aus...', 'info');

        // Ensure database exists
        $this->ensureDatabaseExists();

        // Load and run migrations
        $migrationsFile = dirname(__DIR__, 2) . '/migrations/migrate.php';
        if (!file_exists($migrationsFile)) {
            throw new \RuntimeException('migrate.php nicht gefunden');
        }

        ob_start();
        require_once $migrationsFile;
        runMigrations();
        $output = ob_get_clean();

        // Parse output for log
        if ($output) {
            foreach (preg_split('/\r?\n/', trim($output)) as $line) {
                if (trim($line) !== '') {
                    $this->log(trim($line), 'info');
                }
            }
        }

        $this->log('Migrationen abgeschlossen', 'ok');
        $this->lockSetup();
        $this->sendResponse(true);
    }

    /**
     * Seed database with sample data
     */
    private function seed(): void
    {
        $this->log('Seede Beispieldaten...', 'info');

        $migrationsFile = dirname(__DIR__, 2) . '/migrations/migrate.php';
        require_once $migrationsFile;

        ob_start();
        seedDatabase();
        $output = ob_get_clean();

        if ($output) {
            foreach (preg_split('/\r?\n/', trim($output)) as $line) {
                if (trim($line) !== '') {
                    $this->log(trim($line), 'info');
                }
            }
        }

        $this->log('Seeding abgeschlossen', 'ok');
        $this->lockSetup();
        $this->sendResponse(true);
    }

    /**
     * Fresh setup: migrate + seed
     */
    private function fresh(): void
    {
        $this->log('Starte Fresh Setup (Migrate + Seed)...', 'info');

        $this->ensureDatabaseExists();

        $migrationsFile = dirname(__DIR__, 2) . '/migrations/migrate.php';
        require_once $migrationsFile;

        ob_start();
        runMigrations();
        seedDatabase();
        $output = ob_get_clean();

        if ($output) {
            foreach (preg_split('/\r?\n/', trim($output)) as $line) {
                if (trim($line) !== '') {
                    $this->log(trim($line), 'info');
                }
            }
        }

        $this->ensureDefaultAdmin();
        $this->log('Fresh Setup abgeschlossen', 'ok');
        $this->lockSetup();
        $this->sendResponse(true);
    }

    /**
     * Create or update admin user
     */
    private function createAdmin(): void
    {
        $email = $this->getParam('admin_email');
        $password = $this->getParam('admin_pass');

        if (!$email) {
            // Use defaults if no email provided
            $this->ensureDefaultAdmin();
            $this->sendResponse(true, ['admin_created' => true, 'email' => self::DEFAULT_HEAD_EMAIL]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Ungültige E-Mail-Adresse', 400);
            return;
        }

        if (!$password) {
            $password = bin2hex(random_bytes(8));
            $this->log('Kein Passwort angegeben - generiere: ' . $password, 'warn');
        }

        if (strlen($password) < 8) {
            Response::error('Passwort muss mindestens 8 Zeichen haben', 400);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $existingUser = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);

        if ($existingUser) {
            Database::execute(
                'UPDATE users SET password_hash = ?, role = "head", is_active = 1 WHERE email = ?',
                [$hash, $email]
            );
            $this->log('Benutzer zu Head-Admin aktualisiert: ' . $email, 'ok');
        } else {
            $username = explode('@', $email)[0];
            Database::execute(
                'INSERT INTO users (username, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, "head", 1, NOW())',
                [$username, $email, $hash]
            );
            $this->log('Head-Admin erstellt: ' . $email, 'ok');
        }

        $this->sendResponse(true, [
            'admin_created' => true,
            'email' => $email,
            'password' => $password,
            'warning' => 'Passwort nach erstem Login ändern!',
        ]);
    }

    /**
     * Full setup: validate, migrate, seed, create admin
     */
    private function fullSetup(): void
    {
        $this->log('=== Vollständiges Setup ===', 'info');

        // 1. Ensure database exists
        $this->log('1. Prüfe/Erstelle Datenbank...', 'info');
        $this->ensureDatabaseExists();

        // 2. Run migrations
        $this->log('2. Führe Migrationen aus...', 'info');
        $migrationsFile = dirname(__DIR__, 2) . '/migrations/migrate.php';
        require_once $migrationsFile;

        ob_start();
        runMigrations();

        // 3. Seed (unless disabled)
        $noSeed = $this->getParam('no_seed', false);
        if (!$noSeed) {
            $this->log('3. Seede Beispieldaten...', 'info');
            $alreadyHasData = false;
            try {
                $count = Database::fetchOne('SELECT COUNT(*) AS c FROM events');
                $alreadyHasData = $count && (int)$count['c'] > 0;
            } catch (Throwable) {
                // Ignore
            }

            if ($alreadyHasData && !$this->isForced) {
                $this->log('Seed übersprungen (Daten vorhanden). Nutze force=1 für erneutes Seed.', 'warn');
            } else {
                seedDatabase();
            }
        } else {
            $this->log('3. Seeding übersprungen (no_seed=1)', 'info');
        }

        $output = ob_get_clean();
        if ($output) {
            foreach (preg_split('/\r?\n/', trim($output)) as $line) {
                if (trim($line) !== '') {
                    $this->log(trim($line), 'debug');
                }
            }
        }

        // 4. Create admin
        $this->log('4. Erstelle Admin-Benutzer...', 'info');
        $adminEmail = $this->getParam('admin_email');
        $adminPass = $this->getParam('admin_pass');

        if ($adminEmail) {
            $this->createAdminUser($adminEmail, $adminPass);
        } else {
            $this->ensureDefaultAdmin();
        }

        $this->log('=== Setup abgeschlossen ===', 'ok');
        $this->lockSetup();
        $this->sendResponse(true);
    }

    /**
     * Ensure database exists, create if not
     */
    private function ensureDatabaseExists(): void
    {
        $host = Config::get('db.host');
        $port = Config::get('db.port');
        $name = Config::get('db.name');
        $user = Config::get('db.user');
        $pass = Config::get('db.pass');

        try {
            $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->query("SHOW DATABASES LIKE '" . str_replace("'", "''", $name) . "'");
            $exists = (bool)$stmt->fetchColumn();

            if (!$exists) {
                $this->log("Datenbank '$name' nicht gefunden - erstelle...", 'warn');
                $pdo->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->log('Datenbank erstellt', 'ok');
            } else {
                $this->log('Datenbank existiert bereits', 'ok');
            }
        } catch (PDOException $e) {
            throw new \RuntimeException('Datenbankfehler: ' . $e->getMessage());
        }
    }

    /**
     * Ensure default head admin exists
     */
    private function ensureDefaultAdmin(): void
    {
        try {
            $headExists = Database::fetchOne("SELECT id, email FROM users WHERE role = 'head' LIMIT 1");

            if ($headExists) {
                $this->log('Head-Admin existiert bereits: ' . $headExists['email'], 'info');
                return;
            }

            $this->createAdminUser(self::DEFAULT_HEAD_EMAIL, self::DEFAULT_HEAD_PASSWORD);
            $this->log('Standard-Admin Zugangsdaten:', 'info');
            $this->log('  E-Mail: ' . self::DEFAULT_HEAD_EMAIL, 'info');
            $this->log('  Passwort: ' . self::DEFAULT_HEAD_PASSWORD . ' (BITTE SOFORT ÄNDERN!)', 'warn');
        } catch (Throwable $e) {
            $this->log('Warnung: Admin-Erstellung fehlgeschlagen: ' . $e->getMessage(), 'warn');
        }
    }

    /**
     * Create admin user with given credentials
     */
    private function createAdminUser(string $email, ?string $password): void
    {
        if (!$password) {
            $password = bin2hex(random_bytes(8));
            $this->log('Generiertes Passwort: ' . $password, 'warn');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $existingUser = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);

        if ($existingUser) {
            Database::execute(
                'UPDATE users SET password_hash = ?, role = "head", is_active = 1 WHERE email = ?',
                [$hash, $email]
            );
            $this->log('Benutzer aktualisiert zu Head-Admin: ' . $email, 'ok');
        } else {
            $username = explode('@', $email)[0];
            Database::execute(
                'INSERT INTO users (username, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, "head", 1, NOW())',
                [$username, $email, $hash]
            );
            $this->log('Head-Admin erstellt: ' . $email, 'ok');
        }
    }

    /**
     * Check database connection status
     */
    private function checkDatabaseStatus(): array
    {
        try {
            $host = Config::get('db.host');
            $port = Config::get('db.port');
            $name = Config::get('db.name');
            $user = Config::get('db.user');
            $pass = Config::get('db.pass');

            $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->query("SHOW DATABASES LIKE '" . str_replace("'", "''", $name) . "'");
            $exists = (bool)$stmt->fetchColumn();

            return [
                'connected' => true,
                'database_exists' => $exists,
                'host' => $host,
                'database' => $name,
            ];
        } catch (PDOException $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check migration status
     */
    private function checkMigrationStatus(): array
    {
        try {
            $result = Database::fetchOne('SELECT COUNT(*) AS c FROM migrations');
            return [
                'table_exists' => true,
                'count' => (int)($result['c'] ?? 0),
            ];
        } catch (Throwable) {
            return [
                'table_exists' => false,
                'count' => 0,
            ];
        }
    }

    /**
     * Check if admin user exists
     */
    private function checkAdminExists(): bool
    {
        try {
            $result = Database::fetchOne("SELECT id FROM users WHERE role = 'head' LIMIT 1");
            return $result !== null;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Check if setup is locked
     */
    private function isLocked(): bool
    {
        $lockFile = dirname(__DIR__, 2) . '/' . self::LOCK_FILE;
        return file_exists($lockFile);
    }

    /**
     * Lock setup (production only)
     */
    private function lockSetup(): void
    {
        if ($this->isProduction) {
            $lockFile = dirname(__DIR__, 2) . '/' . self::LOCK_FILE;
            @file_put_contents($lockFile, date('c') . "\n" . 'Locked by web setup');
        }
    }

    /**
     * Get parameter from GET or POST
     */
    private function getParam(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $_POST[$key] ?? $default;
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'info'): void
    {
        $this->log[] = [
            'level' => $level,
            'message' => $message,
            'timestamp' => date('c'),
        ];
    }

    /**
     * Send JSON response
     */
    private function sendResponse(bool $success, array $extra = []): void
    {
        $response = array_merge([
            'success' => $success,
            'messages' => $this->log,
        ], $extra);

        if ($success) {
            Response::success($response);
        } else {
            Response::json($response, 500);
        }
    }
}
