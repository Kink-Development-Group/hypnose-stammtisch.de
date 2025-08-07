<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  echo "=== Manual Migration Execution ===\n\n";

  // First, let's check what events table exists
  echo "1. Checking if events table exists...\n";
  try {
    $eventsTable = Database::fetchAll("DESCRIBE events");
    echo "   Events table exists with columns:\n";
    foreach ($eventsTable as $column) {
      echo "   - {$column['Field']}: {$column['Type']}\n";
    }
  } catch (Exception $e) {
    echo "   Events table does not exist: " . $e->getMessage() . "\n";
  }

  echo "\n2. Creating users table...\n";
  $createUsersSQL = "
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'moderator', 'head') DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_is_active (is_active)
        )
    ";

  Database::execute($createUsersSQL);
  echo "   ✓ Users table created successfully\n";

  echo "\n3. Creating event_series table...\n";
  $createEventSeriesSQL = "
        CREATE TABLE IF NOT EXISTS event_series (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            rrule TEXT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NULL,
            exdates TEXT NULL,
            default_duration_minutes INT DEFAULT 120,
            default_location_type ENUM('online', 'physical', 'hybrid') DEFAULT 'physical',
            default_location_name VARCHAR(255),
            default_location_address TEXT,
            default_category ENUM('workshop', 'stammtisch', 'practice', 'lecture', 'special') DEFAULT 'stammtisch',
            default_max_participants INT NULL,
            default_requires_registration BOOLEAN DEFAULT TRUE,

            status ENUM('draft', 'published', 'cancelled') DEFAULT 'draft',
            tags JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_slug (slug),
            INDEX idx_status (status),
            INDEX idx_start_date (start_date)
        )
    ";

  Database::execute($createEventSeriesSQL);
  echo "   ✓ Event_series table created successfully\n";

  // Check if we can add columns to events table
  echo "\n4. Checking events table for modifications...\n";
  try {
    // Try to add series_id column if it doesn't exist
    try {
      Database::execute("ALTER TABLE events ADD COLUMN series_id INT NULL AFTER id");
      echo "   ✓ Added series_id column to events table\n";
    } catch (Exception $e) {
      if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "   - series_id column already exists\n";
      } else {
        echo "   ✗ Error adding series_id: " . $e->getMessage() . "\n";
      }
    }

    // Try to add instance_date column if it doesn't exist
    try {
      Database::execute("ALTER TABLE events ADD COLUMN instance_date DATE NULL AFTER series_id");
      echo "   ✓ Added instance_date column to events table\n";
    } catch (Exception $e) {
      if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "   - instance_date column already exists\n";
      } else {
        echo "   ✗ Error adding instance_date: " . $e->getMessage() . "\n";
      }
    }

    // Try to add foreign key if it doesn't exist
    try {
      Database::execute("ALTER TABLE events ADD FOREIGN KEY (series_id) REFERENCES event_series(id) ON DELETE CASCADE");
      echo "   ✓ Added foreign key constraint\n";
    } catch (Exception $e) {
      if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "   - Foreign key constraint already exists\n";
      } else {
        echo "   ✗ Error adding foreign key: " . $e->getMessage() . "\n";
      }
    }
  } catch (Exception $e) {
    echo "   ✗ Error modifying events table: " . $e->getMessage() . "\n";
  }

  echo "\n5. Final table check...\n";
  $tables = Database::fetchAll("SHOW TABLES");
  foreach ($tables as $table) {
    $tableName = array_values($table)[0];
    echo "   - $tableName\n";
  }

  echo "\n=== Manual migration completed ===\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
