-- Migration 003: Add start/end time to event_series and parent_event_id to events for instance overrides
-- Forward-only migration

ALTER TABLE event_series
  ADD COLUMN start_time TIME NULL AFTER start_date,
  ADD COLUMN end_time TIME NULL AFTER start_time;

ALTER TABLE events
  ADD COLUMN parent_event_id VARCHAR(36) NULL AFTER series_id,
  ADD INDEX idx_parent_event_id (parent_event_id);

