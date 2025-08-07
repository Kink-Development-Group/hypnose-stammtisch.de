<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  echo "=== Applying Migration 002 modifications to events table ===\n\n";

  // Add series_id column if it doesn't exist
  echo "1. Adding series_id column...\n";
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

  // Add instance_date column if it doesn't exist
  echo "\n2. Adding instance_date column...\n";
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

  // Add foreign key constraint if it doesn't exist
  echo "\n3. Adding foreign key constraint...\n";
  try {
    Database::execute("ALTER TABLE events ADD FOREIGN KEY (series_id) REFERENCES event_series(id) ON DELETE CASCADE");
    echo "   ✓ Added foreign key constraint\n";
  } catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
      echo "   - Foreign key constraint already exists\n";
    } else {
      echo "   ✗ Error adding foreign key: " . $e->getMessage() . "\n";
    }
  }

  // Add indexes
  echo "\n4. Adding indexes...\n";
  try {
    Database::execute("ALTER TABLE events ADD INDEX idx_series_id (series_id)");
    echo "   ✓ Added index for series_id\n";
  } catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
      echo "   - Index for series_id already exists\n";
    } else {
      echo "   ✗ Error adding series_id index: " . $e->getMessage() . "\n";
    }
  }

  try {
    Database::execute("ALTER TABLE events ADD INDEX idx_instance_date (instance_date)");
    echo "   ✓ Added index for instance_date\n";
  } catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
      echo "   - Index for instance_date already exists\n";
    } else {
      echo "   ✗ Error adding instance_date index: " . $e->getMessage() . "\n";
    }
  }

  echo "\n5. Checking events table structure...\n";
  $eventsStructure = Database::fetchAll("DESCRIBE events");
  foreach ($eventsStructure as $column) {
    echo "   - {$column['Field']}: {$column['Type']}\n";
  }

  echo "\n=== Migration 002 modifications completed successfully ===\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
