# Agent Instructions – Hypnose-Stammtisch.de

> **Zweck**: Umfassende Dokumentation für AI-Agenten zur effizienten und konsistenten Arbeit mit diesem Projekt.

---

## 📋 Projekt-Übersicht

**Hypnose-Stammtisch.de** ist ein Community-Kalender für Hypnose-Stammtische im DACH-Raum.

| Eigenschaft     | Wert                                           |
| --------------- | ---------------------------------------------- |
| **Typ**         | Single-Page Application (SPA) + REST-API       |
| **Sprache UI**  | Deutsch                                        |
| **Konformität** | WCAG 2.2 Level AA (Barrierefreiheit)           |
| **Repository**  | `Kink-Development-Group/hypnose-stammtisch.de` |
| **Branches**    | `main` (Production), `dev` (Development)       |

---

## 🛠️ Tech Stack

### Frontend

| Technologie      | Version | Zweck                                      |
| ---------------- | ------- | ------------------------------------------ |
| **Bun**          | Latest  | Runtime & Package Manager (NICHT Node.js!) |
| **Svelte 5**     | Latest  | Reaktives UI-Framework                     |
| **TypeScript**   | Strict  | Typsicherheit                              |
| **Vite**         | Latest  | Build-Tool & Dev-Server (Port 5173)        |
| **Tailwind CSS** | v4      | Utility-First Styling                      |
| **Day.js**       | Latest  | Datum-/Zeitverarbeitung                    |
| **RRULE.js**     | Latest  | Wiederkehrende Events                      |
| **Zod**          | Latest  | Runtime-Validierung                        |

### Backend

| Technologie       | Version              | Zweck                 |
| ----------------- | -------------------- | --------------------- |
| **PHP**           | 8.1+                 | Server-Sprache        |
| **MySQL/MariaDB** | 8.0+                 | Datenbank             |
| **Composer**      | Latest               | Dependency Management |
| **Namespace**     | `HypnoseStammtisch\` | PSR-4 Autoloading     |

### Testing

| Tool                     | Zweck               |
| ------------------------ | ------------------- |
| **Playwright**           | E2E-Tests           |
| **Vitest**               | Frontend Unit-Tests |
| **@axe-core/playwright** | Accessibility-Tests |
| **PHPUnit**              | Backend Unit-Tests  |

---

## 📁 Verzeichnisstruktur

```bash
hypnose-stammtisch.de/
├── src/                          # Frontend-Quellcode
│   ├── components/               # UI-Komponenten
│   │   ├── admin/                # Admin-Panel UI
│   │   ├── calendar/             # Kalender-Ansichten
│   │   ├── forms/                # Wiederverwendbare Formulare
│   │   ├── icons/                # Icon-Komponenten
│   │   ├── layout/               # Header, Footer, Navigation
│   │   ├── map/                  # Leaflet Karten-Integration
│   │   ├── sections/             # Seiten-Abschnitte
│   │   ├── shared/               # Übergreifende Komponenten
│   │   └── ui/                   # Generische UI-Primitives
│   ├── classes/                  # TypeScript Klassen mit Zod-Schema
│   ├── enums/                    # Enumerationen (Role, etc.)
│   ├── pages/                    # SPA-Seiten & Admin-Seiten
│   │   └── admin/                # Admin-Seiten (*Guarded.svelte)
│   ├── stores/                   # Svelte Stores (State Management)
│   ├── styles/                   # Globale CSS-Styles
│   ├── types/                    # TypeScript Type-Definitionen
│   └── utils/                    # Hilfsfunktionen
├── backend/                      # PHP Backend
│   ├── api/                      # API-Endpunkte
│   │   ├── index.php             # Öffentliche API (Events, Kalender)
│   │   └── admin.php             # Admin-API Router
│   ├── src/
│   │   ├── Config/               # Konfigurationsklassen
│   │   ├── Controllers/          # API-Controller (statische Methoden)
│   │   ├── Database/             # Database-Wrapper
│   │   ├── Middleware/           # Auth, CORS, Rate Limiting
│   │   ├── Models/               # Datenmodelle
│   │   └── Utils/                # Response, Validator, EmailService
│   ├── cli/                      # CLI-Werkzeuge
│   ├── config/                   # app.php, database.php
│   └── migrations/               # SQL-Migrationsdateien
├── docs/                         # VitePress Dokumentation
├── tests/                        # Playwright E2E Tests
└── public/                       # Statische Assets
```

---

## 🔧 Wichtige Befehle

### Entwicklung

```bash
bun run dev                    # Frontend (5173) + Backend (8000) parallel
bun run check                  # TypeScript/Svelte Prüfung
bun run format:all             # Formatierung (Frontend + PHP)
```

### Testing

```bash
bun run test                   # Playwright E2E Tests
bun run test:unit              # Vitest Unit Tests (watch mode)
bun run test:unit:run          # Vitest Unit Tests (single run)
bun run test:unit:coverage     # Vitest with coverage
bun run test:a11y              # Accessibility Tests (axe-core)
bun run backend:test           # PHPUnit Tests
```

### Backend CLI

```bash
cd backend && php cli/cli.php setup         # Komplette Einrichtung
cd backend && php cli/cli.php migrate       # Migrationen ausführen
cd backend && php cli/cli.php admin create  # Admin erstellen
cd backend && php cli/cli.php dev serve     # Dev-Server (Port 8000)
```

### Datenbank

```bash
bun run backend:migrate              # Migrationen ausführen
bun run backend:migrate:fresh        # DB zurücksetzen + neu aufbauen
bun run backend:database:status      # Datenbank-Status prüfen
```

---

## 🏗️ Architektur & Datenfluss

```text
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (SPA)                           │
│  Svelte 5 + TypeScript + Vite                                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │   Pages     │──│   Stores    │──│     AdminAPI Class      │ │
│  └─────────────┘  └─────────────┘  └────────────┬────────────┘ │
└─────────────────────────────────────────────────┼───────────────┘
                                                   │ HTTP
                                                   │ (Vite Proxy → :8000)
┌─────────────────────────────────────────────────┼───────────────┐
│                     Backend (PHP 8.1+)           ▼              │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  /api/admin.php (Router)                                  │  │
│  │    → Parse path & method                                  │  │
│  │    → Route zu Controller::method()                        │  │
│  └──────────────────────────────────┬───────────────────────┘  │
│                                      ▼                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Controllers/*.php                                        │  │
│  │    → AdminAuth::requireAuth()                             │  │
│  │    → AdminAuth::requireCSRF() (POST/PUT/DELETE)           │  │
│  │    → Business Logic                                       │  │
│  │    → Response::success() / Response::error()              │  │
│  └──────────────────────────────────┬───────────────────────┘  │
│                                      ▼                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Database::fetchAll() / fetchOne()                        │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Vite Proxy (Entwicklung)

In der Entwicklung leitet `vite.config.ts` alle `/api/*` Requests an `localhost:8000` weiter.

**→ IMMER relative Pfade verwenden**: `/api/admin/events` (NICHT `http://localhost:8000/api/admin/events`)

---

## 🎨 Kritische Konventionen

### 1. Dark Mode (PFLICHT)

**Jedes UI-Element MUSS `dark:` Varianten haben!**

```html
<!-- ✅ KORREKT -->
<div class="bg-white dark:bg-charcoal-800 text-gray-900 dark:text-smoke-50">
  <p class="text-gray-600 dark:text-smoke-400">Text</p>
</div>

<!-- ❌ FALSCH – Keine Dark Mode Varianten -->
<div class="bg-white text-gray-900">
  <p class="text-gray-600">Text</p>
</div>
```

**Farbsystem** (definiert in `tailwind.config.js`):

| Kategorie   | Light Mode              | Dark Mode                   |
| ----------- | ----------------------- | --------------------------- |
| Hintergrund | `bg-white`, `bg-gray-*` | `dark:bg-charcoal-{50-950}` |
| Text        | `text-gray-*`           | `dark:text-smoke-{50-950}`  |
| Primär      | `primary-{50-950}`      | – (gleich)                  |
| Akzent      | `accent-{50-950}`       | – (gleich)                  |
| Sekundär    | `secondary-{50-950}`    | – (gleich)                  |

---

### 2. Admin-Seiten Pattern

**Neue Admin-Seite = IMMER 2 Dateien erstellen:**

```bash
src/pages/admin/
├── AdminFoo.svelte           ← Eigentlicher Inhalt
└── AdminFooGuarded.svelte    ← Wrapper mit AdminGuard
```

**AdminFooGuarded.svelte:**

```svelte
<script lang="ts">
  import AdminGuard from "../../components/admin/AdminGuard.svelte";
  import AdminFoo from "./AdminFoo.svelte";
  export let params = {};
</script>

<AdminGuard component={AdminFoo} {params} />
```

**Route registrieren in `App.svelte`:**

```typescript
const routes = {
  "/admin/foo": AdminFooGuarded,
  // ...
};
```

---

### 3. API-Aufrufe (Frontend)

**IMMER über `AdminAPI` Klasse** – CSRF wird automatisch gehandhabt:

```typescript
import { AdminAPI } from "../stores/admin";

// ✅ KORREKT
const result = await AdminAPI.events.getAll();
const result = await AdminAPI.users.update(id, data);
const result = await AdminAPI.messages.delete(id);

// ❌ FALSCH – Kein direkter fetch() für Admin-Endpunkte
const response = await fetch("/api/admin/events");
```

**Für öffentliche Endpunkte** (z.B. `/api/events`) ist direkter `fetch()` erlaubt.

---

### 4. Controller-Pattern (Backend)

```php
<?php
// backend/src/Controllers/ExampleController.php

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Utils\Response;

class ExampleController
{
    public static function index(): void
    {
        // 1. Auth prüfen
        AdminAuth::requireAuth();

        // 2. CSRF bei POST/PUT/DELETE
        // AdminAuth::requireCSRF();

        // 3. Rollen prüfen
        $user = AdminAuth::getCurrentUser();
        if (!AdminAuth::userHasRole($user, AdminAuth::EVENT_MANAGEMENT_ROLES)) {
            Response::error('Insufficient permissions', 403);
            return;
        }

        // 4. Daten abrufen
        $data = Database::fetchAll("SELECT * FROM example");

        // 5. Response senden
        Response::success(['items' => $data]);
    }

    public static function create(): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();  // ← Bei POST

        $input = json_decode(file_get_contents('php://input'), true);

        // Validierung...

        Database::execute(
            "INSERT INTO example (name) VALUES (?)",
            [$input['name']]
        );

        Response::success(['id' => Database::lastInsertId()], 'Created', 201);
    }
}
```

**Rollen-Konstanten:**

```php
AdminAuth::HEAD_ADMIN_ROLES         // ['head']
AdminAuth::SECURITY_MANAGEMENT_ROLES // ['head', 'admin']
AdminAuth::MESSAGE_MANAGEMENT_ROLES  // ['head', 'admin', 'moderator']
AdminAuth::EVENT_MANAGEMENT_ROLES    // ['head', 'admin', 'event_manager']
```

---

### 5. Zod-Validierung (Frontend)

```typescript
import { z } from "zod";
import { Role } from "../enums/role";

// Schema definieren
export const UserSchema = z.object({
  id: z.union([z.string().uuid(), z.number()]).transform(String),
  username: z.string().min(1),
  email: z.string().email(),
  role: z.nativeEnum(Role),
  is_active: z.boolean().optional().default(true),
  last_login: z.string().nullable().optional(),
  created_at: z.string(),
  updated_at: z.string(),
});

// Type ableiten
export type UserData = z.infer<typeof UserSchema>;

// Validierung anwenden
const user = UserSchema.parse(apiResponse); // Wirft bei Fehler
const user = UserSchema.safeParse(apiResponse); // Gibt Result zurück
```

---

### 6. Response-Klasse (Backend)

```php
// Erfolg
Response::success(['users' => $users]);
Response::success(['id' => $id], 'Created', 201);

// Fehler
Response::error('Validation failed', 400);
Response::error('Not found', 404);
Response::error('Forbidden', 403);

// Spezielle
Response::unauthorized();
Response::forbidden();
Response::notFound();
```

---

## 👥 Rollen-System

**Hierarchisch**: `head` > `admin` > `moderator` > `event_manager`

| Rolle             | Berechtigung                                                |
| ----------------- | ----------------------------------------------------------- |
| **head**          | Vollzugriff inkl. Benutzerverwaltung                        |
| **admin**         | Event- und Nachrichtenverwaltung                            |
| **moderator**     | Nur Nachrichtenverwaltung                                   |
| **event_manager** | Nur Event-Verwaltung (kann vergangene Events nicht löschen) |

**Frontend Enum:**

```typescript
export enum Role {
  HEADADMIN = "head",
  ADMIN = "admin",
  MODERATOR = "moderator",
  EVENTMANAGER = "event_manager",
}
```

---

## 📅 Wiederkehrende Events (RRULE)

- Events mit `rrule` Feld verwenden **RRULE.js** zur Expansion
- Serien werden in `event_series` Tabelle gespeichert
- Generierte Events in `events` mit `series_id` Referenz
- **Deutsche Zusammenfassung**: `src/utils/rruleSummary.ts`

```typescript
import { getRRuleSummary } from "../utils/rruleSummary";

const summary = getRRuleSummary(event.rrule);
// → "Jeden 2. Mittwoch im Monat um 19:00"
```

---

## 🗄️ Datenbank & Migrationen

**Migrations-Verzeichnis**: `backend/migrations/`

| Migration                                    | Beschreibung            |
| -------------------------------------------- | ----------------------- |
| `001_initial_schema.sql`                     | Komplettes Basis-Schema |
| `002_add_event_manager_role.sql`             | Event-Manager Rolle     |
| `003_series_times_and_event_overrides.sql`   | Serien-Zeiten           |
| `004_series_overrides_and_cancellations.sql` | Serien-Absagen          |
| `005_add_security_tables.sql`                | Security-Tabellen       |
| `005_add_stammtisch_locations.sql`           | Stammtisch-Standorte    |
| `006_security_enhancements.sql`              | Security-Erweiterungen  |
| `007_password_reset_tokens.sql`              | Passwort-Reset          |
| `014_add_webauthn_credentials.sql`           | Passkeys (WebAuthn)     |

**Wichtig**: `001_initial_schema.sql` enthält das komplette Schema – in der Entwicklung bei Änderungen die Baseline aktualisieren.

---

## 🔐 Sicherheit

### Session & Auth

- Sessions über `AdminAuth::startSession()`
- 2FA mit TOTP (Google Authenticator kompatibel)
- Backup-Codes für 2FA-Wiederherstellung
- Session-Regeneration alle 30 Minuten
- Passkeys (WebAuthn) als alternativer, phishing-resistenter Login-Pfad;
  Passwort + TOTP bleibt der Fallback (`docs/security/passkeys.md`)

### Rate Limiting & Account Protection

```env
MAX_FAILED_ATTEMPTS=5              # Fehlversuche bis Lockout
IP_BAN_DURATION_SECONDS=3600       # IP-Sperre (0 = permanent)
ACCOUNT_LOCK_DURATION_SECONDS=3600 # Account-Sperre (0 = manuell)
```

### Security CLI

```bash
cd backend && php cli/cli.php security stats        # Statistiken
cd backend && php cli/cli.php security list-bans    # IP-Bans
cd backend && php cli/cli.php security unlock EMAIL # Account entsperren
cd backend && php cli/cli.php security unban IP     # IP freigeben
```

---

## ♿ Accessibility (WCAG 2.2 AA)

**Anforderungen:**

- Farbkontrast ≥ 4.5:1 (normaler Text)
- Keyboard-Navigation vollständig
- Screen Reader Support
- Focus-Indikatoren sichtbar
- ARIA-Labels für interaktive Elemente

**Tests:**

```bash
bun run test:a11y    # Playwright + axe-core Tests
```

**Testdatei**: `tests/accessibility.spec.ts`

---

## 📦 Store-Übersicht

| Store              | Datei                     | Zweck                            |
| ------------------ | ------------------------- | -------------------------------- |
| **adminAuthState** | `stores/admin.ts`         | Auth-Status, 2FA, User           |
| **adminData**      | `stores/adminData.ts`     | Events, Users, Messages State    |
| **adminSecurity**  | `stores/adminSecurity.ts` | Security-Dashboard Daten         |
| **calendar**       | `stores/calendar.ts`      | Kalender-Events, Filter          |
| **map**            | `stores/map.ts`           | Karten-State, Layer              |
| **compliance**     | `stores/compliance.ts`    | Cookie-Consent, Age-Verification |
| **ui**             | `stores/ui.ts`            | Notifications, Modals            |

---

## 🌐 API-Endpunkte

### Öffentlich (`/api/...`)

| Methode | Endpunkt               | Beschreibung      |
| ------- | ---------------------- | ----------------- |
| GET     | `/api/events`          | Alle Events       |
| GET     | `/api/events/upcoming` | Kommende Events   |
| GET     | `/api/events/{id}`     | Einzelnes Event   |
| GET     | `/api/calendar/feed`   | ICS-Kalender-Feed |
| POST    | `/api/contact`         | Kontaktformular   |

### Admin (`/api/admin/...`)

| Methode | Endpunkt           | Beschreibung           |
| ------- | ------------------ | ---------------------- |
| POST    | `/auth/login`      | Login                  |
| POST    | `/auth/logout`     | Logout                 |
| GET     | `/auth/status`     | Auth-Status prüfen     |
| GET     | `/auth/csrf`       | CSRF-Token abrufen     |
| POST    | `/auth/2fa/setup`  | 2FA einrichten         |
| POST    | `/auth/2fa/verify` | 2FA verifizieren       |
| GET     | `/events`          | Admin Events           |
| POST    | `/events`          | Event erstellen        |
| PUT     | `/events/{id}`     | Event aktualisieren    |
| DELETE  | `/events/{id}`     | Event löschen          |
| GET     | `/users`           | Benutzer auflisten     |
| POST    | `/users`           | Benutzer erstellen     |
| PUT     | `/users/{id}`      | Benutzer aktualisieren |
| DELETE  | `/users/{id}`      | Benutzer löschen       |
| GET     | `/messages`        | Nachrichten            |
| DELETE  | `/messages/{id}`   | Nachricht löschen      |

### Passkeys (`/api/admin/auth/webauthn/...`)

Alternativer Login-Pfad neben Passwort + TOTP – siehe
[`docs/security/passkeys.md`](docs/security/passkeys.md).

| Methode | Endpunkt            | Auth | Beschreibung                        |
| ------- | ------------------- | ---- | ----------------------------------- |
| POST    | `/register/options` | ja   | Challenge für die Registrierung     |
| POST    | `/register/verify`  | ja   | Attestation prüfen und speichern    |
| POST    | `/login/options`    | nein | Challenge für die Anmeldung         |
| POST    | `/login/verify`     | nein | Assertion prüfen und Session öffnen |
| GET     | `/credentials`      | ja   | Eigene Passkeys auflisten           |
| DELETE  | `/credentials/{id}` | ja   | Eigenen Passkey löschen             |

---

## 🧩 Komponenten-Platzierung

**Nach Verwendungszweck, NICHT nach Seitenkontext:**

| Komponenten-Typ               | Ordner                 |
| ----------------------------- | ---------------------- |
| Admin-spezifische UI          | `components/admin/`    |
| Kalender-bezogen              | `components/calendar/` |
| Wiederverwendbare Formulare   | `components/forms/`    |
| Icons (Svelte)                | `components/icons/`    |
| Layout (Header, Footer)       | `components/layout/`   |
| Karten (Leaflet)              | `components/map/`      |
| Seiten-Abschnitte             | `components/sections/` |
| Übergreifende (Modals)        | `components/shared/`   |
| Generische UI (Button, Badge) | `components/ui/`       |

---

## ⚠️ Häufige Fehler vermeiden

### ❌ FALSCH vs ✅ RICHTIG

```typescript
// ❌ Node.js statt Bun
npm install / npm run dev
// ✅
bun install / bun run dev

// ❌ Vollständige URL für API
fetch("http://localhost:8000/api/admin/events")
// ✅ Relative URL (Vite Proxy)
fetch("/api/admin/events")

// ❌ Direkter fetch für Admin
await fetch("/api/admin/users")
// ✅ AdminAPI verwenden
await AdminAPI.users.getAll()

// ❌ Dark Mode fehlt
class="bg-white text-gray-900"
// ✅ Mit Dark Mode
class="bg-white dark:bg-charcoal-800 text-gray-900 dark:text-smoke-50"

// ❌ Admin-Seite ohne Guard
// AdminFoo.svelte direkt in routes
// ✅ Mit Guard-Wrapper
// AdminFooGuarded.svelte mit AdminGuard
```

---

## 📝 Checkliste für neue Features

### Frontend-Feature

- [ ] TypeScript mit strikten Types
- [ ] Zod-Schema für API-Daten
- [ ] Dark Mode für alle UI-Elemente
- [ ] Keyboard-Navigation
- [ ] ARIA-Labels
- [ ] Deutsche Beschriftungen
- [ ] AdminAPI für Admin-Endpunkte
- [ ] Error Handling mit Notifications

### Backend-Feature

- [ ] Controller mit statischen Methoden
- [ ] `AdminAuth::requireAuth()` für geschützte Routen
- [ ] `AdminAuth::requireCSRF()` für POST/PUT/DELETE
- [ ] Rollen-Prüfung mit `userHasRole()`
- [ ] Input-Validierung
- [ ] `Response::success()` / `Response::error()`
- [ ] PHPDoc-Dokumentation
- [ ] Route in `admin.php` registrieren

### Neue Admin-Seite

- [ ] `AdminFoo.svelte` (Inhalt)
- [ ] `AdminFooGuarded.svelte` (Wrapper)
- [ ] Route in `App.svelte` registrieren
- [ ] Navigation in AdminLayout erweitern
- [ ] Dark Mode vollständig

---

## 🔗 Wichtige Dateien

| Datei                                  | Beschreibung                    |
| -------------------------------------- | ------------------------------- |
| `src/App.svelte`                       | Haupt-App, Router-Definition    |
| `src/stores/admin.ts`                  | AdminAuthStore, AdminAPI        |
| `src/classes/User.ts`                  | User-Klasse mit Zod-Schema      |
| `src/enums/role.ts`                    | Rollen-Enum                     |
| `backend/api/admin.php`                | Admin-API Router                |
| `backend/src/Middleware/AdminAuth.php` | Auth-Middleware, Rollen         |
| `backend/src/Utils/Response.php`       | Response-Helper                 |
| `tailwind.config.js`                   | Farben, Fonts, Custom Utilities |
| `vite.config.ts`                       | Vite-Konfiguration, Proxy       |

---

_Zuletzt aktualisiert: Januar 2026_
