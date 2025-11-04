<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Config\Config;

/**
 * Manages IP address banning for brute-force protection and security enforcement
 *
 * Provides comprehensive IP ban management including automatic ban duration handling,
 * ban expiration checks, and administrative ban management. Integrates with the
 * AuditLogger for full security event tracking.
 *
 * @package HypnoseStammtisch\Utils
 * @see FailedLoginTracker For automatic IP ban triggering
 * @see AuditLogger For security event logging
 * @see Config For ban duration and trusted proxy configuration
 */
class IpBanManager
{
  /**
   * Ban an IP address with optional automatic expiration
   *
   * Creates a new IP ban or updates an existing one with the same IP address.
   * Ban duration is controlled by the security.ip_ban_duration_seconds config value.
   * Set to 0 for permanent bans. Logs all ban events via AuditLogger.
   *
   * @param string $ipAddress The IP address to ban (IPv4 or IPv6)
   * @param string $reason Human-readable reason for the ban (e.g., "Brute force attack detected")
   * @param int|null $bannedBy User ID of the administrator who created the ban, or null for automatic bans
   * @return void
   * @see isIPBanned() To check if an IP is currently banned
   * @see removeIPBan() To manually remove a ban
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
   * Check if an IP address is currently banned
   *
   * Verifies whether an IP is under an active ban and automatically marks
   * expired bans as inactive. This method should be called before processing
   * any sensitive operations from a client IP.
   *
   * @param string $ipAddress The IP address to check (IPv4 or IPv6)
   * @return bool True if the IP is currently banned, false otherwise
   * @see checkIPBanMiddleware() For request-level ban checking
   * @see getBanDetails() To retrieve full ban information
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
   * Retrieve detailed information about an active IP ban
   *
   * Returns comprehensive ban information including the ban reason, who created it,
   * and when it expires. Only retrieves active bans.
   *
   * @param string $ipAddress The IP address to look up (IPv4 or IPv6)
   * @return array|null Associative array with ban details including 'reason', 'banned_by_username', 'created_at', 'expires_at', or null if no active ban exists
   * @see isIPBanned() To check ban status without retrieving details
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
   * Manually remove an active IP ban (administrative function)
   *
   * Marks an active IP ban as inactive, effectively unbanning the IP address.
   * This operation is logged via AuditLogger with the administrator who performed it.
   * Use this to lift automatic bans or correct false positives.
   *
   * @param string $ipAddress The IP address to unban (IPv4 or IPv6)
   * @param int|null $removedBy User ID of the administrator removing the ban
   * @return bool True if a ban was removed, false if no active ban existed
   * @see banIP() To create a new ban
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
   * Retrieve a paginated list of currently active IP bans (administrative function)
   *
   * Returns active bans ordered by creation date (newest first) with optional
   * pagination support. Includes the username of the administrator who created each ban.
   *
   * @param int $limit Maximum number of bans to return (default: 100)
   * @param int $offset Number of records to skip for pagination (default: 0)
   * @return array Array of associative arrays, each containing ban details including 'ip_address', 'reason', 'banned_by_username', 'created_at', 'expires_at'
   * @see getAllBans() To retrieve both active and inactive bans
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
   * Retrieve a paginated list of all IP bans including inactive ones (administrative audit function)
   *
   * Returns complete ban history ordered by creation date (newest first) with optional
   * pagination support. Useful for security audits and historical ban analysis.
   *
   * @param int $limit Maximum number of bans to return (default: 100)
   * @param int $offset Number of records to skip for pagination (default: 0)
   * @return array Array of associative arrays, each containing ban details including 'ip_address', 'reason', 'banned_by_username', 'is_active', 'created_at', 'expires_at'
   * @see getActiveBans() To retrieve only currently active bans
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
   * Mark all expired IP bans as inactive (maintenance function)
   *
   * Scans for IP bans where the expiration date has passed and marks them as inactive.
   * This cleanup task should be run periodically (e.g., via cron job) to maintain
   * database hygiene. Logs cleanup operations via AuditLogger.
   *
   * @return int Number of expired bans that were cleaned up
   * @see isIPBanned() For automatic expiration checks on individual IPs
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
   * Check IP ban status for middleware/request processing
   *
   * Comprehensive IP ban check suitable for use in request middleware.
   * Returns structured information about the ban status and logs access
   * attempts from banned IPs for security monitoring.
   *
   * @param string $ipAddress The IP address to check (IPv4 or IPv6)
   * @return array Associative array with 'blocked' (bool) key and optional 'reason' and 'ban_details' if blocked
   * @see isIPBanned() For simpler boolean ban check
   * @see getClientIP() To obtain the client's IP address with proxy support
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
   * Get the client's real IP address with comprehensive proxy handling and security validation
   *
   * Intelligently detects the client's IP address while handling common proxy headers
   * (Cloudflare, load balancers, reverse proxies). Validates trusted proxy sources
   * to prevent header spoofing attacks. Filters out private/reserved IP ranges
   * unless explicitly allowed in configuration.
   *
   * Priority order: CF-Connecting-IP → X-Forwarded-For → REMOTE_ADDR
   *
   * Security features:
   * - Validates all IP addresses for correct format
   * - Checks trusted proxy configuration before accepting forwarded headers
   * - Excludes private/reserved IP ranges by default (configurable)
   * - Rate-limited audit logging for untrusted proxy attempts
   *
   * @return string The client's IP address, or '0.0.0.0' if validation fails
   * @see checkIPBanMiddleware() To use the detected IP for ban checking
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
              // Use rate-limited audit logging to prevent log spam during attacks
              self::logUntrustedProxyHeaderRateLimited($ip, $header, $remoteAddr, $trustedProxies);
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
   * Log untrusted proxy header attempts with rate limiting to prevent log spam
   *
   * When an IP address is provided via a proxy header (e.g., X-Forwarded-For) but
   * the request originates from an untrusted proxy, this method logs the attempt
   * with intelligent rate limiting. This prevents log flooding during attacks while
   * maintaining visibility of suspicious activity.
   *
   * @param string $ip The IP address claimed in the forwarded header
   * @param string $header The name of the HTTP header (e.g., 'HTTP_X_FORWARDED_FOR')
   * @param string $remoteAddr The actual remote address that sent the request
   * @param array $trustedProxies List of trusted proxy IP ranges from configuration
   * @return void
   * @see getClientIP() For the main IP detection logic
   */
  private static function logUntrustedProxyHeaderRateLimited(
    string $ip,
    string $header,
    string $remoteAddr,
    array $trustedProxies
  ): void {
    // Get rate limit configuration
    $rateLimitConfig = Config::get('security.audit_log_rate_limit.untrusted_proxy_header');
    $maxLogs = $rateLimitConfig['max_logs'] ?? 10;
    $periodSeconds = $rateLimitConfig['period_seconds'] ?? 300;

    // Create a rate limit key based on remote address and header
    // This groups attempts by the actual source IP
    $rateLimitKey = "audit:untrusted_proxy:{$remoteAddr}:{$header}";

    $rateLimit = RateLimiter::attempt($rateLimitKey, $maxLogs, $periodSeconds);

    if ($rateLimit['allowed']) {
      // Log individual event if within rate limit
      AuditLogger::log(
        'security.untrusted_proxy_header',
        'ip_validation',
        $ip,
        [
          'header' => $header,
          'remote_addr' => $remoteAddr,
          'trusted_proxies' => $trustedProxies,
          'rate_limit_remaining' => $rateLimit['remaining']
        ]
      );
    } else {
      // Rate limit exceeded - log an aggregated warning once per period
      $aggregateKey = "audit:untrusted_proxy_aggregate:{$remoteAddr}";
      $aggregateLimit = RateLimiter::attempt($aggregateKey, 1, $periodSeconds);

      if ($aggregateLimit['allowed']) {
        AuditLogger::log(
          'security.untrusted_proxy_header_rate_limited',
          'ip_validation',
          $remoteAddr,
          [
            'message' => 'Multiple untrusted proxy header attempts detected',
            'max_logs_per_period' => $maxLogs,
            'period_seconds' => $periodSeconds,
            'rate_limit_reset' => $rateLimit['reset'],
            'source_ip' => $remoteAddr
          ]
        );
      }
    }
  }

  /**
   * Check if an IP address matches any of the specified CIDR ranges
   *
   * Iterates through multiple CIDR ranges and returns true if the IP
   * matches any of them. Supports both IPv4 and IPv6 addresses.
   *
   * @param string $ip The IP address to check (IPv4 or IPv6)
   * @param array $ranges Array of CIDR ranges (e.g., ['192.168.1.0/24', '10.0.0.0/8'])
   * @return bool True if the IP is within any of the specified ranges
   * @see isIPInRange() For single range checking
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
   * Check if an IP address is within a specific CIDR range
   *
   * Performs precise CIDR range matching with support for both IPv4 and IPv6.
   * Validates input formats and ensures IP version consistency (IPv4 vs IPv6).
   *
   * @param string $ip The IP address to check (IPv4 or IPv6)
   * @param string $range The CIDR range (e.g., '192.168.1.0/24' or '2001:db8::/32') or a single IP
   * @return bool True if the IP is within the specified range
   * @see isIPInRanges() To check against multiple ranges
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
   * Calculate the network address from an IP and netmask for CIDR comparisons
   *
   * Applies a subnet mask to a binary IP address to extract the network portion.
   * This is used internally for CIDR range matching calculations.
   *
   * @param string $ipBinary Binary representation of the IP address (from inet_pton)
   * @param int $netmask The subnet mask length (0-32 for IPv4, 0-128 for IPv6)
   * @return string Binary representation of the network address, or empty string on error
   * @see isIPInRange() For the primary CIDR matching logic
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

    if (strlen($ipBinary) !== strlen($maskBytes)) {
      return '';
    }
    return $ipBinary & $maskBytes;
  }
}
