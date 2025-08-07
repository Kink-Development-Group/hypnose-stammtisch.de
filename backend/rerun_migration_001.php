<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  echo "=== Re-executing Migration 001 ===\n\n";

  // Execute the full content of migration 001
  $migration001 = file_get_contents(__DIR__ . '/migrations/001_initial_schema.sql');

  // Split by semicolon and execute each statement
  $statements = array_filter(
    array_map('trim', explode(';', $migration001)),
    fn($stmt) => !empty($stmt) && !str_starts_with(trim($stmt), '--')
  );

  echo "Executing " . count($statements) . " statements from migration 001...\n\n";

  foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;

    try {
      Database::execute($statement);

      // Extract table name for better logging
      if (preg_match('/CREATE TABLE\s+IF NOT EXISTS\s+(\w+)/i', $statement, $matches)) {
        echo "   ✓ Created table: {$matches[1]}\n";
      } elseif (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches)) {
        echo "   ✓ Inserted data into: {$matches[1]}\n";
      } else {
        echo "   ✓ Executed statement " . ($index + 1) . "\n";
      }
    } catch (Exception $e) {
      if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "   - Table already exists (skipped)\n";
      } else {
        echo "   ✗ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
        echo "   Statement: " . substr($statement, 0, 100) . "...\n";
      }
    }
  }

  echo "\n=== Final table check ===\n";
  $tables = Database::fetchAll("SHOW TABLES");
  foreach ($tables as $table) {
    $tableName = array_values($table)[0];
    echo "   - $tableName\n";
  }

  echo "\n=== Migration 001 re-execution completed ===\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
