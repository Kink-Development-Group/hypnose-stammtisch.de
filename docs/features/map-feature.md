---
description: Interaktive Stammtisch-Karte für Deutschland, Österreich und die Schweiz.
outline: deep
---

# Stammtisch-Karte

## Übersicht

Die Stammtisch-Karte ermöglicht Besucherinnen und Besuchern, Hypnose-Stammtische in der DACH-Region visuell zu entdecken und zu kontaktieren.

## Features

### Interaktive Karte

- OpenStreetMap-Integration mittels Leaflet.
- Fokus auf Deutschland, Österreich und die Schweiz.
- Responsives Rendering für Desktop, Tablet und Smartphone.

### Stammtisch-Standorte

- Pins markieren jeden Stammtisch.
- Popups zeigen Kerninformationen.
- Detailansicht liefert erweiterte Daten.

### Filter-System

- Länder-Filter (DE, AT, CH) individuell kombinierbar.
- Regionale Filter nach Bundesland oder Kanton.
- Tag-Filter (z. B. anfängerfreundlich).
- Optionaler Filter für aktive Stammtische.

### Mobile Optimierung

- Touch-optimierte Interaktion.
- Detailansicht als Modal auf kleinen Displays.
- Layout passt sich dynamisch an.

## Technische Implementierung

### Komponentenstruktur

```text
src/components/map/
├── MapView.svelte          # Leaflet-Karte
├── MapFilter.svelte        # Filterinterface
├── LocationDetails.svelte  # Detailansicht
└── map.css                 # Leaflet-spezifische Styles
```

### Stores und Daten

```text
src/stores/map.ts           # Zustand und Filterlogik
src/types/stammtisch.ts     # TypeScript-Typen
```

### Seiten

```text
src/pages/Map.svelte        # Route /map
```

### Abhängigkeiten

- `leaflet`
- `@types/leaflet`

## Datenstruktur

```typescript
interface StammtischLocation {
  id: string;
  name: string;
  city: string;
  region: string;
  country: "DE" | "AT" | "CH";
  coordinates: { lat: number; lng: number };
  description: string;
  contact: {
    email?: string;
    website?: string;
    telegram?: string;
    discord?: string;
  };
  meetingInfo: {
    frequency: string;
    location: string;
    nextMeeting?: string;
  };
  tags: string[];
  isActive: boolean;
  lastUpdated: string;
}
```

## Navigation

Die Karte ist über die Hauptnavigation unter "Karte" erreichbar und in das bestehende Routing eingebunden.

## Barrierefreiheit

- ARIA-Labels für interaktive Elemente.
- Vollständige Tastaturnavigation.
- Screen-Reader-kompatible Struktur.
- Konsistentes Fokus-Management.

## Datenschutz und Performance

- Daten werden statisch ausgeliefert (kein SSR-Zugriff auf Kalenderdaten).
- Leaflet wird lazy geladen, um SSR-Konflikte zu vermeiden.
- Marker sind optimiert, um Performance zu sichern.

## Erweiterungsmöglichkeiten

### Geplante Features

- Suchfunktion nach Ort oder Postleitzahl.
- Wegbeschreibung über externe Routenplaner.
- Favoritenverwaltung für angemeldete Nutzer.
- Export der Standortdaten.

### Zukunft: Admin-Interface

- Stammtisch-Verwaltung durch Admins.
- Bulk-Import per CSV/JSON.
- Moderationsworkflow für neue Einträge.

## Wartung

### Daten aktualisieren

- Stammtisch-Daten befinden sich in `src/stores/map.ts` im Array `sampleLocations`.

### Neue Standorte hinzufügen

1. Eintrag zu `sampleLocations` ergänzen.
1. Koordinaten über OpenStreetMap ermitteln.
1. Pflichtfelder ausfüllen und Tags setzen.

### Styling anpassen

- Marker-Styling: `src/components/map/MapView.svelte`
- Filterlayout: `src/components/map/MapFilter.svelte`
- Detailansicht: `src/components/map/LocationDetails.svelte`
- Leaflet Overrides: `src/components/map/map.css`
