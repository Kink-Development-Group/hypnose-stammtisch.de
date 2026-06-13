-- Migration 008: Rename contact_telegram to contact_fetlife in stammtisch_locations
-- Safe: only renames if the column still exists under the old name.
-- Fresh installs: table does not yet exist (created in 009), so this is a no-op.
-- Existing installs: renames the column if needed.
--
-- The no-op branch must NOT return a client result set: the migration runner
-- executes each statement via PDO::exec(), and an unconsumed result set (e.g.
-- from EXECUTE-ing a `SELECT`) leaves the connection with an active cursor,
-- breaking the following DEALLOCATE with "unbuffered queries are active"
-- (errno 2014) on a fresh database. `SET @var` is preparable and returns no
-- result set, so both branches stay result-set free.

SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'stammtisch_locations'
     AND COLUMN_NAME  = 'contact_telegram') > 0,
  'ALTER TABLE stammtisch_locations CHANGE COLUMN contact_telegram contact_fetlife VARCHAR(100)',
  'SET @_rename_noop = 1'
) INTO @_rename_sql;

PREPARE _rename_stmt FROM @_rename_sql;
EXECUTE _rename_stmt;
DEALLOCATE PREPARE _rename_stmt;
