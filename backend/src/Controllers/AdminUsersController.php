<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\Validator;

/**
 * Admin Users Management Controller
 * Only accessible by head admins
 */
class AdminUsersController
{
  /**
   * Check if current user is a head admin
   */
  private static function requireHeadAdmin(): void
  {
    AdminAuth::requireAuth();

    $user = AdminAuth::getCurrentUser();
    if (!$user || $user['role'] !== 'head') {
      Response::error('Insufficient permissions. Head admin role required.', 403);
      exit;
    }
  }

  /**
   * Get all admin users
   */
  public static function index(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    self::requireHeadAdmin();

    try {
      $sql = "SELECT id, username, email, role, is_active, last_login, created_at, updated_at
                    FROM users
                    ORDER BY created_at DESC";

      $users = Database::fetchAll($sql);

      // Remove password_hash from response for security
      $users = array_map(function ($user) {
        unset($user['password_hash']);
        return $user;
      }, $users);

      Response::success($users);
    } catch (\Exception $e) {
      error_log("Error fetching admin users: " . $e->getMessage());
      Response::error('Failed to fetch admin users', 500);
    }
  }

  /**
   * Get specific admin user
   */
  public static function show(string $id): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    self::requireHeadAdmin();

    try {
      $sql = "SELECT id, username, email, role, is_active, last_login, created_at, updated_at
                    FROM users
                    WHERE id = ?";

      $user = Database::fetchOne($sql, [$id]);

      if (!$user) {
        Response::notFound(['message' => 'Admin user not found']);
        return;
      }

      Response::success($user);
    } catch (\Exception $e) {
      error_log("Error fetching admin user: " . $e->getMessage());
      Response::error('Failed to fetch admin user', 500);
    }
  }

  /**
   * Create new admin user
   */
  public static function create(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      Response::error('Method not allowed', 405);
      return;
    }

    self::requireHeadAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new Validator($input);
    $validator->required(['username', 'email', 'password', 'role']);
    $validator->email('email');
    $validator->length('username', 3, 50);
    $validator->length('password', 8, 255);

    // Validate role manually
    if (!Validator::isValidEnum($input['role'] ?? '', ['admin', 'moderator', 'head'])) {
      // Since getErrors() returns a copy, we need to manually set the error
      $errors = $validator->getErrors();
      $errors['role'] = 'Invalid role. Must be admin, moderator, or head.';
      // We need to check this differently
    }

    if (!$validator->isValid() || !Validator::isValidEnum($input['role'] ?? '', ['admin', 'moderator', 'head'])) {
      $errors = $validator->getErrors();
      if (!Validator::isValidEnum($input['role'] ?? '', ['admin', 'moderator', 'head'])) {
        $errors['role'] = 'Invalid role. Must be admin, moderator, or head.';
      }
      Response::error('Validation failed', 400, $errors);
      return;
    }

    try {
      // Check if username or email already exists
      $checkSql = "SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?";
      $existing = Database::fetchOne($checkSql, [$input['username'], $input['email']]);

      if ($existing['count'] > 0) {
        Response::error('Username or email already exists', 409);
        return;
      }

      // Hash password
      $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

      // Insert new user
      $sql = "INSERT INTO users (username, email, password_hash, role, is_active)
                    VALUES (?, ?, ?, ?, ?)";

      $isActive = $input['is_active'] ?? true;
      $result = Database::execute($sql, [
        $input['username'],
        $input['email'],
        $passwordHash,
        $input['role'],
        $isActive
      ]);

      if ($result) {
        $userId = Database::getConnection()->lastInsertId();

        // Fetch the created user
        $createdUser = Database::fetchOne(
          "SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ?",
          [$userId]
        );

        Response::success($createdUser, 'Admin user created successfully', 201);
      } else {
        Response::error('Failed to create admin user', 500);
      }
    } catch (\Exception $e) {
      error_log("Error creating admin user: " . $e->getMessage());
      Response::error('Failed to create admin user', 500);
    }
  }

  /**
   * Update admin user
   */
  public static function update(string $id): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
      Response::error('Method not allowed', 405);
      return;
    }

    self::requireHeadAdmin();

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // Check if user exists
    $existingUser = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    if (!$existingUser) {
      Response::notFound(['message' => 'Admin user not found']);
      return;
    }

    // Prevent self-demotion from head admin
    $currentUser = AdminAuth::getCurrentUser();
    if ($currentUser['id'] == $id && isset($input['role']) && $input['role'] !== 'head') {
      Response::error('Cannot demote yourself from head admin role', 403);
      return;
    }

    $validator = new Validator($input);

    // Only validate fields that are provided
    if (isset($input['username'])) {
      $validator->length('username', 3, 50);
    }
    if (isset($input['email'])) {
      $validator->email('email');
    }
    if (isset($input['password'])) {
      $validator->length('password', 8, 255);
    }

    // Check validation and role enum separately
    $errors = $validator->getErrors();
    if (isset($input['role']) && !Validator::isValidEnum($input['role'], ['admin', 'moderator', 'head'])) {
      $errors['role'] = 'Invalid role. Must be admin, moderator, or head.';
    }

    if (!empty($errors)) {
      Response::error('Validation failed', 400, $errors);
      return;
    }

    try {
      $updateFields = [];
      $updateValues = [];

      // Build dynamic update query
      if (isset($input['username'])) {
        $updateFields[] = 'username = ?';
        $updateValues[] = $input['username'];
      }

      if (isset($input['email'])) {
        $updateFields[] = 'email = ?';
        $updateValues[] = $input['email'];
      }

      if (isset($input['password'])) {
        $updateFields[] = 'password_hash = ?';
        $updateValues[] = password_hash($input['password'], PASSWORD_DEFAULT);
      }

      if (isset($input['role'])) {
        $updateFields[] = 'role = ?';
        $updateValues[] = $input['role'];
      }

      if (isset($input['is_active'])) {
        $updateFields[] = 'is_active = ?';
        $updateValues[] = $input['is_active'];
      }

      if (empty($updateFields)) {
        Response::error('No fields to update', 400);
        return;
      }

      $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
      $updateValues[] = $id;

      $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";

      $result = Database::execute($sql, $updateValues);

      if ($result) {
        // Fetch updated user
        $updatedUser = Database::fetchOne(
          "SELECT id, username, email, role, is_active, last_login, created_at, updated_at FROM users WHERE id = ?",
          [$id]
        );

        Response::success($updatedUser, 'Admin user updated successfully');
      } else {
        Response::error('Failed to update admin user', 500);
      }
    } catch (\Exception $e) {
      error_log("Error updating admin user: " . $e->getMessage());
      Response::error('Failed to update admin user', 500);
    }
  }

  /**
   * Delete admin user
   */
  public static function delete(string $id): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
      Response::error('Method not allowed', 405);
      return;
    }

    self::requireHeadAdmin();

    // Prevent self-deletion
    $currentUser = AdminAuth::getCurrentUser();
    if ($currentUser['id'] == $id) {
      Response::error('Cannot delete yourself', 403);
      return;
    }

    try {
      // Check if user exists
      $user = Database::fetchOne("SELECT id, role FROM users WHERE id = ?", [$id]);
      if (!$user) {
        Response::notFound(['message' => 'Admin user not found']);
        return;
      }

      // Delete the user
      $sql = "DELETE FROM users WHERE id = ?";
      $result = Database::execute($sql, [$id]);

      if ($result) {
        Response::success(null, 'Admin user deleted successfully');
      } else {
        Response::error('Failed to delete admin user', 500);
      }
    } catch (\Exception $e) {
      error_log("Error deleting admin user: " . $e->getMessage());
      Response::error('Failed to delete admin user', 500);
    }
  }

  /**
   * Get current user's permissions
   */
  public static function permissions(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      Response::error('Method not allowed', 405);
      return;
    }

    AdminAuth::requireAuth();

    $user = AdminAuth::getCurrentUser();

    $permissions = [
      'can_manage_users' => $user['role'] === 'head',
      'can_manage_events' => in_array($user['role'], ['head', 'admin']),
      'can_view_messages' => in_array($user['role'], ['head', 'admin', 'moderator']),
      'role' => $user['role']
    ];

    Response::success($permissions);
  }
}
