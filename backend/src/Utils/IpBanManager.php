<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Config\Config;

/**
 * Handles IP banning and management
 */
class IpBanManager
{
    /**
     * Ban an IP address
     */
    public static function banIP(string $ipAddress, string $reason, ?int $bannedBy = null): void
    {
        $banDurationSeconds = Config::get('security.ip_ban_duration_seconds');
        $expiresAt = null;
        
        if ($banDurationSeconds > 0) {
            $expiresAt = date('Y-m-d H:i:s', time() + $banDurationSeconds);
        }
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing bans
        Database::execute(
            'INSERT INTO ip_bans (ip_address, reason, banned_by, expires_at, is_active) 
             VALUES (?, ?, ?, ?, 1) 
             ON DUPLICATE KEY UPDATE 
             reason = VALUES(reason), 
             banned_by = VALUES(banned_by), 
             expires_at = VALUES(expires_at), 
             is_active = 1, 
             created_at = CURRENT_TIMESTAMP',
            [$ipAddress, $reason, $bannedBy, $expiresAt]
        );
        
        AuditLogger::log(
            'security.ip_banned',
            'ip_ban',
            $ipAddress,
            [
                'reason' => $reason,
                'banned_by' => $bannedBy,
                'expires_at' => $expiresAt,
                'duration_seconds' => $banDurationSeconds
            ]
        );
    }

    /**
     * Check if an IP is currently banned
     */
    public static function isIPBanned(string $ipAddress): bool
    {
        $ban = Database::fetchOne(
            'SELECT id, expires_at FROM ip_bans WHERE ip_address = ? AND is_active = 1',
            [$ipAddress]
        );
        
        if (!$ban) {
            return false;
        }
        
        // Check if ban has expired
        if ($ban['expires_at'] && strtotime($ban['expires_at']) <= time()) {
            // Mark as inactive
            Database::execute(
                'UPDATE ip_bans SET is_active = 0 WHERE ip_address = ?',
                [$ipAddress]
            );
            
            AuditLogger::log(
                'security.ip_ban_expired',
                'ip_ban',
                $ipAddress
            );
            
            return false;
        }
        
        return true;
    }

    /**
     * Get ban details for an IP
     */
    public static function getBanDetails(string $ipAddress): ?array
    {
        return Database::fetchOne(
            'SELECT ib.*, u.username as banned_by_username 
             FROM ip_bans ib 
             LEFT JOIN users u ON ib.banned_by = u.id 
             WHERE ib.ip_address = ? AND ib.is_active = 1',
            [$ipAddress]
        );
    }

    /**
     * Manually remove IP ban (admin function)
     */
    public static function removeIPBan(string $ipAddress, ?int $removedBy = null): bool
    {
        $affected = Database::execute(
            'UPDATE ip_bans SET is_active = 0 WHERE ip_address = ? AND is_active = 1',
            [$ipAddress]
        );
        
        if ($affected > 0) {
            AuditLogger::log(
                'security.ip_ban_removed',
                'ip_ban',
                $ipAddress,
                ['removed_by' => $removedBy]
            );
            
            return true;
        }
        
        return false;
    }

    /**
     * Get list of active IP bans (admin function)
     */
    public static function getActiveBans(int $limit = 100, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT ib.*, u.username as banned_by_username 
             FROM ip_bans ib 
             LEFT JOIN users u ON ib.banned_by = u.id 
             WHERE ib.is_active = 1 
             ORDER BY ib.created_at DESC 
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /**
     * Get all IP bans (including inactive) for audit (admin function)
     */
    public static function getAllBans(int $limit = 100, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT ib.*, u.username as banned_by_username 
             FROM ip_bans ib 
             LEFT JOIN users u ON ib.banned_by = u.id 
             ORDER BY ib.created_at DESC 
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /**
     * Clean up expired bans (maintenance function)
     */
    public static function cleanupExpiredBans(): int
    {
        $affected = Database::execute(
            'UPDATE ip_bans SET is_active = 0 
             WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at <= NOW()'
        );
        
        if ($affected > 0) {
            AuditLogger::log(
                'security.expired_bans_cleaned',
                null,
                null,
                ['cleaned_count' => $affected]
            );
        }
        
        return $affected;
    }

    /**
     * Check IP ban before processing request (middleware function)
     */
    public static function checkIPBanMiddleware(string $ipAddress): ?array
    {
        if (self::isIPBanned($ipAddress)) {
            $banDetails = self::getBanDetails($ipAddress);
            
            AuditLogger::log(
                'security.banned_ip_access_attempt',
                'ip_ban',
                $ipAddress,
                [
                    'ban_reason' => $banDetails['reason'] ?? 'Unknown',
                    'ban_created_at' => $banDetails['created_at'] ?? null
                ]
            );
            
            return [
                'blocked' => true,
                'reason' => 'IP address is banned',
                'ban_details' => $banDetails
            ];
        }
        
        return ['blocked' => false];
    }

    /**
     * Get client IP address with proper proxy handling
     */
    public static function getClientIP(): string
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        // Determine environment (default to 'production' if not set)
        $environment = Config::get('environment') ?? 'production';
        // Set IP validation flags based on environment
        $ipValidationFlags = FILTER_VALIDATE_IP;
        if ($environment === 'production') {
            $ipValidationFlags |= FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        }

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                if (filter_var($ip, $ipValidationFlags)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}