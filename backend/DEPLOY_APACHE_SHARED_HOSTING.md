# Deployment auf Hetzner (Apache Shared Hosting)

Diese Anleitung beschreibt, wie das Backend (PHP API) ohne eigenen vServer auf einem klassischen Hetzner Webhosting-Paket betrieben werden kann.

## 1. Voraussetzungen

- PHP >= 8.1 (im Hetzner KonsoleH Backend aktivieren)
- MySQL/MariaDB Datenbank (Zugangsdaten in `.env`)
- Composer lokal ausgeführt (Vendor-Verzeichnis hochladen) **oder** falls möglich SSH + Composer auf dem Webspace
- RewriteEngine (mod_rewrite) aktiviert (standardmäßig bei Hetzner aktiv)

## 2. Verzeichnisstruktur auf dem Webspace

```text
web/              # Document Root (z.B. /usr/home/XYZ/html)
  api/            # -> Inhalt aus backend/api
    index.php
    admin.php
    .htaccess
    install.php   # Nur temporär für Migration/Seeding!
  assets/ (optional falls benötigt)
  ... Frontend Build (dist) falls SPA ausgeliefert wird ...

backend/          # (optional ausserhalb Document Root belassen) Quellcode + vendor
```

Empfehlung: Alles aus `backend/` ausser `backend/api` eine Ebene über das Document Root legen (sofern Hosting das erlaubt). Falls nicht möglich: `.htaccess` Härtung beachten.

## 3. .env Datei

Erstelle `backend/.env` (oder falls du alles INS web/ legst: dort) mit z.B.:

```env
APP_ENV=production
APP_DEBUG=false
BASE_URL=https://example.org

DB_HOST=mysql57XX.db.hetzner.internal
DB_PORT=3306
DB_NAME=yourdbname
DB_USER=yourdbuser
DB_PASS=secret
DB_CHARSET=utf8mb4

INSTALL_TOKEN=ein-langes-einmaliges-token
```

## 4. Abhängigkeiten (Composer)

Auf lokalem Rechner ausführen:

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

Dann komplettes `backend/vendor` hochladen.

## 5. Migration & Seeding ohne CLI

Rufe im Browser auf:

```text
https://example.org/api/install.php?token=INSTALL_TOKEN&action=migrate
```

Optional anschließend:

```text
https://example.org/api/install.php?token=INSTALL_TOKEN&action=seed
```

ODER in einem Schritt:

```text
https://example.org/api/install.php?token=INSTALL_TOKEN&action=fresh
```

Wenn alles durchgelaufen ist: `install.php` löschen oder `INSTALL_TOKEN` entfernen / ändern.

## 6. Sicherheit / Hardening

- `install.php` nach Deployment entfernen
- `APP_DEBUG=false`
- Starke `INSTALL_TOKEN` Zufallszeichenkette verwenden
- Regelmäßige Rotation der Backup Codes für 2FA nur intern möglich — kein öffentliches Listing hinzufügen
- Falls `backend/` innerhalb des Document Roots liegen muss: zusätzliche `.htaccess` mit Deny für `migrations/`, `vendor/`, `logs/`, `config/` hinzufügen.

Beispiel zusätzliche `.htaccess` (im `backend/` Hauptordner, falls öffentlich erreichbar):

```apacheconf
<IfModule mod_authz_core.c>
  <Directory "migrations">
    Require all denied
  </Directory>
  <Directory "vendor">
    Require all denied
  </Directory>
</IfModule>
<FilesMatch "(\.env|\.log|composer\.(json|lock))$">
  Require all denied
</FilesMatch>
```

## 7. Frontend (Svelte) Deployment

Frontend per `npm run build` erzeugen und den Inhalt des `dist` Ordners (oder Vite Build Output) ins Document Root kopieren. API-Aufrufe verweisen auf `/api/...`.

## 8. Cron / Geplante Aufgaben

Hetzner Webhosting erlaubt Cronjobs über KonsoleH. Beispiel (täglich Cache Räumung o.ä.):

```bash
php /pfad/zum/backend/scripts/maintenance.php
```

(Datei nach Bedarf anlegen.)

## 9. Fehlerbehebung

- 500 Fehler: Logs prüfen (falls eingerichtet) oder temporär `APP_DEBUG=true` setzen (kurzzeitig!)
- CORS Probleme: `config/app.php` Eintrag `cors.allowed_origins` prüfen (BASE_URL korrekt?)
- Weiße Seite bei install.php: Prüfen ob `INSTALL_TOKEN` stimmt und PHP-Version >= 8.1.

## 10. Nächste Schritte (Optionale Verbesserungen)

- Token-Expiry für E-Mail-Bestätigung
- Audit-Logging in eigene Tabelle
- Rate-Limiting Middleware (IP + Endpoint)
- Entfernung des Seeders im Live-System

---

Stand: 2025-08-11
