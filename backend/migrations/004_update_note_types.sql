-- Idempotent adjustments: Only add / transform if legacy structure present
-- Add note_type column if it does not exist (MySQL 8+)
-- MariaDB Kompatibilität: IF NOT EXISTS ggf. nicht verfügbar -> dynamisch prüfen
SET @needs_add := (
    SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'message_notes'
        AND COLUMN_NAME = 'note_type'
);

SET @add_stmt := 'SELECT 1';
SET @add_stmt = IF(@needs_add = 1,
    'ALTER TABLE message_notes ADD COLUMN note_type ENUM(\'processing\', \'communication\', \'general\') DEFAULT \'general\'',
    @add_stmt
);
PREPARE add_stmt FROM @add_stmt; EXECUTE add_stmt; DEALLOCATE PREPARE add_stmt;

-- Populate note_type from legacy is_internal flag if that column still exists
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'message_notes'
        AND COLUMN_NAME = 'is_internal'
);

-- Only run update if legacy column present and note_type still default
UPDATE message_notes
    SET note_type = CASE WHEN is_internal = 1 THEN 'processing' ELSE 'communication' END
    WHERE @col_exists = 1;

-- Drop legacy column if present
SET @drop_stmt = (
    SELECT CASE WHEN @col_exists = 1 THEN 'ALTER TABLE message_notes DROP COLUMN is_internal' ELSE 'SELECT 1' END
);
PREPARE stmt2 FROM @drop_stmt; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;
