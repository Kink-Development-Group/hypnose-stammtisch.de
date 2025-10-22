---
description: Sichere Konfiguration der Testumgebung ohne hartcodierte Passwörter.
outline: deep
---

# Security Test Configuration

Die Test-Suite verwendet sichere, konfigurierbare Zugangsdaten und verzichtet vollständig auf hartcodierte Passwörter.

## Problemstellung

Frühere Tests enthielten feste Passwörter wie `password123` oder `admin123`. Auch in Testumgebungen ist dies ein Sicherheitsrisiko.

## Lösung

### 1. Environment Variablen

- `TEST_USER_PASSWORD`: Passwort für reguläre Test-Accounts
- `TEST_ADMIN_PASSWORD`: Passwort für Admin-Test-Accounts

### 2. Automatische Generierung

Sind keine Variablen gesetzt, werden sichere Zufallspasswörter erstellt:

```php
self::$testUserPassword = $_ENV['TEST_USER_PASSWORD'] ?? bin2hex(random_bytes(16));
self::$testAdminPassword = $_ENV['TEST_ADMIN_PASSWORD'] ?? bin2hex(random_bytes(16));
```

### 3. Implementierungsdetails

- Nutzung von `random_bytes()` für kryptografisch sichere Zufallswerte
- Automatische Initialisierung während der Test-Bootstrap-Phase

## Konfigurationsoptionen

### Option 1: Environment Variablen (CI/CD-Empfehlung)

```bash
export TEST_USER_PASSWORD="SecureTestPassword123!"
export TEST_ADMIN_PASSWORD="SecureAdminTestPassword456!"
```

### Option 2: `.env.test`

1. `.env.test.example` kopieren.
1. Zugangsdaten eintragen.
1. Datei in `.gitignore` sicherstellen.

### Option 3: Fallback

Ohne Konfiguration erzeugt die Suite automatisch sichere Passwörter.

## Sicherheitsvorteile

1. Keine Geheimnisse im Quellcode.
1. Umgebungsspezifische Passwörter möglich.
1. Automatische Absicherung in CI/CD.
1. Unterstützt regelmäßige Rotation.

## Best Practices

- Test-Passwörter niemals committen.
- Test- und Produktionszugänge stets trennen.
- In CI/CD-Pipelines auf automatische Generierung setzen.
- Environment Variablen geschützt verwalten.

## Migration

Bestehende Tests wurden auf das neue System angepasst. Für bestehende Pipelines sind keine Änderungen nötig, individuelle Passwörter können optional weiterhin konfiguriert werden.
