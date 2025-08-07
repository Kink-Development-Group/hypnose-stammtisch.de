<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  $tables = Database::fetchAll('SHOW TABLES');
  echo "Existing tables in database:\n";
  foreach ($tables as $table) {
    echo "- " . array_values($table)[0] . "\n";
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
