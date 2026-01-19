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

3. **Setup via CLI (empfohlen):**

```bash
# Vollständiges Setup: DB erstellen, Migrationen, Seed, Admin
php cli/cli.php setup

# Oder einzelne Schritte:
php cli/cli.php setup database    # Nur Datenbank
php cli/cli.php migrate           # Nur Migrationen
php cli/cli.php setup admin       # Nur Admin erstellen
```

4. **Development Server starten:**

```bash
php cli/cli.php dev serve
# Oder direkt:
cd api && php -S localhost:8000
```

Die API ist dann unter `http://localhost:8000` verfügbar.

## Hetzner Webspace Deployment

Siehe [DEPLOY_APACHE_SHARED_HOSTING.md](DEPLOY_APACHE_SHARED_HOSTING.md) für die vollständige Deployment-Anleitung.

### Kurzübersicht

1. **Dateien hochladen:** `backend/` Ordner auf Webspace

2. **Dependencies:** `composer install --no-dev --optimize-autoloader`

3. **.env konfigurieren:** DB-Daten, `SETUP_TOKEN`, `APP_ENV=production`

4. **Setup via Web (ohne SSH):**

```text
https://yourdomain.de/api/setup.php?action=full&token=YOUR_SETUP_TOKEN
```

5. **Setup via CLI (mit SSH):**

```bash
php cli/cli.php setup
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

### Admin Authentication & Password Reset

- `POST /api/admin/auth/login` - Admin login
- `POST /api/admin/auth/logout` - Admin logout
- `GET /api/admin/auth/status` - Check authentication status
- `POST /api/admin/auth/password-reset/request` - Request password reset via email
- `GET /api/admin/auth/password-reset/verify?token={token}` - Verify reset token
- `POST /api/admin/auth/password-reset/reset` - Reset password with token

See [Password Reset Documentation](../docs/features/password-reset.md) for detailed usage.

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
