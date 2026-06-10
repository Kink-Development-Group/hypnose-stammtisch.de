-- Migration 010: Add index on events.end_datetime
-- Needed after switching upcoming/featured filters from start_datetime to end_datetime
-- to prevent full table scans on those queries.

ALTER TABLE events ADD INDEX idx_end_datetime (end_datetime);
