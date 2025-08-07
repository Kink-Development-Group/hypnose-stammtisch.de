<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'moderator', 'head') DEFAULT 'admin'";
  Database::execute($sql);
  echo "Successfully added head role to users table.\n";

  // Insert migration record
  $insertSql = "INSERT INTO migrations (version, description) VALUES ('005', 'Add head admin role for user management') ON DUPLICATE KEY UPDATE description = VALUES(description)";
  Database::execute($insertSql);
  echo "Migration record added.\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
