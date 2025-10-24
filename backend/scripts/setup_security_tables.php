<?php

declare(strict_types=1);

/**
 * Manual migration script for security tables
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__ . '/..');

try {
  echo "Creating security tables...\n";

  // Create failed_logins table
  $sql = "CREATE TABLE IF NOT EXISTS failed_logins (
      id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
      account_id INT NULL,
      username_entered VARCHAR(255) NULL,
      ip_address VARCHAR(45) NOT NULL,
      user_agent TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_account_id (account_id),
      INDEX idx_ip_address (ip_address),
      INDEX idx_created_at (created_at),
      FOREIGN KEY (account_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

  Database::execute($sql);
  echo "✓ failed_logins table created\n";

  // Create ip_bans table
  $sql = "CREATE TABLE IF NOT EXISTS ip_bans (
      id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
      ip_address VARCHAR(45) NOT NULL UNIQUE,
      reason VARCHAR(255) NOT NULL,
      banned_by INT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      expires_at TIMESTAMP NULL,
      is_active BOOLEAN DEFAULT TRUE,
      INDEX idx_ip_address (ip_address),
      INDEX idx_expires_at (expires_at),
      INDEX idx_is_active (is_active),
      FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

  Database::execute($sql);
  echo "✓ ip_bans table created\n";

  // Add columns to users table
  try {
    Database::execute("ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL AFTER is_active");
    echo "✓ Added locked_until column to users table\n";
  } catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
      echo "- locked_until column already exists\n";
    } else {
      throw $e;
    }
  }

  try {
    Database::execute("ALTER TABLE users ADD COLUMN locked_reason VARCHAR(255) NULL AFTER locked_until");
    echo "✓ Added locked_reason column to users table\n";
  } catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
      echo "- locked_reason column already exists\n";
    } else {
      throw $e;
    }
  }

  try {
    Database::execute("ALTER TABLE users ADD INDEX idx_locked_until (locked_until)");
    echo "✓ Added index for locked_until\n";
  } catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
      echo "- Index idx_locked_until already exists\n";
    } else {
      throw $e;
    }
  }

  echo "\nSecurity tables setup completed successfully!\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
