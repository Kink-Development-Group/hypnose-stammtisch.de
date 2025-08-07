<?php

declare(strict_types=1);

/**
 * Quick Database Check Script
 */

require_once __DIR__ . '/../backend/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__ . '/../backend');

try {
  $db = Database::getConnection();

  echo "Checking database tables...\n";

  // Show all tables
  $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

  echo "Found tables:\n";
  foreach ($tables as $table) {
    echo "  - $table\n";
  }

  // Try to describe users table if it exists
  if (in_array('users', $tables)) {
    echo "\nUsers table structure:\n";
    $structure = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($structure as $field) {
      echo "  {$field['Field']} ({$field['Type']})\n";
    }
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
