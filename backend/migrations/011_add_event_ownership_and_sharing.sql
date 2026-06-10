-- Migration 011: Event ownership + per-event manager sharing
-- Adds a `created_by` owner column to events and event_series, a polymorphic
-- `event_access` grant table (event OR series shared with another manager),
-- and back-fills existing ownerless records to the primary head admin.
-- NOTE: never add `INSERT INTO migrations` here; the runner records the version itself.

-- Owner column on single events
ALTER TABLE events
  ADD COLUMN created_by INT NULL,
  ADD INDEX idx_created_by (created_by),
  ADD CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Owner column on event series
ALTER TABLE event_series
  ADD COLUMN created_by INT NULL,
  ADD INDEX idx_series_created_by (created_by),
  ADD CONSTRAINT fk_event_series_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Sharing grants: a manager (user_id) is granted access to an event or series.
-- target_id is the UUID of the events.id or event_series.id row (polymorphic, no FK).
CREATE TABLE IF NOT EXISTS event_access (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  target_type ENUM('event','series') NOT NULL,
  target_id VARCHAR(36) NOT NULL,
  user_id INT NOT NULL,
  granted_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_target_user (target_type, target_id, user_id),
  INDEX idx_target (target_type, target_id),
  INDEX idx_user_id (user_id),
  CONSTRAINT fk_event_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_event_access_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Back-fill existing ownerless records to the primary head admin (lowest id).
-- If no head admin exists the subquery yields NULL and the rows stay ownerless.
UPDATE events SET created_by = (SELECT id FROM users WHERE role = 'head' ORDER BY id ASC LIMIT 1) WHERE created_by IS NULL;
UPDATE event_series SET created_by = (SELECT id FROM users WHERE role = 'head' ORDER BY id ASC LIMIT 1) WHERE created_by IS NULL;
