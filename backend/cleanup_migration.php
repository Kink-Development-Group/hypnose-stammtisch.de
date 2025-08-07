<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  echo "=== Cleaning up failed migration ===\n\n";

  // Remove the failed migration 002 record
  echo "Removing migration 002 record...\n";
  Database::execute("DELETE FROM migrations WHERE version = '002'");

  echo "Migration 002 record removed.\n";

  // Show remaining migrations
  $remainingMigrations = Database::fetchAll("SELECT version, description FROM migrations ORDER BY version");
  echo "\nRemaining migrations:\n";
  foreach ($remainingMigrations as $migration) {
    echo "- Version: {$migration['version']}, Description: {$migration['description']}\n";
  }

  echo "\nReady to run migrations again.\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
