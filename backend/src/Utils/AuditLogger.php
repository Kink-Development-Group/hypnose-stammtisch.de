<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;

class AuditLogger
{
  public static function log(string $action, ?string $resourceType = null, ?string $resourceId = null, array $meta = []): void
  {
    try {
      $user = AdminAuth::getCurrentUser();
      $userId = $user['id'] ?? null;
      $ip = $_SERVER['REMOTE_ADDR'] ?? null;
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
    }
  }
}
