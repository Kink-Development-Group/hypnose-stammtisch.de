# Stammtisch-Karte Feature

## Übersicht

Die neue Stammtisch-Karte ermöglicht es Besuchern, Hypnose-Stammtische in der DACH-Region (Deutschland, Österreich, Schweiz) visuell zu entdecken und zu kontaktieren.

## Features

### 🗺️ Interaktive Karte

- **OpenStreetMap Integration**: Basiert auf Leaflet für eine responsive, zugängliche Kartenerfahrung
- **DACH-Region Fokus**: Optimiert für Deutschland, Österreich und die Schweiz
- **Responsive Design**: Funktioniert auf Desktop, Tablet und Smartphone

### 📍 Stammtisch-Standorte

- **Visuelle Markierungen**: Jeder Stammtisch wird mit einer Pinnadel markiert
- **Popup-Details**: Klick auf Markierung zeigt Grundinformationen
- **Detailansicht**: Vollständige Informationen in separater Detailkomponente

### 🔍 Filter-System

- **Länder-Filter**: Deutschland, Österreich, Schweiz einzeln oder kombiniert
- **Regions-Filter**: Nach Bundesländern/Kantonen filtern
- **Tag-Filter**: Nach Charakteristika filtern (anfängerfreundlich, erfahren, etc.)
- **Aktiv-Filter**: Nur aktive Stammtische anzeigen

### 📱 Mobile Optimierung

- **Touch-Friendly**: Optimiert für Touch-Bedienung
- **Modal-Details**: Details werden auf Mobile in einem Modal angezeigt
- **Responsive Layouts**: Angepasste Layouts für verschiedene Bildschirmgrößen

## Technische Implementierung

### Komponenten-Struktur

```
src/components/map/
├── MapView.svelte          # Hauptkarte mit Leaflet
├── MapFilter.svelte        # Filter-Interface
├── LocationDetails.svelte  # Detailansicht für Standorte
└── map.css                # Leaflet-spezifische Styles
```

### Stores & Datenmanagement

```
src/stores/map.ts           # Zustandsmanagement für Karte
src/types/stammtisch.ts     # TypeScript-Typen
```

### Seiten

```
src/pages/Map.svelte        # Hauptseite unter /map
```

### Abhängigkeiten

- **Leaflet**: Für die Kartenintegration
- **@types/leaflet**: TypeScript-Definitionen

## Datenstruktur

### StammtischLocation Interface

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

## Navigation Integration

Die Karte ist über die Hauptnavigation unter **"Karte"** erreichbar und in das bestehende Routing-System integriert.

## Zugänglichkeit (A11y)

- **ARIA-Labels**: Alle interaktiven Elemente haben beschreibende Labels
- **Keyboard-Navigation**: Vollständig per Tastatur bedienbar
- **Screen Reader Support**: Optimiert für Assistive Technologien
- **Focus Management**: Klare Fokus-Indikatoren

## Datenschutz & Performance

- **Statische Daten**: Stammtisch-Daten sind statisch und nicht automatisch aus dem Kalender übernommen
- **Lazy Loading**: Leaflet wird dynamisch geladen um SSR-Probleme zu vermeiden
- **Optimierte Markers**: Effiziente Marker-Verwaltung für bessere Performance

## Erweiterungsmöglichkeiten

### Geplante Features

- **Suchfunktion**: Nach PLZ oder Ortsnamen suchen
- **Routing**: Wegbeschreibungen zu Stammtischen
- **Favoriten**: Stammtische als Favoriten markieren
- **Export**: Standortdaten exportieren

### Admin-Interface (Future)

- **Stammtisch-Verwaltung**: Admin-Interface für Standort-Management
- **Bulk-Import**: CSV/JSON Import für mehrere Standorte
- **Moderations-Tools**: Überprüfung und Freigabe neuer Standorte

## Wartung

### Daten aktualisieren

Stammtisch-Daten befinden sich in `src/stores/map.ts` im `sampleLocations` Array.

### Neue Standorte hinzufügen

1. Neuen Eintrag zum `sampleLocations` Array hinzufügen
2. Koordinaten über OpenStreetMap ermitteln
3. Alle erforderlichen Felder ausfüllen
4. Tags entsprechend der Stammtisch-Charakteristika setzen

### Styling anpassen

- Marker-Styles: `src/components/map/MapView.svelte`
- Filter-Interface: `src/components/map/MapFilter.svelte`
- Detailansicht: `src/components/map/LocationDetails.svelte`
- Leaflet-Overrides: `src/components/map/map.css`
