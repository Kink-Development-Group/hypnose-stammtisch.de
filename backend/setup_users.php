<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

try {
  // Create users table
  $sql = "
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

  Database::execute($sql);
  echo "Successfully created users table with head role.\n";

  // Insert default head admin user
  // Password is 'admin123' hashed with password_hash()
  $insertSql = "
        INSERT INTO users (username, email, password_hash, role, is_active)
        VALUES ('headadmin', 'head@hypnose-stammtisch.de', ?, 'head', 1)
        ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            role = VALUES(role)
    ";

  $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
  Database::execute($insertSql, [$hashedPassword]);
  echo "Successfully created head admin user.\n";
  echo "Login credentials: head@hypnose-stammtisch.de / admin123\n";
  echo "IMPORTANT: Please change this password immediately after first login!\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
