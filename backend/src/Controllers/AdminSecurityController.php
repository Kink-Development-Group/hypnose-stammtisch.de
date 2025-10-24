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
 * Admin security management controller
 */
class AdminSecurityController
{
    /**
     * Get failed login history
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
     * Get IP ban list
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
     * Unlock user account
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
     * Remove IP ban
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
     * Manually ban IP address
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
     * Get locked accounts
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
     * Get security statistics
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
     * Clean up expired bans (maintenance endpoint)
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