# Hypnose-Stammtisch.de Backend Setup Guide

## Entwicklung (Development)

### Voraussetzungen

- PHP 8.1 oder höher
- Composer
- MySQL/MariaDB (für Produktion) oder SQLite (für Entwicklung)

### Installation

1. **Dependencies installieren:**

```bash
cd backend
composer install
```

2. **Umgebungsvariablen konfigurieren:**

```bash
cp .env.example .env
# Bearbeite .env mit deinen Datenbankdaten
```

3. **Datenbank erstellen und migrieren:**

```bash
# MySQL/MariaDB
mysql -u root -p -e "CREATE DATABASE hypnose_stammtisch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migration ausführen
php migrations/migrate.php
```

4. **Development Server starten:**

```bash
cd api
php -S localhost:8000
```

Die API ist dann unter `http://localhost:8000` verfügbar.

## Hetzner Webspace Deployment

### Vorbereitung

1. **Dateien hochladen:**
   - Lade den gesamten `backend`-Ordner in dein Webspace-Verzeichnis hoch
   - Die `api`-Datei sollte über HTTP erreichbar sein (z.B. `https://yourdomain.de/api/`)

2. **Dependencies installieren:**

```bash
# Per SSH auf dem Server (wenn verfügbar)
cd /path/to/your/backend
composer install --no-dev --optimize-autoloader

# Alternativ: Lade den vendor-Ordner lokal runter und lade ihn hoch
```

3. **Datenbank einrichten:**
   - Erstelle eine MySQL-Datenbank über das Hetzner Control Panel
   - Notiere dir die Datenbankdaten (Host, Username, Password, Datenbankname)

4. **Umgebungsvariablen konfigurieren:**

```bash
# Erstelle .env basierend auf .env.example
# Füge deine Hetzner-Datenbankdaten ein:

DB_HOST=your-database-host.your-server.de
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_username
DB_PASS=your_password

APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.de
```

5. **Datenbankschema erstellen:**

```bash
# Per SSH oder PHPMyAdmin:
php migrations/migrate.php

# Oder importiere die SQL-Datei direkt:
# mysql -u username -p database_name < migrations/001_initial_schema.sql
```

### Apache-Konfiguration

Falls du Apache verwendest, stelle sicher, dass die `.htaccess`-Datei im `api`-Ordner vorhanden ist und `mod_rewrite` aktiviert ist.

### Nginx-Konfiguration

Falls du Nginx verwendest, nutze die bereitgestellte `nginx.conf.example` als Basis für deine Server-Konfiguration.

## API-Endpunkte

### Events

- `GET /api/events` - Alle Events auflisten
- `GET /api/events/upcoming` - Kommende Events
- `GET /api/events/featured` - Featured Events
- `GET /api/events/{id}` - Einzelnes Event
- `GET /api/events/meta` - Event-Metadaten

### Contact

- `POST /api/contact` - Kontaktformular absenden

### Calendar

- `GET /api/calendar/feed` - Öffentlicher ICS-Feed
- `GET /api/calendar/feed/{token}` - Privater ICS-Feed
- `GET /api/calendar/event/{id}/ics` - ICS für einzelnes Event
- `GET /api/calendar/meta` - Calendar-Metadaten

## Sicherheit

### Produktions-Checklist

- [ ] `APP_DEBUG=false` in .env
- [ ] Sichere Datenbankpasswörter verwenden
- [ ] .env-Datei vor Web-Zugriff schützen
- [ ] HTTPS aktivieren
- [ ] Rate Limiting konfigurieren
- [ ] Backup-Strategie implementieren
- [ ] Log-Monitoring einrichten

### Schutz sensibler Dateien

Stelle sicher, dass folgende Dateien/Ordner nicht über HTTP erreichbar sind:

- `.env`
- `composer.json`
- `composer.lock`
- `vendor/` (sollte außerhalb des Web-Root liegen)
- Alle `.log`-Dateien

## Troubleshooting

### Häufige Probleme

1. **500 Internal Server Error:**
   - Prüfe PHP-Logs
   - Stelle sicher, dass composer install ausgeführt wurde
   - Prüfe .env-Konfiguration

2. **Datenbankverbindungsfehler:**
   - Prüfe DB-Konfiguration in .env
   - Stelle sicher, dass die Datenbank existiert
   - Prüfe Netzwerkverbindung zum DB-Server

3. **CORS-Fehler:**
   - Prüfe Frontend-URL in der CORS-Konfiguration
   - Stelle sicher, dass Response::addCorsHeaders() aufgerufen wird

4. **Autoloader-Fehler:**
   - Führe `composer dump-autoload` aus
   - Prüfe PSR-4-Namespace-Konfiguration

## Performance-Optimierung

### Für Produktion

1. **PHP-OPCache aktivieren**
2. **Composer-Autoloader optimieren:** `composer install --optimize-autoloader --no-dev`
3. **HTTP-Caching für ICS-Feeds aktivieren**
4. **Database Connection Pooling verwenden**
5. **CDN für statische Assets verwenden**

## Monitoring

### Empfohlene Logs

- PHP Error Logs
- Database Query Logs
- API Access Logs
- Calendar Feed Access Logs

### Metriken

- API Response Times
- Database Query Performance
- Calendar Feed Downloads
- Contact Form Submissions
