<?php

declare(strict_types=1);

/**
 * Development Utilities Command
 * Handles development server, testing, and debugging tools
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__ . '/../..');

class DevelopmentCommand
{
  private string $action = '';
  private array $options = [];

  public function __construct(array $args)
  {
    $this->parseArguments($args);
  }

  private function parseArguments(array $args): void
  {
    // Remove the script name from args if it's there
    if (!empty($args) && str_contains($args[0], 'dev.php')) {
      array_shift($args);
    }

    if (empty($args) || in_array($args[0], ['--help', '-h'])) {
      $this->showHelp();
      exit(0);
    }

    $this->action = array_shift($args);

    // Parse options
    for ($i = 0; $i < count($args); $i++) {
      $arg = $args[$i];

      if (str_starts_with($arg, '--')) {
        $key = substr($arg, 2);
        $value = isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '--')
          ? $args[++$i]
          : true;
        $this->options[$key] = $value;
      }
    }
  }
  private function showHelp(): void
  {
    echo "Development Utilities Command\n";
    echo "============================\n\n";
    echo "Usage: php cli.php dev <action> [options]\n\n";
    echo "Actions:\n";
    echo "  serve        Start development server\n";
    echo "  test         Run API tests\n";
    echo "  debug        Debug mode utilities\n";
    echo "  logs         View application logs\n";
    echo "  config       Show configuration\n";
    echo "  routes       List API routes\n";
    echo "  clear        Clear caches and temp files\n";
    echo "  validate     Validate system requirements\n\n";
    echo "Options:\n";
    echo "  --port INT     Server port (default: 8000)\n";
    echo "  --host TEXT    Server host (default: localhost)\n";
    echo "  --env TEXT     Environment (dev/prod)\n";
    echo "  --verbose      Show detailed output\n";
    echo "  --tail         Follow log file in real-time\n\n";
    echo "Examples:\n";
    echo "  php cli.php dev serve --port 8080\n";
    echo "  php cli.php dev test --verbose\n";
    echo "  php cli.php dev logs --tail\n\n";
  }

  public function run(): void
  {
    try {
      switch ($this->action) {
        case 'serve':
          $this->startDevServer();
          break;
        case 'test':
          $this->runTests();
          break;
        case 'debug':
          $this->debugMode();
          break;
        case 'logs':
          $this->viewLogs();
          break;
        case 'config':
          $this->showConfig();
          break;
        case 'routes':
          $this->listRoutes();
          break;
        case 'clear':
          $this->clearCaches();
          break;
        case 'validate':
          $this->validateSystem();
          break;
        default:
          $this->output("Unknown action: {$this->action}", 'error');
          $this->showHelp();
          exit(1);
      }
    } catch (Exception $e) {
      $this->output("Error: " . $e->getMessage(), 'error');
      exit(1);
    }
  }

  private function startDevServer(): void
  {
    $port = $this->options['port'] ?? 8000;
    $host = $this->options['host'] ?? 'localhost';
    $documentRoot = __DIR__ . '/../../api';
    $router = $documentRoot . '/router.php';

    $this->output("Starting development server...", 'info');
    $this->output("Server: http://{$host}:{$port}", 'success');
    $this->output("Document root: {$documentRoot}", 'info');
    $this->output("Press Ctrl+C to stop", 'info');

    if (!file_exists($router)) {
      $this->output('Router script missing at ' . $router, 'error');
      exit(1);
    }

    // Start PHP built-in server with router to mirror .htaccess behavior
    $command = sprintf(
      'php -S %s -t %s %s',
      $this->escapeForShell($host . ':' . $port),
      $this->escapeForShell($documentRoot),
      $this->escapeForShell($router)
    );

    system($command);
  }

  /**
   * Escape shell arguments cross-platform (CMD vs. POSIX)
   */
  private function escapeForShell(string $value): string
  {
    if (DIRECTORY_SEPARATOR === '\\') {
      // Windows cmd.exe uses double quotes for escaping
      return "\"" . str_replace("\"", "\"\"", $value) . "\"";
    }

    return escapeshellarg($value);
  }

  private function runTests(): void
  {
    $this->output("Running API tests...", 'info');

    $tests = [
      'Database Connection' => [$this, 'testDatabaseConnection'],
      'Admin Authentication' => [$this, 'testAdminAuth'],
      'Events API' => [$this, 'testEventsAPI'],
      'Contact API' => [$this, 'testContactAPI'],
      'Calendar API' => [$this, 'testCalendarAPI'],
    ];

    $passed = 0;
    $failed = 0;

    foreach ($tests as $testName => $testFunction) {
      try {
        $result = call_user_func($testFunction);
        if ($result) {
          $this->output("  ✓ {$testName}", 'success');
          $passed++;
        } else {
          $this->output("  ✗ {$testName}: Failed", 'error');
          $failed++;
        }
      } catch (Exception $e) {
        $this->output("  ✗ {$testName}: " . $e->getMessage(), 'error');
        $failed++;
      }
    }

    $this->output("\nTest Results:", 'info');
    $this->output("Passed: {$passed}", 'success');
    $this->output("Failed: {$failed}", $failed > 0 ? 'error' : 'info');
  }

  private function testDatabaseConnection(): bool
  {
    Database::getConnection();
    $result = Database::fetchOne("SELECT 1 as test");
    return $result['test'] == 1;
  }

  private function testAdminAuth(): bool
  {
    // Test admin user exists
    $admin = Database::fetchOne("SELECT id FROM users WHERE role = 'head' LIMIT 1");
    return !empty($admin);
  }

  private function testEventsAPI(): bool
  {
    // Test events table structure
    $columns = Database::fetchAll("DESCRIBE events");
    $requiredColumns = ['id', 'title', 'slug', 'start_datetime', 'end_datetime'];

    $existingColumns = array_column($columns, 'Field');
    foreach ($requiredColumns as $required) {
      if (!in_array($required, $existingColumns)) {
        return false;
      }
    }

    return true;
  }

  private function testContactAPI(): bool
  {
    // Test contact submissions table
    $table = Database::fetchOne("SHOW TABLES LIKE 'contact_submissions'");
    return !empty($table);
  }

  private function testCalendarAPI(): bool
  {
    // Test calendar tokens table
    $table = Database::fetchOne("SHOW TABLES LIKE 'calendar_feed_tokens'");
    return !empty($table);
  }

  private function debugMode(): void
  {
    $this->output("Debug Information", 'info');
    $this->output(str_repeat('=', 40), 'info');

    // PHP Info
    $this->output("PHP Version: " . PHP_VERSION, 'info');
    $this->output("PHP SAPI: " . php_sapi_name(), 'info');

    // Memory usage
    $memoryUsage = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    $this->output("Memory Usage: " . $this->formatBytes($memoryUsage), 'info');
    $this->output("Memory Peak: " . $this->formatBytes($memoryPeak), 'info');

    // Environment variables
    $this->output("\nEnvironment Variables:", 'info');
    $envVars = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'SMTP_HOST', 'SMTP_PORT'];

    foreach ($envVars as $var) {
      $value = $_ENV[$var] ?? 'NOT SET';
      if ($var === 'DB_PASSWORD' && $value !== 'NOT SET') {
        $value = '***';
      }
      $this->output("  {$var}: {$value}", 'info');
    }

    // Database status
    $this->output("\nDatabase Status:", 'info');
    try {
      $connection = Database::getConnection();
      $this->output("  Connection: OK", 'success');

      $dbInfo = Database::fetchOne("SELECT DATABASE() as db, VERSION() as version");
      $this->output("  Database: {$dbInfo['db']}", 'info');
      $this->output("  Version: {$dbInfo['version']}", 'info');
    } catch (Exception $e) {
      $this->output("  Connection: FAILED - " . $e->getMessage(), 'error');
    }

    // File permissions
    $this->output("\nFile Permissions:", 'info');
    $checkDirs = [
      __DIR__ . '/../../api' => 'API Directory',
      __DIR__ . '/../../migrations' => 'Migrations Directory',
      __DIR__ . '/../../config' => 'Config Directory'
    ];

    foreach ($checkDirs as $dir => $name) {
      if (is_dir($dir)) {
        $readable = is_readable($dir) ? 'R' : '-';
        $writable = is_writable($dir) ? 'W' : '-';
        $this->output("  {$name}: {$readable}{$writable}", 'info');
      } else {
        $this->output("  {$name}: NOT FOUND", 'error');
      }
    }
  }

  private function viewLogs(): void
  {
    $logFile = __DIR__ . '/../../logs/app.log';

    if (!file_exists($logFile)) {
      $this->output("Log file not found: {$logFile}", 'warning');
      return;
    }

    if ($this->options['tail'] ?? false) {
      $this->output("Following log file... (Press Ctrl+C to stop)", 'info');
      system("tail -f " . escapeshellarg($logFile));
    } else {
      $lines = (int)($this->options['lines'] ?? 50);
      $this->output("Last {$lines} lines from log file:", 'info');
      system("tail -n {$lines} " . escapeshellarg($logFile));
    }
  }

  private function showConfig(): void
  {
    $this->output("Configuration", 'info');
    $this->output(str_repeat('=', 40), 'info');

    // Database config
    $this->output("Database:", 'info');
    $this->output("  Host: " . ($_ENV['DB_HOST'] ?? 'NOT SET'), 'info');
    $this->output("  Database: " . ($_ENV['DB_DATABASE'] ?? 'NOT SET'), 'info');
    $this->output("  Username: " . ($_ENV['DB_USERNAME'] ?? 'NOT SET'), 'info');

    // Email config
    $this->output("\nEmail:", 'info');
    $this->output("  SMTP Host: " . ($_ENV['SMTP_HOST'] ?? 'NOT SET'), 'info');
    $this->output("  SMTP Port: " . ($_ENV['SMTP_PORT'] ?? 'NOT SET'), 'info');
    $this->output("  From Email: " . ($_ENV['FROM_EMAIL'] ?? 'NOT SET'), 'info');

    // Application config
    $this->output("\nApplication:", 'info');
    $this->output("  Environment: " . ($_ENV['APP_ENV'] ?? 'development'), 'info');
    $this->output("  Debug: " . ($_ENV['APP_DEBUG'] ?? 'true'), 'info');
    $this->output("  Base URL: " . ($_ENV['BASE_URL'] ?? 'NOT SET'), 'info');
  }

  private function listRoutes(): void
  {
    $this->output("API Routes", 'info');
    $this->output(str_repeat('=', 60), 'info');

    $routes = [
      'GET    /api/' => 'API Info',
      'GET    /api/events' => 'List Events',
      'POST   /api/events' => 'Create Event (Admin)',
      'PUT    /api/events/{id}' => 'Update Event (Admin)',
      'DELETE /api/events/{id}' => 'Delete Event (Admin)',
      'GET    /api/calendar/feed' => 'Calendar Feed',
      'POST   /api/contact' => 'Contact Form',
      'POST   /api/admin/login' => 'Admin Login',
      'POST   /api/admin/logout' => 'Admin Logout',
      'GET    /api/admin/events' => 'Admin Events List',
      'GET    /api/admin/messages' => 'Admin Messages',
      'GET    /api/admin/users' => 'Admin Users (Head only)',
      'POST   /api/admin/users' => 'Create Admin User (Head only)',
      'PUT    /api/admin/users/{id}' => 'Update Admin User (Head only)',
      'DELETE /api/admin/users/{id}' => 'Delete Admin User (Head only)',
    ];

    foreach ($routes as $route => $description) {
      $this->output(sprintf("%-30s %s", $route, $description), 'info');
    }
  }

  private function clearCaches(): void
  {
    $this->output("Clearing caches and temporary files...", 'info');

    $cacheDirs = [
      __DIR__ . '/../../cache',
      __DIR__ . '/../../temp',
      __DIR__ . '/../../logs'
    ];

    foreach ($cacheDirs as $dir) {
      if (is_dir($dir)) {
        $files = glob($dir . '/*');
        $count = 0;

        foreach ($files as $file) {
          if (is_file($file) && basename($file) !== '.gitkeep') {
            unlink($file);
            $count++;
          }
        }

        if ($count > 0) {
          $this->output("Cleared {$count} files from " . basename($dir), 'info');
        }
      }
    }

    $this->output("Cache cleared successfully", 'success');
  }

  private function validateSystem(): void
  {
    $this->output("System Validation", 'info');
    $this->output(str_repeat('=', 40), 'info');

    $checks = [
      'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
      'PDO Extension' => extension_loaded('pdo'),
      'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
      'cURL Extension' => extension_loaded('curl'),
      'JSON Extension' => extension_loaded('json'),
      'OpenSSL Extension' => extension_loaded('openssl'),
      'Config Directory Readable' => is_readable(__DIR__ . '/../../config'),
      'API Directory Readable' => is_readable(__DIR__ . '/../../api'),
      'Database Connection' => $this->testDatabaseConnection(),
    ];

    $allPassed = true;

    foreach ($checks as $check => $result) {
      $status = $result ? 'PASS' : 'FAIL';
      $color = $result ? 'success' : 'error';
      $this->output(sprintf("  %-30s %s", $check, $status), $color);

      if (!$result) {
        $allPassed = false;
      }
    }

    $this->output("\nValidation " . ($allPassed ? 'PASSED' : 'FAILED'), $allPassed ? 'success' : 'error');

    if (!$allPassed) {
      $this->output("Please fix the failed checks before proceeding", 'warning');
    }
  }

  private function formatBytes(int $bytes): string
  {
    $units = ['B', 'KB', 'MB', 'GB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
      $bytes /= 1024;
    }

    return round($bytes, 2) . ' ' . $units[$i];
  }

  private function output(string $message, string $type = 'info'): void
  {
    $colors = [
      'info' => "\033[36m",    // Cyan
      'success' => "\033[32m", // Green
      'warning' => "\033[33m", // Yellow
      'error' => "\033[31m",   // Red
      'debug' => "\033[37m",   // White
    ];

    $reset = "\033[0m";
    $color = $colors[$type] ?? $colors['info'];

    echo $color . $message . $reset . "\n";
  }
}

// Execute command
$dev = new DevelopmentCommand($argv);
$dev->run();
