-- Migration 015: Known event series ("Bekannte Event-Reihen" section on the home page)
--
-- The section used to be a hardcoded array in the frontend component, so every
-- editorial change (a new series, a changed price, a series that stopped) needed
-- a commit and a deployment — and the "next date" text went stale on its own.
--
-- The table is deliberately NOT called `event_series`: that name is already taken
-- by the recurring-series definitions of our own events. A row here is an editorial
-- card that describes a series, ours or somebody else's; `linked_series_id` connects
-- it to an `event_series` row when we do run it ourselves, so the next date can be
-- computed instead of typed.
--
-- NOTE: never add `INSERT INTO migrations` here; the runner records the version itself.

CREATE TABLE IF NOT EXISTS known_event_series (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  location VARCHAR(255) NOT NULL,
  frequency VARCHAR(255) NOT NULL,
  description TEXT,
  formats JSON,
  price VARCHAR(100),
  tags JSON,
  detail_url VARCHAR(500) NOT NULL DEFAULT '/events',
  next_event_source ENUM('auto', 'manual', 'none') NOT NULL DEFAULT 'none',
  next_event_text VARCHAR(255) NULL,
  linked_series_id VARCHAR(36) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_status (status),
  INDEX idx_is_active (is_active),
  INDEX idx_sort_order (sort_order),
  INDEX idx_slug (slug),
  INDEX idx_linked_series_id (linked_series_id),

  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (linked_series_id) REFERENCES event_series(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Take over the three series that were hardcoded in the frontend so the home page
-- looks unchanged after the deployment. The "next date" starts out as `none`: the
-- dates in the old array were long past, and an admin can either link the series
-- or type a current date. Each insert is guarded by its own slug, so re-running the
-- runner (the CI smoke test does) adds nothing and an environment already curated
-- through the admin UI keeps its own rows.

INSERT INTO known_event_series (
  title, slug, location, frequency, description, formats, price, tags,
  detail_url, next_event_source, sort_order, status, is_active
)
SELECT
  'Hamburger Hypnose Munch',
  'hamburger-hypnose-munch',
  'Hamburg • Club Catonium',
  'Monatlich • Jeden 1. Freitag',
  'Monatliche Treffen im Club Catonium mit verschiedenen Formaten:',
  '["Hypnose 101/102 - Einsteiger-Abende", "Koalabox - Praxis-Abende für Geübte", "Sonderthemen und Workshops"]',
  '10€ Eintritt',
  '["Alle Levels", "Erotisch", "Community"]',
  '/events',
  'none',
  1,
  'published',
  1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM known_event_series WHERE slug = 'hamburger-hypnose-munch');

INSERT INTO known_event_series (
  title, slug, location, frequency, description, formats, price, tags,
  detail_url, next_event_source, sort_order, status, is_active
)
SELECT
  'Hypnose-Stammtisch Rhein-Main',
  'hypnose-stammtisch-rhein-main',
  'Mainz • Rhein-Main-Gebiet',
  'Monatlich • Jeden 2. Samstag',
  'Treffen für Begeisterte der erotischen, BDSM- und Freizeithypnose:',
  '["Austausch und Networking", "Gemeinsames Ausprobieren", "Entspannte Atmosphäre"]',
  'Kostenfrei',
  '["Erotisch", "BDSM", "Vernetzung"]',
  '/events',
  'none',
  2,
  'published',
  1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM known_event_series WHERE slug = 'hypnose-stammtisch-rhein-main');

INSERT INTO known_event_series (
  title, slug, location, frequency, description, formats, price, tags,
  detail_url, next_event_source, sort_order, status, is_active
)
SELECT
  'Hypno Study Frankfurt',
  'hypno-study-frankfurt',
  'Frankfurt • Eventspace',
  'Monatlich • Jeden 3. Mittwoch',
  'Deeptalk-Stammtisch mit Fokus auf Techniken und Diskussionen:',
  '["Technischer Austausch", "English speakers welcome", "Fortgeschrittene Methoden"]',
  '12€ Unkostenbeitrag',
  '["Fortgeschritten", "Techniken", "International"]',
  '/events',
  'none',
  3,
  'published',
  1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM known_event_series WHERE slug = 'hypno-study-frankfurt');
