# Hypnose-Stammtisch.de - Copilot Instructions

## Projektübersicht

Svelte 5 + TypeScript Frontend mit PHP 8.1+ Backend für einen Community-Kalender. Deutsche UI, gemischte Dokumentation.

## Tech Stack

- **Runtime**: Bun (statt Node.js)
- **Frontend**: Svelte 5, TypeScript, Vite, Tailwind CSS, Day.js, RRULE.js, Zod
- **Backend**: PHP 8.1+, MySQL/MariaDB, Composer (PSR-4: `HypnoseStammtisch\`)
- **Testing**: Playwright + @axe-core/playwright (a11y), PHPUnit

## Architektur

### Frontend (`src/`)

```
src/
├── stores/admin.ts      # AdminAPI Klasse für /api/admin/* mit CSRF-Handling
├── stores/adminData.ts  # Svelte Stores für Admin-State
├── classes/*.ts         # TypeScript Klassen mit Zod-Validierung
├── pages/admin/*Guarded.svelte  # Admin-Seiten mit AdminGuard Wrapper
└── components/admin/    # Wiederverwendbare Admin-Komponenten
```

### Backend (`backend/src/`)

```
backend/src/
├── Controllers/         # Statische Methoden, nutze AdminAuth::requireAuth()
├── Middleware/AdminAuth.php  # Session/Auth, Rollen-Handling
├── Utils/Response.php   # Response::success(), Response::error()
└── Utils/Validator.php  # Eingabevalidierung
```

## Wichtige Konventionen

### Dark Mode (Tailwind) – PFLICHT

```css
dark:bg-charcoal-{50-950}  /* Hintergründe */
dark:text-smoke-{50-950}   /* Texte */
```

**Jedes UI-Element muss `dark:` Varianten haben!** Farben in `tailwind.config.js` definiert.

### API-Aufrufe (Frontend)

```typescript
const result = await AdminAPI.events.getAll(); // Nutze AdminAPI Klasse
```

CSRF wird automatisch bei POST/PUT/DELETE hinzugefügt.

### Controller-Pattern (Backend)

```php
AdminAuth::requireAuth();
AdminAuth::requireCSRF();
$user = AdminAuth::getCurrentUser();
if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_MANAGEMENT_ROLES)) {
    Response::error('Insufficient permissions', 403);
    return;
}
```

### Zod-Validierung (Frontend)

Alle API-Daten durch Zod-Schemas validieren:

```typescript
export const UserSchema = z.object({
  id: z.union([z.string().uuid(), z.number()]).transform(String),
  // ...
});
const user = UserSchema.parse(apiResponse);
```

## Befehle (Bun)

```bash
bun run dev              # Frontend + Backend parallel
bun run backend:migrate  # Migrationen ausführen
bun run format:all       # Frontend + PHP formatieren
bun run test             # Playwright Tests
bun run check            # TypeScript/Svelte prüfen
```

### Backend CLI

```bash
cd backend && php cli/cli.php setup     # Komplette Einrichtung
cd backend && php cli/cli.php migrate   # Migrationen
cd backend && php cli/cli.php admin create  # Admin erstellen
```

## Rollen (hierarchisch)

`head` > `admin` > `moderator` > `event_manager`

**Rollen-Konstanten** in `AdminAuth.php`:

```php
AdminAuth::HEAD_ADMIN_ROLES         // ['head']
AdminAuth::SECURITY_MANAGEMENT_ROLES // ['head', 'admin']
AdminAuth::MESSAGE_MANAGEMENT_ROLES  // ['head', 'admin', 'moderator']
AdminAuth::EVENT_MANAGEMENT_ROLES    // ['head', 'admin', 'event_manager']
```

## Dateikonventionen

- **Admin-Seiten**: `*Guarded.svelte` wrapper um `*.svelte` Inhalt
- **API-Daten-Klassen**: TypeScript-Klasse mit Zod-Schema in `src/classes/`
- **Controller**: Statische Methoden, keine Instanziierung
