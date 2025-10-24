<?php

declare(strict_types=1);

/**
 * Security maintenance script for automated cleanup
 * Should be run periodically (e.g., via cron job)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\IpBanManager;
use HypnoseStammtisch\Utils\AuditLogger;

// Load configuration
Config::load(__DIR__ . '/..');

class SecurityMaintenance
{
  /**
   * Run all maintenance tasks
   */
  public static function runAll(): void
  {
    echo "Starting security maintenance tasks...\n";

    $results = [
      'expired_bans_cleaned' => self::cleanupExpiredBans(),
      'old_failed_logins_cleaned' => self::cleanupOldFailedLogins(),
      'expired_locks_cleaned' => self::cleanupExpiredAccountLocks(),
      'analytics_updated' => self::updateAnalytics()
    ];

    echo "Maintenance completed:\n";
    foreach ($results as $task => $count) {
      echo "  {$task}: {$count}\n";
    }

    // Log maintenance run
    AuditLogger::log(
      'security.maintenance_completed',
      null,
      null,
      $results
    );
  }

  /**
   * Clean up expired IP bans
   */
  public static function cleanupExpiredBans(): int
  {
    echo "Cleaning up expired IP bans...\n";
    return IpBanManager::cleanupExpiredBans();
  }

  /**
   * Clean up old failed login records (GDPR compliance)
   */
  public static function cleanupOldFailedLogins(int $retentionDays = 90): int
  {
    echo "Cleaning up old failed login records (>{$retentionDays} days)...\n";

    $cutoffDate = date('Y-m-d H:i:s', time() - ($retentionDays * 86400));

    $deleted = Database::execute(
      'DELETE FROM failed_logins WHERE created_at < ?',
      [$cutoffDate]
    );

    if ($deleted > 0) {
      AuditLogger::log(
        'security.old_failed_logins_cleaned',
        null,
        null,
        [
          'deleted_count' => $deleted,
          'retention_days' => $retentionDays,
          'cutoff_date' => $cutoffDate
        ]
      );
    }

    return $deleted;
  }

  /**
   * Clean up expired account locks
   */
  public static function cleanupExpiredAccountLocks(): int
  {
    echo "Cleaning up expired account locks...\n";

    $affected = Database::execute(
      'UPDATE users SET locked_until = NULL, locked_reason = NULL
             WHERE locked_until IS NOT NULL
             AND locked_until != "0000-00-00 00:00:00"
             AND locked_until <= NOW()'
    );

    if ($affected > 0) {
      AuditLogger::log(
        'security.expired_locks_cleaned',
        null,
        null,
        ['cleaned_count' => $affected]
      );
    }

    return $affected;
  }

  /**
   * Update security analytics (if analytics table exists)
   */
  public static function updateAnalytics(): int
  {
    echo "Updating security analytics...\n";

    try {
      // Check if analytics table exists
      $tableExists = Database::fetchOne(
        "SHOW TABLES LIKE 'failed_login_analytics'"
      );

      if (!$tableExists) {
        return 0;
      }

      // Aggregate failed logins by IP and user agent for analytics
      $inserted = Database::execute("
                INSERT INTO failed_login_analytics (ip_address, user_agent_hash, first_seen, last_seen, attempt_count)
                SELECT
                    fl.ip_address,
                    SHA2(COALESCE(fl.user_agent, 'unknown'), 256) as user_agent_hash,
                    MIN(fl.created_at) as first_seen,
                    MAX(fl.created_at) as last_seen,
                    COUNT(*) as attempt_count
                FROM failed_logins fl
                LEFT JOIN failed_login_analytics fla ON fl.ip_address = fla.ip_address
                    AND SHA2(COALESCE(fl.user_agent, 'unknown'), 256) = fla.user_agent_hash
                WHERE fla.id IS NULL
                    AND fl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY fl.ip_address, SHA2(COALESCE(fl.user_agent, 'unknown'), 256)
                ON DUPLICATE KEY UPDATE
                    last_seen = VALUES(last_seen),
                    attempt_count = attempt_count + VALUES(attempt_count)
            ");

      return $inserted;
    } catch (Exception $e) {
      error_log("Analytics update failed: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Generate security report
   */
  public static function generateReport(): array
  {
    $report = [
      'timestamp' => date('Y-m-d H:i:s'),
      'periods' => []
    ];

    $periods = [
      '1_hour' => 3600,
      '24_hours' => 86400,
      '7_days' => 604800,
      '30_days' => 2592000
    ];

    foreach ($periods as $label => $seconds) {
      $since = date('Y-m-d H:i:s', time() - $seconds);

      $report['periods'][$label] = [
        'failed_logins' => Database::fetchOne(
          'SELECT COUNT(*) as count FROM failed_logins WHERE created_at >= ?',
          [$since]
        )['count'] ?? 0,

        'unique_ips' => Database::fetchOne(
          'SELECT COUNT(DISTINCT ip_address) as count FROM failed_logins WHERE created_at >= ?',
          [$since]
        )['count'] ?? 0,

        'unique_accounts' => Database::fetchOne(
          'SELECT COUNT(DISTINCT account_id) as count FROM failed_logins WHERE account_id IS NOT NULL AND created_at >= ?',
          [$since]
        )['count'] ?? 0
      ];
    }

    // Current status
    $report['current_status'] = [
      'active_ip_bans' => Database::fetchOne(
        'SELECT COUNT(*) as count FROM ip_bans WHERE is_active = 1'
      )['count'] ?? 0,

      'locked_accounts' => Database::fetchOne(
        'SELECT COUNT(*) as count FROM users WHERE locked_until IS NOT NULL AND (locked_until > NOW() OR locked_until = "0000-00-00 00:00:00")'
      )['count'] ?? 0,

      'total_users' => Database::fetchOne(
        'SELECT COUNT(*) as count FROM users WHERE is_active = 1'
      )['count'] ?? 0
    ];

    // Top failed login sources
    $report['top_failed_ips'] = Database::fetchAll(
      'SELECT ip_address, COUNT(*) as attempts
             FROM failed_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY ip_address
             ORDER BY attempts DESC
             LIMIT 10'
    );

    return $report;
  }

  /**
   * Check for suspicious patterns and alert
   */
  public static function checkSuspiciousActivity(): array
  {
    $alerts = [];

    // Check for unusual spike in failed logins
    $recentFailures = Database::fetchOne(
      'SELECT COUNT(*) as count FROM failed_logins WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    )['count'] ?? 0;

    if ($recentFailures > 100) { // Configurable threshold
      $alerts[] = [
        'type' => 'high_failed_login_rate',
        'message' => "High number of failed logins in last hour: {$recentFailures}",
        'severity' => 'high'
      ];
    }

    // Check for distributed attacks (many IPs, few attempts each)
    $distributedPattern = Database::fetchOne(
      'SELECT COUNT(DISTINCT ip_address) as unique_ips, COUNT(*) as total_attempts
             FROM failed_logins
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );

    $uniqueIPs = $distributedPattern['unique_ips'] ?? 0;
    $totalAttempts = $distributedPattern['total_attempts'] ?? 0;

    if ($uniqueIPs > 20 && $totalAttempts > 50 && ($totalAttempts / $uniqueIPs) < 5) {
      $alerts[] = [
        'type' => 'distributed_attack_pattern',
        'message' => "Possible distributed attack: {$uniqueIPs} IPs with {$totalAttempts} attempts",
        'severity' => 'medium'
      ];
    }

    // Check for accounts with unusual failure patterns
    $suspiciousAccounts = Database::fetchAll(
      'SELECT u.username, u.email, COUNT(fl.id) as failures
             FROM users u
             JOIN failed_logins fl ON u.id = fl.account_id
             WHERE fl.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY u.id
             HAVING failures > 20
             ORDER BY failures DESC
             LIMIT 5'
    );

    if (!empty($suspiciousAccounts)) {
      $alerts[] = [
        'type' => 'accounts_under_attack',
        'message' => "Accounts with high failure rates: " . count($suspiciousAccounts),
        'data' => $suspiciousAccounts,
        'severity' => 'medium'
      ];
    }

    return $alerts;
  }
}

// Command line handling
if (php_sapi_name() === 'cli') {
  $command = $argv[1] ?? 'all';

  switch ($command) {
    case 'all':
      SecurityMaintenance::runAll();
      break;

    case 'cleanup-bans':
      $cleaned = SecurityMaintenance::cleanupExpiredBans();
      echo "Cleaned up {$cleaned} expired bans.\n";
      break;

    case 'cleanup-logs':
      $retentionDays = isset($argv[2]) ? (int)$argv[2] : 90;
      $cleaned = SecurityMaintenance::cleanupOldFailedLogins($retentionDays);
      echo "Cleaned up {$cleaned} old failed login records.\n";
      break;

    case 'cleanup-locks':
      $cleaned = SecurityMaintenance::cleanupExpiredAccountLocks();
      echo "Cleaned up {$cleaned} expired account locks.\n";
      break;

    case 'report':
      $report = SecurityMaintenance::generateReport();
      echo json_encode($report, JSON_PRETTY_PRINT) . "\n";
      break;

    case 'check-alerts':
      $alerts = SecurityMaintenance::checkSuspiciousActivity();
      if (empty($alerts)) {
        echo "No suspicious activity detected.\n";
      } else {
        echo "Alerts detected:\n";
        foreach ($alerts as $alert) {
          echo "[{$alert['severity']}] {$alert['type']}: {$alert['message']}\n";
        }
      }
      break;

    default:
      echo "Security Maintenance Script\n";
      echo "===========================\n\n";
      echo "Usage: php maintenance.php <command>\n\n";
      echo "Commands:\n";
      echo "  all                     Run all maintenance tasks\n";
      echo "  cleanup-bans            Clean up expired IP bans\n";
      echo "  cleanup-logs [days]     Clean up old failed login records (default: 90 days)\n";
      echo "  cleanup-locks           Clean up expired account locks\n";
      echo "  report                  Generate security report (JSON)\n";
      echo "  check-alerts            Check for suspicious activity\n";
      break;
  }
}
