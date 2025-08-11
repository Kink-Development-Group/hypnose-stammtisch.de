<?php

declare(strict_types=1);

/**
 * Database Migration Runner for Hypnose Stammtisch
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__ . '/..');

function runMigrations(): void
{
  echo "Starting database migrations...\n";

  try {
    $connection = Database::getConnection();

    // Make sure we're not in a transaction
    if (Database::inTransaction()) {
      Database::rollback();
      echo "Warning: Rolling back existing transaction before starting migrations.\n";
    }

    // Create migrations table if it doesn't exist
    $connection->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                version VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_version (version)
            )
        ");

    // Get executed migrations
    $executed = Database::fetchAll("SELECT version FROM migrations ORDER BY version");
    $executedVersions = array_column($executed, 'version');

    // Use ONLY the consolidated baseline migration. Legacy fragments kept for reference are ignored.
    $migrationFiles = [__DIR__ . '/001_initial_schema.sql'];

    $migrationCount = 0;

    $seenVersionFile = [];
    foreach ($migrationFiles as $file) {
      $filename = basename($file);
      $version = substr($filename, 0, 3); // Get first 3 characters as version

      // Skip duplicate version files beyond the first one
      if (isset($seenVersionFile[$version])) {
        echo "Duplicate migration file for version {$version} ({$filename}) – skipping duplicate.\n";
        continue;
      }
      $seenVersionFile[$version] = $filename;

      if (in_array($version, $executedVersions)) {
        echo "Migration {$version} already executed, skipping...\n";
        // Short-circuit: if base schema (001) is applied, ignore higher legacy fragments
        if ($version === '001') {
          echo "Base schema present – skipping any remaining legacy migration files.\n";
          break;
        }
        continue;
      }

      echo "Executing migration {$version}...\n";

      $sql = file_get_contents($file);

      // Split by Semicolon (naiv) und bereinige führende Kommentarzeilen je Statement
      $rawChunks = array_map('trim', explode(';', $sql));
      $statements = [];
      foreach ($rawChunks as $chunk) {
        if ($chunk === '') {
          continue;
        }
        $lines = preg_split('/\r?\n/', $chunk);
        $filtered = [];
        foreach ($lines as $ln) {
          // Entferne reine Kommentarzeilen (beginnend mit -- oder leer)
          if (preg_match('/^\s*--/', $ln)) {
            continue;
          }
          $filtered[] = $ln;
        }
        $clean = trim(implode("\n", $filtered));
        if ($clean !== '') {
          $statements[] = $clean;
        }
      }

      try {
        $useTx = count($statements) > 0;
        if ($useTx) {
          try {
            Database::beginTransaction();
          } catch (Exception $e) {
            $useTx = false;
          }
        }

        foreach ($statements as $statement) {
          if (trim($statement)) {
            $connection->exec($statement);
          }
        }

        // Refresh executed versions (in case file inserted itself)
        $executed = Database::fetchAll("SELECT version FROM migrations ORDER BY version");
        $executedVersions = array_column($executed, 'version');
        $alreadyInserted = in_array($version, $executedVersions, true);
        if (!$alreadyInserted) {
          Database::execute(
            "INSERT INTO migrations (version, description) VALUES (?, ?)",
            [$version, "Migration from file: {$filename}"]
          );
          $executedVersions[] = $version;
        }

        if ($useTx && Database::inTransaction()) {
          Database::commit();
        }

        echo "Migration {$version} completed successfully.\n";
        $migrationCount++;
      } catch (Exception $e) {
        // Rollback on error
        if (Database::inTransaction()) {
          Database::rollback();
        }
        throw new RuntimeException("Migration {$version} failed: " . $e->getMessage());
      }
    }

    if ($migrationCount === 0) {
      echo "No new migrations to execute.\n";
    } else {
      echo "Successfully executed {$migrationCount} migration(s).\n";
    }
  } catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
  }
}

function seedDatabase(): void
{
  echo "Seeding database with sample data...\n";

  try {
    // Insert sample events
    $sampleEvents = [
      [
        'title' => 'Einsteiger Hypnose Workshop',
        'slug' => 'einsteiger-hypnose-workshop-2025-08',
        'description' => 'Ein sanfter Einstieg in die Welt der Hypnose für Neulinge.',
        'content' => 'Dieser Workshop bietet eine sichere und unterstützende Umgebung für alle, die ihre ersten Schritte in der Hypnose machen möchten...',
        'start_datetime' => '2025-08-15 19:00:00',
        'end_datetime' => '2025-08-15 21:00:00',
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
        'registration_deadline' => '2025-08-13 23:59:59'
      ],
      [
        'title' => 'Hypnose Stammtisch August',
        'slug' => 'hypnose-stammtisch-august-2025',
        'description' => 'Unser monatlicher Stammtisch für Austausch und Praxis.',
        'content' => 'Jeden ersten Freitag im Monat treffen wir uns zu unserem Stammtisch...',
        'start_datetime' => '2025-08-01 18:30:00',
        'end_datetime' => '2025-08-01 22:00:00',
        'category' => 'stammtisch',
        'difficulty_level' => 'all',
        'location_type' => 'hybrid',
        'location_name' => 'Café Hypnos + Online',
        'location_address' => 'Hypnosegasse 42, 10117 Berlin',
        'location_url' => 'https://zoom.us/j/example',
        'max_participants' => 20,
        'organizer_name' => 'Max Mustermann',
        'organizer_email' => 'max@hypnose-stammtisch.de',
        'status' => 'published'
      ]
    ];

    foreach ($sampleEvents as $event) {
      $columns = implode(', ', array_keys($event));
      $placeholders = ':' . implode(', :', array_keys($event));

      Database::execute(
        "INSERT INTO events ({$columns}) VALUES ({$placeholders})",
        $event
      );
    }

    // Insert default calendar token if not exists
    $existingToken = Database::fetchOne("SELECT id FROM calendar_feed_tokens WHERE name = ?", ['Default Public Feed']);

    if (!$existingToken) {
      $token = hash('sha256', 'default_public_feed_' . time() . random_int(1000, 9999));
      Database::execute(
        "INSERT INTO calendar_feed_tokens (token, name, description, access_level) VALUES (?, ?, ?, ?)",
        [$token, 'Default Public Feed', 'Default public calendar feed token', 'public']
      );
      echo "Created default calendar feed token: {$token}\n";
    }

    echo "Database seeded successfully.\n";
  } catch (Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
  }
}

// Main execution
// Allow inclusion from web context; only execute CLI handler when run directly
if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
  $command = $argv[1] ?? 'migrate';

  switch ($command) {
    case 'migrate':
      runMigrations();
      break;

    case 'seed':
      seedDatabase();
      break;

    case 'fresh':
      runMigrations();
      seedDatabase();
      break;

    default:
      echo "Usage: php migrate.php [migrate|seed|fresh]\n";
      echo "  migrate - Run pending migrations\n";
      echo "  seed    - Seed database with sample data\n";
      echo "  fresh   - Run migrations and seed data\n";
      exit(1);
  }
}
