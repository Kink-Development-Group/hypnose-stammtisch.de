-- Migration 008: Rename contact_telegram to contact_fetlife
-- This migration renames the contact_telegram column to contact_fetlife in stammtisch_locations table

ALTER TABLE stammtisch_locations CHANGE COLUMN contact_telegram contact_fetlife VARCHAR(100);
