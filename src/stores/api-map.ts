import { derived, writable } from "svelte/store";
import type {
  MapFilter,
  MapViewport,
  StammtischLocation,
} from "../types/stammtisch";

// API-basierte Map-Stores
const API_BASE = "/api";

// Loading states
export const isLoadingLocations = writable(false);
export const locationsError = writable<string | null>(null);

// Compatibility alias for existing components
export const isMapLoading = isLoadingLocations;

// Raw data from API
const rawLocations = writable<StammtischLocation[]>([]);
const locationsMeta = writable<{
  countries: Array<{ code: string; name: string; count: number }>;
  regions: Array<{ name: string; country: string; count: number }>;
  tags: Array<{ name: string; count: number }>;
  totalCount: number;
}>({
  countries: [],
  regions: [],
  tags: [],
  totalCount: 0,
});

// Map state stores (same as original)
export const mapViewport = writable<MapViewport>({
  center: { lat: 51.1657, lng: 10.4515 }, // Deutschland Zentrum
  zoom: 6,
});

export const mapFilter = writable<MapFilter>({
  countries: ["DE", "AT", "CH"],
  regions: [],
  tags: [],
  activeOnly: true,
});

export const selectedLocation = writable<StammtischLocation | null>(null);

// Derived stores
export const allStammtischLocations = derived(rawLocations, ($raw) => $raw);

// Compatibility aliases for existing components
export const filteredLocations = derived(
  [allStammtischLocations, mapFilter],
  ([$locations, $filter]) => {
    return $locations.filter((location) => {
      // Country filter
      if (
        $filter.countries.length > 0 &&
        !$filter.countries.includes(location.country)
      ) {
        return false;
      }

      // Region filter
      if (
        $filter.regions.length > 0 &&
        !$filter.regions.includes(location.region)
      ) {
        return false;
      }

      // Tag filter
      if (
        $filter.tags.length > 0 &&
        !$filter.tags.some((tag) => location.tags.includes(tag))
      ) {
        return false;
      }

      // Active only filter
      if ($filter.activeOnly && !location.isActive) {
        return false;
      }

      return true;
    });
  },
);

export const filteredStammtischLocations = filteredLocations;

export const availableCountries = derived(locationsMeta, ($meta) => {
  return $meta.countries;
});

export const availableRegions = derived(
  [locationsMeta, mapFilter],
  ([$meta, $filter]) => {
    if ($filter.countries.length === 0) {
      return $meta.regions;
    }
    return $meta.regions.filter((region) =>
      $filter.countries.includes(region.country),
    );
  },
);

export const availableTags = derived(locationsMeta, ($meta) => {
  return $meta.tags.map((tag) => tag.name);
});

// API Functions
export async function loadStammtischLocations() {
  isLoadingLocations.set(true);
  locationsError.set(null);

  try {
    const response = await fetch(`${API_BASE}/stammtisch-locations`);
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.error || "Failed to load locations");
    }

    rawLocations.set(result.data || []);
  } catch (error) {
    console.error("Failed to load stammtisch locations:", error);
    locationsError.set(
      error instanceof Error ? error.message : "Unknown error",
    );
    // Fallback to empty array
    rawLocations.set([]);
  } finally {
    isLoadingLocations.set(false);
  }
}

export async function loadStammtischLocationsMeta() {
  try {
    const response = await fetch(`${API_BASE}/stammtisch-locations/meta`);
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.error || "Failed to load locations metadata");
    }

    locationsMeta.set(
      result.data || {
        countries: [],
        regions: [],
        tags: [],
        totalCount: 0,
      },
    );
  } catch (error) {
    console.error("Failed to load stammtisch locations metadata:", error);
    // Keep existing metadata on error
  }
}

export async function getStammtischLocationById(
  id: string,
): Promise<StammtischLocation | null> {
  try {
    const response = await fetch(`${API_BASE}/stammtisch-locations/${id}`);
    if (!response.ok) {
      if (response.status === 404) {
        return null;
      }
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.error || "Failed to load location");
    }

    return result.data || null;
  } catch (error) {
    console.error(`Failed to load stammtisch location ${id}:`, error);
    return null;
  }
}

// Helper functions (same as original)
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

// Auto-load data when store is first imported
if (typeof window !== "undefined") {
  // Only load in browser
  loadStammtischLocations();
  loadStammtischLocationsMeta();
}
