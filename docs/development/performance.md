---
description: Performance-Optimierungen für das Hypnose-Stammtisch-Frontend
outline: deep
---

# Performance-Strategie

Diese Seite beschreibt die aktuellen Performance-Maßnahmen im Frontend sowie Empfehlungen für weitere Optimierungen. Ziel ist ein schnelles, responsives Nutzererlebnis – insbesondere auf Mobilgeräten.

## Aktuelle Optimierungen

### Stammtisch-Karte

- **Lazy Loading**: Leaflet wird erst auf der Map-Seite dynamisch geladen.
- **GeoJSON-Caching**: Ländergrenzen werden via `fetch` mit `force-cache` geladen und per In-Memory-Cache wiederverwendet.
- **Marker-Reuse**: Das Marker-Icon wird einmalig erstellt und für alle Marker genutzt, um DOM-Overhead zu vermeiden.
- **Stale-While-Revalidate**: Stammtisch-Daten und Metadaten werden im Browser (`localStorage`) zwischengespeichert. Beim Seitenaufruf wird zuerst der Cache geladen, anschließend werden frische Daten nachgeladen.
- **Tippfehler-Schutz**: Payloads aus dem Backend werden durch `StammtischLocationFactory` via Zod validiert und normalisiert.

### Build & Bundle

- **Manual Chunking** (`vite.config.ts`): Vendor-, Kalender- und UI-Abhängigkeiten werden in getrennte Bundles aufgeteilt.
- **CSS-Minimierung**: Tailwind erzeugt nur genutzte Klassen.
- **Preactives**: Svelte nutzt Compiler-Optimierungen (`dev` vs `build`).

## Empfehlungen

1. **Bilder optimieren**: Falls künftig Bilder eingebunden werden, sollten sie voroptimiert (WebP/AVIF) und lazy geladen werden.
2. **Component-Level Code-Splitting**: Für große Seiten-Komponenten `import()` einsetzen (z. B. Admin-Module).
3. **HTTP Caching Header**: Im Backend Cache-Control Header für statische Assets ergänzen.
4. **Monitoring**: Lighthouse CI oder SpeedCurve integrieren, um Performance kontinuierlich zu messen.
5. **Service Worker prüfen**: Für Offline-Caching und Prefetching in Betracht ziehen – nach Security-Review.

## Benchmarks & Tools

| Tool            | Anwendungsfall                    |
| --------------- | --------------------------------- |
| `npm run build` | Lighthouse-Audits auf Dist-Bundle |
| WebPageTest     | Real-Life Netzwerkszenarien       |
| Sentry          | Web-Vitals Monitoring             |

## Verantwortlichkeiten

- **Frontend-Team**: Implementierung und Review neuer Optimierungen.
- **Backend-Team**: API-Response-Zeiten überwachen, Caching-Header setzen.
- **QA**: Performance-Regression-Tests im Release-Prozess.

## Nächste Schritte

- [ ] Lighthouse Budget definieren (z. B. LCP < 2.5s).
- [ ] Automatisches Monitoring für FCP/LCP einführen.
- [ ] Regelmäßige Review-Meetings (monatlich) für Performance-Reports.
