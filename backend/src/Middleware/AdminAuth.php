<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Middleware;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;

/**
 * Admin authentication middleware
 */
class AdminAuth
{
  /**
   * Start secure session if not already started
   */
  public static function startSession(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'cookie_lifetime' => 3600 * 8, // 8 hours
      ]);
    }
  }

  /**
   * Check if user is authenticated as admin
   */
  public static function isAuthenticated(): bool
  {
    self::startSession();
    return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_user_email']);
  }

  /**
   * Get current authenticated admin user
   */
  public static function getCurrentUser(): ?array
  {
    if (!self::isAuthenticated()) {
      return null;
    }

    $sql = "SELECT id, username, email, role, last_login FROM users WHERE id = ? AND is_active = 1";
    $user = Database::fetchOne($sql, [$_SESSION['admin_user_id']]);

    return $user ?: null;
  }

  /**
   * Authenticate user with email and password
   */
  public static function authenticate(string $email, string $password): array
  {
    $sql = "SELECT id, username, email, password_hash, role FROM users
                WHERE email = ? AND is_active = 1";
    $user = Database::fetchOne($sql, [$email]);

    if (!$user || !password_verify($password, $user['password_hash'])) {
      return ['success' => false, 'message' => 'Invalid credentials'];
    }

    // Update last login
    $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
    Database::execute($updateSql, [$user['id']]);

    // Start session
    self::startSession();
    session_regenerate_id(true);

    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_user_email'] = $user['email'];
    $_SESSION['admin_user_role'] = $user['role'];

    return [
      'success' => true,
      'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
      ]
    ];
  }

  /**
   * Logout current user
   */
  public static function logout(): void
  {
    self::startSession();
    session_unset();
    session_destroy();
  }

  /**
   * Require admin authentication (middleware function)
   */
  public static function requireAuth(): void
  {
    if (!self::isAuthenticated()) {
      Response::unauthorized(['message' => 'Authentication required']);
      exit;
    }
  }

  /**
   * Check CSRF token
   */
  public static function verifyCSRF(string $token = null): bool
  {
    self::startSession();

    if (!isset($_SESSION['csrf_token'])) {
      return false;
    }

    $providedToken = $token ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;

    return $providedToken && hash_equals($_SESSION['csrf_token'], $providedToken);
  }

  /**
   * Generate CSRF token
   */
  public static function generateCSRF(): string
  {
    self::startSession();

    if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
  }
}
