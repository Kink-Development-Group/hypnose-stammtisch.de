<?php

declare(strict_types=1);

/**
 * Test Command
 * Handles all testing functionality and validation
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__ . '/../..');

class TestCommand
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
    if (!empty($args) && str_contains($args[0], 'test.php')) {
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
    echo "Test Command\n";
    echo "============\n\n";
    echo "Usage: php cli.php test <action> [options]\n\n";
    echo "Actions:\n";
    echo "  all          Run all tests\n";
    echo "  api          Test API endpoints\n";
    echo "  database     Test database functionality\n";
    echo "  events       Test event creation and management\n";
    echo "  auth         Test authentication system\n";
    echo "  contact      Test contact form\n";
    echo "  calendar     Test calendar functionality\n";
    echo "  users        Test user management\n";
    echo "  autoload     Test autoloader functionality\n\n";
    echo "Options:\n";
    echo "  --verbose    Show detailed output\n";
    echo "  --stop       Stop on first failure\n";
    echo "  --format     Output format (text/json)\n\n";
    echo "Examples:\n";
    echo "  php cli.php test all --verbose\n";
    echo "  php cli.php test api --stop\n";
    echo "  php cli.php test database\n\n";
  }

  public function run(): void
  {
    try {
      switch ($this->action) {
        case 'all':
          $this->runAllTests();
          break;
        case 'api':
          $this->testAPI();
          break;
        case 'database':
          $this->testDatabase();
          break;
        case 'events':
          $this->testEvents();
          break;
        case 'auth':
          $this->testAuth();
          break;
        case 'contact':
          $this->testContact();
          break;
        case 'calendar':
          $this->testCalendar();
          break;
        case 'users':
          $this->testUsers();
          break;
        case 'autoload':
          $this->testAutoload();
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

  private function runAllTests(): void
  {
    $this->output("Running comprehensive test suite...", 'info');
    $this->output(str_repeat('=', 50), 'info');

    $testSuites = [
      'Autoload' => [$this, 'testAutoload'],
      'Database' => [$this, 'testDatabase'],
      'Authentication' => [$this, 'testAuth'],
      'Events' => [$this, 'testEvents'],
      'Users' => [$this, 'testUsers'],
      'Contact' => [$this, 'testContact'],
      'Calendar' => [$this, 'testCalendar'],
      'API' => [$this, 'testAPI'],
    ];

    $results = [];
    $totalTests = 0;
    $totalPassed = 0;

    foreach ($testSuites as $suiteName => $testFunction) {
      $this->output("\n[{$suiteName}]", 'info');

      try {
        $result = call_user_func($testFunction);
        $results[$suiteName] = $result;
        $totalTests += $result['total'];
        $totalPassed += $result['passed'];

        $status = $result['passed'] === $result['total'] ? 'PASS' : 'FAIL';
        $color = $result['passed'] === $result['total'] ? 'success' : 'error';
        $this->output("  Result: {$status} ({$result['passed']}/{$result['total']})", $color);

        if ($this->options['stop'] ?? false && $result['passed'] !== $result['total']) {
          break;
        }
      } catch (Exception $e) {
        $this->output("  Error: " . $e->getMessage(), 'error');
        $results[$suiteName] = ['total' => 1, 'passed' => 0, 'error' => $e->getMessage()];
        $totalTests++;

        if ($this->options['stop'] ?? false) {
          break;
        }
      }
    }

    // Summary
    $this->output("\nTest Summary", 'info');
    $this->output(str_repeat('=', 50), 'info');
    $this->output("Total Tests: {$totalTests}", 'info');
    $this->output("Passed: {$totalPassed}", 'success');
    $this->output("Failed: " . ($totalTests - $totalPassed), $totalPassed === $totalTests ? 'info' : 'error');
    $this->output("Success Rate: " . round(($totalPassed / $totalTests) * 100, 1) . "%", 'info');

    if ($this->options['format'] === 'json') {
      echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
    }
  }

  private function testAutoload(): array
  {
    $tests = [
      'Autoloader exists' => function () {
        return file_exists(__DIR__ . '/../../vendor/autoload.php');
      },
      'Config class loads' => function () {
        return class_exists('HypnoseStammtisch\Config\Config');
      },
      'Database class loads' => function () {
        return class_exists('HypnoseStammtisch\Database\Database');
      },
      'Controllers load' => function () {
        return class_exists('HypnoseStammtisch\Controllers\AdminAuthController');
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function testDatabase(): array
  {
    $tests = [
      'Database connection' => function () {
        Database::getConnection();
        return true;
      },
      'Select query' => function () {
        $result = Database::fetchOne("SELECT 1 as test");
        return $result['test'] == 1;
      },
      'Required tables exist' => function () {
        $tables = ['users', 'events', 'contact_submissions', 'migrations'];
        foreach ($tables as $table) {
          $exists = Database::fetchOne("SHOW TABLES LIKE ?", [$table]);
          if (!$exists) return false;
        }
        return true;
      },
      'Transaction support' => function () {
        Database::beginTransaction();
        Database::rollback();
        return true;
      },
      'Prepared statements' => function () {
        $result = Database::fetchOne("SELECT ? as test", ['hello']);
        return $result['test'] === 'hello';
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function testAuth(): array
  {
    $tests = [
      'Admin users exist' => function () {
        $admin = Database::fetchOne("SELECT id FROM users WHERE role IN ('head', 'admin') LIMIT 1");
        return !empty($admin);
      },
      'Password hashing' => function () {
        $hash = password_hash('test123', PASSWORD_DEFAULT);
        return password_verify('test123', $hash);
      },
      'Session handling' => function () {
        if (session_status() === PHP_SESSION_NONE) {
          session_start();
        }
        $_SESSION['test'] = 'value';
        return $_SESSION['test'] === 'value';
      },
      'Role validation' => function () {
        $validRoles = ['head', 'admin', 'moderator'];
        $invalidUser = Database::fetchOne("SELECT id FROM users WHERE role NOT IN (?, ?, ?)", $validRoles);
        return empty($invalidUser);
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function testEvents(): array
  {
    $tests = [
      'Events table structure' => function () {
        $columns = Database::fetchAll("DESCRIBE events");
        $required = ['id', 'title', 'slug', 'start_datetime', 'end_datetime', 'status'];
        $existing = array_column($columns, 'Field');

        foreach ($required as $col) {
          if (!in_array($col, $existing)) return false;
        }
        return true;
      },
      'Event creation' => function () {
        $testEvent = [
          'title' => 'Test Event ' . time(),
          'slug' => 'test-event-' . time(),
          'description' => 'Test event description',
          'start_datetime' => date('Y-m-d H:i:s', strtotime('+1 week')),
          'end_datetime' => date('Y-m-d H:i:s', strtotime('+1 week +2 hours')),
          'status' => 'draft',
          'category' => 'workshop',
          'difficulty_level' => 'beginner',
          'location_type' => 'physical',
          'max_participants' => 10,
          'requires_registration' => 1
        ];

        $columns = implode(', ', array_keys($testEvent));
        $placeholders = ':' . implode(', :', array_keys($testEvent));
        Database::execute("INSERT INTO events ({$columns}) VALUES ({$placeholders})", $testEvent);
        $id = Database::getConnection()->lastInsertId();

        // Cleanup
        Database::execute("DELETE FROM events WHERE id = ?", [$id]);

        return $id > 0;
      },
      'Event validation' => function () {
        // Check for events with invalid dates
        $invalid = Database::fetchOne("SELECT COUNT(*) as count FROM events WHERE start_datetime > end_datetime");
        return $invalid['count'] == 0;
      },
      'Event registration table' => function () {
        $table = Database::fetchOne("SHOW TABLES LIKE 'event_registrations'");
        return !empty($table);
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function testUsers(): array
  {
    $tests = [
      'Users table exists' => function () {
        $table = Database::fetchOne("SHOW TABLES LIKE 'users'");
        return !empty($table);
      },
      'Head admin exists' => function () {
        $head = Database::fetchOne("SELECT id FROM users WHERE role = 'head' LIMIT 1");
        return !empty($head);
      },
      'User roles valid' => function () {
        $validRoles = ['head', 'admin', 'moderator'];
        $invalid = Database::fetchOne("SELECT COUNT(*) as count FROM users WHERE role NOT IN (?, ?, ?)", $validRoles);
        return $invalid['count'] == 0;
      },
      'Email uniqueness' => function () {
        $duplicates = Database::fetchOne("
                    SELECT COUNT(*) as count
                    FROM (
                        SELECT email, COUNT(*) as cnt
                        FROM users
                        GROUP BY email
                        HAVING cnt > 1
                    ) as dups
                ");
        return $duplicates['count'] == 0;
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function testContact(): array
  {
    $tests = [
      'Contact table exists' => function () {
        $table = Database::fetchOne("SHOW TABLES LIKE 'contact_submissions'");
        return !empty($table);
      },
      'Contact submission' => function () {
        $submission = [
          'name' => 'Test User',
          'email' => 'test@example.com',
          'subject' => 'Test Subject',
          'message' => 'Test message content',
          'status' => 'new',
          'created_at' => date('Y-m-d H:i:s')
        ];

        $columns = implode(', ', array_keys($submission));
        $placeholders = ':' . implode(', :', array_keys($submission));
        $id = Database::execute("INSERT INTO contact_submissions ({$columns}) VALUES ({$placeholders})", $submission);

        // Cleanup
        Database::execute("DELETE FROM contact_submissions WHERE id = ?", [$id]);

        return $id > 0;
      },
      'Email validation format' => function () {
        $invalidEmails = Database::fetchOne("
                    SELECT COUNT(*) as count
                    FROM contact_submissions
                    WHERE email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'
                ");
        return $invalidEmails['count'] == 0;
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function testCalendar(): array
  {
    $tests = [
      'Calendar tokens table' => function () {
        $table = Database::fetchOne("SHOW TABLES LIKE 'calendar_feed_tokens'");
        return !empty($table);
      },
      'Default token exists' => function () {
        $token = Database::fetchOne("SELECT id FROM calendar_feed_tokens LIMIT 1");
        return !empty($token);
      },
      'Token uniqueness' => function () {
        $duplicates = Database::fetchOne("
                    SELECT COUNT(*) as count
                    FROM (
                        SELECT token, COUNT(*) as cnt
                        FROM calendar_feed_tokens
                        GROUP BY token
                        HAVING cnt > 1
                    ) as dups
                ");
        return $duplicates['count'] == 0;
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function testAPI(): array
  {
    $tests = [
      'API files exist' => function () {
        $files = ['index.php', 'admin.php'];
        foreach ($files as $file) {
          if (!file_exists(__DIR__ . '/../../api/' . $file)) {
            return false;
          }
        }
        return true;
      },
      'Controllers exist' => function () {
        $controllers = [
          'EventsController',
          'ContactController',
          'CalendarController',
          'AdminAuthController'
        ];

        foreach ($controllers as $controller) {
          if (!class_exists("HypnoseStammtisch\\Controllers\\{$controller}")) {
            return false;
          }
        }
        return true;
      },
      'Middleware exists' => function () {
        return class_exists('HypnoseStammtisch\Middleware\AdminAuth');
      },
      'Models exist' => function () {
        return class_exists('HypnoseStammtisch\Models\Event');
      },
    ];

    return $this->runTestSuite($tests);
  }

  private function runTestSuite(array $tests): array
  {
    $passed = 0;
    $total = count($tests);

    foreach ($tests as $testName => $testFunction) {
      try {
        $result = call_user_func($testFunction);

        if ($result) {
          $this->output("  ✓ {$testName}", 'success');
          $passed++;
        } else {
          $this->output("  ✗ {$testName}: Failed", 'error');
        }

        if ($this->options['verbose'] ?? false) {
          $this->output("    Result: " . ($result ? 'PASS' : 'FAIL'), 'debug');
        }
      } catch (Exception $e) {
        $this->output("  ✗ {$testName}: " . $e->getMessage(), 'error');

        if ($this->options['verbose'] ?? false) {
          $this->output("    Exception: " . $e->getTraceAsString(), 'debug');
        }
      }
    }

    return ['total' => $total, 'passed' => $passed];
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
$test = new TestCommand($argv);
$test->run();
