/**
 * Simple localStorage backed cache for Stammtisch location data.
 */
import type {
  StammtischLocation,
  StammtischLocationMeta,
} from "../types/stammtisch";

interface CacheRecord<T> {
  data: T;
  expiresAt: number;
}

const CACHE_KEYS = {
  LOCATIONS: "stammtisch.locations",
  META: "stammtisch.locations.meta",
} as const;

const TEN_MINUTES = 10 * 60 * 1000;
const isBrowser = typeof window !== "undefined";

/**
 * Centralized access to cached Stammtisch data to improve perceived performance.
 */
export class StammtischLocationCache {
  /**
   * Load cached locations when available and not expired.
   */
  static loadLocations(): StammtischLocation[] | null {
    return this.load<StammtischLocation[]>(CACHE_KEYS.LOCATIONS);
  }

  /**
   * Persist locations to localStorage with a short TTL.
   */
  static saveLocations(locations: StammtischLocation[]): void {
    this.save(CACHE_KEYS.LOCATIONS, locations);
  }

  /**
   * Load cached filter metadata when available.
   */
  static loadMeta(): StammtischLocationMeta | null {
    return this.load<StammtischLocationMeta>(CACHE_KEYS.META);
  }

  /**
   * Persist metadata for quicker warm loads.
   */
  static saveMeta(meta: StammtischLocationMeta): void {
    this.save(CACHE_KEYS.META, meta);
  }

  /**
   * Clear all cached entries.
   */
  static clear(): void {
    if (!isBrowser) return;
    window.localStorage.removeItem(CACHE_KEYS.LOCATIONS);
    window.localStorage.removeItem(CACHE_KEYS.META);
  }

  private static load<T>(key: string): T | null {
    if (!isBrowser) return null;

    try {
      const raw = window.localStorage.getItem(key);
      if (!raw) return null;

      const payload = JSON.parse(raw) as CacheRecord<T>;
      if (!payload || typeof payload.expiresAt !== "number") {
        window.localStorage.removeItem(key);
        return null;
      }

      if (payload.expiresAt < Date.now()) {
        window.localStorage.removeItem(key);
        return null;
      }

      return payload.data;
    } catch (error) {
      console.warn("Failed to load Stammtisch cache", error);
      window.localStorage.removeItem(key);
      return null;
    }
  }

  private static save<T>(key: string, data: T): void {
    if (!isBrowser) return;

    try {
      const payload: CacheRecord<T> = {
        data,
        expiresAt: Date.now() + TEN_MINUTES,
      };
      window.localStorage.setItem(key, JSON.stringify(payload));
    } catch (error) {
      console.warn("Failed to persist Stammtisch cache", error);
    }
  }
}
