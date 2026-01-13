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
    setup.php     # Einheitlicher Setup-Endpunkt
    .htaccess
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

# Setup-Token für Web-basiertes Setup (Required in Production!)
SETUP_TOKEN=ein-langes-einmaliges-token
```

## 4. Abhängigkeiten (Composer)

Auf lokalem Rechner ausführen:

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

Dann komplettes `backend/vendor` hochladen.

## 5. Setup ohne CLI (Web-basiert)

### 5.1 Status prüfen

```text
https://example.org/api/setup.php?action=status&token=SETUP_TOKEN
```

### 5.2 System-Anforderungen validieren

```text
https://example.org/api/setup.php?action=validate&token=SETUP_TOKEN
```

### 5.3 Vollständiges Setup (empfohlen für Erstinstallation)

```text
https://example.org/api/setup.php?action=full&token=SETUP_TOKEN
```

Dies führt aus:

1. Datenbank erstellen (falls nicht vorhanden)
2. Alle Migrationen ausführen
3. Beispieldaten einspielen
4. Standard Head-Admin anlegen

### 5.4 Nur Migrationen

```text
https://example.org/api/setup.php?action=migrate&token=SETUP_TOKEN
```

### 5.5 Custom Admin erstellen

```text
https://example.org/api/setup.php?action=admin&admin_email=admin@example.com&admin_pass=sicheres-passwort&token=SETUP_TOKEN
```

### 5.6 Verfügbare Aktionen

| Aktion     | Beschreibung                                      |
| ---------- | ------------------------------------------------- |
| `status`   | Zeigt Status und verfügbare Aktionen              |
| `validate` | Prüft System-Anforderungen                        |
| `migrate`  | Führt nur Datenbank-Migrationen aus               |
| `seed`     | Spielt nur Beispieldaten ein                      |
| `fresh`    | Migrate + Seed (Clean Install)                    |
| `admin`    | Erstellt/Aktualisiert Admin-Benutzer              |
| `full`     | Komplettes Setup (empfohlen für Erstinstallation) |

### 5.7 Parameter

| Parameter     | Beschreibung                                         |
| ------------- | ---------------------------------------------------- |
| `action`      | Auszuführende Aktion (required)                      |
| `token`       | Setup-Token aus .env (required in production)        |
| `force`       | `1` = Erzwingt Ausführung auch wenn bereits gesperrt |
| `admin_email` | E-Mail für Admin (bei action=admin/full)             |
| `admin_pass`  | Passwort für Admin (bei action=admin/full)           |
| `no_seed`     | `1` = Überspringt Seeding bei full Setup             |

## 6. Setup mit CLI (falls SSH verfügbar)

Falls SSH-Zugang besteht, ist das CLI-Tool zu bevorzugen:

```bash
cd backend
php cli/cli.php setup              # Vollständiges Setup
php cli/cli.php setup database     # Nur Datenbank
php cli/cli.php setup admin        # Nur Admin erstellen
php cli/cli.php migrate            # Nur Migrationen
```

## 7. Sicherheit / Hardening

- **SETUP_TOKEN nach Deployment ändern** oder Setup sperren (`.setup.lock` wird automatisch erstellt)
- `APP_DEBUG=false`
- Starke Zufallszeichenkette für `SETUP_TOKEN` verwenden
- Regelmäßige Rotation der Backup Codes für 2FA
- Falls `backend/` innerhalb des Document Roots liegen muss: `.htaccess` Härtung beachten (bereits enthalten)

### Automatische Sperrung

Nach erfolgreichem Setup in Production wird automatisch eine `.setup.lock` Datei erstellt.
Weitere Setup-Aufrufe werden blockiert, es sei denn:

- Parameter `force=1` wird übergeben
- Die `.setup.lock` Datei wird manuell gelöscht

## 8. Frontend (Svelte) Deployment

Frontend per `bun run build` erzeugen und den Inhalt des `dist` Ordners (oder Vite Build Output) ins Document Root kopieren. API-Aufrufe verweisen auf `/api/...`.

## 9. Cron / Geplante Aufgaben

Hetzner Webhosting erlaubt Cronjobs über KonsoleH. Beispiel (täglich Cache Räumung o.ä.):

```bash
php /pfad/zum/backend/scripts/maintenance.php
```

(Datei nach Bedarf anlegen.)

## 10. Fehlerbehebung

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
