---
description: Verwaltung der Stammtisch-Standorte im Admin-Bereich
outline: deep
---

# Stammtisch-Standorte Verwaltung

## Übersicht

Die Stammtisch-Standorte-Verwaltung ermöglicht es Administratoren, die auf der interaktiven Karte angezeigten Stammtisch-Standorte zu erstellen, bearbeiten und zu verwalten.

## Features

### Standort-Verwaltung

- Erstellen neuer Stammtisch-Standorte
- Bearbeiten bestehender Standorte
- Löschen von Standorten
- Bulk-Aktionen für Status-Updates
- Filterung nach Land, Region und Tags

### Vollseitiges Modal-Formular

Das "New Location" Formular wurde auf ein vollseitiges, scrollbares Layout umgestellt:

- **Sticky Header**: Titel und Schließen-Buttons bleiben beim Scrollen sichtbar
- **Scrollbarer Content**: Formularinhalte nutzen die volle Seitenhöhe mit Seitenscrollbar
- **Sticky Footer**: Speichern- und Abbrechen-Buttons sind immer sichtbar
- **Responsive Design**: Optimiert für Desktop und Tablet
- **Max-Höhe**: 92vh für optimale Nutzung des Viewport

### Internationalisierung (i18n)

Alle Labels und Texte nutzen das zentrale i18n-System:

- Mehrsprachige Unterstützung (DE/EN)
- Konsistente Terminologie
- Einfache Wartbarkeit

### Typ-Sicherheit

Verwendung von TypeScript-Enums für erhöhte Typ-Sicherheit:

- `LocationStatus`: `DRAFT`, `PUBLISHED`, `ARCHIVED`
- `CountryCode`: `GERMANY`, `AUSTRIA`, `SWITZERLAND`

## Technische Implementierung

### Komponenten

```text
src/components/admin/
└── AdminStammtischLocations.svelte  # Hauptkomponente
```

### Enums

```typescript
// src/enums/locationStatus.ts
export enum LocationStatus {
  DRAFT = "draft",
  PUBLISHED = "published",
  ARCHIVED = "archived",
}

// src/enums/countryCode.ts
export enum CountryCode {
  GERMANY = "DE",
  AUSTRIA = "AT",
  SWITZERLAND = "CH",
}
```

### i18n-Schlüssel

Alle Texte sind in `src/utils/i18n.ts` definiert:

```typescript
"adminLocations.modal.titleCreate": "Neuen Standort erstellen",
"adminLocations.modal.titleEdit": "Standort bearbeiten",
"adminLocations.form.nameLabel": "Name",
"adminLocations.form.cityLabel": "Stadt",
// ... weitere Schlüssel
```

## Formularfelder

### Grundinformationen

- **Name** (Pflicht): Name des Stammtisches
- **Stadt** (Pflicht): Standort-Stadt
- **Region** (Pflicht): Bundesland oder Kanton
- **Land** (Pflicht): DE, AT oder CH
- **Breitengrad** (Pflicht): Koordinaten für Kartenanzeige
- **Längengrad** (Pflicht): Koordinaten für Kartenanzeige
- **Beschreibung**: Freitext-Beschreibung

### Kontaktinformationen

- **E-Mail**: Kontakt-E-Mail-Adresse
- **Telefon**: Telefonnummer
- **FetLife**: FetLife-Handle
- **Website**: URL zur Website

### Treffen-Informationen

- **Häufigkeit**: z.B. "Jeden 1. Samstag im Monat"
- **Ort**: Treffpunkt-Name
- **Adresse**: Vollständige Adresse
- **Nächstes Treffen**: Datum/Uhrzeit des nächsten Treffens

### Tags

- Vordefinierte Tags zur schnellen Auswahl
- Benutzerdefinierte Tags hinzufügen
- Visuelle Verwaltung ausgewählter Tags

### Status & Sichtbarkeit

- **Status**: Entwurf, Veröffentlicht, Archiviert
- **Aktiv**: Checkbox für aktive/inaktive Standorte

## Best Practices

### Code-Qualität

- **OOP First**: Verwendung von Klassen und Interfaces
- **DRY**: Wiederverwendbare Funktionen und Komponenten
- **Typ-Sicherheit**: TypeScript-Typen und Enums
- **TSDoc**: Vollständige Dokumentation aller Funktionen

### Dokumentation

Alle Funktionen sind mit TSDoc-Kommentaren versehen:

```typescript
/**
 * Fetch all stammtisch locations from the API.
 * @returns Promise that resolves when locations are loaded
 */
async function loadLocations(): Promise<void> {
  // Implementation
}
```

### Performance

- Lazy Loading von Daten
- Optimistische UI-Updates
- Bulk-Operationen für mehrere Standorte

## API-Integration

### Endpoints

- `GET /api/admin/stammtisch-locations` - Liste alle Standorte
- `GET /api/admin/stammtisch-locations/stats` - Statistiken
- `POST /api/admin/stammtisch-locations` - Neuer Standort
- `PUT /api/admin/stammtisch-locations/{id}` - Standort aktualisieren
- `DELETE /api/admin/stammtisch-locations/{id}` - Standort löschen
- `POST /api/admin/stammtisch-locations/bulk-status` - Bulk-Status-Update

### Datenstruktur

```typescript
interface LocationFormData {
  name: string;
  city: string;
  region: string;
  country: CountryCode;
  latitude: number;
  longitude: number;
  description: string;
  contact_email: string;
  contact_phone: string;
  contact_telegram: string;
  contact_website: string;
  meeting_frequency: string;
  meeting_location: string;
  meeting_address: string;
  next_meeting: string;
  tags: string[];
  is_active: boolean;
  status: LocationStatus;
}
```

## Changelog

### Version 1.0.1 (November 2025)

**Verbesserungen:**

- ✅ Vollseitiges Modal-Layout statt fester 256px Höhe
- ✅ Sticky Header und Footer im Modal
- ✅ Verwendung von TypeScript-Enums (`LocationStatus`, `CountryCode`)
- ✅ i18n-Integration für alle Texte
- ✅ TSDoc-Dokumentation für alle Funktionen
- ✅ Verbesserte Typ-Sicherheit
- ✅ OOP und DRY-Prinzipien

**UI/UX:**

- Desktop-Nutzer profitieren von der vollen Seitenhöhe
- Bessere Übersicht über alle Formularfelder
- Konsistentes Layout mit anderen Admin-Modals (AdminEvents)
- Verbesserte Benutzerfreundlichkeit

## Siehe auch

- [Admin Management](/admin/management)
- [Map Feature](/features/map-feature)
- [Architecture](/architecture/user-structure)
