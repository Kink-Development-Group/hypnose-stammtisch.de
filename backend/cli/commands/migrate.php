<?php

declare(strict_types=1);

/**
 * Database Migration Command
 * Handles all database migration operations
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__ . '/../..');

class MigrationCommand
{
  private bool $fresh = false;
  private bool $seed = false;
  private bool $verbose = false;

  public function __construct(array $args)
  {
    $this->parseArguments($args);
  }

  private function parseArguments(array $args): void
  {
    // Remove the script name from args if it's there
    if (!empty($args) && str_contains($args[0], 'migrate.php')) {
      array_shift($args);
    }

    foreach ($args as $arg) {
      switch ($arg) {
        case '--fresh':
          $this->fresh = true;
          break;
        case '--seed':
          $this->seed = true;
          break;
        case '--verbose':
        case '-v':
          $this->verbose = true;
          break;
        case '--help':
        case '-h':
          $this->showHelp();
          exit(0);
      }
    }
  }
  private function showHelp(): void
  {
    echo "Migration Command\n";
    echo "================\n\n";
    echo "Usage: php cli.php migrate [options]\n\n";
    echo "Options:\n";
    echo "  --fresh    Drop all tables and run all migrations from scratch\n";
    echo "  --seed     Run database seeding after migrations\n";
    echo "  --verbose  Show detailed output\n";
    echo "  --help     Show this help message\n\n";
  }

  public function run(): void
  {
    try {
      if ($this->fresh) {
        $this->output("Running fresh migration (dropping all tables)...", 'info');
        $this->dropAllTables();
      }

      $this->output("Starting database migrations...", 'info');
      $this->runMigrations();

      if ($this->seed) {
        $this->output("Running database seeding...", 'info');
        $this->seedDatabase();
      }

      $this->output("Migration completed successfully!", 'success');
    } catch (Exception $e) {
      $this->output("Migration failed: " . $e->getMessage(), 'error');
      exit(1);
    }
  }

  private function dropAllTables(): void
  {
    $connection = Database::getConnection();

    // Disable foreign key checks
    $connection->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = Database::fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
      $tableName = array_values($table)[0];
      $this->output("Dropping table: $tableName", 'warning');
      $connection->exec("DROP TABLE IF EXISTS `$tableName`");
    }

    // Re-enable foreign key checks
    $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
  }

  private function runMigrations(): void
  {
    $connection = Database::getConnection();

    // Create migrations table if it doesn't exist
    $connection->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                version VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_version (version)
            )
        ");

    // Get executed migrations
    $executed = Database::fetchAll("SELECT version FROM migrations ORDER BY version");
    $executedVersions = array_column($executed, 'version');

    // Find migration files
    $migrationFiles = glob(__DIR__ . '/../../migrations/*.sql');
    sort($migrationFiles);

    if (empty($migrationFiles)) {
      $this->output("No migration files found.", 'warning');
      return;
    }

    $migrationCount = 0;

    foreach ($migrationFiles as $file) {
      $filename = basename($file);
      $version = substr($filename, 0, 3); // Get first 3 characters as version

      if (in_array($version, $executedVersions)) {
        $this->output("Migration {$version} already executed, skipping...", 'info');
        continue;
      }

      $this->output("Executing migration {$version}...", 'info');

      $sql = file_get_contents($file);
      $this->executeMigrationSQL($sql, $version, $filename);

      $migrationCount++;
    }

    if ($migrationCount === 0) {
      $this->output("No new migrations to execute.", 'info');
    } else {
      $this->output("Successfully executed {$migrationCount} migration(s).", 'success');
    }
  }

  private function executeMigrationSQL(string $sql, string $version, string $filename): void
  {
    // Clean and split SQL statements
    $statements = $this->parseSQLStatements($sql);

    Database::beginTransaction();

    try {
      foreach ($statements as $statement) {
        if (trim($statement)) {
          if ($this->verbose) {
            $this->output("Executing: " . substr(trim($statement), 0, 60) . "...", 'debug');
          }
          Database::getConnection()->exec($statement);
        }
      }

      // Record migration
      Database::execute(
        "INSERT INTO migrations (version, description) VALUES (?, ?)",
        [$version, "Migration from file: {$filename}"]
      );

      Database::commit();
      $this->output("Migration {$version} completed successfully.", 'success');
    } catch (Exception $e) {
      Database::rollback();
      throw new RuntimeException("Migration {$version} failed: " . $e->getMessage());
    }
  }

  private function parseSQLStatements(string $sql): array
  {
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split by semicolon but be careful with strings and procedures
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';

    for ($i = 0; $i < strlen($sql); $i++) {
      $char = $sql[$i];

      if (!$inString && ($char === '"' || $char === "'")) {
        $inString = true;
        $stringChar = $char;
      } elseif ($inString && $char === $stringChar) {
        // Check if it's escaped
        if ($i === 0 || $sql[$i - 1] !== '\\') {
          $inString = false;
        }
      } elseif (!$inString && $char === ';') {
        $statements[] = trim($current);
        $current = '';
        continue;
      }

      $current .= $char;
    }

    if (trim($current)) {
      $statements[] = trim($current);
    }

    return array_filter($statements, fn($stmt) => !empty(trim($stmt)));
  }

  private function seedDatabase(): void
  {
    $this->output("Seeding database with sample data...", 'info');

    try {
      // Insert sample events
      $this->seedEvents();

      // Insert default calendar token
      $this->seedCalendarTokens();

      // Insert default head admin if not exists
      $this->seedAdminUser();

      $this->output("Database seeded successfully.", 'success');
    } catch (Exception $e) {
      $this->output("Seeding failed: " . $e->getMessage(), 'error');
      throw $e;
    }
  }

  private function seedEvents(): void
  {
    $events = [
      [
        'title' => 'Einsteiger Hypnose Workshop',
        'slug' => 'einsteiger-hypnose-workshop-' . date('Y-m'),
        'description' => 'Ein sanfter Einstieg in die Welt der Hypnose für Neulinge.',
        'content' => 'Dieser Workshop bietet eine sichere und unterstützende Umgebung für alle, die ihre ersten Schritte in der Hypnose machen möchten...',
        'start_datetime' => date('Y-m-d H:i:s', strtotime('+1 week 19:00')),
        'end_datetime' => date('Y-m-d H:i:s', strtotime('+1 week 21:00')),
        'category' => 'workshop',
        'difficulty_level' => 'beginner',
        'location_type' => 'physical',
        'location_name' => 'Community Center Berlin',
        'location_address' => 'Musterstraße 123, 10115 Berlin',
        'max_participants' => 12,
        'organizer_name' => 'Sarah Müller',
        'organizer_email' => 'sarah@hypnose-stammtisch.de',
        'status' => 'published',
        'requires_registration' => true,
        'registration_deadline' => date('Y-m-d H:i:s', strtotime('+5 days 23:59'))
      ]
    ];

    foreach ($events as $event) {
      // Check if event already exists
      $existing = Database::fetchOne("SELECT id FROM events WHERE slug = ?", [$event['slug']]);
      if (!$existing) {
        $columns = implode(', ', array_keys($event));
        $placeholders = ':' . implode(', :', array_keys($event));
        Database::execute("INSERT INTO events ({$columns}) VALUES ({$placeholders})", $event);
        $this->output("Created sample event: {$event['title']}", 'info');
      }
    }
  }

  private function seedCalendarTokens(): void
  {
    $existing = Database::fetchOne("SELECT id FROM calendar_feed_tokens WHERE name = ?", ['Default Public Feed']);

    if (!$existing) {
      $token = hash('sha256', 'default_public_feed_' . time() . random_int(1000, 9999));
      Database::execute(
        "INSERT INTO calendar_feed_tokens (token, name, description, access_level) VALUES (?, ?, ?, ?)",
        [$token, 'Default Public Feed', 'Default public calendar feed token', 'public']
      );
      $this->output("Created default calendar feed token: {$token}", 'info');
    }
  }

  private function seedAdminUser(): void
  {
    $existing = Database::fetchOne("SELECT id FROM users WHERE role = 'head' LIMIT 1");

    if (!$existing) {
      $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
      Database::execute(
        "INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)",
        ['headadmin', 'head@hypnose-stammtisch.de', $hashedPassword, 'head', 1]
      );
      $this->output("Created head admin user: head@hypnose-stammtisch.de / admin123", 'warning');
      $this->output("IMPORTANT: Change this password immediately after first login!", 'error');
    }
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
$migration = new MigrationCommand($argv);
$migration->run();
