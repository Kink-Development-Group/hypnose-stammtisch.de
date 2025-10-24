<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Config\Config;

/**
 * Handles tracking and management of failed login attempts
 */
class FailedLoginTracker
{
    /**
     * Record a failed login attempt
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
     * Get failed attempt count for an account within the time window
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
     * Get failed attempt count for an IP within the time window
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
     * Clear failed attempts for successful login
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
     * Lock user account
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
     * Check if account is currently locked
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
     * Manually unlock account (admin function)
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
     * Check if user is head admin
     */
    public static function isHeadAdmin(array $user): bool
    {
        $headAdminRole = Config::get('security.head_admin_role_name');
        return $user['role'] === $headAdminRole;
    }

    /**
     * Handle failed login logic - lock account and/or ban IP if threshold exceeded
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
     * Get failed login history (admin function)
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