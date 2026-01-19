# Hypnose-Stammtisch.de - Copilot Instructions

## Projektübersicht

Community-Kalender mit Svelte 5 + TypeScript Frontend und PHP 8.1+ Backend. Deutsche UI, WCAG 2.2 AA konform.

## Tech Stack & Runtime

- **Runtime**: Bun (NICHT Node.js) – `bun run`, `bun install`
- **Frontend**: Svelte 5, TypeScript, Vite, Tailwind CSS v4, Day.js, RRULE.js, Zod
- **Backend**: PHP 8.1+, MySQL/MariaDB, Composer (PSR-4: `HypnoseStammtisch\`)
- **Testing**: Playwright + @axe-core/playwright (a11y), PHPUnit

## Architektur

### Datenfluss

```
Frontend (SPA)  →  /api/admin/*  →  backend/api/admin.php (Router)
                                        ↓
                                 Controllers/*.php (statische Methoden)
                                        ↓
                                 Database::fetchAll/fetchOne
```

**Vite Proxy**: In Entwicklung leitet `vite.config.ts` alle `/api/*` Requests an `localhost:8000` weiter. Daher keine vollständigen URLs in API-Aufrufen verwenden – immer relative Pfade wie `/api/admin/events`.

### Frontend (`src/`)

| Pfad                          | Zweck                                                                |
| ----------------------------- | -------------------------------------------------------------------- |
| `stores/admin.ts`             | `AdminAuthStore` + `AdminAPI` Klasse für alle `/api/admin/*` Aufrufe |
| `stores/adminData.ts`         | Svelte Stores für Admin-State (Events, Users, etc.)                  |
| `classes/*.ts`                | TypeScript Klassen mit Zod-Schema (z.B. `User.ts`)                   |
| `pages/admin/*Guarded.svelte` | Admin-Seiten mit `AdminGuard` Wrapper                                |
| `utils/rruleSummary.ts`       | RRULE → Deutsche Zusammenfassung                                     |

### Komponenten-Struktur (`src/components/`)

| Ordner      | Zweck                                            |
| ----------- | ------------------------------------------------ |
| `admin/`    | Admin-Panel UI (AdminGuard, Tabellen, Formulare) |
| `calendar/` | Kalender-Ansichten und Event-Darstellung         |
| `forms/`    | Wiederverwendbare Formular-Komponenten           |
| `map/`      | Karten-Integration (Leaflet)                     |
| `ui/`       | Generische UI-Elemente (Buttons, Modals, Badges) |
| `layout/`   | Header, Footer, Navigation                       |
| `shared/`   | Komponenten die überall genutzt werden           |

**Neue Komponente platzieren**: Nach Verwendungszweck, nicht nach Seitenkontext. Generische Buttons → `ui/`, Admin-spezifisch → `admin/`.

### Backend (`backend/`)

| Pfad                           | Zweck                                      |
| ------------------------------ | ------------------------------------------ |
| `api/admin.php`                | Router für Admin-Endpunkte                 |
| `src/Controllers/`             | Statische Controller-Methoden              |
| `src/Middleware/AdminAuth.php` | Session, 2FA, Rollen-Prüfung               |
| `src/Utils/Response.php`       | `Response::success()`, `Response::error()` |
| `cli/cli.php`                  | Einheitliches CLI-Tool                     |

## Kritische Konventionen

### 1. Dark Mode (Tailwind) – PFLICHT

```css
dark:bg-charcoal-{50-950}  /* Hintergründe */
dark:text-smoke-{50-950}   /* Texte */
```

**Jedes UI-Element muss `dark:` Varianten haben!** Farben in `tailwind.config.js`.

### 2. Admin-Seiten Pattern

Neue Admin-Seite = 2 Dateien:

```
AdminFoo.svelte         ← Eigentlicher Inhalt
AdminFooGuarded.svelte  ← Wrapper mit AdminGuard
```

```svelte
<!-- AdminFooGuarded.svelte -->
<script lang="ts">
  import AdminGuard from "../../components/admin/AdminGuard.svelte";
  import AdminFoo from "./AdminFoo.svelte";
  export let params = {};
</script>
<AdminGuard component={AdminFoo} {params} />
```

### 3. API-Aufrufe (Frontend)

```typescript
// IMMER über AdminAPI Klasse – CSRF automatisch
const result = await AdminAPI.events.getAll();
const result = await AdminAPI.users.update(id, data);
```

### 4. Controller-Pattern (Backend)

```php
public static function index(): void
{
    AdminAuth::requireAuth();
    AdminAuth::requireCSRF();  // Bei POST/PUT/DELETE

    $user = AdminAuth::getCurrentUser();
    if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_MANAGEMENT_ROLES)) {
        Response::error('Insufficient permissions', 403);
        return;
    }

    $data = Database::fetchAll("SELECT * FROM events");
    Response::success(['events' => $data]);
}
```

### 5. Zod-Validierung (Frontend)

```typescript
export const UserSchema = z.object({
  id: z.union([z.string().uuid(), z.number()]).transform(String),
  role: z.nativeEnum(Role),
  // ...
});
const user = UserSchema.parse(apiResponse); // Wirft bei Fehler
```

## Befehle

```bash
# Entwicklung
bun run dev              # Frontend (5173) + Backend (8000) parallel
bun run check            # TypeScript/Svelte prüfen

# Testing
bun run test             # Playwright E2E Tests
bun run test:a11y        # Accessibility Tests (axe-core)
bun run backend:test     # PHPUnit Tests

# Formatierung
bun run format:all       # Frontend + PHP

# Datenbank
bun run backend:migrate        # Migrationen ausführen
bun run backend:migrate:fresh  # DB zurücksetzen + neu aufbauen
```

### Backend CLI

```bash
cd backend && php cli/cli.php setup       # Komplette Einrichtung
cd backend && php cli/cli.php migrate     # Migrationen
cd backend && php cli/cli.php admin create  # Admin erstellen
cd backend && php cli/cli.php dev serve   # Dev-Server starten
```

### Security CLI

```bash
cd backend && php cli/cli.php security stats        # Statistiken anzeigen
cd backend && php cli/cli.php security list-bans    # IP-Bans auflisten
cd backend && php cli/cli.php security unlock EMAIL # Account entsperren
cd backend && php cli/cli.php security unban IP     # IP-Sperre aufheben
```

**Security-Konfiguration** via `.env`:

- `MAX_FAILED_ATTEMPTS=5` – Fehlversuche bis Lockout
- `IP_BAN_DURATION_SECONDS=3600` – IP-Sperre (0 = permanent)
- `ACCOUNT_LOCK_DURATION_SECONDS=3600` – Account-Sperre (0 = manuell)

## Rollen (hierarchisch)

`head` > `admin` > `moderator` > `event_manager`

```php
AdminAuth::HEAD_ADMIN_ROLES         // ['head']
AdminAuth::SECURITY_MANAGEMENT_ROLES // ['head', 'admin']
AdminAuth::MESSAGE_MANAGEMENT_ROLES  // ['head', 'admin', 'moderator']
AdminAuth::EVENT_MANAGEMENT_ROLES    // ['head', 'admin', 'event_manager']
```

## Wiederkehrende Events (RRULE)

- Events mit `rrule` Feld → RRULE.js zur Expansion
- Series in `event_series` Tabelle, generierte Events in `events` mit `series_id`
- `src/utils/rruleSummary.ts` für deutsche Textdarstellung

## Migrationen

- **Single Baseline**: `001_initial_schema.sql` enthält komplettes Schema
- Keine inkrementellen Migrationen – bei Änderungen Baseline aktualisieren (dev) oder neue Migration (prod)

## Accessibility

- WCAG 2.2 AA Konformität erforderlich
- Tests in `tests/accessibility.spec.ts`
- Keyboard-Navigation + Screen Reader Support prüfen
