import { writable } from "svelte/store";
import {
  COOKIE_CATEGORIES,
  COOKIE_EXPIRY,
  COOKIE_NAMES,
  getCookie,
  setCookie,
} from "../utils/cookies";

/**
 * Cookie consent preferences
 */
export interface ConsentPreferences {
  essential: boolean; // Always true, cannot be disabled
  functional: boolean;
  analytics: boolean;
  marketing: boolean;
}

/**
 * Consent state
 */
export interface ConsentState {
  hasConsented: boolean; // Whether user has made a choice
  preferences: ConsentPreferences;
  timestamp: number;
}

/**
 * Default consent preferences (only essential cookies)
 */
const DEFAULT_PREFERENCES: ConsentPreferences = {
  essential: true,
  functional: false,
  analytics: false,
  marketing: false,
};

/**
 * Load saved consent preferences from cookie
 */
function loadConsentPreferences(): ConsentState {
  const savedConsent = getCookie(COOKIE_NAMES.CONSENT_PREFERENCES);

  if (savedConsent) {
    try {
      const parsed = JSON.parse(savedConsent);
      return {
        hasConsented: true,
        preferences: {
          essential: true, // Always true
          functional: parsed.functional ?? false,
          analytics: parsed.analytics ?? false,
          marketing: parsed.marketing ?? false,
        },
        timestamp: parsed.timestamp || Date.now(),
      };
    } catch (e) {
      console.error("Failed to parse consent preferences:", e);
    }
  }

  return {
    hasConsented: false,
    preferences: DEFAULT_PREFERENCES,
    timestamp: Date.now(),
  };
}

/**
 * Save consent preferences to cookie
 */
function saveConsentPreferences(state: ConsentState): void {
  const data = {
    functional: state.preferences.functional,
    analytics: state.preferences.analytics,
    marketing: state.preferences.marketing,
    timestamp: state.timestamp,
  };

  setCookie(COOKIE_NAMES.CONSENT_PREFERENCES, JSON.stringify(data), {
    maxAge: COOKIE_EXPIRY.ONE_YEAR,
    secure: true,
    sameSite: "lax",
  });
}

/**
 * Consent store
 */
function createConsentStore() {
  const { subscribe, set, update } = writable<ConsentState>(
    loadConsentPreferences(),
  );

  return {
    subscribe,

    /**
     * Accept all cookies
     */
    acceptAll: () => {
      const newState: ConsentState = {
        hasConsented: true,
        preferences: {
          essential: true,
          functional: true,
          analytics: true,
          marketing: true,
        },
        timestamp: Date.now(),
      };
      set(newState);
      saveConsentPreferences(newState);
    },

    /**
     * Accept only essential cookies
     */
    acceptEssential: () => {
      const newState: ConsentState = {
        hasConsented: true,
        preferences: DEFAULT_PREFERENCES,
        timestamp: Date.now(),
      };
      set(newState);
      saveConsentPreferences(newState);
    },

    /**
     * Set custom preferences
     */
    setPreferences: (preferences: Partial<ConsentPreferences>) => {
      update((state) => {
        const newState: ConsentState = {
          hasConsented: true,
          preferences: {
            essential: true, // Always true
            functional: preferences.functional ?? state.preferences.functional,
            analytics: preferences.analytics ?? state.preferences.analytics,
            marketing: preferences.marketing ?? state.preferences.marketing,
          },
          timestamp: Date.now(),
        };
        saveConsentPreferences(newState);
        return newState;
      });
    },

    /**
     * Reset consent (show banner again)
     */
    reset: () => {
      const newState: ConsentState = {
        hasConsented: false,
        preferences: DEFAULT_PREFERENCES,
        timestamp: Date.now(),
      };
      set(newState);
      saveConsentPreferences(newState);
    },
  };
}

export const consentStore = createConsentStore();

/**
 * Age verification store
 */
function createAgeVerificationStore() {
  const { subscribe, set } = writable<boolean>(false);

  // Check if age is verified on initialization
  if (typeof window !== "undefined") {
    const isVerified = getCookie(COOKIE_NAMES.AGE_VERIFIED) === "true";
    set(isVerified);
  }

  return {
    subscribe,

    /**
     * Mark user as age verified
     */
    verify: () => {
      setCookie(COOKIE_NAMES.AGE_VERIFIED, "true", {
        maxAge: COOKIE_EXPIRY.ONE_YEAR,
        secure: true,
        sameSite: "lax",
      });
      set(true);
    },

    /**
     * Check if user is verified
     */
    isVerified: (): boolean => {
      return getCookie(COOKIE_NAMES.AGE_VERIFIED) === "true";
    },
  };
}

export const ageVerificationStore = createAgeVerificationStore();

/**
 * Show cookie banner store
 */
export const showCookieBanner = writable(false);

/**
 * Show age verification modal store
 */
export const showAgeVerification = writable(false);
