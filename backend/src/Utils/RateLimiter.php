<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;

class RateLimiter
{
  /**
   * Attempt a rate limited action.
   * @param string $key Unique key (e.g., ip:route)
   * @param int $maxHits Allowed hits per period
   * @param int $periodSeconds Period length
   * @return array{allowed:bool,remaining:int,reset:int}
   */
    public static function attempt(string $key, int $maxHits, int $periodSeconds): array
    {
        $now = time();
        $row = Database::fetchOne('SELECT `key`, hits, period_start FROM rate_limit_keys WHERE `key` = ?', [$key]);
        if (!$row) {
            Database::execute('INSERT INTO rate_limit_keys (`key`, hits, period_start) VALUES (?, 1, CURRENT_TIMESTAMP)', [$key]);
            return ['allowed' => true, 'remaining' => $maxHits - 1, 'reset' => $now + $periodSeconds];
        }
        $periodStart = strtotime($row['period_start']);
        if ($periodStart + $periodSeconds <= $now) {
            Database::execute('UPDATE rate_limit_keys SET hits = 1, period_start = CURRENT_TIMESTAMP WHERE `key` = ?', [$key]);
            return ['allowed' => true, 'remaining' => $maxHits - 1, 'reset' => $now + $periodSeconds];
        }
        $hits = (int)$row['hits'];
        if ($hits >= $maxHits) {
            return ['allowed' => false, 'remaining' => 0, 'reset' => $periodStart + $periodSeconds];
        }
        Database::execute('UPDATE rate_limit_keys SET hits = hits + 1 WHERE `key` = ?', [$key]);
        return ['allowed' => true, 'remaining' => $maxHits - ($hits + 1), 'reset' => $periodStart + $periodSeconds];
    }
}
