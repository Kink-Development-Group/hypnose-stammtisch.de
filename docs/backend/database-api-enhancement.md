---
description: Erweiterung der Datenbank-API um lastInsertId und insertAndGetId.
outline: deep
---

# Database Class Method Addition

## Problem

`SecurityLockoutIntegrationTest.php` nutzte `Database::lastInsertId()`, die Methode existierte jedoch nicht. PHPStorm/Intelephense meldeten einen Fehler.

## Lösung

Die `Database`-Klasse erhielt zwei ergänzende Methoden:

### `lastInsertId()`

```php
/**
 * Get last insert ID
 */
public static function lastInsertId(): string|false
{
    return self::getConnection()->lastInsertId();
}
```

### `insertAndGetId()`

```php
/**
 * Execute an INSERT statement and return the last insert ID
 * This is safer than execute() + lastInsertId() as it's atomic
 */
public static function insertAndGetId(string $sql, array $params = []): string|false
{
    self::execute($sql, $params);
    return self::lastInsertId();
}
```

## Verwendung

### Bestehendes Muster

```php
Database::execute(
    'INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)',
    ['test_user', 'test@example.com', $passwordHash, 'user', 1]
);
$userId = Database::lastInsertId();
```

### Empfohlenes Muster

```php
$userId = Database::insertAndGetId(
    'INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)',
    ['test_user', 'test@example.com', $passwordHash, 'user', 1]
);
```

### Alternative (bereits vorhanden)

```php
$userId = Database::insert(
    'INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)',
    ['test_user', 'test@example.com', $passwordHash, 'user', 1]
);
```

## Vorteile

1. Konsistente API für Datenbankoperationen.
1. Kompatibilität mit bestehendem Testcode.
1. Sichere Handhabung des PDO-Status.
1. Mehr Klarheit zwischen Ausführung und Insert mit ID.

## Migration

### Für neue Inserts

```php
$id = Database::insertAndGetId($sql, $params);
```

### Für bestehenden Code

`lastInsertId()` bleibt verfügbar, sodass keine sofortigen Änderungen nötig sind.

## API-Überblick

| Methode            | Anwendungsfall            | Rückgabe        |
| ------------------ | ------------------------- | --------------- |
| `execute()`        | Allgemeine SQL-Ausführung | `PDOStatement`  |
| `insert()`         | Insert mit ID-Rückgabe    | `string\|false` |
| `insertAndGetId()` | Empfohlener Insert-Weg    | `string\|false` |
| `lastInsertId()`   | Letzte ID nach Insert     | `string\|false` |

## Sicherheit

- Durchgehende Nutzung von Prepared Statements.
- `lastInsertId()` nur unmittelbar nach einem Insert verwenden.
- Bei mehreren Inserts Transaktionen einsetzen.
