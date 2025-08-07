<?php

require_once __DIR__ . '/vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__);

echo "=== Admin Users Management System Test ===\n\n";

try {
  // Test 1: Check if users table exists and has head role
  echo "1. Checking users table structure...\n";
  $tableInfo = Database::fetchAll("DESCRIBE users");

  foreach ($tableInfo as $column) {
    if ($column['Field'] === 'role') {
      echo "   - Role column found: " . $column['Type'] . "\n";
      break;
    }
  }

  // Test 2: Check existing users
  echo "\n2. Existing users in database:\n";
  $users = Database::fetchAll("SELECT id, username, email, role, is_active FROM users");

  if (empty($users)) {
    echo "   No users found.\n";
  } else {
    foreach ($users as $user) {
      echo "   - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Role: {$user['role']}, Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    }
  }

  // Test 3: Test login with head admin
  echo "\n3. Testing head admin login...\n";
  $headAdmin = Database::fetchOne("SELECT * FROM users WHERE role = 'head' LIMIT 1");

  if ($headAdmin) {
    echo "   Head admin found: {$headAdmin['username']} ({$headAdmin['email']})\n";

    // Test password verification
    $testPassword = 'admin123';
    if (password_verify($testPassword, $headAdmin['password_hash'])) {
      echo "   ✓ Password verification successful\n";
    } else {
      echo "   ✗ Password verification failed\n";
    }
  } else {
    echo "   ✗ No head admin found\n";
  }

  echo "\n=== Test completed successfully ===\n";
  echo "\nYou can now:\n";
  echo "1. Visit http://localhost:5174/admin/login\n";
  echo "2. Login with: head@hypnose-stammtisch.de / admin123\n";
  echo "3. Navigate to 'Admin-Benutzer' to manage users\n";
  echo "\nIMPORTANT: Change the default password after first login!\n";
} catch (Exception $e) {
  echo "Error during test: " . $e->getMessage() . "\n";
}
