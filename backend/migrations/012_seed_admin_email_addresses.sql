-- Migration 012: Seed a default sender address for admin_email_addresses
-- The "reply by email" backend feature (AdminMessagesController::getEmailAddresses)
-- only returns rows WHERE is_active = 1. On SFTP + migrate-endpoint deploys the
-- table is otherwise populated solely by the CLI setup (setupEmailAddresses), which
-- never runs in that pipeline — so on beta/prod the "Absender" dropdown stays empty
-- and no reply can be sent. Seed one active default address so replying works out
-- of the box. Mirrors the default used by setupEmailAddresses() (contact.general).
--
-- Idempotent + non-destructive: the row is only inserted when the table has no rows
-- at all, so an environment already configured via the CLI setup or the admin UI is
-- left untouched (we never add a competing default to an existing configuration).
-- NOTE: never add `INSERT INTO migrations` here; the runner records the version itself.

INSERT INTO admin_email_addresses (email, display_name, department, is_default, is_active)
SELECT 'info@hypnose-stammtisch.de', 'Allgemein', 'allgemein', 1, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM admin_email_addresses);
