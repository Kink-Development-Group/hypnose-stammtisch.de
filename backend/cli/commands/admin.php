<?php

declare(strict_types=1);

/**
 * Admin Management Command
 * Handles admin user creation, modification, and role management
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;

Config::load(__DIR__ . '/../..');

class AdminCommand
{
  private string $action = '';
  private array $options = [];

  public function __construct(array $args)
  {
    $this->parseArguments($args);
  }

  private function parseArguments(array $args): void
  {
    // Remove the script name from args if it's there
    if (!empty($args) && str_contains($args[0], 'admin.php')) {
      array_shift($args);
    }

    if (empty($args) || in_array($args[0], ['--help', '-h'])) {
      $this->showHelp();
      exit(0);
    }

    $this->action = array_shift($args);

    // Parse options
    for ($i = 0; $i < count($args); $i++) {
      $arg = $args[$i];

      if (str_starts_with($arg, '--')) {
        $key = substr($arg, 2);
        $value = isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '--')
          ? $args[++$i]
          : true;
        $this->options[$key] = $value;
      }
    }
  }

  private function showHelp(): void
  {
    echo "Admin Management Command\n";
    echo "=======================\n\n";
    echo "Usage: php cli.php admin <action> [options]\n\n";
    echo "Actions:\n";
    echo "  create           Create a new admin user\n";
    echo "  list             List all admin users\n";
    echo "  update           Update an admin user\n";
    echo "  delete           Delete an admin user\n";
    echo "  change-password  Change admin password\n";
    echo "  promote          Promote user to admin role\n";
    echo "  demote           Demote admin user\n\n";
    echo "Options:\n";
    echo "  --username TEXT  Username for the admin\n";
    echo "  --email TEXT     Email address\n";
    echo "  --password TEXT  Password (will prompt if not provided)\n";
    echo "  --role TEXT      Role: head, admin, moderator\n";
    echo "  --active BOOL    Set user as active/inactive (true/false)\n";
    echo "  --id INT         User ID for update/delete operations\n";
    echo "  --notes TEXT     Admin notes\n\n";
    echo "Examples:\n";
    echo "  php cli.php admin create --username admin1 --email admin1@example.com --role admin\n";
    echo "  php cli.php admin list\n";
    echo "  php cli.php admin update --id 1 --role head\n";
    echo "  php cli.php admin change-password --id 1\n\n";
  }

  public function run(): void
  {
    try {
      switch ($this->action) {
        case 'create':
          $this->createAdmin();
          break;
        case 'list':
          $this->listAdmins();
          break;
        case 'update':
          $this->updateAdmin();
          break;
        case 'delete':
          $this->deleteAdmin();
          break;
        case 'change-password':
          $this->changePassword();
          break;
        case 'promote':
          $this->promoteUser();
          break;
        case 'demote':
          $this->demoteUser();
          break;
        case 'hash':
          $this->generateHash();
          break;
        default:
          $this->output("Unknown action: {$this->action}", 'error');
          $this->showHelp();
          exit(1);
      }
    } catch (Exception $e) {
      $this->output("Error: " . $e->getMessage(), 'error');
      exit(1);
    }
  }

  private function createAdmin(): void
  {
    $username = $this->options['username'] ?? $this->prompt('Username');
    $email = $this->options['email'] ?? $this->prompt('Email');
    $role = $this->options['role'] ?? $this->promptChoice('Role', ['admin', 'moderator', 'head'], 'admin');
    $active = $this->options['active'] ?? true;

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException("Invalid email format");
    }

    // Check if user already exists
    $existing = Database::fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
    if ($existing) {
      throw new RuntimeException("User with this username or email already exists");
    }

    // Get password
    $password = $this->options['password'] ?? $this->promptPassword('Password');
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Create user
    $userId = Database::execute(
      "INSERT INTO users (username, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
      [$username, $email, $passwordHash, $role, $active ? 1 : 0]
    );

    $this->output("Admin user created successfully!", 'success');
    $this->output("ID: {$userId}", 'info');
    $this->output("Username: {$username}", 'info');
    $this->output("Email: {$email}", 'info');
    $this->output("Role: {$role}", 'info');
    $this->output("Active: " . ($active ? 'Yes' : 'No'), 'info');
  }

  private function listAdmins(): void
  {
    $users = Database::fetchAll("
            SELECT id, username, email, role, is_active, created_at, last_login
            FROM users
            WHERE role IN ('head', 'admin', 'moderator')
            ORDER BY
                CASE role
                    WHEN 'head' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'moderator' THEN 3
                END,
                username
        ");

    if (empty($users)) {
      $this->output("No admin users found.", 'info');
      return;
    }

    $this->output("Admin Users:", 'info');
    $this->output(str_repeat('=', 80), 'info');

    foreach ($users as $user) {
      $status = $user['is_active'] ? 'Active' : 'Inactive';
      $lastLogin = $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never';

      echo "\n";
      $this->output("ID: {$user['id']}", 'info');
      $this->output("Username: {$user['username']}", 'info');
      $this->output("Email: {$user['email']}", 'info');
      $this->output("Role: {$user['role']}", 'info');
      $this->output("Status: {$status}", $user['is_active'] ? 'success' : 'warning');
      $this->output("Created: " . date('Y-m-d H:i:s', strtotime($user['created_at'])), 'info');
      $this->output("Last Login: {$lastLogin}", 'info');

      $this->output(str_repeat('-', 40), 'debug');
    }
  }

  private function updateAdmin(): void
  {
    $id = $this->options['id'] ?? $this->prompt('User ID');

    if (!is_numeric($id)) {
      throw new InvalidArgumentException("User ID must be numeric");
    }

    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    if (!$user) {
      throw new RuntimeException("User not found");
    }

    $updates = [];
    $params = [];

    // Collect updates
    if (isset($this->options['username'])) {
      $updates[] = "username = ?";
      $params[] = $this->options['username'];
    }

    if (isset($this->options['email'])) {
      if (!filter_var($this->options['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Invalid email format");
      }
      $updates[] = "email = ?";
      $params[] = $this->options['email'];
    }

    if (isset($this->options['role'])) {
      if (!in_array($this->options['role'], ['head', 'admin', 'moderator'])) {
        throw new InvalidArgumentException("Invalid role");
      }
      $updates[] = "role = ?";
      $params[] = $this->options['role'];
    }

    if (isset($this->options['active'])) {
      $active = filter_var($this->options['active'], FILTER_VALIDATE_BOOLEAN);
      $updates[] = "is_active = ?";
      $params[] = $active ? 1 : 0;
    }

    if (isset($this->options['notes'])) {
      $updates[] = "admin_notes = ?";
      $params[] = $this->options['notes'];
    }

    if (empty($updates)) {
      throw new InvalidArgumentException("No updates provided");
    }

    $params[] = $id;

    Database::execute(
      "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?",
      $params
    );

    $this->output("User updated successfully!", 'success');
  }

  private function deleteAdmin(): void
  {
    $id = $this->options['id'] ?? $this->prompt('User ID');

    if (!is_numeric($id)) {
      throw new InvalidArgumentException("User ID must be numeric");
    }

    $user = Database::fetchOne("SELECT username, email, role FROM users WHERE id = ?", [$id]);
    if (!$user) {
      throw new RuntimeException("User not found");
    }

    $this->output("User to delete:", 'warning');
    $this->output("Username: {$user['username']}", 'info');
    $this->output("Email: {$user['email']}", 'info');
    $this->output("Role: {$user['role']}", 'info');

    if (!$this->confirm("Are you sure you want to delete this user?")) {
      $this->output("Operation cancelled.", 'info');
      return;
    }

    Database::execute("DELETE FROM users WHERE id = ?", [$id]);
    $this->output("User deleted successfully!", 'success');
  }

  private function changePassword(): void
  {
    $id = $this->options['id'] ?? $this->prompt('User ID');

    if (!is_numeric($id)) {
      throw new InvalidArgumentException("User ID must be numeric");
    }

    $user = Database::fetchOne("SELECT username FROM users WHERE id = ?", [$id]);
    if (!$user) {
      throw new RuntimeException("User not found");
    }

    $password = $this->options['password'] ?? $this->promptPassword('New Password');
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    Database::execute("UPDATE users SET password_hash = ? WHERE id = ?", [$passwordHash, $id]);

    $this->output("Password updated successfully for user: {$user['username']}", 'success');
  }

  private function generateHash(): void
  {
    $password = $this->options['password'] ?? $this->promptPassword('Password to hash');
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $this->output("Password hash:", 'info');
    $this->output($hash, 'success');
  }

  private function promoteUser(): void
  {
    $id = $this->options['id'] ?? $this->prompt('User ID');
    $newRole = $this->options['role'] ?? $this->promptChoice('New Role', ['admin', 'moderator', 'head'], 'admin');

    Database::execute("UPDATE users SET role = ? WHERE id = ?", [$newRole, $id]);
    $this->output("User promoted to {$newRole}!", 'success');
  }

  private function demoteUser(): void
  {
    $id = $this->options['id'] ?? $this->prompt('User ID');
    $newRole = $this->options['role'] ?? $this->promptChoice('New Role', ['moderator'], 'moderator');

    Database::execute("UPDATE users SET role = ? WHERE id = ?", [$newRole, $id]);
    $this->output("User demoted to {$newRole}!", 'warning');
  }

  private function prompt(string $message): string
  {
    echo $message . ': ';
    return trim(fgets(STDIN));
  }

  private function promptPassword(string $message): string
  {
    echo $message . ': ';

    // Hide password input on Unix systems
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
      system('stty -echo');
      $password = trim(fgets(STDIN));
      system('stty echo');
      echo "\n";
    } else {
      $password = trim(fgets(STDIN));
    }

    return $password;
  }

  private function promptChoice(string $message, array $choices, string $default = ''): string
  {
    $choicesStr = implode('/', $choices);
    if ($default) {
      $choicesStr .= " (default: {$default})";
    }

    echo "{$message} [{$choicesStr}]: ";
    $input = trim(fgets(STDIN));

    if (empty($input) && $default) {
      return $default;
    }

    if (!in_array($input, $choices)) {
      $this->output("Invalid choice. Please select from: " . implode(', ', $choices), 'error');
      return $this->promptChoice($message, $choices, $default);
    }

    return $input;
  }

  private function confirm(string $message): bool
  {
    echo $message . ' [y/N]: ';
    $response = trim(fgets(STDIN));
    return strtolower($response) === 'y' || strtolower($response) === 'yes';
  }

  private function output(string $message, string $type = 'info'): void
  {
    $colors = [
      'info' => "\033[36m",    // Cyan
      'success' => "\033[32m", // Green
      'warning' => "\033[33m", // Yellow
      'error' => "\033[31m",   // Red
      'debug' => "\033[37m",   // White
    ];

    $reset = "\033[0m";
    $color = $colors[$type] ?? $colors['info'];

    echo $color . $message . $reset . "\n";
  }
}

// Execute command
$admin = new AdminCommand($argv);
$admin->run();
