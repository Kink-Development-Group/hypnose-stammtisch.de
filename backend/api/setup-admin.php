<?php
// Simple script to create head admin that can be called via browser
require_once '../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

try {
  Config::load('..');

  // Check if head admin already exists
  $existingUser = Database::fetchOne(
    "SELECT id, email FROM users WHERE email = ?",
    ['head@hypnose-stammtisch.de']
  );

  if ($existingUser) {
    echo json_encode([
      'success' => true,
      'message' => 'Head admin already exists',
      'user_id' => $existingUser['id']
    ]);
    exit;
  }

  // Create head admin
  $username = 'headadmin';
  $email = 'head@hypnose-stammtisch.de';
  $password = 'admin123';
  $role = 'head';

  $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

  Database::execute(
    "INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
    [$username, $email, $hashedPassword, $role, true]
  );

  // Get the created user
  $newUser = Database::fetchOne(
    "SELECT id FROM users WHERE email = ?",
    [$email]
  );

  echo json_encode([
    'success' => true,
    'message' => 'Head admin created successfully',
    'user_id' => $newUser['id'],
    'credentials' => [
      'email' => $email,
      'password' => $password
    ]
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Error: ' . $e->getMessage()
  ]);
}
