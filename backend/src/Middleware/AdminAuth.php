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
      // Set session cookie parameters before starting session
      session_set_cookie_params([
        'lifetime' => 3600 * 8, // 8 hours
        'path' => '/',
        'domain' => 'localhost',
        'secure' => false, // Set to false for local development
        'httponly' => true,
        'samesite' => 'Lax' // Changed from 'Strict' to 'Lax' for cross-origin
      ]);

      session_start([
        'use_strict_mode' => true,
        'cookie_lifetime' => 3600 * 8, // 8 hours
        'gc_maxlifetime' => 3600 * 8, // Match cookie lifetime
        'gc_probability' => 1,
        'gc_divisor' => 100,
      ]);

      // Regenerate session ID periodically for security
      if (!isset($_SESSION['session_started'])) {
        session_regenerate_id(true);
        $_SESSION['session_started'] = time();
      } elseif (time() - $_SESSION['session_started'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['session_started'] = time();
      }
    }
  }

  /**
   * Check if user is authenticated as admin (incl. 2FA)
   */
  public static function isAuthenticated(): bool
  {
    self::startSession();
    return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_user_email']) && !empty($_SESSION['admin_2fa_verified']);
  }

  /**
   * Get current authenticated admin user
   */
  public static function getCurrentUser(): ?array
  {
    if (!self::isAuthenticated()) {
      return null;
    }

    $sql = "SELECT id, username, email, role, is_active, last_login, created_at, updated_at
            FROM users WHERE id = ? AND is_active = 1";
    $user = Database::fetchOne($sql, [$_SESSION['admin_user_id']]);

    if ($user) {
      // Ensure is_active is boolean and convert datetime fields to ISO format
      $user['is_active'] = (bool)$user['is_active'];
      $user['last_login'] = $user['last_login'] ? date('c', strtotime($user['last_login'])) : null;
      $user['created_at'] = date('c', strtotime($user['created_at']));
      $user['updated_at'] = date('c', strtotime($user['updated_at']));
    }

    return $user ?: null;
  }

  /**
   * Authenticate user with email & password (stage 1 of 2FA)
   */
  public static function authenticate(string $email, string $password, string $ipAddress = ''): array
  {
    $sql = "SELECT id, username, email, password_hash, role, is_active, locked_until, locked_reason, twofa_secret, twofa_enabled, created_at, updated_at
            FROM users WHERE email = ? AND is_active = 1";
    $user = Database::fetchOne($sql, [$email]);

    // Get IP if not provided
    if (empty($ipAddress)) {
      $ipAddress = \HypnoseStammtisch\Utils\IpBanManager::getClientIP();
    }

    // Check if user exists and password is correct
    if (!$user || !password_verify($password, $user['password_hash'])) {
      // Handle failed login - even if user doesn't exist, we track by IP and username
      \HypnoseStammtisch\Utils\FailedLoginTracker::handleFailedLogin(
        $user ? (int)$user['id'] : null,
        $email,
        $ipAddress
      );
      
      return ['success' => false, 'message' => 'Invalid credentials'];
    }

    // Check if account is locked
    if (\HypnoseStammtisch\Utils\FailedLoginTracker::isAccountLocked((int)$user['id'])) {
      return [
        'success' => false, 
        'message' => 'Account is temporarily locked due to security reasons'
      ];
    }

    // Success - clear any failed attempts for this account
    \HypnoseStammtisch\Utils\FailedLoginTracker::clearFailedAttemptsForAccount((int)$user['id']);

    // Start session
    self::startSession();
    session_regenerate_id(true);

    $_SESSION['admin_user_password_ok'] = true;
    $_SESSION['admin_2fa_verified'] = false;
    $_SESSION['admin_user_pending_id'] = $user['id'];

    $twofaConfigured = !empty($user['twofa_secret']) && (bool)$user['twofa_enabled'];

    return [
      'success' => true,
      'user_id' => $user['id'],
      'twofa_required' => true,
      'twofa_configured' => $twofaConfigured,
      'message' => $twofaConfigured ? 'Second factor required' : '2FA setup required'
    ];
  }

  /**
   * Logout current user
   */
  public static function logout(): void
  {
    self::startSession();

    // Clear all session data
    session_unset();

    // Destroy the session
    session_destroy();

    // Clear the session cookie by setting it to expire in the past
    if (isset($_COOKIE[session_name()])) {
      setcookie(
        session_name(),
        '',
        time() - 3600,
        '/',
        'localhost',
        false,
        true
      );
    }

    // Also clear any additional admin-specific cookies if they exist
    $adminCookies = ['admin_session', 'admin_token', 'admin_remember'];
    foreach ($adminCookies as $cookieName) {
      if (isset($_COOKIE[$cookieName])) {
        setcookie(
          $cookieName,
          '',
          time() - 3600,
          '/',
          'localhost',
          false,
          true
        );
      }
    }
  }

  /**
   * Require admin authentication
   */
  public static function requireAuth(): void
  {
    if (!self::isAuthenticated()) {
      Response::unauthorized(['message' => 'Authentication required']);
      exit;
    }
  }

  /**
   * Finalize 2FA after successful verification
   */
  /**
   * Finalize 2FA nach erfolgreicher Verifikation.
   * Akzeptiert sowohl int als auch string, da Session IDs numerisch gespeichert sein können.
   */
  public static function finalizeTwoFactor(int|string $userId): array
  {
    // Ensure string for DB layer (Prepared Statements akzeptieren beides, wir normalisieren dennoch)
    $idParam = (string)$userId;
    $sql = "SELECT id, username, email, role, is_active, last_login, created_at, updated_at, twofa_enabled FROM users WHERE id = ?";
    $user = Database::fetchOne($sql, [$idParam]);

    if (!$user) {
      return ['success' => false, 'message' => 'User not found'];
    }

    Database::execute("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$idParam]);

    $user['last_login'] = date('c');

    $_SESSION['admin_user_id'] = $user['id']; // bleibt numerisch oder string wie aus DB
    $_SESSION['admin_user_email'] = $user['email'];
    $_SESSION['admin_user_role'] = $user['role'];
    $_SESSION['admin_2fa_verified'] = true;

    unset($_SESSION['admin_user_pending_id'], $_SESSION['admin_user_password_ok']);

    return [
      'success' => true,
      'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'is_active' => (bool)$user['is_active'],
        'last_login' => $user['last_login'],
        'created_at' => date('c', strtotime($user['created_at'])),
        'updated_at' => date('c', strtotime($user['updated_at']))
      ]
    ];
  }

  /**
   * Verify CSRF token
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
