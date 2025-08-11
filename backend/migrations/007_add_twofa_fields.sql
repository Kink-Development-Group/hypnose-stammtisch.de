-- Migration: Add mandatory 2FA (TOTP) fields to users table
-- Version: 007
-- Date: 2025-08-11

INSERT INTO migrations (version, description)
VALUES ('007', 'Add mandatory 2FA (TOTP) fields to users table')
ON DUPLICATE KEY UPDATE description = VALUES(description);

ALTER TABLE users
  ADD COLUMN twofa_secret VARCHAR(64) NULL AFTER password_hash,
  ADD COLUMN twofa_enabled BOOLEAN NOT NULL DEFAULT FALSE AFTER twofa_secret;

-- Future extension placeholder for hardware keys (WebAuthn)
-- CREATE TABLE user_webauthn_credentials (...);
