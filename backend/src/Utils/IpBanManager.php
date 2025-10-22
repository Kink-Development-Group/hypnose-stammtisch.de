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
    $stmt = Database::execute(
      'UPDATE ip_bans SET is_active = 0
             WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at <= NOW()'
    );

    $affected = $stmt->rowCount();

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
   * Get client IP address with proper proxy handling and security validation
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

    // Get trusted proxy configuration
    $trustedProxies = Config::get('security.trusted_proxies') ?? [];
    $allowPrivateIPs = Config::get('security.allow_private_ips') ?? false;

    // Always validate basic IP format first
    $baseValidationFilter = FILTER_VALIDATE_IP;

    // For public-facing applications, exclude private/reserved ranges by default
    // Only allow them if explicitly configured
    $publicValidationFlags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

    foreach ($ipHeaders as $header) {
      if (!empty($_SERVER[$header])) {
        $ips = array_map('trim', explode(',', $_SERVER[$header]));

        foreach ($ips as $ip) {
          // Skip empty values
          if (empty($ip)) {
            continue;
          }

          // First check if it's a valid IP at all
          if (!filter_var($ip, $baseValidationFilter)) {
            continue;
          }

          // If we're processing forwarded headers, validate the source
          if ($header !== 'REMOTE_ADDR' && !empty($trustedProxies)) {
            $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!self::isIPInRanges($remoteAddr, $trustedProxies)) {
              // Don't trust forwarded headers from untrusted sources
              AuditLogger::log(
                'security.untrusted_proxy_header',
                'ip_validation',
                $ip,
                [
                  'header' => $header,
                  'remote_addr' => $remoteAddr,
                  'trusted_proxies' => $trustedProxies
                ]
              );
              continue;
            }
          }

          // Check if private IPs are allowed
          if ($allowPrivateIPs) {
            // Allow private IPs if explicitly configured
            if (filter_var($ip, $baseValidationFilter)) {
              return $ip;
            }
          } else {
            // Default: exclude private and reserved ranges
            if (filter_var($ip, $baseValidationFilter, $publicValidationFlags)) {
              return $ip;
            }
          }
        }
      }
    }

    // Fallback to REMOTE_ADDR with validation
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Validate the fallback IP
    if ($allowPrivateIPs && filter_var($remoteAddr, $baseValidationFilter)) {
      return $remoteAddr;
    } elseif (!$allowPrivateIPs && filter_var($remoteAddr, $baseValidationFilter, $publicValidationFlags)) {
      return $remoteAddr;
    }

    // Log suspicious fallback scenario
    AuditLogger::log(
      'security.ip_validation_fallback',
      'ip_validation',
      $remoteAddr,
      [
        'allow_private_ips' => $allowPrivateIPs,
        'headers_checked' => array_keys($_SERVER),
        'final_fallback' => '0.0.0.0'
      ]
    );

    return '0.0.0.0';
  }

  /**
   * Check if an IP address is within specified CIDR ranges
   */
  private static function isIPInRanges(string $ip, array $ranges): bool
  {
    foreach ($ranges as $range) {
      if (self::isIPInRange($ip, $range)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if an IP is within a CIDR range
   */
  private static function isIPInRange(string $ip, string $range): bool
  {
    // Handle single IP (no CIDR notation)
    if (strpos($range, '/') === false) {
      return $ip === $range;
    }

    list($rangeIP, $netmask) = explode('/', $range, 2);

    // Validate inputs
    if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($rangeIP, FILTER_VALIDATE_IP)) {
      return false;
    }

    // Convert to binary representation
    $ipBinary = inet_pton($ip);
    $rangeIPBinary = inet_pton($rangeIP);

    if ($ipBinary === false || $rangeIPBinary === false) {
      return false;
    }

    // IPv4 vs IPv6 handling
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      if (!filter_var($rangeIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false; // IP version mismatch
      }
      $maxNetmask = 32;
    } else {
      if (!filter_var($rangeIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return false; // IP version mismatch
      }
      $maxNetmask = 128;
    }

    // Validate netmask
    $netmask = (int) $netmask;
    if ($netmask < 0 || $netmask > $maxNetmask) {
      return false;
    }

    // Calculate network addresses
    $ipNetwork = self::getNetworkAddress($ipBinary, $netmask);
    $rangeNetwork = self::getNetworkAddress($rangeIPBinary, $netmask);

    return $ipNetwork === $rangeNetwork;
  }

  /**
   * Get network address for CIDR calculations
   */
  private static function getNetworkAddress(string $ipBinary, int $netmask): string
  {
    $ipLength = strlen($ipBinary) * 8; // Convert bytes to bits
    $maskBytes = '';

    for ($i = 0; $i < strlen($ipBinary); $i++) {
      $byte = 0;
      for ($j = 0; $j < 8; $j++) {
        $bitPosition = $i * 8 + $j;
        if ($bitPosition < $netmask) {
          $byte |= (1 << (7 - $j));
        }
      }
      $maskBytes .= chr($byte);
    }

    return $ipBinary & $maskBytes;
  }
}
