<?php

declare(strict_types=1);

/**
 * Database Management Command
 * Handles database utilities, backup, restore, and maintenance
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__ . '/../..');

class DatabaseCommand
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
    if (!empty($args) && str_contains($args[0], 'database.php')) {
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
    echo "Database Management Command\n";
    echo "==========================\n\n";
    echo "Usage: php cli.php database <action> [options]\n\n";
    echo "Actions:\n";
    echo "  status      Show database connection status and info\n";
    echo "  check       Check database tables and structure\n";
    echo "  backup      Create database backup\n";
    echo "  restore     Restore database from backup\n";
    echo "  cleanup     Clean up old data and optimize\n";
    echo "  reset       Reset database (danger!)\n";
    echo "  tables      List all tables\n";
    echo "  size        Show database and table sizes\n";
    echo "  test        Test database connection and queries\n\n";
    echo "Options:\n";
    echo "  --file PATH      Backup/restore file path\n";
    echo "  --table TEXT     Specific table name\n";
    echo "  --force          Force operation without confirmation\n";
    echo "  --verbose        Show detailed output\n";
    echo "  --days INT       Number of days for cleanup (default: 30)\n\n";
    echo "Examples:\n";
    echo "  php cli.php database status\n";
    echo "  php cli.php database backup --file backup.sql\n";
    echo "  php cli.php database cleanup --days 7\n\n";
  }

  public function run(): void
  {
    try {
      switch ($this->action) {
        case 'status':
          $this->showStatus();
          break;
        case 'check':
          $this->checkDatabase();
          break;
        case 'backup':
          $this->backupDatabase();
          break;
        case 'restore':
          $this->restoreDatabase();
          break;
        case 'cleanup':
          $this->cleanupDatabase();
          break;
        case 'reset':
          $this->resetDatabase();
          break;
        case 'tables':
          $this->listTables();
          break;
        case 'size':
          $this->showSizes();
          break;
        case 'test':
          $this->testDatabase();
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

  private function showStatus(): void
  {
    $this->output("Database Status", 'info');
    $this->output(str_repeat('=', 40), 'info');

    try {
      $connection = Database::getConnection();
      $this->output("Connection: OK", 'success');

      // Database info
      $dbInfo = Database::fetchOne("SELECT DATABASE() as db_name, VERSION() as version");
      $this->output("Database: {$dbInfo['db_name']}", 'info');
      $this->output("MySQL Version: {$dbInfo['version']}", 'info');

      // Connection info
      $charset = Database::fetchOne("SELECT @@character_set_database as charset, @@collation_database as collation");
      $this->output("Charset: {$charset['charset']}", 'info');
      $this->output("Collation: {$charset['collation']}", 'info');

      // Table count
      $tableCount = Database::fetchOne("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()");
      $this->output("Tables: {$tableCount['count']}", 'info');

      // Check required tables
      $requiredTables = ['users', 'events', 'event_registrations', 'contact_submissions', 'migrations'];
      $this->output("\nRequired Tables:", 'info');

      foreach ($requiredTables as $table) {
        $exists = Database::fetchOne("SHOW TABLES LIKE '{$table}'");
        $status = $exists ? 'EXISTS' : 'MISSING';
        $color = $exists ? 'success' : 'error';
        $this->output("  {$table}: {$status}", $color);
      }
    } catch (Exception $e) {
      $this->output("Connection: FAILED", 'error');
      $this->output("Error: " . $e->getMessage(), 'error');
    }
  }

  private function checkDatabase(): void
  {
    $this->output("Checking database integrity...", 'info');

    $issues = [];

    // Check table structure
    $tables = Database::fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
      $tableName = array_values($table)[0];

      try {
        // Check table status
        $status = Database::fetchOne("CHECK TABLE `{$tableName}`");
        if ($status['Msg_text'] !== 'OK') {
          $issues[] = "Table {$tableName}: {$status['Msg_text']}";
        }

        // Analyze table
        $analyze = Database::fetchOne("ANALYZE TABLE `{$tableName}`");
        if ($analyze['Msg_text'] !== 'OK' && $analyze['Msg_text'] !== 'Table is already up to date') {
          $issues[] = "Analyze {$tableName}: {$analyze['Msg_text']}";
        }
      } catch (Exception $e) {
        $issues[] = "Error checking {$tableName}: " . $e->getMessage();
      }
    }

    // Check for orphaned records
    $this->checkOrphanedRecords($issues);

    // Check for data consistency
    $this->checkDataConsistency($issues);

    if (empty($issues)) {
      $this->output("Database check completed: No issues found", 'success');
    } else {
      $this->output("Database check completed: " . count($issues) . " issues found", 'warning');
      foreach ($issues as $issue) {
        $this->output("  - {$issue}", 'error');
      }
    }
  }

  private function checkOrphanedRecords(array &$issues): void
  {
    // Check for orphaned event registrations
    $orphaned = Database::fetchOne("
            SELECT COUNT(*) as count
            FROM event_registrations er
            LEFT JOIN events e ON er.event_id = e.id
            WHERE e.id IS NULL
        ");

    if ($orphaned['count'] > 0) {
      $issues[] = "Found {$orphaned['count']} orphaned event registrations";
    }
  }

  private function checkDataConsistency(array &$issues): void
  {
    // Check for events with invalid dates
    $invalidDates = Database::fetchOne("
            SELECT COUNT(*) as count
            FROM events
            WHERE start_datetime > end_datetime
        ");

    if ($invalidDates['count'] > 0) {
      $issues[] = "Found {$invalidDates['count']} events with invalid date ranges";
    }

    // Check for users without proper roles
    $invalidRoles = Database::fetchOne("
            SELECT COUNT(*) as count
            FROM users
            WHERE role NOT IN ('head', 'admin', 'moderator')
        ");

    if ($invalidRoles['count'] > 0) {
      $issues[] = "Found {$invalidRoles['count']} users with invalid roles";
    }
  }

  private function backupDatabase(): void
  {
    $filename = $this->options['file'] ?? 'backup_' . date('Y-m-d_H-i-s') . '.sql';

    if (!str_ends_with($filename, '.sql')) {
      $filename .= '.sql';
    }

    $this->output("Creating database backup: {$filename}", 'info');

    $dbConfig = [
      'host' => $_ENV['DB_HOST'],
      'username' => $_ENV['DB_USERNAME'],
      'password' => $_ENV['DB_PASSWORD'],
      'database' => $_ENV['DB_DATABASE']
    ];

    $command = sprintf(
      'mysqldump -h%s -u%s -p%s %s > %s',
      escapeshellarg($dbConfig['host']),
      escapeshellarg($dbConfig['username']),
      escapeshellarg($dbConfig['password']),
      escapeshellarg($dbConfig['database']),
      escapeshellarg($filename)
    );

    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
      $size = filesize($filename);
      $this->output("Backup created successfully: {$filename} (" . $this->formatBytes($size) . ")", 'success');
    } else {
      throw new RuntimeException("Backup failed with return code: {$returnCode}");
    }
  }

  private function restoreDatabase(): void
  {
    $filename = $this->options['file'] ?? null;

    if (!$filename) {
      throw new InvalidArgumentException("Backup file must be specified with --file option");
    }

    if (!file_exists($filename)) {
      throw new InvalidArgumentException("Backup file not found: {$filename}");
    }

    if (!$this->options['force'] ?? false) {
      $this->output("WARNING: This will overwrite the current database!", 'error');
      echo "Are you sure you want to continue? [y/N]: ";
      $confirm = trim(fgets(STDIN));

      if (strtolower($confirm) !== 'y' && strtolower($confirm) !== 'yes') {
        $this->output("Operation cancelled.", 'info');
        return;
      }
    }

    $this->output("Restoring database from: {$filename}", 'info');

    $dbConfig = [
      'host' => $_ENV['DB_HOST'],
      'username' => $_ENV['DB_USERNAME'],
      'password' => $_ENV['DB_PASSWORD'],
      'database' => $_ENV['DB_DATABASE']
    ];

    $command = sprintf(
      'mysql -h%s -u%s -p%s %s < %s',
      escapeshellarg($dbConfig['host']),
      escapeshellarg($dbConfig['username']),
      escapeshellarg($dbConfig['password']),
      escapeshellarg($dbConfig['database']),
      escapeshellarg($filename)
    );

    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
      $this->output("Database restored successfully from: {$filename}", 'success');
    } else {
      throw new RuntimeException("Restore failed with return code: {$returnCode}");
    }
  }

  private function cleanupDatabase(): void
  {
    $days = (int)($this->options['days'] ?? 30);

    $this->output("Cleaning up database (older than {$days} days)...", 'info');

    $cleanupDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // Clean old contact submissions
    $deletedContacts = Database::execute(
      "DELETE FROM contact_submissions WHERE created_at < ? AND status = 'resolved'",
      [$cleanupDate]
    );

    if ($deletedContacts > 0) {
      $this->output("Deleted {$deletedContacts} old contact submissions", 'info');
    }

    // Clean old event registrations for past events
    $deletedRegistrations = Database::execute("
            DELETE er FROM event_registrations er
            JOIN events e ON er.event_id = e.id
            WHERE e.end_datetime < ?
        ", [$cleanupDate]);

    if ($deletedRegistrations > 0) {
      $this->output("Deleted {$deletedRegistrations} old event registrations", 'info');
    }

    // Optimize tables
    $this->output("Optimizing tables...", 'info');
    $tables = Database::fetchAll("SHOW TABLES");

    foreach ($tables as $table) {
      $tableName = array_values($table)[0];
      Database::getConnection()->exec("OPTIMIZE TABLE `{$tableName}`");
    }

    $this->output("Database cleanup completed", 'success');
  }

  private function resetDatabase(): void
  {
    if (!$this->options['force'] ?? false) {
      $this->output("WARNING: This will delete ALL data in the database!", 'error');
      echo "Type 'DELETE ALL DATA' to confirm: ";
      $confirm = trim(fgets(STDIN));

      if ($confirm !== 'DELETE ALL DATA') {
        $this->output("Operation cancelled.", 'info');
        return;
      }
    }

    $this->output("Resetting database...", 'warning');

    // Drop all tables
    $connection = Database::getConnection();
    $connection->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = Database::fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
      $tableName = array_values($table)[0];
      $connection->exec("DROP TABLE IF EXISTS `{$tableName}`");
    }

    $connection->exec("SET FOREIGN_KEY_CHECKS = 1");

    $this->output("Database reset completed", 'success');
    $this->output("Run 'php cli.php migrate' to recreate tables", 'info');
  }

  private function listTables(): void
  {
    $tables = Database::fetchAll("
            SELECT
                TABLE_NAME as name,
                ENGINE as engine,
                TABLE_ROWS as rows,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY TABLE_NAME
        ");

    if (empty($tables)) {
      $this->output("No tables found", 'info');
      return;
    }

    $this->output("Database Tables:", 'info');
    $this->output(str_repeat('=', 60), 'info');

    foreach ($tables as $table) {
      $this->output(sprintf(
        "%-25s %-10s %8s rows %8s MB",
        $table['name'],
        $table['engine'],
        number_format($table['rows']),
        $table['size_mb']
      ), 'info');
    }
  }

  private function showSizes(): void
  {
    // Database size
    $dbSize = Database::fetchOne("
            SELECT
                ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
        ");

    $this->output("Database Size: {$dbSize['size_mb']} MB", 'info');
    $this->output(str_repeat('=', 40), 'info');

    // Table sizes
    $tables = Database::fetchAll("
            SELECT
                TABLE_NAME as name,
                ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_mb,
                ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_mb,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as total_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
        ");

    foreach ($tables as $table) {
      $this->output(sprintf(
        "%-25s Data: %6s MB  Index: %6s MB  Total: %6s MB",
        $table['name'],
        $table['data_mb'],
        $table['index_mb'],
        $table['total_mb']
      ), 'info');
    }
  }

  private function testDatabase(): void
  {
    $this->output("Testing database operations...", 'info');

    $tests = [
      'Connection' => function () {
        Database::getConnection();
        return "OK";
      },
      'Select Query' => function () {
        $result = Database::fetchOne("SELECT 1 as test");
        return $result['test'] == 1 ? "OK" : "FAILED";
      },
      'Table Access' => function () {
        Database::fetchOne("SELECT COUNT(*) as count FROM migrations");
        return "OK";
      },
      'Transaction' => function () {
        Database::beginTransaction();
        Database::rollback();
        return "OK";
      }
    ];

    foreach ($tests as $testName => $test) {
      try {
        $result = $test();
        $this->output("  {$testName}: {$result}", 'success');
      } catch (Exception $e) {
        $this->output("  {$testName}: FAILED - " . $e->getMessage(), 'error');
      }
    }

    $this->output("Database tests completed", 'info');
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
$database = new DatabaseCommand($argv);
$database->run();
