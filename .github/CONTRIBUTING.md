# Contributing zu Hypnose-Stammtisch.de

Vielen Dank, dass du zu Hypnose-Stammtisch.de beitragen möchtest! 🎉
Dieser Leitfaden fasst die wichtigsten Konventionen zusammen. Eine ausführliche
Architektur-Übersicht findest du in [`.github/copilot-instructions.md`](./copilot-instructions.md)
und im [README](../README.md).

## 📜 Verhaltenskodex

Mit deiner Teilnahme akzeptierst du unseren [Code of Conduct](../CODE_OF_CONDUCT.md).
Bitte halte die Community freundlich und respektvoll.

## 🔒 Sicherheit

Sicherheitslücken **niemals** als öffentliches Issue melden. Nutze stattdessen
die vertraulichen Kanäle aus [SECURITY.md](../SECURITY.md).

## 🛠️ Entwicklungsumgebung

Voraussetzungen:

- **Bun** (nicht Node.js) als Runtime
- **PHP 8.1+** und **Composer**
- **MySQL 8.0+** oder **MariaDB 10.6+**

```bash
# Frontend-Abhängigkeiten
bun install

# Backend-Abhängigkeiten
cd backend && composer install && cd ..

# Frontend (5173) + Backend (8000) parallel starten
bun run dev
```

## 🌿 Branch- & Git-Workflow

- `main` → **Produktion** (deployt automatisch via SFTP)
- `dev` → **Beta/Staging** (deployt automatisch via SFTP)
- Feature-Branches von `dev` abzweigen: `feature/<kurzbeschreibung>` bzw.
  `fix/<kurzbeschreibung>`

```bash
git switch dev
git switch -c feature/meine-funktion
```

> ⚠️ Pushes auf `dev` und `main` lösen ein automatisches Deployment aus.
> Arbeite daher immer in Feature-Branches und gehe über Pull Requests.

## ✍️ Commit-Konventionen

Wir verwenden [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <kurze Beschreibung>
```

Gängige Typen: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`,
`chore`, `ci`. Beispiele:

```
feat(events): Serien-Bearbeitung im Admin-Panel
fix(calendar): RRULE-Expansion über Monatsgrenzen korrigiert
chore(deps): Tailwind auf v4.1 aktualisiert
```

## ✅ Vor dem Pull Request

Bitte stelle sicher, dass folgende Befehle lokal erfolgreich durchlaufen:

```bash
bun run check        # TypeScript / Svelte
bun run format:all   # Prettier (Frontend) + PHP-Formatierung
bun run test         # Playwright E2E
bun run test:a11y    # Accessibility (axe-core)
bun run backend:test # PHPUnit (falls Backend betroffen)
```

## 🎯 Code-Konventionen (Kurzfassung)

- **Dark Mode ist Pflicht**: Jedes UI-Element braucht `dark:`-Varianten.
- **API-Aufrufe** im Frontend immer über die `AdminAPI`-Klasse (CSRF automatisch),
  niemals mit absoluten URLs – nutze relative Pfade wie `/api/admin/events`.
- **Admin-Seiten** bestehen aus zwei Dateien: `AdminFoo.svelte` +
  `AdminFooGuarded.svelte` (Wrapper mit `AdminGuard`).
- **Backend-Controller** nutzen statische Methoden, `AdminAuth::requireAuth()` /
  `requireCSRF()` und `Response::success()` / `Response::error()`.
- **Validierung** im Frontend mit Zod-Schemas, im Backend mit Prepared Statements.
- **Barrierefreiheit**: WCAG 2.2 AA – Tastatur-Navigation, ARIA, Kontrast ≥ 4.5:1.

## 🗄️ Datenbank-Migrationen

- **Single Baseline**: `backend/migrations/001_initial_schema.sql` enthält das
  vollständige Schema.
- Bei Schema-Änderungen: Baseline aktualisieren (dev) bzw. neue Migration anlegen
  (prod) und mit `bun run backend:migrate:fresh` lokal verifizieren.

## 🔄 Pull-Request-Prozess

1. Fülle das [Pull-Request-Template](./PULL_REQUEST_TEMPLATE.md) aus.
2. Verlinke das zugehörige Issue (`Closes #123`).
3. Halte den PR fokussiert und möglichst klein.
4. Reagiere auf Review-Feedback; CI muss grün sein, bevor gemergt wird.

Danke für deinen Beitrag! ❤️
