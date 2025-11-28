<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;

class AuditLogger
{
    /**
     * Flag to prevent recursive calls during IP resolution
     * This breaks the circular dependency: AuditLogger::log() -> IpBanManager::getClientIP() -> AuditLogger::log()
     */
    private static bool $isLogging = false;

    public static function log(string $action, ?string $resourceType = null, ?string $resourceId = null, array $meta = []): void
    {
        // Prevent recursive calls that could cause infinite loops and memory exhaustion
        if (self::$isLogging) {
            return;
        }

        self::$isLogging = true;
        try {
            $user = AdminAuth::getCurrentUser();
            $userId = $user['id'] ?? null;
            $ip = IpBanManager::getClientIP();
            $metaJson = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            Database::execute('INSERT INTO audit_logs (user_id, ip_address, action, resource_type, resource_id, meta) VALUES (?, ?, ?, ?, ?, ?)', [
                $userId,
                $ip,
                $action,
                $resourceType,
                $resourceId,
                $metaJson
            ]);
        } catch (\Throwable $e) {
            // Fail silently, but log to PHP error log
            error_log('Audit log insert failed: ' . $e->getMessage());
        } finally {
            self::$isLogging = false;
        }
    }
}
