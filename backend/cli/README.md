# Backend CLI Tool

Ein einheitliches Kommandozeilen-Interface für die Verwaltung der Hypnose Stammtisch Anwendung.

## Überblick

Dieses CLI-Tool ersetzt alle verstreuten Skripte mit organisierten, professionellen Befehlen. Es bietet eine konsistente Schnittstelle für alle Backend-Operationen.

## Installation & Einrichtung

```bash
# 1. Abhängigkeiten installieren
composer install

# 2. Komplette Anwendungseinrichtung
php cli/cli.php setup

# 3. Entwicklungsserver starten
php cli/cli.php dev serve
```

## Verfügbare Befehle

### Setup

**Komplette Anwendungseinrichtung und Konfiguration**

```bash
# Vollständige Einrichtung
php cli/cli.php setup

# Nur Datenbank einrichten
php cli/cli.php setup database

# Admin-Benutzer erstellen
php cli/cli.php setup admin --admin-email admin@example.com

# System validieren
php cli/cli.php setup validate

# Anwendung zurücksetzen (Vorsicht!)
php cli/cli.php setup reset --force
```

### Migrate

**Datenbankmigrationen verwalten**

```bash
# Alle Migrationen ausführen
php cli/cli.php migrate

# Frische Migration (alle Tabellen löschen)
php cli/cli.php migrate --fresh

# Migration mit Seed-Daten
php cli/cli.php migrate --fresh --seed

# Verbose-Ausgabe
php cli/cli.php migrate --verbose
```

### Admin

**Admin-Benutzer verwalten**

```bash
# Admin-Benutzer erstellen
php cli/cli.php admin create --username admin1 --email admin@example.com --role admin

# Alle Admin-Benutzer auflisten
php cli/cli.php admin list

# Admin-Benutzer aktualisieren
php cli/cli.php admin update --id 1 --role head

# Passwort ändern
php cli/cli.php admin change-password --id 1

# Admin-Benutzer löschen
php cli/cli.php admin delete --id 1

# Password-Hash generieren
php cli/cli.php admin hash --password mypassword
```

### Database

**Datenbank-Utilities und Wartung**

```bash
# Datenbankstatus anzeigen
php cli/cli.php database status

# Datenbank prüfen
php cli/cli.php database check

# Backup erstellen
php cli/cli.php database backup --file backup.sql

# Backup wiederherstellen
php cli/cli.php database restore --file backup.sql

# Alte Daten bereinigen
php cli/cli.php database cleanup --days 30

# Tabellen auflisten
php cli/cli.php database tables

# Datenbankgrößen anzeigen
php cli/cli.php database size

# Datenbank testen
php cli/cli.php database test
```

### Dev

**Entwicklungstools**

```bash
# Entwicklungsserver starten
php cli/cli.php dev serve --port 8080

# API-Tests ausführen
php cli/cli.php dev test --verbose

# Debug-Informationen anzeigen
php cli/cli.php dev debug

# Logs anzeigen
php cli/cli.php dev logs --tail

# Konfiguration anzeigen
php cli/cli.php dev config

# API-Routen auflisten
php cli/cli.php dev routes

# Caches leeren
php cli/cli.php dev clear

# System validieren
php cli/cli.php dev validate
```

### Test

**Tests und Validierungen**

```bash
# Alle Tests ausführen
php cli/cli.php test all --verbose

# Spezifische Tests
php cli/cli.php test api
php cli/cli.php test database
php cli/cli.php test auth
php cli/cli.php test events

# Bei erstem Fehler stoppen
php cli/cli.php test all --stop

# JSON-Ausgabe
php cli/cli.php test all --format json
```

## Allgemeine Optionen

- `--help`, `-h`: Hilfe anzeigen
- `--verbose`, `-v`: Detaillierte Ausgabe
- `--force`: Operation ohne Bestätigung erzwingen

## Schnellstart-Beispiele

### Neue Installation

```bash
# 1. Vollständige Einrichtung
php cli/cli.php setup --admin-email admin@hypnose-stammtisch.de

# 2. Tests ausführen
php cli/cli.php test all

# 3. Entwicklungsserver starten
php cli/cli.php dev serve
```

### Entwicklung

```bash
# Migration nach Schema-Änderungen
php cli/cli.php migrate --fresh --seed

# Admin-Benutzer erstellen
php cli/cli.php admin create --username testadmin --email test@example.com --role admin

# Tests während Entwicklung
php cli/cli.php test api --verbose
```

### Wartung

```bash
# Backup vor Updates
php cli/cli.php database backup --file backup_$(date +%Y%m%d).sql

# Alte Daten bereinigen
php cli/cli.php database cleanup --days 7

# System-Health-Check
php cli/cli.php dev validate
```

## Ersetzt folgende alte Skripte

Das CLI-System ersetzt und vereinheitlicht folgende verstreute Skripte:

- `check_users.php` → `php cli/cli.php admin list`
- `generate_hash.php` → `php cli/cli.php admin hash`
- `fix_missing_tables.php` → `php cli/cli.php migrate --fresh`
- `test_autoload.php` → `php cli/cli.php test autoload`
- `test_event_creation.php` → `php cli/cli.php test events`
- `update_admin_password.php` → `php cli/cli.php admin change-password`
- `start-dev.php` → `php cli/cli.php dev serve`
- Alle `debug_*.php` → `php cli/cli.php dev debug`
- Alle `setup_*.php` → `php cli/cli.php setup`
- Alle Migration-Scripts → `php cli/cli.php migrate`

## Struktur

```
backend/cli/
├── cli.php              # Haupteinstiegspunkt
└── commands/
    ├── setup.php        # Anwendungseinrichtung
    ├── migrate.php      # Datenbankmigrationen
    ├── admin.php        # Admin-Verwaltung
    ├── database.php     # Datenbank-Utilities
    ├── dev.php          # Entwicklungstools
    └── test.php         # Tests und Validierung
```

## Vorteile

1. **Einheitliche Schnittstelle**: Alle Backend-Operationen über ein Tool
2. **Professionelle Struktur**: Organisierte Befehle statt verstreuter Skripte
3. **Konsistente Hilfe**: Jeder Befehl hat detaillierte Hilfe
4. **Farbige Ausgabe**: Bessere Lesbarkeit in der Konsole
5. **Fehlerbehandlung**: Robuste Fehlerbehandlung und Rollback
6. **Validierung**: Umfassende System- und Datenvalidierung

## Fehlerbehebung

### Berechtigungsfehler

```bash
# Auf Unix-Systemen
chmod +x cli/cli.php

# Oder direkt mit PHP ausführen
php cli/cli.php <command>
```

### Datenbank-Verbindungsfehler

```bash
# Konfiguration prüfen
php cli/cli.php dev config

# Datenbankstatus prüfen
php cli/cli.php database status
```

### Migration-Probleme

```bash
# Migration-Status prüfen
php cli/cli.php database check

# Bei Problemen: Fresh migration
php cli/cli.php migrate --fresh --seed
```
