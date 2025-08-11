-- Migration 002: Add event_manager role to users.role enum
-- Forward-only: modifies ENUM to include new value

ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','moderator','head','event_manager') NOT NULL DEFAULT 'admin';
