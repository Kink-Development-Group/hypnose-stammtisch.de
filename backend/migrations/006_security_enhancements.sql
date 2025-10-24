-- Security Enhancement: Account Lockout & IP Blocking - Configuration Update
-- Update security configuration with additional indexes and optimizations

-- Add additional indexes for better performance
CREATE INDEX IF NOT EXISTS idx_failed_logins_account_created ON failed_logins(account_id, created_at);
CREATE INDEX IF NOT EXISTS idx_failed_logins_ip_created ON failed_logins(ip_address, created_at);
CREATE INDEX IF NOT EXISTS idx_ip_bans_active_expires ON ip_bans(is_active, expires_at);

-- Add user agent analysis table for security insights (optional)
CREATE TABLE IF NOT EXISTS failed_login_analytics (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  ip_address VARCHAR(45) NOT NULL,
  user_agent_hash VARCHAR(64) NOT NULL,
  first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  attempt_count INT DEFAULT 1,
  INDEX idx_ip_hash (ip_address, user_agent_hash),
  INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Procedure to clean up old failed login records (GDPR compliance)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanupOldFailedLogins(IN retention_days INT)
BEGIN
  DELETE FROM failed_logins
  WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);

  DELETE FROM failed_login_analytics
  WHERE last_seen < DATE_SUB(NOW(), INTERVAL retention_days DAY);
END//
DELIMITER ;

-- Update existing users table to add security metadata
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS last_failed_login TIMESTAMP NULL AFTER locked_reason,
  ADD COLUMN IF NOT EXISTS failed_login_count INT DEFAULT 0 AFTER last_failed_login,
  ADD COLUMN IF NOT EXISTS security_notes TEXT NULL AFTER failed_login_count;

-- Add indexes for the new columns
CREATE INDEX IF NOT EXISTS idx_users_last_failed_login ON users(last_failed_login);
CREATE INDEX IF NOT EXISTS idx_users_failed_count ON users(failed_login_count);
