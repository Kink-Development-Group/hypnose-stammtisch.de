import { derived, writable } from "svelte/store";
import type { CountryInfo } from "../classes/CountryMetadata";
import { CountryMetadata } from "../classes/CountryMetadata";
import { StammtischLocationCache } from "../classes/StammtischLocationCache";
import { StammtischLocationFactory } from "../classes/StammtischLocationFactory";
import { CountryCode } from "../enums/countryCode";
import { LocationStatus } from "../enums/locationStatus";
import type {
  MapFilter,
  MapViewport,
  StammtischLocation,
  StammtischLocationMeta,
} from "../types/stammtisch";

const isBrowser = typeof window !== "undefined";
const DEFAULT_COUNTRY_CODES = CountryMetadata.getDefaultCountryCodes();

// API-based store for Stammtisch locations
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
  countries: [...DEFAULT_COUNTRY_CODES],
  regions: [],
  tags: [],
  activeOnly: true,
});

// Location metadata for filters
export const locationsMeta = writable<StammtischLocationMeta>({
  regions: [],
  tags: [],
  countries: CountryMetadata.getSupportedCountries(),
});

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
  ([$locations, $filter]) =>
    $locations.filter((location) => {
      if (
        $filter.countries.length > 0 &&
        !$filter.countries.includes(location.country)
      ) {
        return false;
      }

      if (
        $filter.regions.length > 0 &&
        !$filter.regions.includes(location.region)
      ) {
        return false;
      }

      if ($filter.tags.length > 0) {
        const hasMatchingTag = $filter.tags.some((tag) =>
          location.tags.includes(tag),
        );
        if (!hasMatchingTag) {
          return false;
        }
      }

      if ($filter.activeOnly && !location.isActive) {
        return false;
      }

      return location.status === LocationStatus.PUBLISHED;
    }),
);

export const filteredLocations = derived(
  filteredStammtischLocations,
  ($filteredLocations) => $filteredLocations,
);

/**
 * Load Stammtisch locations from the backend and update the cache.
 */
export async function loadStammtischLocations(): Promise<void> {
  try {
    locationsLoading.set(true);
    locationsError.set("");

    const response = await fetch("/api/stammtisch-locations", {
      credentials: "same-origin",
      cache: "no-cache",
    });

    if (!response.ok) {
      throw new Error(`Failed to load locations: ${response.status}`);
    }

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.message || "Failed to load locations");
    }

    const normalized = StammtischLocationFactory.fromApiArray(result.data);
    allStammtischLocations.set(normalized);
    StammtischLocationCache.saveLocations(normalized);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error occurred";
    console.error("Error loading Stammtisch locations:", error);
    locationsError.set(errorMessage);
  } finally {
    locationsLoading.set(false);
    isMapLoading.set(false);
  }
}

/**
 * Load filter metadata and persist a cached copy for faster warm starts.
 */
export async function loadStammtischLocationsMeta(): Promise<void> {
  try {
    const response = await fetch("/api/stammtisch-locations/meta", {
      credentials: "same-origin",
      cache: "no-cache",
    });

    if (!response.ok) {
      return;
    }

    const result = await response.json();
    if (!result.success) {
      return;
    }

    const normalized = normalizeMeta(result.data);
    locationsMeta.set(normalized);
    StammtischLocationCache.saveMeta(normalized);
  } catch (error) {
    console.error("Error loading Stammtisch metadata:", error);
  }
}

/**
 * Fetch a single location by id and normalize the payload.
 */
export async function getStammtischLocationById(
  id: string,
): Promise<StammtischLocation | null> {
  try {
    const response = await fetch(`/api/stammtisch-locations/${id}`, {
      credentials: "same-origin",
      cache: "no-cache",
    });

    if (!response.ok) {
      return null;
    }

    const result = await response.json();
    if (!result.success) {
      return null;
    }

    return StammtischLocationFactory.fromApi(result.data);
  } catch (error) {
    console.error("Error fetching Stammtisch location:", error);
    return null;
  }
}

/**
 * Open the details drawer for a given location.
 */
export function openLocationDetails(location: StammtischLocation): void {
  selectedLocation.set(location);
}

/**
 * Close the details drawer.
 */
export function closeLocationDetails(): void {
  selectedLocation.set(null);
}

/**
 * Persist the current map viewport so that re-renders keep context.
 */
export function updateMapViewport(viewport: Partial<MapViewport>): void {
  mapViewport.update((current) => ({
    ...current,
    ...viewport,
  }));
}

/**
 * Toggle a country filter entry.
 */
export function toggleCountryFilter(country: CountryCode): void {
  mapFilter.update((filter) => {
    const exists = filter.countries.includes(country);
    const countries = exists
      ? filter.countries.filter((entry) => entry !== country)
      : [...filter.countries, country];

    return { ...filter, countries };
  });
}

/**
 * Toggle a region filter entry.
 */
export function toggleRegionFilter(region: string): void {
  mapFilter.update((filter) => {
    const exists = filter.regions.includes(region);
    const regions = exists
      ? filter.regions.filter((entry) => entry !== region)
      : [...filter.regions, region];

    return { ...filter, regions };
  });
}

/**
 * Toggle a tag filter entry.
 */
export function toggleTagFilter(tag: string): void {
  mapFilter.update((filter) => {
    const exists = filter.tags.includes(tag);
    const tags = exists
      ? filter.tags.filter((entry) => entry !== tag)
      : [...filter.tags, tag];

    return { ...filter, tags };
  });
}

/**
 * Reset filters to their defaults.
 */
export function resetMapFilters(): void {
  mapFilter.set({
    countries: [...DEFAULT_COUNTRY_CODES],
    regions: [],
    tags: [],
    activeOnly: true,
  });
}

function normalizeMeta(data: unknown): StammtischLocationMeta {
  const payload = (data ?? {}) as Partial<StammtischLocationMeta> & {
    countries?: Array<Partial<CountryInfo> & { code?: string }>;
  };

  const regions = Array.isArray(payload.regions)
    ? uniqueStrings(payload.regions)
    : [];

  const tags = Array.isArray(payload.tags) ? uniqueStrings(payload.tags) : [];

  const countries = Array.isArray(payload.countries)
    ? payload.countries
        .map((country) => CountryMetadata.resolveInfo(country))
        .filter((entry): entry is CountryInfo => Boolean(entry))
    : [];

  return {
    regions,
    tags,
    countries:
      countries.length > 0
        ? countries
        : CountryMetadata.getSupportedCountries(),
  };
}

function uniqueStrings(values: string[]): string[] {
  return Array.from(
    new Set(
      values
        .map((value) => (typeof value === "string" ? value.trim() : ""))
        .filter((value): value is string => value.length > 0),
    ),
  ).sort((a, b) => a.localeCompare(b));
}

if (isBrowser) {
  const cachedLocations = StammtischLocationCache.loadLocations();
  if (cachedLocations && cachedLocations.length > 0) {
    allStammtischLocations.set(cachedLocations);
    isMapLoading.set(false);
  }

  const cachedMeta = StammtischLocationCache.loadMeta();
  if (cachedMeta) {
    locationsMeta.set(cachedMeta);
  }

  void loadStammtischLocations();
  void loadStammtischLocationsMeta();
}
