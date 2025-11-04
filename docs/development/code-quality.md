---
description: Code-Qualitätsstandards und Best Practices für die Entwicklung
outline: deep
---

# Code-Qualitätsstandards

Diese Seite dokumentiert die Code-Qualitätsstandards und Best Practices für das Hypnose-Stammtisch-Projekt.

## Prinzipien

### 1. OOP First (Object-Oriented Programming)

Alle Business-Logik sollte in Klassen gekapselt werden:

**TypeScript/Frontend:**

- Nutze Klassen für Domain-Modelle (z.B. `User`, `Event`)
- Factory-Methoden für sichere Instanz-Erstellung
- Private/Public Member für Kapselung

**PHP/Backend:**

- Nutze Namespaces und Klassen
- Static-Methoden für Utilities
- Dependency Injection wo möglich

### 2. DRY (Don't Repeat Yourself)

Vermeide Code-Duplikation durch:

- **Wiederverwendbare Utilities**: `UserHelpers`, `ValidationHelpers`
- **Shared Types/Enums**: `Role`, `Locale`
- **Komponenten-Extraktion**: Gemeinsame UI-Elemente in separate Komponenten

**Beispiel:**

```typescript
// ❌ Schlecht: Code-Duplikation
const adminBadge = user.role === "admin" ? "bg-blue-100" : "bg-gray-100";
const modBadge = user.role === "moderator" ? "bg-green-100" : "bg-gray-100";

// ✅ Gut: Wiederverwendbare Helper-Funktion
const badge = UserHelpers.getRoleBadgeClass(user.role);
```

### 3. TypeScript-Dokumentation (TSDoc)

Alle öffentlichen APIs müssen mit TSDoc dokumentiert werden:

**Pflichtfelder:**

- Funktionsbeschreibung
- `@param` für alle Parameter
- `@returns` für Rückgabewerte
- `@example` für Verwendungsbeispiele
- `@throws` für geworfene Exceptions

**Beispiel:**

````typescript
/**
 * Check if a user has a specific permission.
 *
 * @param user - User instance or null
 * @param permission - Permission to check
 * @returns `true` if user has the permission, `false` otherwise
 *
 * @example
 * ```typescript
 * if (UserHelpers.hasPermission(currentUser, 'manage_users')) {
 *   // Show user management UI
 * }
 * ```
 *
 * @remarks
 * Returns `false` if user is null for safe null checking.
 */
static hasPermission(user: User | null, permission: string): boolean {
  // ...
}
````

### 4. i18n (Internationalisierung)

Alle Benutzer-sichtbaren Texte müssen internationalisierbar sein:

**Implementierung:**

```typescript
// ❌ Schlecht: Hartcodierte Texte
return "Head Admin";

// ✅ Gut: i18n-System nutzen
import { t } from "../utils/i18n";
return t("role.headAdmin");
```

**Aktueller Stand:**

- i18n-System ist in `src/utils/i18n.ts` implementiert
- Security-Module nutzen bereits i18n
- **TODO**: User-Klasse und UserHelpers auf i18n umstellen

### 5. Type Safety

Nutze TypeScript's Typ-System vollständig:

```typescript
// ❌ Schlecht: any-Types
function getUser(id: any): any { ... }

// ✅ Gut: Konkrete Typen
function getUser(id: string): User | null { ... }

// ✅ Noch besser: Generics wo sinnvoll
function fromApiArray<T>(data: any[], mapper: (item: any) => T): T[] { ... }
```

## Code-Struktur

### Frontend (TypeScript/Svelte)

```text
src/
├── classes/        # Domain-Modelle (User, Event, etc.)
├── components/     # Wiederverwendbare UI-Komponenten
├── enums/          # Enumerationen (Role, Status, etc.)
├── pages/          # Seiten-Komponenten
├── stores/         # Svelte Stores für State Management
├── types/          # TypeScript Type-Definitionen
└── utils/          # Utility-Funktionen und Helpers
```

### Backend (PHP)

```text
backend/src/
├── Config/         # Konfigurationsklassen
├── Controllers/    # API-Controller
├── Database/       # Datenbank-Layer
├── Middleware/     # Middleware (Auth, CORS, etc.)
├── Models/         # Datenbankmodelle
└── Utils/          # Utility-Klassen
```

## Validierung

### Frontend

Nutze Zod für Runtime-Validierung:

```typescript
import { z } from "zod";

export const UserSchema = z.object({
  id: z.union([z.string().uuid(), z.number()]).transform(String),
  username: z.string().min(1),
  email: z.string().email(),
  role: z.nativeEnum(Role),
});
```

### Backend

Nutze die Validator-Klasse:

```php
$validator = new Validator($input);
$validator->required(['username', 'email']);
$validator->email('email');
$validator->length('username', 3, 50);

if (!$validator->isValid()) {
  Response::error('Validation failed', 400, $validator->getErrors());
  return;
}
```

## Testing

### Unit Tests

```typescript
// Jest/Vitest
describe('User', () => {
  it('should create user from API data', () => {
    const user = User.fromApiData(mockApiData);
    expect(user.username).toBe('testuser');
  });

  it('should check permissions correctly', () => {
    const admin = User.fromApiData({ role: Role.ADMIN, ... });
    expect(admin.canManageEvents()).toBe(true);
    expect(admin.canManageUsers()).toBe(false);
  });
});
```

### Integration Tests

```php
// PHPUnit
public function testUserCreation(): void
{
    $data = [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'role' => 'admin'
    ];

    $response = $this->post('/api/admin/users', $data);
    $this->assertEquals(201, $response->getStatusCode());
}
```

## Performance

### Frontend Performance

- **Lazy Loading**: Komponenten bei Bedarf laden
- **Code Splitting**: Route-basiertes Splitting
- **Memoization**: Teure Berechnungen cachen

### Backend Performance

- **Datenbankindizes**: Für häufige Queries
- **Prepared Statements**: Immer für User-Input
- **Caching**: Response-Caching wo möglich

## Sicherheit

### Input-Validierung

- **Frontend**: Client-seitige Validierung für UX
- **Backend**: Server-seitige Validierung ist Pflicht
- **Nie vertrauen**: Client-Daten immer validieren

### XSS-Schutz

```svelte
<!-- ❌ Schlecht: Raw HTML -->
{@html userInput}

<!-- ✅ Gut: Escaped Text -->
{userInput}
```

### CSRF-Schutz

```typescript
// CSRF-Token für alle State-Changing Requests
await fetch("/api/admin/users", {
  method: "POST",
  headers: {
    "X-CSRF-Token": csrfToken,
  },
});
```

## Migration Checkliste

Bei Änderungen an bestehenden APIs:

- [ ] Alte Schnittstelle für mindestens 2 Versionen beibehalten
- [ ] Deprecation Warnings hinzufügen
- [ ] Migration Guide in Dokumentation
- [ ] Tests für alte und neue API

## Tools

### Linting

```bash
# Frontend
npm run lint

# Backend
composer phpstan
composer phpcs
```

### Formatierung

```bash
# Frontend
npm run format

# Backend
composer format
```

## Weitere Ressourcen

- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Svelte Best Practices](https://svelte.dev/docs)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Clean Code Principles](https://github.com/ryanmcdermott/clean-code-javascript)
