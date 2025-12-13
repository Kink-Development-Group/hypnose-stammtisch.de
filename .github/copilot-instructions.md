# Hypnose-Stammtisch.de - Copilot Instructions

## Projektübersicht
Svelte 5 + TypeScript Frontend mit PHP 8.1+ Backend für einen Community-Kalender. Deutsche UI, gemischte Dokumentation.

## Tech Stack
- **Frontend**: Svelte 5, TypeScript, Vite, Tailwind CSS, Day.js, RRULE.js, Zod
- **Backend**: PHP 8.1+, MySQL/MariaDB, Composer (PSR-4: `HypnoseStammtisch\`)
- **Testing**: Playwright + @axe-core/playwright (a11y), PHPUnit

## Architektur

### Frontend (`src/`)
- `stores/admin.ts` – `AdminAPI` Klasse für alle `/api/admin/*` Aufrufe mit CSRF-Token-Handling
- `stores/adminData.ts` – Svelte Stores für Admin-State
- `classes/*.ts` – TypeScript Klassen mit Zod-Validierung (z.B. `User.ts`)
- `pages/admin/*Guarded.svelte` – Admin-Seiten mit `AdminGuard` Wrapper
- `components/admin/` – Wiederverwendbare Admin-Komponenten

### Backend (`backend/src/`)
- `Controllers/` – Statische Methoden, nutze `AdminAuth::requireAuth()`, `AdminAuth::requireCSRF()`
- `Middleware/AdminAuth.php` – Session/Auth-Handling, Rollen: `head`, `admin`, `moderator`, `event_manager`
- `Utils/Response.php` – `Response::success()`, `Response::error()` für JSON-Antworten
- `Utils/Validator.php` – Eingabevalidierung

## Wichtige Konventionen

### Dark Mode (Tailwind)
```
dark:bg-charcoal-{50-950}  # Hintergründe
dark:text-smoke-{50-950}   # Texte
```
Jedes UI-Element muss `dark:` Varianten haben. Farben in `tailwind.config.js` definiert.

### API-Aufrufe (Frontend)
```typescript
const result = await AdminAPI.events.getAll();  // Nutze AdminAPI Klasse
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
Alle API-Daten durch Zod-Schemas validieren bevor sie verwendet werden.

## Befehle
- `npm run dev` – Frontend + Backend parallel starten
- `npm run backend:migrate` – Migrationen ausführen
- `npm run format:all` – Frontend + PHP formatieren
- `npm run test` – Playwright Tests

## Rollen
`head` > `admin` > `moderator` > `event_manager` (hierarchisch)
