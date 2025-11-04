<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Config\Config;

/**
 * Handles tracking and management of failed login attempts.
 *
 * This class provides comprehensive protection against brute-force login attacks by:
 * - Recording all failed login attempts with IP and account tracking
 * - Enforcing configurable thresholds for account lockout and IP banning
 * - Protecting Head Admin accounts from automatic lockout (IP-only ban)
 * - Providing admin tools for managing locked accounts
 *
 * @package HypnoseStammtisch\Utils
 * @see IpBanManager For IP-based blocking functionality
 * @see AuditLogger For security event logging
 */
class FailedLoginTracker
{
  /**
   * Record a failed login attempt.
   *
   * Stores the failed login in the database with account ID (if known),
   * attempted username, IP address, and user agent for security analysis.
   *
   * @param int|null $accountId The user account ID if account exists, null otherwise
   * @param string|null $usernameEntered The username/email that was attempted
   * @param string $ipAddress The source IP address of the attempt
   *
   * @return void
   *
   * @throws \Exception If database insert fails
   */
  public static function recordFailedAttempt(?int $accountId, ?string $usernameEntered, string $ipAddress): void
  {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    Database::execute(
      'INSERT INTO failed_logins (account_id, username_entered, ip_address, user_agent) VALUES (?, ?, ?, ?)',
      [$accountId, $usernameEntered, $ipAddress, $userAgent]
    );

    AuditLogger::log(
      'auth.failed_login_recorded',
      'user',
      $accountId ? (string)$accountId : null,
      [
        'username_entered' => $usernameEntered,
        'ip_address' => $ipAddress
      ]
    );
  }

  /**
   * Get failed attempt count for an account within the time window.
   *
   * Counts only attempts that occurred within the configured time window
   * (security.time_window_seconds) to prevent old attempts from affecting
   * current account status.
   *
   * @param int $accountId The user account ID to check
   *
   * @return int Number of failed attempts within the time window
   */
  public static function getFailedAttemptsForAccount(int $accountId): int
  {
    $timeWindowSeconds = Config::get('security.time_window_seconds');
    $threshold = date('Y-m-d H:i:s', time() - $timeWindowSeconds);

    $result = Database::fetchOne(
      'SELECT COUNT(*) as count FROM failed_logins WHERE account_id = ? AND created_at >= ?',
      [$accountId, $threshold]
    );

    return (int)($result['count'] ?? 0);
  }

  /**
   * Get failed attempt count for an IP address within the time window.
   *
   * Counts only attempts that occurred within the configured time window
   * (security.time_window_seconds) for the specified IP address.
   *
   * @param string $ipAddress The IP address to check
   *
   * @return int Number of failed attempts within the time window
   */
  public static function getFailedAttemptsForIP(string $ipAddress): int
  {
    $timeWindowSeconds = Config::get('security.time_window_seconds');
    $threshold = date('Y-m-d H:i:s', time() - $timeWindowSeconds);

    $result = Database::fetchOne(
      'SELECT COUNT(*) as count FROM failed_logins WHERE ip_address = ? AND created_at >= ?',
      [$ipAddress, $threshold]
    );

    return (int)($result['count'] ?? 0);
  }

  /**
   * Clear all failed login attempts for an account.
   *
   * Called automatically after a successful login to reset the failure count
   * and prevent false positives from affecting future login attempts.
   *
   * @param int $accountId The user account ID to clear attempts for
   *
   * @return void
   */
  public static function clearFailedAttemptsForAccount(int $accountId): void
  {
    Database::execute(
      'DELETE FROM failed_logins WHERE account_id = ?',
      [$accountId]
    );

    AuditLogger::log(
      'auth.failed_attempts_cleared',
      'user',
      (string)$accountId
    );
  }

  /**
   * Lock a user account due to security violations.
   *
   * Locks the account for the configured duration (security.account_lock_duration_seconds).
   * If duration is 0, the account remains locked until manually unlocked by an admin.
   *
   * @param int $accountId The user account ID to lock
   * @param string $reason The reason for locking (logged for audit purposes)
   *
   * @return void
   *
   * @see unlockAccount() To manually unlock an account
   */
  public static function lockAccount(int $accountId, string $reason = 'Too many failed login attempts'): void
  {
    $lockDurationSeconds = Config::get('security.account_lock_duration_seconds');
    $lockedUntil = null;

    if ($lockDurationSeconds > 0) {
      $lockedUntil = date('Y-m-d H:i:s', time() + $lockDurationSeconds);
    }

    Database::execute(
      'UPDATE users SET locked_until = ?, locked_reason = ? WHERE id = ?',
      [$lockedUntil, $reason, $accountId]
    );

    AuditLogger::log(
      'auth.account_locked',
      'user',
      (string)$accountId,
      [
        'reason' => $reason,
        'locked_until' => $lockedUntil,
        'duration_seconds' => $lockDurationSeconds
      ]
    );
  }

  /**
   * Check if a user account is currently locked.
   *
   * Automatically clears expired locks before checking lock status.
   *
   * @param int $accountId The user account ID to check
   *
   * @return bool True if account is locked, false otherwise
   */
  public static function isAccountLocked(int $accountId): bool
  {
    $user = Database::fetchOne(
      'SELECT locked_until FROM users WHERE id = ?',
      [$accountId]
    );

    if (!$user || !$user['locked_until']) {
      return false;
    }

    // Check if lock has expired
    if (strtotime($user['locked_until']) <= time()) {
      // Clear expired lock
      Database::execute(
        'UPDATE users SET locked_until = NULL, locked_reason = NULL WHERE id = ?',
        [$accountId]
      );
      return false;
    }

    return true;
  }

  /**
   * Manually unlock a user account (admin function).
   *
   * Removes the account lock and clears all failed login attempts.
   * This action is logged for audit purposes.
   *
   * @param int $accountId The user account ID to unlock
   * @param int|null $unlockedBy The admin user ID performing the unlock (for audit logging)
   *
   * @return bool True if account was unlocked, false if account was not locked
   */
  public static function unlockAccount(int $accountId, ?int $unlockedBy = null): bool
  {
    $affected = Database::execute(
      'UPDATE users SET locked_until = NULL, locked_reason = NULL WHERE id = ?',
      [$accountId]
    );

    if ($affected > 0) {
      AuditLogger::log(
        'auth.account_unlocked',
        'user',
        (string)$accountId,
        ['unlocked_by' => $unlockedBy]
      );

      // Clear failed attempts as well
      self::clearFailedAttemptsForAccount($accountId);

      return true;
    }

    return false;
  }

  /**
   * Check if a user is a Head Admin.
   *
   * Head Admins are protected from automatic account lockout - only their
   * IP addresses can be banned for security violations.
   *
   * @param array $user User data array with 'role' key
   *
   * @return bool True if user is a Head Admin, false otherwise
   */
  public static function isHeadAdmin(array $user): bool
  {
    $headAdminRole = Config::get('security.head_admin_role_name');
    return $user['role'] === $headAdminRole;
  }

  /**
   * Handle failed login logic - lock account and/or ban IP if threshold exceeded.
   *
   * This is the main entry point for failed login processing. It:
   * 1. Records the failed attempt
   * 2. Checks attempts against configured thresholds
   * 3. Bans IP if IP-based threshold exceeded
   * 4. Locks account if account-based threshold exceeded (except Head Admins)
   *
   * @param int|null $accountId The user account ID if known, null otherwise
   * @param string|null $usernameEntered The attempted username/email
   * @param string $ipAddress The source IP address
   *
   * @return void
   *
   * @see recordFailedAttempt() Records the attempt
   * @see IpBanManager::banIP() Handles IP banning
   * @see lockAccount() Handles account locking
   */
  public static function handleFailedLogin(?int $accountId, ?string $usernameEntered, string $ipAddress): void
  {
    // Record the failed attempt
    self::recordFailedAttempt($accountId, $usernameEntered, $ipAddress);

    $maxAttempts = Config::get('security.max_failed_attempts');
    $shouldBanIP = false;
    $shouldLockAccount = false;

    // Check IP-based attempts
    $ipAttempts = self::getFailedAttemptsForIP($ipAddress);
    if ($ipAttempts > $maxAttempts) {
      $shouldBanIP = true;
    }

    // Check account-based attempts (if we know the account)
    if ($accountId) {
      $accountAttempts = self::getFailedAttemptsForAccount($accountId);
      if ($accountAttempts > $maxAttempts) {
        // Get user details to check if head admin
        $user = Database::fetchOne(
          'SELECT id, role FROM users WHERE id = ?',
          [$accountId]
        );

        if ($user && !self::isHeadAdmin($user)) {
          $shouldLockAccount = true;
        }
        $shouldBanIP = true;
      }
    }

    // Execute actions
    if ($shouldBanIP) {
      IpBanManager::banIP($ipAddress, 'Exceeded failed login attempts');
    }

    if ($shouldLockAccount) {
      self::lockAccount($accountId, 'Too many failed login attempts');
    }
  }

  /**
   * Get failed login history for admin review.
   *
   * Returns a paginated list of failed login attempts with associated user
   * information (if account exists) for security analysis and monitoring.
   *
   * @param int $limit Maximum number of records to return (default: 100)
   * @param int $offset Number of records to skip for pagination (default: 0)
   *
   * @return array Array of failed login records with user details
   */
  public static function getFailedLoginHistory(int $limit = 100, int $offset = 0): array
  {
    return Database::fetchAll(
      'SELECT fl.*, u.username, u.email
             FROM failed_logins fl
             LEFT JOIN users u ON fl.account_id = u.id
             ORDER BY fl.created_at DESC
             LIMIT ? OFFSET ?',
      [$limit, $offset]
    );
  }
}
