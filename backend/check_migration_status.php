<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  echo "=== Checking database migration status ===\n\n";

  // Check executed migrations
  $executedMigrations = Database::fetchAll("SELECT version, description, executed_at FROM migrations ORDER BY version");

  echo "Executed migrations:\n";
  foreach ($executedMigrations as $migration) {
    echo "- Version: {$migration['version']}, Description: {$migration['description']}, Executed: {$migration['executed_at']}\n";
  }

  echo "\nChecking table existence:\n";

  // Check if users table exists
  $tables = Database::fetchAll("SHOW TABLES");
  $tableNames = array_map(function ($table) {
    return array_values($table)[0];
  }, $tables);

  echo "Existing tables: " . implode(', ', $tableNames) . "\n";

  // Check users table structure if it exists
  if (in_array('users', $tableNames)) {
    echo "\nUsers table structure:\n";
    $userTableInfo = Database::fetchAll("DESCRIBE users");
    foreach ($userTableInfo as $column) {
      echo "- {$column['Field']}: {$column['Type']}\n";
    }

    // Check existing users
    $users = Database::fetchAll("SELECT id, username, email, role FROM users");
    echo "\nExisting users:\n";
    foreach ($users as $user) {
      echo "- ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Role: {$user['role']}\n";
    }
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
