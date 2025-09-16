# Security: Account Lockout & IP Blocking

Dieses Feature implementiert umfassenden Schutz gegen Brute-Force-Angriffe durch automatisches Account-Lockout und IP-Blocking.

## 🛡️ Übersicht

- **Automatisches Account-Lockout** nach wiederholten fehlgeschlagenen Login-Versuchen
- **IP-Banning** für verdächtige Aktivitäten
- **Head-Admin-Schutz**: Head-Admin-Accounts werden nicht gesperrt (nur IP wird gebannt)
- **Vollständige Auditierung** aller sicherheitsrelevanten Ereignisse
- **Admin-Tools** für Verwaltung und Monitoring
- **DSGVO-konform** mit konfigurierbaren Aufbewahrungszeiten

## ⚙️ Konfiguration

### Umgebungsvariablen (.env)

```env
# Maximale Anzahl fehlgeschlagener Versuche vor Sperrung
MAX_FAILED_ATTEMPTS=5

# Zeitfenster für Zählung (Sekunden) - 900 = 15 Minuten
TIME_WINDOW_SECONDS=900

# IP-Ban-Dauer (Sekunden) - 3600 = 1 Stunde, 0 = permanent
IP_BAN_DURATION_SECONDS=3600

# Account-Lock-Dauer (Sekunden) - 3600 = 1 Stunde, 0 = manuell
ACCOUNT_LOCK_DURATION_SECONDS=3600

# Rollenname für Head-Admin (wird nicht gesperrt)
HEAD_ADMIN_ROLE_NAME=head
```

### Standardwerte

- **MAX_FAILED_ATTEMPTS**: 5 Versuche
- **TIME_WINDOW_SECONDS**: 900 Sekunden (15 Minuten)
- **IP_BAN_DURATION_SECONDS**: 3600 Sekunden (1 Stunde)
- **ACCOUNT_LOCK_DURATION_SECONDS**: 3600 Sekunden (1 Stunde)
- **HEAD_ADMIN_ROLE_NAME**: "head"

## 🔄 Funktionsweise

### Normaler Account

1. Nach 6+ fehlgeschlagenen Versuchen im Zeitfenster:
   - Account wird gesperrt
   - IP wird gebannt
   - Ereignis wird geloggt

### Head-Admin-Account

1. Nach 6+ fehlgeschlagenen Versuchen im Zeitfenster:
   - Account bleibt aktiv
   - IP wird gebannt
   - Ereignis wird geloggt

### Erfolgreicher Login

- Setzt Zähler für Account zurück
- Löscht failed_logins Einträge für Account

## 🛠️ Admin-Verwaltung

### Web-Interface (Admin Panel)

#### Sicherheitsstatistiken

```
GET /api/admin/security/stats
```

#### Failed Login History

```
GET /api/admin/security/failed-logins?page=1&limit=50
```

#### IP-Bans verwalten

```
GET /api/admin/security/ip-bans
POST /api/admin/security/ban-ip
POST /api/admin/security/remove-ip-ban
```

#### Accounts entsperren

```
GET /api/admin/security/locked-accounts
POST /api/admin/security/unlock-account
```

### CLI-Tools

#### Security Command

```bash
# Übersicht aller verfügbaren Befehle
php cli/commands/security.php

# Sicherheitsstatistiken
php cli/commands/security.php stats

# Aktive IP-Bans anzeigen
php cli/commands/security.php list-bans

# Gesperrte Accounts anzeigen
php cli/commands/security.php list-locked

# Account entsperren
php cli/commands/security.php unlock username@example.com
php cli/commands/security.php unlock 123

# IP-Ban entfernen
php cli/commands/security.php unban 192.168.1.100

# IP manuell bannen
php cli/commands/security.php ban 192.168.1.100 "Suspicious activity"

# Abgelaufene Bans bereinigen
php cli/commands/security.php cleanup

# Fehlgeschlagene Logins anzeigen
php cli/commands/security.php failed-logins
```

#### Security Maintenance

```bash
# Alle Wartungsaufgaben ausführen
php scripts/security_maintenance.php all

# Nur abgelaufene Bans bereinigen
php scripts/security_maintenance.php cleanup-bans

# Alte Failed-Login-Records löschen (90 Tage)
php scripts/security_maintenance.php cleanup-logs 90

# Abgelaufene Account-Locks bereinigen
php scripts/security_maintenance.php cleanup-locks

# Sicherheitsbericht generieren
php scripts/security_maintenance.php report

# Auf verdächtige Aktivitäten prüfen
php scripts/security_maintenance.php check-alerts
```

## 📊 Monitoring & Alerting

### Audit Logs

Alle Ereignisse werden in der `audit_logs` Tabelle protokolliert:

- `auth.failed_login_recorded`
- `auth.account_locked`
- `auth.account_unlocked`
- `security.ip_banned`
- `security.ip_ban_removed`
- `security.banned_ip_access_attempt`

### Metriken

- Failed logins pro Zeitraum
- Anzahl aktiver IP-Bans
- Anzahl gesperrter Accounts
- Unique IPs mit Failed Logins

### Automatische Alerts

Das Wartungsskript erkennt verdächtige Muster:

- Ungewöhnlich hohe Anzahl fehlgeschlagener Logins
- Distributed Attack Patterns
- Accounts unter schwerem Angriff

## 🗄️ Datenbank-Schema

### failed_logins

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

### ip_bans

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

### users (erweitert)

```sql
ALTER TABLE users
  ADD COLUMN locked_until TIMESTAMP NULL,
  ADD COLUMN locked_reason VARCHAR(255) NULL;
```

## 🔧 Wartung

### Automatische Bereinigung (Cron Job)

```bash
# Täglich um 3:00 Uhr
0 3 * * * cd /path/to/backend && php scripts/security_maintenance.php all

# Wöchentlich alte Logs bereinigen (DSGVO)
0 2 * * 0 cd /path/to/backend && php scripts/security_maintenance.php cleanup-logs 90
```

### Manuelle Notfall-Entsperrung

```bash
# Bei kritischen Problemen
php cli/commands/security.php unlock admin@example.com
php cli/commands/security.php unban 192.168.1.100
```

## 🔒 Sicherheitsüberlegungen

### Race Conditions

- Atomare Datenbankoperationen
- Transaktionale Updates
- Proper Locking bei kritischen Operationen

### Information Disclosure Prevention

- Keine spezifischen Fehlermeldungen
- Generische Login-Fehlermeldungen
- Kein Preisgeben von Account-Status

### DSGVO/Datenschutz

- IP-Adressen zweckgebunden speichern
- Begrenzte Aufbewahrungsdauer
- Automatische Bereinigung alter Daten
- Optional: IP-Hashing möglich

### Performance-Optimierung

- Optimierte Datenbankindizes
- Effiziente Zeitfenster-Abfragen
- Regelmäßige Bereinigung

## 🧪 Testing

### Unit Tests

```bash
# Failed Login Tracker Tests
vendor/bin/phpunit tests/Utils/FailedLoginTrackerTest.php

# IP Ban Manager Tests
vendor/bin/phpunit tests/Utils/IpBanManagerTest.php
```

### Integration Tests

```bash
# Vollständige Security Flow Tests
vendor/bin/phpunit tests/Integration/SecurityLockoutIntegrationTest.php
```

### Test-Szenarien

- Schwellenübertritt normale Accounts
- Head-Admin-Ausnahme-Behandlung
- Zeitfenster-Logik
- IP-Proxy-Handling
- Race-Condition-Szenarien
- Cleanup-Funktionalität

## 📚 API-Dokumentation

### Security Statistics

```http
GET /api/admin/security/stats

Response:
{
  "failed_logins_24h": 15,
  "active_ip_bans": 3,
  "locked_accounts": 1,
  "unique_ips_failed_24h": 8
}
```

### Failed Login History

```http
GET /api/admin/security/failed-logins?page=1&limit=50

Response:
{
  "failed_logins": [...],
  "pagination": {
    "page": 1,
    "limit": 50,
    "offset": 0
  }
}
```

### Account Management

```http
POST /api/admin/security/unlock-account
Content-Type: application/json

{
  "account_id": 123
}
```

### IP Ban Management

```http
POST /api/admin/security/ban-ip
Content-Type: application/json

{
  "ip_address": "192.168.1.100",
  "reason": "Suspicious activity"
}
```

## 🚨 Notfall-Procedures

### Alle Sicherheitsmechanismen temporär deaktivieren

```bash
# .env temporär ändern
MAX_FAILED_ATTEMPTS=999999
IP_BAN_DURATION_SECONDS=0
ACCOUNT_LOCK_DURATION_SECONDS=0
```

### Alle Sperren aufheben

```bash
# Alle Account-Locks entfernen
php cli/commands/security.php cleanup-locks

# Alle IP-Bans entfernen
mysql -u root -p hypnose_stammtisch -e "UPDATE ip_bans SET is_active = 0"

# Failed Login Historie löschen
mysql -u root -p hypnose_stammtisch -e "DELETE FROM failed_logins"
```

## 📞 Support

Bei Problemen mit dem Security-System:

1. **Logs prüfen**: `backend/logs/`
2. **CLI-Tools verwenden**: `php cli/commands/security.php`
3. **Wartungsskript ausführen**: `php scripts/security_maintenance.php`
4. **Dokumentation konsultieren**: `docs/SECURITY_LOCKOUT_IMPLEMENTATION.md`

---

**⚠️ Wichtiger Hinweis**: Dieses System schützt vor Brute-Force-Angriffen, kann aber legitime Benutzer aussperren. Stellen Sie sicher, dass Admin-Zugang über CLI verfügbar ist!
