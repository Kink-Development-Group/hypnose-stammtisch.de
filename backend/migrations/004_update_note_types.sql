-- Migration: Update note type system
-- Version: 004
-- Date: 2025-08-07

-- Add note_type column and migrate data
ALTER TABLE message_notes
ADD COLUMN note_type ENUM('processing', 'communication', 'general') DEFAULT 'general';

-- Migrate existing data: is_internal = true -> 'processing', is_internal = false -> 'communication'
UPDATE message_notes
SET note_type = CASE
    WHEN is_internal = 1 THEN 'processing'
    ELSE 'communication'
END;

-- Remove old is_internal column
ALTER TABLE message_notes
DROP COLUMN is_internal;
