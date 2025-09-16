import { derived, writable } from "svelte/store";
import type {
  MapFilter,
  MapViewport,
  StammtischLocation,
} from "../types/stammtisch";

// API-based store for stammtisch locations
export const allStammtischLocations = writable<StammtischLocation[]>([]);
export const locationsLoading = writable<boolean>(false);
export const locationsError = writable<string>("");

// Map viewport store
export const mapViewport = writable<MapViewport>({
  center: { lat: 49.5, lng: 10.5 }, // Optimiert für DACH-Region
  zoom: 6,
});

// Filter store
export const mapFilter = writable<MapFilter>({
  countries: ["DE", "AT", "CH"], // Default: all DACH countries
  regions: [],
  tags: [],
  activeOnly: true,
});

// Location metadata for filters
export const locationsMeta = writable<{
  regions: string[];
  tags: string[];
  countries: Array<{ code: string; name: string; flag: string }>;
}>({
  regions: [],
  tags: [],
  countries: [
    { code: "DE", name: "Deutschland", flag: "🇩🇪" },
    { code: "AT", name: "Österreich", flag: "🇦🇹" },
    { code: "CH", name: "Schweiz", flag: "🇨🇭" },
  ],
});

// Legacy exports for backward compatibility
export const availableRegions = derived(
  locationsMeta,
  ($meta) => $meta.regions,
);
export const availableTags = derived(locationsMeta, ($meta) => $meta.tags);

// Selected location for details view
export const selectedLocation = writable<StammtischLocation | null>(null);

// Loading states
export const isMapLoading = writable<boolean>(true);
export const isLoadingLocations = derived(
  locationsLoading,
  ($loading) => $loading,
);

// Filtered locations based on current filter settings
export const filteredStammtischLocations = derived(
  [allStammtischLocations, mapFilter],
  ([$locations, $filter]) => {
    return $locations.filter((location) => {
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

      // Filter by active status
      if ($filter.activeOnly && !location.isActive) {
        return false;
      }

      // Only show published locations for public users
      if (location.status !== "published") {
        return false;
      }

      return true;
    });
  },
);

// Derived store for location count by filters
export const filteredLocations = derived(
  filteredStammtischLocations,
  ($filteredLocations) => $filteredLocations,
);

/**
 * Load stammtisch locations from API
 */
export async function loadStammtischLocations(): Promise<void> {
  try {
    locationsLoading.set(true);
    locationsError.set("");

    const response = await fetch("/api/stammtisch-locations", {
      credentials: "same-origin",
    });

    if (!response.ok) {
      throw new Error(`Failed to load locations: ${response.status}`);
    }

    const result = await response.json();
    if (result.success) {
      allStammtischLocations.set(result.data || []);
    } else {
      throw new Error(result.message || "Failed to load locations");
    }
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error occurred";
    console.error("Error loading stammtisch locations:", error);
    locationsError.set(errorMessage);
    // Set empty array on error
    allStammtischLocations.set([]);
  } finally {
    locationsLoading.set(false);
    isMapLoading.set(false);
  }
}

/**
 * Load location metadata for filters
 */
export async function loadStammtischLocationsMeta(): Promise<void> {
  try {
    const response = await fetch("/api/stammtisch-locations/meta", {
      credentials: "same-origin",
    });

    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        locationsMeta.set(result.data);
      }
    }
  } catch (error) {
    console.error("Error loading location metadata:", error);
  }
}

/**
 * Get stammtisch location by ID
 */
export async function getStammtischLocationById(
  id: string,
): Promise<StammtischLocation | null> {
  try {
    const response = await fetch(`/api/stammtisch-locations/${id}`, {
      credentials: "same-origin",
    });

    if (!response.ok) {
      return null;
    }

    const result = await response.json();
    return result.success ? result.data : null;
  } catch (error) {
    console.error("Error fetching location by ID:", error);
    return null;
  }
}

// Helper functions
export function openLocationDetails(location: StammtischLocation) {
  selectedLocation.set(location);
}

export function closeLocationDetails() {
  selectedLocation.set(null);
}

export function updateMapViewport(viewport: Partial<MapViewport>) {
  mapViewport.update((current) => ({
    ...current,
    ...viewport,
  }));
}

export function toggleCountryFilter(country: string) {
  mapFilter.update((filter) => {
    const countries = [...filter.countries];
    const index = countries.indexOf(country);

    if (index > -1) {
      countries.splice(index, 1);
    } else {
      countries.push(country);
    }

    return { ...filter, countries };
  });
}

export function toggleRegionFilter(region: string) {
  mapFilter.update((filter) => {
    const regions = [...filter.regions];
    const index = regions.indexOf(region);

    if (index > -1) {
      regions.splice(index, 1);
    } else {
      regions.push(region);
    }

    return { ...filter, regions };
  });
}

export function toggleTagFilter(tag: string) {
  mapFilter.update((filter) => {
    const tags = [...filter.tags];
    const index = tags.indexOf(tag);

    if (index > -1) {
      tags.splice(index, 1);
    } else {
      tags.push(tag);
    }

    return { ...filter, tags };
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
