---
description: Account-Lockout- und IP-Blocking-Strategie mit Admin- und CLI-Tools.
outline: deep
---

# Account Lockout & IP Blocking

## Übersicht

Diese Implementierung bietet umfassenden Schutz gegen Brute-Force-Angriffe durch automatische Account-Sperren, IP-Blocking sowie umfangreiche Verwaltungs- und Monitoring-Werkzeuge.

## Funktionalitäten

### Failed Login Tracking

- Speichert jeden Fehlversuch in der Tabelle `failed_logins`.
- Erfasst Account-ID, IP-Adresse, Benutzername und Zeitstempel.
- Zählt Versuche innerhalb eines konfigurierbaren Zeitfensters.

### Account Lockout

- Sperrt Standard-Accounts nach Überschreiten der Schwellwerte.
- Head-Admin-Accounts bleiben aktiv; nur die IP wird gebannt.
- Unterstützt temporäre und permanente Sperren.
- Manuelle Entsperrung über Admin-UI oder CLI möglich.

### IP Banning

- Automatisches IP-Blocking bei verdächtigen Aktivitäten.
- Temporäre und permanente Sperren konfigurierbar.
- Middleware-Check blockiert gebannte IPs vor der Authentifizierung.
- Admin-Tools erlauben manuelle Verwaltung.

## Konfiguration

Füge folgende Variablen zur `.env` hinzu:

```bash
MAX_FAILED_ATTEMPTS=5
TIME_WINDOW_SECONDS=900
IP_BAN_DURATION_SECONDS=3600
ACCOUNT_LOCK_DURATION_SECONDS=3600
HEAD_ADMIN_ROLE_NAME=head
```

| Variable                        | Standardwert | Beschreibung                                    |
| ------------------------------- | ------------ | ----------------------------------------------- |
| `MAX_FAILED_ATTEMPTS`           | 5            | Fehlversuche, bevor Schutzmaßnahmen greifen     |
| `TIME_WINDOW_SECONDS`           | 900          | Zeitfenster für die Zählung in Sekunden         |
| `IP_BAN_DURATION_SECONDS`       | 3600         | Dauer eines IP-Bans; `0` für permanent          |
| `ACCOUNT_LOCK_DURATION_SECONDS` | 3600         | Dauer einer Account-Sperre; `0` für nur manuell |
| `HEAD_ADMIN_ROLE_NAME`          | head         | Rollenname für Head-Admins                      |

## Datenbank-Schema

### `failed_logins`

```sql
CREATE TABLE failed_logins (
  id VARCHAR(36) PRIMARY KEY,
  account_id INT NULL,
  username_entered VARCHAR(255) NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `ip_bans`

```sql
CREATE TABLE ip_bans (
  id VARCHAR(36) PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL UNIQUE,
  reason VARCHAR(255) NOT NULL,
  banned_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,
  is_active BOOLEAN DEFAULT TRUE
);
```

### `users` Erweiterung

```sql
ALTER TABLE users
  ADD COLUMN locked_until TIMESTAMP NULL,
  ADD COLUMN locked_reason VARCHAR(255) NULL;
```

## API-Endpunkte

Alle Endpunkte unter `/api/admin/security` erfordern eine authentifizierte Admin-Session (`admin` oder `head`).

| Methode | Pfad                    | Beschreibung                                  |
| ------- | ----------------------- | --------------------------------------------- |
| `GET`   | `/stats`                | Sicherheitsstatistiken abrufen                |
| `GET`   | `/failed-logins`        | Liste fehlgeschlagener Logins                 |
| `GET`   | `/ip-bans`              | Aktive und historische IP-Bans                |
| `GET`   | `/locked-accounts`      | Gesperrte Accounts                            |
| `POST`  | `/unlock-account`       | Account manuell entsperren                    |
| `POST`  | `/remove-ip-ban`        | IP-Ban aufheben                               |
| `POST`  | `/ban-ip`               | IP-Adresse manuell bannen                     |
| `POST`  | `/cleanup-expired-bans` | Abgelaufene Bans bereinigen (nur Head-Admins) |

## CLI-Tools

```bash
# Sicherheitsstatistiken anzeigen
php cli/commands/security.php stats

# Aktive IP-Bans listen
php cli/commands/security.php list-bans

# Gesperrte Accounts auflisten
php cli/commands/security.php list-locked

# Account entsperren
php cli/commands/security.php unlock username@example.com

# IP-Ban entfernen
php cli/commands/security.php unban 192.168.1.100

# IP manuell bannen
php cli/commands/security.php ban 192.168.1.100 "Brute force attack"

# Abgelaufene Bans bereinigen
php cli/commands/security.php cleanup

# Fehlgeschlagene Logins anzeigen
php cli/commands/security.php failed-logins
```

## Ablauf bei fehlgeschlagenem Login

1. IP-Check vor der Authentifizierung.
1. Authentifizierungsversuch durchführen.
1. Bei Fehler: Fehlversuch speichern und Zähler prüfen.
1. Schwellwert überschritten?
   - Head-Admin: Nur IP bannen.
   - Standard-Account: Account sperren und IP bannen.
1. Bei Erfolg: Historie für den Account zurücksetzen.

## Sicherheitsaspekte

### Race Conditions

- Atomare Datenbankoperationen
- Transaktionale Updates für kritische Pfade
- Sauberes Locking

### Information Disclosure

- Generische Fehlermeldungen beim Login
- Kein Offenlegen von Account-Status

### Datenschutz

- Zweckgebundene Speicherung von IP-Adressen
- Cleanup-Mechanismen für Alt-Daten
- Optional: Hashing von IPs

### Performance

- Optimierte Indizes auf Zeit- und IP-Spalten
- Regelmäßige Bereinigung alter Datensätze
- Optionales Caching von Ban-Status

## Monitoring und Alerting

### Audit-Events

- `auth.failed_login_recorded`
- `auth.account_locked`
- `auth.account_unlocked`
- `security.ip_banned`
- `security.ip_ban_removed`
- `security.banned_ip_access_attempt`

### Kennzahlen

- Fehlversuche pro Zeitraum
- Anzahl aktiver IP-Bans
- Gesperrte Accounts
- Einzigartige IPs mit Fehlversuchen

## Wartung

### Regelmäßige Tasks

1. `security.php cleanup` ausführen
1. Alte `failed_logins` Einträge entfernen
1. Sicherheitsmetriken überwachen

### Notfall-Entsperrung

```bash
php cli/commands/security.php unlock admin@example.com
php cli/commands/security.php unban 192.168.1.100
```

## Testing

- Unit Tests: `FailedLoginTrackerTest.php`, `IpBanManagerTest.php`
- Integrationstests: komplette Login-Flows inkl. Middleware
- Test-Cases: Zeitfenster, Head-Admin-Ausnahmen, Proxy-Handling

## Best Practices

1. Konfiguration regelmäßig überprüfen und anpassen.
1. Monitoring etablieren und Alerts definieren.
1. Datenbereinigung automatisieren.
1. Dokumentation aktuell halten.
1. Sicherheitsmechanismen in jeder Release testen.
