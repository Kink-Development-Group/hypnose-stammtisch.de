<?php
require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__);

try {
  // Generate new password hash
  $newHash = password_hash('admin123', PASSWORD_DEFAULT);
  echo "New password hash: $newHash\n";

  // Update the admin user
  $sql = "UPDATE users SET password_hash = ? WHERE email = 'admin@example.com'";
  Database::execute($sql, [$newHash]);

  echo "Password updated successfully\n";

  // Verify the update
  $admin = Database::fetchOne("SELECT password_hash FROM users WHERE email = 'admin@example.com'");
  $passwordCheck = password_verify('admin123', $admin['password_hash']);
  echo "Password verification after update: " . ($passwordCheck ? 'SUCCESS' : 'FAILED') . "\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
