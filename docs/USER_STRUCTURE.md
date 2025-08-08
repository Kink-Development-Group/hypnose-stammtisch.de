# User-Objektstrukturierung

Dieses Dokument beschreibt die Strukturierung der User-Objekte im Frontend der Anwendung.

## Übersicht

Alle User-Objekte im Frontend sind jetzt Instanzen der `User`-Klasse (`src/classes/User.ts`), anstatt einfache JavaScript-Objekte zu sein. Dies bietet bessere Typsicherheit, Validierung und eine konsistente API.

## User-Klasse

### Eigenschaften

- `id: string` - Eindeutige Benutzer-ID (UUID oder umgewandelte Nummer)
- `username: string` - Benutzername
- `email: string` - E-Mail-Adresse (validiert)
- `role: Role` - Benutzerrolle (enum)
- `is_active: boolean` - Aktiv-Status
- `last_login: Date | null` - Letzter Login
- `created_at: Date` - Erstellungsdatum
- `updated_at: Date` - Letztes Update

### Factory-Methoden

```typescript
// Aus API-Daten erstellen
const user = User.fromApiData(apiResponse);

// Aus beliebigem Objekt erstellen
const user = User.fromObject(userObject);
```

### Utility-Methoden

```typescript
// Berechtigungen prüfen
user.canManageUsers();
user.canManageEvents();
user.canManageMessages();

// Display-Informationen
user.getRoleDisplayName();
user.getRoleBadgeClass();
user.getFormattedLastLogin();
user.getFormattedCreatedAt();

// Konvertierung
user.toApiObject();
user.update(partialData);
```

## UserHelpers Utility

Die `UserHelpers`-Klasse (`src/utils/userHelpers.ts`) bietet statische Hilfsmethoden:

```typescript
// Array von API-Daten zu User-Array
const users = UserHelpers.fromApiArray(apiData);

// Berechtigungen prüfen
const canManage = UserHelpers.hasPermission(user, "manage_users");

// Berechtigungsobjekt abrufen
const permissions = UserHelpers.getPermissions(user);

// Display-Hilfsfunktionen
UserHelpers.getRoleDisplayName(role);
UserHelpers.getRoleBadgeClass(role);
UserHelpers.formatDate(date);
```

## Integration in Stores

### Admin Store (`src/stores/admin.ts`)

Der Admin Store wurde aktualisiert, um User-Instanzen zu verwenden:

```typescript
export interface AdminAuthState {
  isAuthenticated: boolean;
  user: User | null; // Jetzt User-Klasse statt Interface
  loading: boolean;
}
```

Beim Login und Status-Check werden automatisch User-Instanzen erstellt:

```typescript
const user = User.fromApiData(result.data);
adminAuthState.update((state) => ({
  ...state,
  user,
  // ...
}));
```

## Verwendung in Komponenten

### Importing

```typescript
import User from "../../classes/User";
import { UserHelpers } from "../../utils/userHelpers";
import { Role } from "../../enums/role";
```

### Typ-Deklarationen

```typescript
let users: User[] = [];
let editingUser: User | null = null;
let currentUser: User | null = null;
```

### API-Daten zu User-Instanzen

```typescript
// Einzelner User
const user = User.fromApiData(apiData);

// Array von Usern
const users = UserHelpers.fromApiArray(apiArray);
```

### Berechtigungen prüfen

```typescript
// Direkt am User-Objekt
if (currentUser?.canManageUsers()) {
  // Zeige Admin-UI
}

// Über UserHelpers
const permissions = UserHelpers.getPermissions(currentUser);
if (permissions.can_manage_users) {
  // Zeige Admin-UI
}
```

### Template-Verwendung

```svelte
<!-- Role anzeigen -->
<span class="badge {UserHelpers.getRoleBadgeClass(user.role)}">
  {UserHelpers.getRoleDisplayName(user.role)}
</span>

<!-- Formatierte Daten -->
<td>{UserHelpers.formatDate(user.last_login)}</td>
<td>{user.getFormattedCreatedAt()}</td>

<!-- Bedingte Anzeige basierend auf Berechtigungen -->
{#if user.canManageUsers()}
  <button>Benutzer verwalten</button>
{/if}
```

## Validierung

Alle User-Objekte werden durch Zod-Schemas validiert:

```typescript
export const UserSchema = z.object({
  id: z.union([z.string().uuid(), z.number()]).transform(String),
  username: z.string().min(1),
  email: z.string().email(),
  role: z.nativeEnum(Role),
  is_active: z.boolean().optional().default(true),
  last_login: z.string().datetime().nullable().optional(),
  created_at: z.string().datetime(),
  updated_at: z.string().datetime(),
});
```

## Migration Checklist

- [x] User-Klasse überarbeitet und erweitert
- [x] UserHelpers Utility-Klasse erstellt
- [x] Admin Store aktualisiert
- [x] AdminUsers-Komponente aktualisiert
- [x] AdminLayout-Komponente aktualisiert
- [x] TypeScript-Validierung erfolgreich
- [ ] Weitere Komponenten nach Bedarf aktualisieren
- [ ] Tests erstellen/aktualisieren

## Best Practices

1. **Immer Factory-Methoden verwenden**: Erstelle User-Instanzen nur über `User.fromApiData()` oder `User.fromObject()`
2. **UserHelpers für statische Operationen**: Verwende `UserHelpers` für Operationen, die nicht User-spezifisch sind
3. **Typisierung beibehalten**: Verwende immer `User` oder `User | null` statt `any`
4. **Validierung nutzen**: Verlasse dich auf die eingebaute Zod-Validierung
5. **Berechtigungen über Methoden**: Nutze die eingebauten Permission-Methoden statt manueller Rolle-Checks

## Kompatibilität

Die neue Struktur ist rückwärtskompatibel mit der bestehenden API, da:

- User-Objekte können über `toApiObject()` in das ursprüngliche Format konvertiert werden
- Factory-Methoden akzeptieren sowohl neue als auch alte Datenstrukturen
- Die Role-Enum ist kompatibel mit den String-Werten der API
