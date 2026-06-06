-- Migration 004: Series overrides & cancellations + unique constraint
-- Adds override_type + cancellation_reason to events for series overrides
-- Adds unique constraint on (series_id, instance_date)
-- Forward-only

ALTER TABLE events
  ADD COLUMN override_type ENUM('changed','cancelled') NULL AFTER parent_event_id,
  ADD COLUMN cancellation_reason TEXT NULL AFTER override_type;

-- Unique constraint to ensure only one override (changed or cancelled) per series instance
ALTER TABLE events
  ADD UNIQUE KEY unique_series_instance (series_id, instance_date);

