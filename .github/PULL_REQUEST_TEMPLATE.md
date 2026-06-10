<!--
  Danke für deinen Beitrag zu Hypnose-Stammtisch.de! 🎉
  Bitte fülle die Abschnitte aus, die für deinen PR relevant sind.
  Abschnitte, die nicht zutreffen, kannst du löschen.
-->

## 📝 Beschreibung

<!-- Was ändert dieser PR und warum? Kurz und in eigenen Worten. -->

## 🔗 Verknüpfte Issues

<!-- z.B. "Closes #123", "Fixes #456", "Relates to #789" -->

Closes #

## 🧩 Art der Änderung

<!-- Zutreffendes mit "x" markieren. -->

- [ ] 🐛 Bugfix (nicht-breaking, behebt ein Problem)
- [ ] ✨ Feature (nicht-breaking, neue Funktionalität)
- [ ] 💥 Breaking Change (Fix oder Feature, das bestehendes Verhalten ändert)
- [ ] ♿ Accessibility (Verbesserung der Barrierefreiheit)
- [ ] 🎨 UI / Styling (keine Logikänderung)
- [ ] 🔧 Refactoring (kein funktionales Verhalten geändert)
- [ ] 📝 Dokumentation
- [ ] 🧪 Tests
- [ ] 🔁 CI / Build / Dependencies

## 🎯 Betroffene Bereiche

- [ ] Frontend (`src/`)
- [ ] Backend (`backend/`)
- [ ] Datenbank / Migrationen
- [ ] CI / Deployment (`.github/`)
- [ ] Dokumentation

## 🗄️ Datenbank-Migrationen

<!-- Falls keine Migrationen betroffen sind, diesen Abschnitt löschen. -->

- [ ] Dieser PR enthält Schema-Änderungen
- [ ] Baseline (`backend/migrations/001_initial_schema.sql`) wurde aktualisiert (dev) **oder** eine neue Migration ergänzt (prod)
- [ ] Fresh-Install (`bun run backend:migrate:fresh`) wurde lokal erfolgreich getestet

## ✅ Checkliste

- [ ] Mein Code folgt den Konventionen aus `.github/copilot-instructions.md` und `CONTRIBUTING.md`
- [ ] Commit-Messages folgen [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `chore:` …)
- [ ] `bun run check` läuft fehlerfrei (TypeScript / Svelte)
- [ ] `bun run format:all` wurde ausgeführt (Frontend + PHP)
- [ ] `bun run test` (E2E) ist grün
- [ ] `bun run backend:test` (PHPUnit) ist grün – sofern Backend betroffen
- [ ] Neue UI-Elemente haben `dark:`-Varianten (Dark Mode ist Pflicht)
- [ ] `bun run test:a11y` ist grün und WCAG 2.2 AA bleibt gewahrt (Tastatur, ARIA, Kontrast)
- [ ] Ich habe relevante Dokumentation aktualisiert (README, `docs/`, Kommentare)
- [ ] Keine Secrets, Tokens oder personenbezogenen Daten im Diff

## 📸 Screenshots / Aufnahmen

<!-- Bei UI-Änderungen: vorher/nachher. Sonst löschen. -->

| Vorher | Nachher |
| ------ | ------- |
|        |         |

## 🧪 Wie wurde getestet?

<!-- Beschreibe deine Test-Schritte, Browser/Umgebung und ggf. manuelle Prüfungen. -->

## 💬 Zusätzliche Hinweise

<!-- Offene Fragen, Follow-ups, bewusste Trade-offs für die Reviewer. -->
