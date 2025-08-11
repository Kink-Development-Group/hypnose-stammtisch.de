-- Create rate_limits table for simple DB-based throttling
CREATE TABLE IF NOT EXISTS rate_limits (
  `key` VARCHAR(160) NOT NULL PRIMARY KEY,
  `hits` INT NOT NULL DEFAULT 0,
  `period_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_period_start (period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
