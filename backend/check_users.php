<?php
require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Load configuration
Config::load(__DIR__);

try {
  $users = Database::fetchAll("SELECT id, username, email, role, is_active FROM users");

  echo "Users in database:\n";
  foreach ($users as $user) {
    echo "ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Role: {$user['role']}, Active: {$user['is_active']}\n";
  }

  // Test password verification
  $admin = Database::fetchOne("SELECT password_hash FROM users WHERE email = 'admin@example.com'");
  if ($admin) {
    $passwordCheck = password_verify('admin123', $admin['password_hash']);
    echo "Password verification for admin123: " . ($passwordCheck ? 'SUCCESS' : 'FAILED') . "\n";
  } else {
    echo "Admin user not found\n";
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}
