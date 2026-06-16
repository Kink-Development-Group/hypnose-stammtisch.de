-- Migration 013: Add 'convention' and 'party' to the event category ENUMs
--
-- Extends the category ENUM on both `events.category` and
-- `event_series.default_category` so events can be categorised as conventions
-- ("Convention") and parties — see GitHub issue #119.
--
-- MODIFY COLUMN re-declares the full ENUM definition. This is idempotent
-- (re-running sets the same definition) and preserves all existing rows, since
-- every prior value remains part of the ENUM. Both tables are created in
-- migration 001, so they always exist by the time this runs — no
-- information_schema guard is needed. Forward-only; no result set is produced,
-- so the runner's PDO::exec() path stays clean (see migrations/README + 008).

ALTER TABLE events
  MODIFY COLUMN category
  ENUM('workshop','stammtisch','practice','lecture','special','convention','party')
  DEFAULT 'stammtisch';

ALTER TABLE event_series
  MODIFY COLUMN default_category
  ENUM('workshop','stammtisch','practice','lecture','special','convention','party')
  DEFAULT 'stammtisch';
