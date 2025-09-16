# Account Lockout & IP Blocking Security Implementation

## Übersicht

Diese Implementierung bietet umfassenden Schutz gegen Brute-Force-Angriffe durch:

- Automatisches Account-Lockout nach wiederholten fehlgeschlagenen Login-Versuchen
- IP-Banning für verdächtige Aktivitäten
- Spezielle Behandlung für Head-Admin-Accounts
- Vollständige Auditierung und Logging
- Admin-Tools für Verwaltung und Monitoring

## Funktionalitäten

### 1. Failed Login Tracking

- Alle fehlgeschlagenen Login-Versuche werden in der `failed_logins` Tabelle gespeichert
- Tracking nach Account-ID, IP-Adresse und Zeitstempel
- Zeitfenster-basierte Zählung (konfigurierbar)

### 2. Account Lockout

- Normale Accounts werden nach Überschreitung der Schwelle gesperrt
- Head-Admin-Accounts bleiben aktiv (nur IP wird gebannt)
- Temporäre oder permanente Sperrung möglich
- Manuelle Entsperrung durch Admins

### 3. IP Banning

- Automatische IP-Sperrung bei verdächtigen Aktivitäten
- Temporäre oder permanente Bans
- Middleware-Check vor Authentifizierung
- Admin-Tools für manuelle Verwaltung

## Konfiguration (.env)

```env
# Maximale Anzahl fehlgeschlagener Versuche (Standard: 5)
MAX_FAILED_ATTEMPTS=5

# Zeitfenster für Zählung in Sekunden (Standard: 900 = 15 Minuten)
TIME_WINDOW_SECONDS=900

# IP-Ban-Dauer in Sekunden (Standard: 3600 = 1 Stunde, 0 = permanent)
IP_BAN_DURATION_SECONDS=3600

# Account-Lock-Dauer in Sekunden (Standard: 3600 = 1 Stunde, 0 = manuelle Entsperrung)
ACCOUNT_LOCK_DURATION_SECONDS=3600

# Rollenname für Head-Admin (Standard: head)
HEAD_ADMIN_ROLE_NAME=head
```

## Datenbank-Schema

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

## API-Endpunkte

### Admin Security Management

#### GET /api/admin/security/stats

Sicherheitsstatistiken abrufen

```json
{
  "failed_logins_24h": 15,
  "active_ip_bans": 3,
  "locked_accounts": 1,
  "unique_ips_failed_24h": 8
}
```

#### GET /api/admin/security/failed-logins

Failed Login History

```json
{
  "failed_logins": [...],
  "pagination": {...}
}
```

#### GET /api/admin/security/ip-bans

Active IP Bans

```json
{
  "ip_bans": [...],
  "pagination": {...}
}
```

#### GET /api/admin/security/locked-accounts

Locked Accounts

```json
{
  "locked_accounts": [...]
}
```

#### POST /api/admin/security/unlock-account

Account entsperren

```json
{
  "account_id": 123
}
```

#### POST /api/admin/security/remove-ip-ban

IP-Ban entfernen

```json
{
  "ip_address": "192.168.1.100"
}
```

#### POST /api/admin/security/ban-ip

IP manuell bannen

```json
{
  "ip_address": "192.168.1.100",
  "reason": "Suspicious activity"
}
```

#### POST /api/admin/security/cleanup-expired-bans

Abgelaufene Bans bereinigen (nur Head-Admin)

## CLI-Tools

### Security Command Interface

```bash
# Sicherheitsstatistiken anzeigen
php cli/commands/security.php stats

# Aktive IP-Bans auflisten
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

# Fehlgeschlagene Login-Versuche anzeigen
php cli/commands/security.php failed-logins
```

## Ablauf bei fehlgeschlagenem Login

1. **IP-Check**: Prüfung ob IP bereits gebannt ist (vor Auth)
2. **Authentifizierung**: Login-Versuch durchführen
3. **Bei Misserfolg**:
   - Failed Login aufzeichnen
   - Zähler für Account und IP prüfen
   - Bei Überschreitung:
     - Head-Admin: Nur IP bannen
     - Normal: Account sperren + IP bannen
4. **Bei Erfolg**: Failed-Login-Zähler zurücksetzen

## Sicherheitsüberlegungen

### Race Conditions

- Atomare Datenbankoperationen
- Transaktionale Updates wo nötig
- Proper Locking bei kritischen Operationen

### Information Disclosure

- Keine spezifischen Fehlermeldungen ("Account existiert nicht")
- Generische Antworten bei Login-Fehlern
- Kein Preisgeben von Account-Status

### DSGVO/Datenschutz

- IP-Adressen zweckgebunden speichern
- Begrenzte Aufbewahrungsdauer
- Cleanup-Mechanismen für alte Daten
- Optional: IP-Hashing statt Klartext

### Performance

- Optimierte Datenbankindizes
- Effiziente Abfragen mit Zeitfenstern
- Cleanup für alte Daten

## Monitoring & Alerting

### Audit Logging

Alle sicherheitsrelevanten Ereignisse werden geloggt:

- `auth.failed_login_recorded`
- `auth.account_locked`
- `auth.account_unlocked`
- `security.ip_banned`
- `security.ip_ban_removed`
- `security.banned_ip_access_attempt`

### Metriken

- Failed logins pro Zeitraum
- Anzahl aktiver Bans
- Gesperrte Accounts
- Unique IPs mit Failed Logins

## Wartung

### Regelmäßige Tasks

1. **Cleanup abgelaufener Bans**: `security.php cleanup`
2. **Bereinigung alter Failed-Login-Records** (DSGVO)
3. **Monitoring der Sicherheitsmetriken**

### Notfall-Entsperrung

Bei kritischen Problemen können Accounts und IPs über das CLI entsperrt werden:

```bash
php cli/commands/security.php unlock admin@example.com
php cli/commands/security.php unban 192.168.1.100
```

## Testing

### Unit Tests

- `FailedLoginTrackerTest.php`: Login-Tracking und Account-Lockout
- `IpBanManagerTest.php`: IP-Banning und Management

### Integration Tests

- End-to-End Login-Flows
- Middleware-Integration
- Admin-API-Endpunkte

### Test-Cases

- Schwellenübertritt normale Accounts
- Head-Admin-Ausnahme-Behandlung
- Zeitfenster-Logik
- IP-Proxy-Handling
- Race-Condition-Szenarien

## Best Practices

1. **Konfiguration**: Anpassung an spezifische Sicherheitsanforderungen
2. **Monitoring**: Regelmäßige Überprüfung der Sicherheitsmetriken
3. **Wartung**: Periodische Bereinigung alter Daten
4. **Documentation**: Aktualisierung bei Änderungen
5. **Testing**: Regelmäßige Validierung der Sicherheitsmechanismen
