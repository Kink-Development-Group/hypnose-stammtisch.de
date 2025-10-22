---
description: Implementierung von Account-Lockout, IP-Blocking und Administrationsfunktionen.
outline: deep
---

# Security Implementation: Account Lockout & IP Blocking

Dieses Dokument beschreibt die Implementierung der Account-Lockout- und IP-Blocking-Sicherheitsfunktionen zum Schutz vor Brute-Force-Angriffen.

## Overview

Das Sicherheitssystem schützt gegen Brute-Force-Login-Angriffe durch:

1. **Account Lockout**: Temporäres Sperren von Benutzerkonten nach wiederholten fehlgeschlagenen Login-Versuchen.
2. **IP Blocking**: Sperren von Quell-IP-Adressen mit übermäßigen Fehlversuchen.
3. **Head Admin Protection**: Spezielle Behandlung für Head-Admin-Konten (nur IP-Sperre, kein Account Lock).
4. **Comprehensive Auditing**: Vollständiges Logging aller Sicherheitsereignisse.
5. **Admin Management**: Tools für Administratoren zur Verwaltung von Sperren und Banns.

## Features

### Core Security Features

- **Configurable Thresholds**: Konfigurierbare Schwellwerte, Zeitfenster und Bann-Dauern.
- **Time-Window Based**: Berücksichtigt nur Versuche innerhalb eines konfigurierbaren Zeitfensters.
- **Role-Based Protection**: Head-Admin-Konten können nicht gesperrt werden (nur IP-Ban).
- **Automatic Cleanup**: Abgelaufene Banns werden automatisch bereinigt.
- **Race Condition Safe**: Atomare Datenbankoperationen verhindern Inkonsistenzen.

### Admin Management Features

- Anzeige von fehlgeschlagenen Login-Historien
- Anzeige aktiver und historischer IP-Banns
- Anzeige gesperrter Konten
- Manuelles Entsperren von Konten
- Manuelles Entfernen von IP-Banns
- Manuelles Sperren von IP-Adressen
- Security-Statistik-Dashboard
- Bereinigung abgelaufener Banns

## Configuration

Füge folgende Umgebungsvariablen zur `.env` hinzu:

```bash
# Failed Login Protection
MAX_FAILED_ATTEMPTS=5
TIME_WINDOW_SECONDS=900
IP_BAN_DURATION_SECONDS=3600
ACCOUNT_LOCK_DURATION_SECONDS=3600
HEAD_ADMIN_ROLE_NAME=head
```

### Configuration Options

| Variable                         | Default | Description                                                      |
| -------------------------------- | ------- | ---------------------------------------------------------------- |
| `MAX_FAILED_ATTEMPTS`            | 5       | Anzahl fehlgeschlagener Versuche vor Schutzmaßnahmen             |
| `TIME_WINDOW_SECONDS`            | 900     | Zeitfenster (15 min) für die Zählung                             |
| `IP_BAN_DURATION_SECONDS`        | 3600    | IP-Ban-Dauer (1 Stunde), 0 = permanent                           |
| `ACCOUNT_LOCK_DURATION_SECONDS`  | 3600    | Account-Lock-Dauer (1 Stunde), 0 = nur manuelles Unlock          |
| `HEAD_ADMIN_ROLE_NAME`           | head    | Rollenname für Head-Administratoren                              |
| `AUDIT_UNTRUSTED_PROXY_MAX_LOGS` | 10      | Max. Audit-Logs pro Zeitperiode für untrusted Proxy-Headers      |
| `AUDIT_UNTRUSTED_PROXY_PERIOD`   | 300     | Zeitperiode in Sekunden (5 min) für Rate-Limiting von Audit-Logs |

### Audit Log Rate Limiting

Um Log-Spam bei koordinierten Angriffen mit gefälschten Proxy-Headers zu verhindern, implementiert das System Rate-Limiting für Audit-Logs:

**Problem**: Bei einem Angriff mit vielen gefälschten Proxy-Headers könnten hunderte von Audit-Log-Einträgen pro Sekunde erzeugt werden, was die Datenbank überlastet und die Log-Analyse erschwert.

**Lösung**: Rate-Limiting pro Quell-IP und Header-Typ:

- Innerhalb des konfigurierten Limits werden einzelne Ereignisse detailliert protokolliert
- Bei Überschreitung des Limits wird ein aggregierter Log-Eintrag erstellt
- Aggregierte Einträge enthalten Informationen über die Anzahl blockierter Logs
- Die IP-Validierung funktioniert unabhängig vom Logging weiterhin korrekt

**Konfiguration**:

```bash
# Audit Log Rate Limiting für untrusted Proxy Headers
AUDIT_UNTRUSTED_PROXY_MAX_LOGS=10  # Max. 10 detaillierte Logs
AUDIT_UNTRUSTED_PROXY_PERIOD=300    # Pro 5 Minuten
```

**Vorteile**:

- Schutz vor Log-Flooding-Angriffen
- Erhalt wichtiger Security-Informationen
- Keine Beeinträchtigung der Sicherheitsfunktionalität
- Bessere Performance bei Angriffen
- Übersichtlichere Audit-Logs

## Database Schema

### New Tables

Die Implementierung fügt drei neue Datenbankstrukturen hinzu:

#### `failed_logins` Table

```sql
CREATE TABLE failed_logins (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  account_id INT NULL,
  username_entered VARCHAR(255) NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_account_id (account_id),
  INDEX idx_ip_address (ip_address),
  INDEX idx_created_at (created_at),
  FOREIGN KEY (account_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `ip_bans` Table

```sql
CREATE TABLE ip_bans (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  ip_address VARCHAR(45) NOT NULL UNIQUE,
  reason VARCHAR(255) NOT NULL,
  banned_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,
  is_active BOOLEAN DEFAULT TRUE,
  INDEX idx_ip_address (ip_address),
  INDEX idx_expires_at (expires_at),
  INDEX idx_is_active (is_active),
  FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `users` Table Extensions

```sql
ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN locked_reason VARCHAR(255) NULL;
ALTER TABLE users ADD INDEX idx_locked_until (locked_until);
```

## Implementation Details

### Core Classes

#### `FailedLoginTracker`

- Zeichnet fehlgeschlagene Logins auf
- Zählt Versuche pro Account und IP
- Handhabt Account-Lockout-Logik
- Verwalten von Sperrablauf
- Stellt Admin-Funktionen zur Accountverwaltung bereit

#### `IpBanManager`

- Verwalten von IP-Sperren
- Prüft Bannstatus inklusive Ablaufanzeige
- Unterstützt Proxies bei der IP-Erkennung
- Bereinigt und entfernt Banns
- Stellt Admin-Funktionen für IP-Management bereit

#### `AdminSecurityController`

- REST-API-Endpunkte für Sicherheitsverwaltung
- Stellt Admin-Interface für Security-Events bereit
- Handhabt Authentifizierung und Autorisierung

### Security Flow

#### Login Process

1. **IP Ban Check**: Vor der Authentifizierung wird geprüft, ob die IP gesperrt ist.
1. **Rate Limiting**: Standard Rate Limiting anwenden.
1. **Authentication**: Nutzer authentifizieren.
1. **Failed Login Handling**: Bei fehlgeschlagener Authentifizierung:
   - Fehlversuch mit IP- und Account-Daten speichern.
   - Aktuelle Versuche für Account und IP zählen.
   - Bei Überschreiten der Schwellwerte:
     - Normale Accounts: Account sperren und IP bannen.
     - Head-Admin-Accounts: Nur IP bannen (Account bleibt aktiv).

1. **Success Handling**: Bei erfolgreicher Authentifizierung:
   - Fehlversuchshistorie für den Account leeren.
   - Mit normalem 2FA-Flow fortfahren.

#### Security Checks

- **Account Lock Check**: Vor Authentifizierung prüfen, ob Account gesperrt ist.
- **IP Ban Check**: Gebannte IPs frühzeitig blockieren.
- **Time Window**: Nur Versuche innerhalb des Zeitfensters zählen.
- **Expiration**: Ablauf von Sperren und Banns automatisch verarbeiten.

### API Endpoints

Alle Security-Endpunkte erfordern Admin-Authentifizierung (`admin` oder `head` Rolle):

```text
GET  /admin/security/failed-logins     - Get failed login history
GET  /admin/security/ip-bans           - Get IP ban list
GET  /admin/security/locked-accounts   - Get locked accounts
GET  /admin/security/stats             - Get security statistics
POST /admin/security/unlock-account    - Unlock an account manually
POST /admin/security/remove-ip-ban     - Remove an IP ban manually
POST /admin/security/ban-ip            - Ban an IP address manually
POST /admin/security/cleanup-expired-bans - Clean up expired bans (head admin only)
```

### Example API Usage

#### Unlock Account

```bash
curl -X POST /admin/security/unlock-account \
  -H "Content-Type: application/json" \
  -d '{"account_id": 123}'
```

#### Remove IP Ban

```bash
curl -X POST /admin/security/remove-ip-ban \
  -H "Content-Type: application/json" \
  -d '{"ip_address": "192.168.1.100"}'
```

#### Get Security Stats

```bash
curl -X GET /admin/security/stats
```

## Deployment

### 1. Apply Database Migration

Führe die Migration aus, um die benötigten Tabellen anzulegen:

```bash
php backend/migrations/migrate.php
```

Oder manuell:

```bash
php /path/to/apply_security_migration.php
```

### 2. Update Configuration

Füge die Sicherheitskonfiguration zur `.env` hinzu.

### 3. Update Environment

Stelle sicher, dass der Webserver die aktualisierte Version verwendet.

### 4. Test Implementation

Führe Tests aus, um die Funktionalität zu verifizieren:

```bash
php backend/vendor/bin/phpunit tests/Utils/FailedLoginTrackerTest.php
php backend/vendor/bin/phpunit tests/Utils/IpBanManagerTest.php
php backend/vendor/bin/phpunit tests/Integration/SecurityFlowTest.php
```

## Monitoring and Maintenance

### Regular Maintenance

1. **Monitor Security Stats**: Regelmäßig Dashboard prüfen.
2. **Review Failed Logins**: Muster in Fehlversuchen analysieren.
3. **Clean Up Expired Bans**: Endpoint nutzen oder Cronjob einrichten.
4. **Audit Log Review**: Sicherheitsrelevante Auditlogs beobachten.

### Recommended Cron Jobs

```bash
# Clean up expired bans daily
0 2 * * * curl -X POST https://your-domain.com/admin/security/cleanup-expired-bans

# Clean up old failed login records (older than 30 days)
0 3 * * * mysql -e "DELETE FROM failed_logins WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

### Alerts and Monitoring

Empfohlene Alerts:

- Hohe Anzahl fehlgeschlagener Logins
- Mehrere IP-Banns in kurzer Zeit
- Wiederholte Versuche von gebannten IPs
- Angriffe auf Head-Admin-Konten

## Security Considerations

### Privacy and GDPR Compliance

- **IP Address Storage**: IP-Adressen werden aus Sicherheitsgründen mit begrenzter Aufbewahrung gespeichert.
- **Data Retention**: Automatisches Aufräumen alter Security-Logs einplanen.
- **Purpose Limitation**: Daten ausschließlich für Security-Zwecke nutzen.

### Performance Considerations

- **Database Indexes**: Indizes auf zeit- und IP-basierten Abfragen.
- **Cleanup**: Regelmäßige Bereinigung, um Tabellenwachstum zu verhindern.
- **Caching**: Bann-Status cachen für High-Traffic-Szenarien.

### Additional Recommendations

1. **Rate Limiting**: Bestehendes Rate Limiting ergänzt den Schutz.
2. **Monitoring**: Security-Events überwachen.
3. **Backup Strategy**: Security-Tabellen in Backups einschließen.
4. **Testing**: Sicherheitsfunktionen regelmäßig testen.
5. **Documentation**: Prozesse dokumentiert halten.

## Troubleshooting

### Common Issues

1. **Migration Fails**: Datenbankberechtigungen und bestehende Strukturen prüfen.
2. **IP Detection**: Proxy-Header korrekt konfigurieren.
3. **False Positives**: Zeitfenster und Schwellwerte überprüfen.
4. **Admin Access**: Admin-Rollen prüfen.

### Debug Mode

Aktiviere den Debug-Modus der Konfiguration für detaillierte Fehlerausgaben.

### Log Locations

Sicherheitsereignisse werden protokolliert in:

- **Audit Logs**: `audit_logs` Tabelle
- **Failed Logins**: `failed_logins` Tabelle
- **Application Logs**: PHP-Error-Logs

## Testing

Die Implementierung umfasst umfangreiche Tests:

- **Unit Tests**: Einzelne Komponenten und Funktionen
- **Integration Tests**: Komplette Security-Flows
- **Edge Case Tests**: Zeitfenster, Race Conditions, Edge Cases

Tests ausführen mit:

```bash
php backend/vendor/bin/phpunit tests/
```

## Support

Bei Fragen oder Problemen:

1. Troubleshooting-Abschnitt prüfen
2. Audit-Logs auf Security-Events prüfen
3. Datenbanktabellen auf Konsistenz checken
4. Konfiguration validieren

## Security Advisory

Diese Implementierung bietet starken Schutz gegen Brute-Force-Angriffe, sollte aber Teil einer ganzheitlichen Sicherheitsstrategie sein:

- Starke Passwort-Richtlinien
- Regelmäßige Security-Updates
- Netzwerk-Schutz (Firewalls, DDoS-Protection)
- Regelmäßige Security-Audits
- Security-Awareness für Nutzer
