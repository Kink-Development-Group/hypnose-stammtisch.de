-- Migration: add email change pending fields
ALTER TABLE users
  ADD COLUMN pending_email VARCHAR(255) NULL AFTER email,
  ADD COLUMN email_change_token VARCHAR(64) NULL AFTER pending_email,
  ADD COLUMN email_change_requested_at TIMESTAMP NULL AFTER email_change_token;

INSERT INTO migrations (version, description)
VALUES ('009', 'Add pending email change columns')
ON DUPLICATE KEY UPDATE description = VALUES(description);
