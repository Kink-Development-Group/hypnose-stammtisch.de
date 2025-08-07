<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  echo "=== Detailed Database Check ===\n\n";

  // Show all tables with SHOW TABLES
  echo "1. Using SHOW TABLES:\n";
  $result = Database::getConnection()->query("SHOW TABLES");
  $tables = $result->fetchAll(PDO::FETCH_NUM);

  foreach ($tables as $table) {
    echo "- {$table[0]}\n";
  }

  // Alternative method using information_schema
  echo "\n2. Using INFORMATION_SCHEMA:\n";
  $dbName = Config::get('db.name');
  $result2 = Database::fetchAll("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$dbName]);

  foreach ($result2 as $table) {
    echo "- {$table['TABLE_NAME']}\n";
  }

  echo "\nDatabase name: $dbName\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
