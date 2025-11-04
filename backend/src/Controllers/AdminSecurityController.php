<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\AuditLogger;
use HypnoseStammtisch\Utils\FailedLoginTracker;
use HypnoseStammtisch\Utils\IpBanManager;

/**
 * Administrative security management controller
 *
 * Provides REST API endpoints for administrators to manage security features including:
 * - Failed login history monitoring
 * - IP ban management (view, create, remove)
 * - Account lock management (view, unlock)
 * - Security statistics and reporting
 * - Maintenance operations (cleanup expired bans)
 *
 * All endpoints require Head Admin or Admin role authentication.
 *
 * @package HypnoseStammtisch\Controllers
 * @see FailedLoginTracker For failed login data management
 * @see IpBanManager For IP ban operations
 * @see AdminAuth For authentication verification
 */
class AdminSecurityController
{
    /**
     * Retrieve paginated failed login history (Admin endpoint)
     *
     * Returns a list of recent failed login attempts with details about the account,
     * IP address, and timestamp. Supports pagination via query parameters.
     *
     * Query parameters:
     * - page: Page number (default: 1)
     * - limit: Results per page, max 100 (default: 50)
     *
     * Required permissions: Head Admin or Admin
     *
     * @return void Outputs JSON response with failed_logins array and pagination info
     * @see FailedLoginTracker::getFailedLoginHistory()
     */
    public static function getFailedLogins(): void
    {
        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['head', 'admin'])) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = ($page - 1) * $limit;

        $history = FailedLoginTracker::getFailedLoginHistory($limit, $offset);

        Response::success([
            'failed_logins' => $history,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }

    /**
     * Retrieve paginated IP ban list (Admin endpoint)
     *
     * Returns a list of IP bans with details about the banned IP, reason, ban creator,
     * and expiration date. Can show only active bans or all bans including inactive ones.
     *
     * Query parameters:
     * - page: Page number (default: 1)
     * - limit: Results per page, max 100 (default: 50)
     * - show_all: Include inactive bans (true/false, default: false)
     *
     * Required permissions: Head Admin or Admin
     *
     * @return void Outputs JSON response with ip_bans array and pagination info
     * @see IpBanManager::getActiveBans()
     * @see IpBanManager::getAllBans()
     */
    public static function getIPBans(): void
    {
        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['head', 'admin'])) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = ($page - 1) * $limit;
        $showAll = isset($_GET['show_all']) && $_GET['show_all'] === 'true';

        if ($showAll) {
            $bans = IpBanManager::getAllBans($limit, $offset);
        } else {
            $bans = IpBanManager::getActiveBans($limit, $offset);
        }

        Response::success([
            'ip_bans' => $bans,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'offset' => $offset,
                'show_all' => $showAll
            ]
        ]);
    }

    /**
     * Unlock a locked user account (Admin endpoint)
     *
     * Removes the account lock from a user who has been locked due to failed login attempts.
     * The operation is logged via AuditLogger with the administrator who performed the unlock.
     *
     * Request body (JSON):
     * - account_id: User ID to unlock (required, integer)
     *
     * Required permissions: Head Admin or Admin
     * HTTP method: POST
     *
     * @return void Outputs JSON response with success message and unlocked account details
     * @see FailedLoginTracker::unlockAccount()
     */
    public static function unlockAccount(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['head', 'admin'])) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $accountId = (int)($input['account_id'] ?? 0);

        if (!$accountId) {
            Response::error('Account ID is required', 400);
            return;
        }

        // Check if account exists
        $user = Database::fetchOne('SELECT id, username, email FROM users WHERE id = ?', [$accountId]);
        if (!$user) {
            Response::error('User not found', 404);
            return;
        }

        $success = FailedLoginTracker::unlockAccount($accountId, (int)$currentUser['id']);

        if ($success) {
            Response::success([
                'message' => 'Account unlocked successfully',
                'account' => $user
            ]);
        } else {
            Response::error('Failed to unlock account', 500);
        }
    }

    /**
     * Remove an active IP ban (Admin endpoint)
     *
     * Lifts an IP ban, allowing the IP address to access the system again.
     * The operation is logged via AuditLogger with the administrator who removed the ban.
     * Validates IP address format before processing.
     *
     * Request body (JSON):
     * - ip_address: IP address to unban (required, valid IPv4 or IPv6)
     *
     * Required permissions: Head Admin or Admin
     * HTTP method: POST
     *
     * @return void Outputs JSON response with success message or 404 if ban not found
     * @see IpBanManager::removeIPBan()
     */
    public static function removeIPBan(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['head', 'admin'])) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ipAddress = trim($input['ip_address'] ?? '');

        if (empty($ipAddress)) {
            Response::error('IP address is required', 400);
            return;
        }

        // Validate IP format
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            Response::error('Invalid IP address format', 400);
            return;
        }

        $success = IpBanManager::removeIPBan($ipAddress, (int)$currentUser['id']);

        if ($success) {
            Response::success([
                'message' => 'IP ban removed successfully',
                'ip_address' => $ipAddress
            ]);
        } else {
            Response::error('Failed to remove IP ban or ban not found', 404);
        }
    }

    /**
     * Manually ban an IP address (Admin endpoint)
     *
     * Creates a new IP ban with a custom reason. The ban duration is controlled by
     * the security.ip_ban_duration_seconds configuration value. Validates IP address
     * format before processing. The operation is logged via AuditLogger.
     *
     * Request body (JSON):
     * - ip_address: IP address to ban (required, valid IPv4 or IPv6)
     * - reason: Reason for the ban (optional, default: "Manual ban by admin")
     *
     * Required permissions: Head Admin or Admin
     * HTTP method: POST
     *
     * @return void Outputs JSON response with success message and ban details
     * @see IpBanManager::banIP()
     */
    public static function banIP(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['head', 'admin'])) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $ipAddress = trim($input['ip_address'] ?? '');
        $reason = trim($input['reason'] ?? 'Manual ban by admin');

        if (empty($ipAddress)) {
            Response::error('IP address is required', 400);
            return;
        }

        // Validate IP format
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            Response::error('Invalid IP address format', 400);
            return;
        }

        IpBanManager::banIP($ipAddress, $reason, (int)$currentUser['id']);

        Response::success([
            'message' => 'IP banned successfully',
            'ip_address' => $ipAddress,
            'reason' => $reason
        ]);
    }

    /**
     * Retrieve list of currently locked user accounts (Admin endpoint)
     *
     * Returns all user accounts that are currently locked due to failed login attempts.
     * Includes accounts with permanent locks and those with time-based locks that haven't
     * yet expired.
     *
     * Required permissions: Head Admin or Admin
     *
     * @return void Outputs JSON response with locked_accounts array containing id, username, email, role, locked_until, locked_reason, created_at
     */
    public static function getLockedAccounts(): void
    {
        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['head', 'admin'])) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        $lockedAccounts = Database::fetchAll(
            'SELECT id, username, email, role, locked_until, locked_reason, created_at
             FROM users
             WHERE locked_until IS NOT NULL AND (locked_until > NOW() OR locked_until = "0000-00-00 00:00:00")
             ORDER BY locked_until DESC'
        );

        Response::success([
            'locked_accounts' => $lockedAccounts
        ]);
    }

    /**
     * Retrieve security statistics dashboard (Admin endpoint)
     *
     * Provides aggregated security metrics for the last 24 hours including:
     * - Total failed login attempts
     * - Number of unique IPs with failed logins
     * - Count of active IP bans
     * - Count of locked user accounts
     *
     * Useful for security monitoring and threat assessment.
     *
     * Required permissions: Head Admin or Admin
     *
     * @return void Outputs JSON response with statistics object containing failed_logins_24h, active_ip_bans, locked_accounts, unique_ips_failed_24h
     */
    public static function getSecurityStats(): void
    {
        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || !in_array($currentUser['role'], ['head', 'admin'])) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        // Get stats for the last 24 hours
        $since24h = date('Y-m-d H:i:s', time() - 86400);

        $stats = [
            'failed_logins_24h' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM failed_logins WHERE created_at >= ?',
                [$since24h]
            )['count'] ?? 0,

            'active_ip_bans' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM ip_bans WHERE is_active = 1'
            )['count'] ?? 0,

            'locked_accounts' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM users WHERE locked_until IS NOT NULL AND (locked_until > NOW() OR locked_until = "0000-00-00 00:00:00")'
            )['count'] ?? 0,

            'unique_ips_failed_24h' => Database::fetchOne(
                'SELECT COUNT(DISTINCT ip_address) as count FROM failed_logins WHERE created_at >= ?',
                [$since24h]
            )['count'] ?? 0
        ];

        Response::success($stats);
    }

    /**
     * Clean up expired IP bans (Maintenance endpoint - Head Admin only)
     *
     * Marks all expired IP bans as inactive to maintain database hygiene.
     * This is a maintenance operation that should be run periodically.
     *
     * Required permissions: Head Admin only (restricted)
     * HTTP method: POST
     *
     * @return void Outputs JSON response with cleanup message and count of cleaned bans
     * @see IpBanManager::cleanupExpiredBans()
     */
    public static function cleanupExpiredBans(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405);
            return;
        }

        if (!AdminAuth::isAuthenticated()) {
            Response::error('Unauthorized', 401);
            return;
        }

        $currentUser = AdminAuth::getCurrentUser();
        if (!$currentUser || $currentUser['role'] !== 'head') {
            Response::error('Insufficient permissions - head admin only', 403);
            return;
        }

        $cleaned = IpBanManager::cleanupExpiredBans();

        Response::success([
            'message' => 'Cleanup completed',
            'cleaned_count' => $cleaned
        ]);
    }
}
