-- Migration: Add head admin role
-- Version: 005
-- Date: 2025-08-07

-- Modify the role ENUM to include 'head' role
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'moderator', 'head') DEFAULT 'admin';

-- Insert migration record
INSERT INTO migrations (version, description)
VALUES ('005', 'Add head admin role for user management')
ON DUPLICATE KEY UPDATE description = VALUES(description);
