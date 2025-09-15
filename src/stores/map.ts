import { derived, writable } from "svelte/store";
import type {
  MapFilter,
  MapViewport,
  StammtischLocation,
} from "../types/stammtisch";

// Sample data für DACH-Region Stammtische
const sampleLocations: StammtischLocation[] = [
  // Deutschland
  {
    id: "berlin-01",
    name: "Hypnose Stammtisch Berlin",
    city: "Berlin",
    region: "Berlin",
    country: "DE",
    coordinates: { lat: 52.52, lng: 13.405 },
    description:
      "Monatlicher Stammtisch für alle Interessierten der Hypnose in Berlin. Sowohl Anfänger als auch erfahrene Hypnotiseure sind willkommen.",
    contact: {
      email: "berlin@hypnose-stammtisch.de",
      telegram: "@HypnoseBerlin",
    },
    meetingInfo: {
      frequency: "Jeden 1. Samstag im Monat",
      location: "Kulturzentrum Mitte, Seminarraum 3",
      nextMeeting: "2025-10-04T18:00:00",
    },
    tags: ["anfängerfreundlich", "praxis", "theorie"],
    isActive: true,
    lastUpdated: "2025-09-15T00:00:00Z",
  },
  {
    id: "muenchen-01",
    name: "München Hypnose Circle",
    city: "München",
    region: "Bayern",
    country: "DE",
    coordinates: { lat: 48.1351, lng: 11.582 },
    description:
      "Praxisorientierte Treffen mit Fokus auf moderne Hypnosetechniken und Sicherheitspraktiken.",
    contact: {
      email: "muenchen@hypnose-stammtisch.de",
      website: "https://hypnose-muenchen.example.com",
    },
    meetingInfo: {
      frequency: "Jeden 3. Freitag im Monat",
      location: "Volkshochschule München, Raum 204",
    },
    tags: ["erfahren", "praxis", "sicherheit"],
    isActive: true,
    lastUpdated: "2025-09-10T00:00:00Z",
  },
  {
    id: "hamburg-01",
    name: "Hypnose Hamburg Nord",
    city: "Hamburg",
    region: "Hamburg",
    country: "DE",
    coordinates: { lat: 53.5511, lng: 9.9937 },
    description:
      "Entspannte Atmosphäre für Austausch und praktische Übungen in der Hansestadt.",
    contact: {
      email: "hamburg@hypnose-stammtisch.de",
      discord: "HypnoseHamburg#1234",
    },
    meetingInfo: {
      frequency: "Jeden 2. Sonntag im Monat",
      location: "Bürgerhaus Barmbek, Gruppraum A",
    },
    tags: ["anfängerfreundlich", "entspannt", "community"],
    isActive: true,
    lastUpdated: "2025-09-12T00:00:00Z",
  },
  {
    id: "koeln-01",
    name: "Kölner Hypnose Initiative",
    city: "Köln",
    region: "Nordrhein-Westfalen",
    country: "DE",
    coordinates: { lat: 50.9375, lng: 6.9603 },
    description:
      "Interdisziplinärer Austausch zwischen Therapeuten, Coaches und Hobby-Hypnotiseuren.",
    contact: {
      email: "koeln@hypnose-stammtisch.de",
    },
    meetingInfo: {
      frequency: "Jeden letzten Donnerstag im Monat",
      location: "VHS Köln, Raum 312",
    },
    tags: ["therapeutisch", "coaching", "fachlich"],
    isActive: true,
    lastUpdated: "2025-09-08T00:00:00Z",
  },

  // Österreich
  {
    id: "wien-01",
    name: "Wiener Hypnose Runde",
    city: "Wien",
    region: "Wien",
    country: "AT",
    coordinates: { lat: 48.2082, lng: 16.3738 },
    description:
      "Traditioneller Stammtisch mit Fokus auf klassische und moderne Hypnosetechniken.",
    contact: {
      email: "wien@hypnose-stammtisch.de",
      website: "https://hypnose-wien.example.at",
    },
    meetingInfo: {
      frequency: "Jeden 2. Mittwoch im Monat",
      location: "Wiener Volkshochschule, Seminarraum 5",
    },
    tags: ["traditionell", "klassisch", "modern"],
    isActive: true,
    lastUpdated: "2025-09-14T00:00:00Z",
  },
  {
    id: "salzburg-01",
    name: "Salzburg Hypnose Kreis",
    city: "Salzburg",
    region: "Salzburg",
    country: "AT",
    coordinates: { lat: 47.8095, lng: 13.055 },
    description:
      "Gemütliche Runde für alle, die sich für Hypnose und Bewusstseinsarbeit interessieren.",
    contact: {
      email: "salzburg@hypnose-stammtisch.de",
      telegram: "@HypnoseSalzburg",
    },
    meetingInfo: {
      frequency: "Jeden 1. Samstag im Monat",
      location: "Kulturhaus Salzburg, Kleiner Saal",
    },
    tags: ["bewusstsein", "spirituell", "community"],
    isActive: true,
    lastUpdated: "2025-09-11T00:00:00Z",
  },

  // Schweiz
  {
    id: "zuerich-01",
    name: "Zürich Hypnose Meetup",
    city: "Zürich",
    region: "Zürich",
    country: "CH",
    coordinates: { lat: 47.3769, lng: 8.5417 },
    description:
      "Internationaler Stammtisch (DE/EN) mit Fokus auf Hypnose in therapeutischen Kontexten.",
    contact: {
      email: "zuerich@hypnose-stammtisch.de",
      website: "https://hypnose-zurich.example.ch",
    },
    meetingInfo: {
      frequency: "Jeden 3. Dienstag im Monat",
      location: "Volkshochschule Zürich, Raum B12",
    },
    tags: ["international", "therapeutisch", "mehrsprachig"],
    isActive: true,
    lastUpdated: "2025-09-13T00:00:00Z",
  },
  {
    id: "bern-01",
    name: "Berner Hypnose Gesellschaft",
    city: "Bern",
    region: "Bern",
    country: "CH",
    coordinates: { lat: 46.9481, lng: 7.4474 },
    description:
      "Wissenschaftlich orientierte Gruppe mit Fokus auf Forschung und praktische Anwendung.",
    contact: {
      email: "bern@hypnose-stammtisch.de",
    },
    meetingInfo: {
      frequency: "Jeden 4. Donnerstag im Monat",
      location: "Universität Bern, Gebäude 3, Raum 201",
    },
    tags: ["wissenschaftlich", "forschung", "akademisch"],
    isActive: true,
    lastUpdated: "2025-09-09T00:00:00Z",
  },
];

// Stores
export const allStammtischLocations =
  writable<StammtischLocation[]>(sampleLocations);

export const mapViewport = writable<MapViewport>({
  center: { lat: 50.1109, lng: 8.6821 }, // DACH-Region center (Frankfurt area)
  zoom: 6,
});

export const mapFilter = writable<MapFilter>({
  countries: ["DE", "AT", "CH"],
  regions: [],
  tags: [],
  activeOnly: true,
});

export const selectedLocation = writable<StammtischLocation | null>(null);
export const isMapLoading = writable<boolean>(false);

// Derived stores
export const filteredLocations = derived(
  [allStammtischLocations, mapFilter],
  ([$locations, $filter]) => {
    return $locations.filter((location) => {
      // Filter by active status
      if ($filter.activeOnly && !location.isActive) {
        return false;
      }

      // Filter by countries
      if (
        $filter.countries.length > 0 &&
        !$filter.countries.includes(location.country)
      ) {
        return false;
      }

      // Filter by regions
      if (
        $filter.regions.length > 0 &&
        !$filter.regions.includes(location.region)
      ) {
        return false;
      }

      // Filter by tags
      if ($filter.tags.length > 0) {
        const hasMatchingTag = $filter.tags.some((tag) =>
          location.tags.includes(tag),
        );
        if (!hasMatchingTag) {
          return false;
        }
      }

      return true;
    });
  },
);

// Available filter options derived from data
export const availableRegions = derived(
  allStammtischLocations,
  ($locations) => {
    const regions = new Set($locations.map((l) => l.region));
    return Array.from(regions).sort();
  },
);

export const availableTags = derived(allStammtischLocations, ($locations) => {
  const tags = new Set($locations.flatMap((l) => l.tags));
  return Array.from(tags).sort();
});

// Helper functions
export function openLocationDetails(location: StammtischLocation) {
  selectedLocation.set(location);
}

export function closeLocationDetails() {
  selectedLocation.set(null);
}

export function updateMapViewport(viewport: Partial<MapViewport>) {
  mapViewport.update((current) => ({ ...current, ...viewport }));
}

export function toggleCountryFilter(country: string) {
  mapFilter.update((current) => {
    const countries = current.countries.includes(country)
      ? current.countries.filter((c) => c !== country)
      : [...current.countries, country];
    return { ...current, countries };
  });
}

export function toggleRegionFilter(region: string) {
  mapFilter.update((current) => {
    const regions = current.regions.includes(region)
      ? current.regions.filter((r) => r !== region)
      : [...current.regions, region];
    return { ...current, regions };
  });
}

export function toggleTagFilter(tag: string) {
  mapFilter.update((current) => {
    const tags = current.tags.includes(tag)
      ? current.tags.filter((t) => t !== tag)
      : [...current.tags, tag];
    return { ...current, tags };
  });
}

export function resetMapFilters() {
  mapFilter.set({
    countries: ["DE", "AT", "CH"],
    regions: [],
    tags: [],
    activeOnly: true,
  });
}
