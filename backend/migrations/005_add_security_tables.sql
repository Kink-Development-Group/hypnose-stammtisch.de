-- Security Enhancement: Account Lockout & IP Blocking
-- Add tables for tracking failed logins and IP bans

-- Table for tracking failed login attempts
CREATE TABLE IF NOT EXISTS failed_logins (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  account_id INT NULL,
  username_entered VARCHAR(255) NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_account_id (account_id),
  INDEX idx_ip_address (ip_address),
  INDEX idx_created_at (created_at),
  FOREIGN KEY (account_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for managing IP bans
CREATE TABLE IF NOT EXISTS ip_bans (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  ip_address VARCHAR(45) NOT NULL UNIQUE,
  reason VARCHAR(255) NOT NULL,
  banned_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,
  is_active BOOLEAN DEFAULT TRUE,
  INDEX idx_ip_address (ip_address),
  INDEX idx_expires_at (expires_at),
  INDEX idx_is_active (is_active),
  FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add locked_until field to users table for account lockouts
ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL AFTER is_active;
ALTER TABLE users ADD COLUMN locked_reason VARCHAR(255) NULL AFTER locked_until;
ALTER TABLE users ADD INDEX idx_locked_until (locked_until);