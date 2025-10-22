---
description: Struktur und Verwendung der User-Klasse im Frontend.
outline: deep
---

# User-Objektstrukturierung

## Übersicht

Alle User-Objekte im Frontend sind Instanzen der Klasse `User` (`src/classes/User.ts`). Das schafft Typsicherheit, Validierung und eine konsistente API.

## User-Klasse

### Eigenschaften

- `id: string`
- `username: string`
- `email: string`
- `role: Role`
- `is_active: boolean`
- `last_login: Date | null`
- `created_at: Date`
- `updated_at: Date`

### Factory-Methoden

```typescript
const user = User.fromApiData(apiResponse);
const user = User.fromObject(userObject);
```

### Utility-Methoden

```typescript
user.canManageUsers();
user.canManageEvents();
user.canManageMessages();
user.getRoleDisplayName();
user.getRoleBadgeClass();
user.getFormattedLastLogin();
user.getFormattedCreatedAt();
user.toApiObject();
user.update(partialData);
```

## UserHelpers Utility

Die Klasse `UserHelpers` (`src/utils/userHelpers.ts`) stellt statische Hilfsfunktionen bereit:

```typescript
const users = UserHelpers.fromApiArray(apiData);
const canManage = UserHelpers.hasPermission(user, "manage_users");
const permissions = UserHelpers.getPermissions(user);
UserHelpers.getRoleDisplayName(role);
UserHelpers.getRoleBadgeClass(role);
UserHelpers.formatDate(date);
```

## Integration in Stores

Der Admin Store (`src/stores/admin.ts`) nutzt User-Instanzen:

```typescript
export interface AdminAuthState {
  isAuthenticated: boolean;
  user: User | null;
  loading: boolean;
}
```

Beim Login und Status-Check werden automatisch User-Instanzen erzeugt:

```typescript
const user = User.fromApiData(result.data);
adminAuthState.update((state) => ({
  ...state,
  user,
}));
```

## Verwendung in Komponenten

```typescript
import User from "../../classes/User";
import { UserHelpers } from "../../utils/userHelpers";
import { Role } from "../../enums/role";
```

```svelte
<span class="badge {UserHelpers.getRoleBadgeClass(user.role)}">
  {UserHelpers.getRoleDisplayName(user.role)}
</span>

{#if user.canManageUsers()}
  <button>Benutzer verwalten</button>
{/if}
```

## Validierung

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

- [x] User-Klasse erweitert
- [x] UserHelpers erstellt
- [x] Admin Store aktualisiert
- [x] AdminUsers-Komponente angepasst
- [x] AdminLayout-Komponente aktualisiert
- [x] TypeScript-Validierung erfolgreich
- [ ] Weitere Komponenten prüfen
- [ ] Tests ergänzen

## Best Practices

1. Immer `User.fromApiData()` oder `User.fromObject()` verwenden.
1. `UserHelpers` für statische Operationen nutzen.
1. Typisierung strikt beibehalten.
1. Zod-Validierung nutzen.
1. Berechtigungen über Methoden prüfen, nicht über Rollennamen.

## Kompatibilität

- `toApiObject()` liefert das ursprüngliche API-Format.
- Factory-Methoden unterstützen alte und neue Strukturen.
- Das `Role`-Enum bleibt kompatibel mit String-Rollen der API.
