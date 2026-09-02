# Migrations (Refactored)

Dieses Projekt nutzt eine _Single Baseline_ Migration (`001_initial_schema.sql`), die das komplette Schema enthält.

## Hintergrund

Historisch existierten viele inkrementelle Migrationen (002–011). Diese wurden entfernt, weil:

- Häufige Refactors inkonsistente Zwischenzustände erzeugten
- Setup auf Shared Hosting deterministisch & idempotent sein soll
- Ein frischer Deploy nur eine vollständige Schema-Erstellung benötigt

## Aktuelles Vorgehen

- `migrate.php` führt ausschließlich `001_initial_schema.sql` aus
- Die Tabelle `migrations` erhält Eintrag Version `001`
- Weitere Schemaänderungen in Zukunft: entweder
  1. Neue inkrementelle Migrationen ab `002` (forward-only)
  2. Oder (solange keine produktiven Daten) erneutes Ersetzen der Baseline

## Upgrade bestehender Installationen

Falls bereits eine frühere, fragmentierte DB existiert:

1. Backup anlegen
2. Leere Datenbank + frisches Setup mit Baseline ausführen
3. Relevante Daten (Events, Submissions) selektiv importieren

Direkte automatische Konvertierung alter Tabellen entfällt bewusst zur Minimierung von Fehlerrisiken.

## Migrationen produktiv ausführen (per URL)

Der Deploy führt Migrationen **nicht** automatisch aus – sie sind ein manueller
Schritt gegen die Live-DB. Auf Shared Hosting ohne CLI-Zugriff geht das über den
abgesicherten Web-Endpoint [`/api/setup.php`](../api/setup.php)
([`SetupController`](../src/Controllers/SetupController.php)).

Der Token steht als `SETUP_TOKEN` (ersatzweise `INSTALL_TOKEN`) in der `.env` des
Servers und ist in Production Pflicht.

1. **Status prüfen** (ändert nichts, zeigt fehlende Migrationen):

   ```text
   https://<host>/api/setup.php?action=status&token=<SETUP_TOKEN>
   ```

2. **Migrationen ausführen** (nur `runMigrations()`, kein Seeding – bestehende
   Daten bleiben unberührt; bereits gelaufene Versionen werden übersprungen):

   ```text
   https://<host>/api/setup.php?action=migrate&token=<SETUP_TOKEN>
   ```

3. **Kontrolle:** `action=status` erneut aufrufen.

### Stolperfallen

- **Lock:** Nach erfolgreichem Setup legt der Controller `.setup.lock` an. Existiert
  die Datei bereits, antwortet der Endpoint mit `403 "Setup gesperrt"`. Dann
  `&force=1` anhängen:

  ```text
  https://<host>/api/setup.php?action=migrate&token=<SETUP_TOKEN>&force=1
  ```

- **Kein Token gesetzt:** Ist `SETUP_TOKEN` in der `.env` leer, kommt
  `403 "SETUP_TOKEN nicht konfiguriert"` – Token erst in der `.env` setzen.
- **Erreichbarkeit:** Die `.php`-Variante (`/api/setup.php?...`) trifft die Datei
  direkt; bei aktivem Rewrite funktioniert auch `/api/setup?...`.
- **Alternative mit CLI-Zugriff:** `php backend/migrations/migrate.php migrate`.

## Tests

Empfohlenes Schnelltest-Skript (Pseudo):

```bash
php backend/setup.php --no-seed
php backend/scripts/check_tables.php
```

Alle erwarteten Tabellen (siehe Baseline-Datei) müssen vorhanden sein.

## Tabellenüberblick (Auszug)

- events, event_series, event_registrations
- users (+ 2FA + pending email fields), user_twofa_backup_codes, user_webauthn_credentials (Passkeys)
- contact_submissions, submissions
- message_notes, message_responses, admin_email_addresses
- calendar_feed_tokens, sessions
- rate_limits (legacy IP+Endpoint), rate_limit_keys (generischer Limiter)
- audit_logs

## Zukunft

Sobald erste produktive Daten persistent sind:

- Keine Rewrites der Baseline mehr
- Nur additive, forward-only Migrationen hinzufügen (002,003,...)
