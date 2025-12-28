/**
 * Compliance Store - Cookie Consent & Age Verification Management
 *
 * Handles GDPR-compliant cookie consent and age verification for the DACH region.
 * Implements ePrivacy Directive requirements with granular consent categories.
 */

import { derived, get, writable } from "svelte/store";

// =============================================================================
// Types & Interfaces
// =============================================================================

export interface CookieConsent {
  /** Essential cookies - always true, cannot be disabled */
  essential: true;
  /** Preference cookies - user settings, theme, language */
  preferences: boolean;
  /** Statistics cookies - anonymous usage analytics */
  statistics: boolean;
  /** Marketing cookies - advertising and tracking */
  marketing: boolean;
}

export interface ConsentRecord {
  /** Timestamp when consent was given/updated */
  timestamp: string;
  /** Version of the consent form */
  version: string;
  /** The consent choices */
  consent: CookieConsent;
}

export interface ComplianceState {
  /** Whether the user has made a cookie consent choice */
  hasConsentChoice: boolean;
  /** The current cookie consent settings */
  cookieConsent: CookieConsent;
  /** Whether age verification has been completed */
  ageVerified: boolean;
  /** Whether the cookie banner should be shown */
  showCookieBanner: boolean;
  /** Whether the cookie settings modal should be shown */
  showCookieSettings: boolean;
  /** Whether the age verification modal should be shown */
  showAgeVerification: boolean;
}

// =============================================================================
// Constants
// =============================================================================

/** Cookie names used by the compliance system */
export const COOKIE_NAMES = {
  CONSENT: "cookie_consent",
  AGE_VERIFIED: "age_verified",
} as const;

/** Cookie expiration in days (1 year as per GDPR recommendation) */
export const COOKIE_EXPIRY_DAYS = 365;

/** Current version of the consent form - increment when consent form changes */
export const CONSENT_VERSION = "1.0.0";

/** Default consent values (all optional cookies disabled) */
export const DEFAULT_CONSENT: CookieConsent = {
  essential: true,
  preferences: false,
  statistics: false,
  marketing: false,
};

/** All cookies accepted */
export const ACCEPT_ALL_CONSENT: CookieConsent = {
  essential: true,
  preferences: true,
  statistics: true,
  marketing: true,
};

// =============================================================================
// Cookie Utilities
// =============================================================================

/**
 * Set a cookie with proper security attributes
 */
export function setCookie(
  name: string,
  value: string,
  days: number = COOKIE_EXPIRY_DAYS,
): void {
  const date = new Date();
  date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
  const expires = date.toUTCString();

  // Build cookie string with proper format
  // Format: name=value; path=/; expires=DATE; SameSite=Lax; Secure
  let cookieString = `${name}=${encodeURIComponent(value)}; path=/; expires=${expires}; SameSite=Lax`;

  // Add Secure flag only on HTTPS
  if (window.location.protocol === "https:") {
    cookieString += "; Secure";
  }

  console.log("[Compliance] Setting cookie:", cookieString);
  document.cookie = cookieString;
}

/**
 * Get a cookie value by name
 */
export function getCookie(name: string): string | null {
  // Check if we're in browser environment
  if (typeof document === "undefined") {
    return null;
  }

  const nameEQ = `${name}=`;
  const cookies = document.cookie.split(";");

  for (const cookie of cookies) {
    let c = cookie.trim();
    if (c.indexOf(nameEQ) === 0) {
      const value = decodeURIComponent(c.substring(nameEQ.length));
      console.log(`[Compliance] Got cookie ${name}:`, value);
      return value;
    }
  }
  console.log(
    `[Compliance] Cookie ${name} not found. All cookies:`,
    document.cookie,
  );
  return null;
}

/**
 * Delete a cookie by name
 */
export function deleteCookie(name: string): void {
  document.cookie = `${name}=;path=/;expires=Thu, 01 Jan 1970 00:00:00 GMT`;
}

/**
 * Delete all non-essential cookies
 */
export function deleteNonEssentialCookies(): void {
  const cookies = document.cookie.split(";");

  for (const cookie of cookies) {
    const cookieName = cookie.split("=")[0].trim();
    // Keep essential cookies
    if (
      !Object.values(COOKIE_NAMES).includes(
        cookieName as (typeof COOKIE_NAMES)[keyof typeof COOKIE_NAMES],
      )
    ) {
      deleteCookie(cookieName);
    }
  }
}

// =============================================================================
// Consent Storage
// =============================================================================

/**
 * Save consent record to cookie
 */
function saveConsentRecord(consent: CookieConsent): void {
  const record: ConsentRecord = {
    timestamp: new Date().toISOString(),
    version: CONSENT_VERSION,
    consent,
  };
  setCookie(COOKIE_NAMES.CONSENT, JSON.stringify(record), COOKIE_EXPIRY_DAYS);
}

/**
 * Load consent record from cookie
 */
function loadConsentRecord(): ConsentRecord | null {
  const cookieValue = getCookie(COOKIE_NAMES.CONSENT);
  if (!cookieValue) return null;

  try {
    const record = JSON.parse(cookieValue) as ConsentRecord;
    // Validate version - if outdated, user needs to re-consent
    if (record.version !== CONSENT_VERSION) {
      return null;
    }
    return record;
  } catch {
    return null;
  }
}

/**
 * Save age verification to cookie
 */
function saveAgeVerification(): void {
  setCookie(COOKIE_NAMES.AGE_VERIFIED, "true", COOKIE_EXPIRY_DAYS);
}

/**
 * Load age verification from cookie
 */
function loadAgeVerification(): boolean {
  return getCookie(COOKIE_NAMES.AGE_VERIFIED) === "true";
}

// =============================================================================
// Store Creation
// =============================================================================

function createComplianceStore() {
  // Start with default state - will be hydrated on client
  const initialState: ComplianceState = {
    hasConsentChoice: false,
    cookieConsent: DEFAULT_CONSENT,
    ageVerified: false,
    showCookieBanner: false, // Don't show until hydrated
    showCookieSettings: false,
    showAgeVerification: false, // Don't show until hydrated
  };

  const { subscribe, set, update } = writable<ComplianceState>(initialState);

  // Track if we've hydrated from cookies
  let hydrated = false;

  return {
    subscribe,

    /**
     * Hydrate store from cookies - call this on client mount
     */
    hydrate: () => {
      if (hydrated || typeof document === "undefined") return;
      hydrated = true;

      const consentRecord = loadConsentRecord();
      const ageVerified = loadAgeVerification();

      set({
        hasConsentChoice: consentRecord !== null,
        cookieConsent: consentRecord?.consent ?? DEFAULT_CONSENT,
        ageVerified,
        showCookieBanner: consentRecord === null,
        showCookieSettings: false,
        showAgeVerification: !ageVerified,
      });
    },

    /**
     * Accept all cookies
     */
    acceptAll: () => {
      update((state) => {
        saveConsentRecord(ACCEPT_ALL_CONSENT);
        return {
          ...state,
          hasConsentChoice: true,
          cookieConsent: ACCEPT_ALL_CONSENT,
          showCookieBanner: false,
          showCookieSettings: false,
        };
      });
    },

    /**
     * Reject all optional cookies (only essential)
     */
    rejectAll: () => {
      update((state) => {
        saveConsentRecord(DEFAULT_CONSENT);
        deleteNonEssentialCookies();
        return {
          ...state,
          hasConsentChoice: true,
          cookieConsent: DEFAULT_CONSENT,
          showCookieBanner: false,
          showCookieSettings: false,
        };
      });
    },

    /**
     * Save custom consent preferences
     */
    savePreferences: (consent: Omit<CookieConsent, "essential">) => {
      const fullConsent: CookieConsent = {
        essential: true,
        ...consent,
      };

      update((state) => {
        saveConsentRecord(fullConsent);

        // Delete cookies for disabled categories
        if (!fullConsent.statistics) {
          // Delete analytics cookies if statistics disabled
          // Add specific cookie names here when analytics is implemented
        }
        if (!fullConsent.marketing) {
          // Delete marketing cookies if marketing disabled
        }

        return {
          ...state,
          hasConsentChoice: true,
          cookieConsent: fullConsent,
          showCookieBanner: false,
          showCookieSettings: false,
        };
      });
    },

    /**
     * Verify user is 18+
     */
    verifyAge: () => {
      update((state) => {
        saveAgeVerification();
        return {
          ...state,
          ageVerified: true,
          showAgeVerification: false,
        };
      });
    },

    /**
     * User declined age verification - redirect away
     */
    declineAge: () => {
      window.location.href = "https://duckduckgo.com";
    },

    /**
     * Open cookie settings modal
     */
    openSettings: () => {
      update((state) => ({
        ...state,
        showCookieSettings: true,
      }));
    },

    /**
     * Close cookie settings modal
     */
    closeSettings: () => {
      update((state) => ({
        ...state,
        showCookieSettings: false,
      }));
    },

    /**
     * Re-show cookie banner (for footer link)
     */
    reopenBanner: () => {
      update((state) => ({
        ...state,
        showCookieBanner: true,
      }));
    },

    /**
     * Withdraw consent - reset to default and show banner
     */
    withdrawConsent: () => {
      update((state) => {
        deleteCookie(COOKIE_NAMES.CONSENT);
        deleteNonEssentialCookies();
        return {
          ...state,
          hasConsentChoice: false,
          cookieConsent: DEFAULT_CONSENT,
          showCookieBanner: true,
          showCookieSettings: false,
        };
      });
    },

    /**
     * Reset all compliance state (for testing)
     */
    reset: () => {
      deleteCookie(COOKIE_NAMES.CONSENT);
      deleteCookie(COOKIE_NAMES.AGE_VERIFIED);
      deleteNonEssentialCookies();
      set({
        hasConsentChoice: false,
        cookieConsent: DEFAULT_CONSENT,
        ageVerified: false,
        showCookieBanner: true,
        showCookieSettings: false,
        showAgeVerification: true,
      });
    },
  };
}

// =============================================================================
// Export Store Instance
// =============================================================================

export const complianceStore = createComplianceStore();

// =============================================================================
// Derived Stores for Convenience
// =============================================================================

/** Whether analytics/statistics cookies are allowed */
export const statisticsAllowed = derived(
  complianceStore,
  ($store) => $store.cookieConsent.statistics,
);

/** Whether marketing cookies are allowed */
export const marketingAllowed = derived(
  complianceStore,
  ($store) => $store.cookieConsent.marketing,
);

/** Whether preference cookies are allowed */
export const preferencesAllowed = derived(
  complianceStore,
  ($store) => $store.cookieConsent.preferences,
);

/** Whether the site can be used (age verified) */
export const canUseSite = derived(
  complianceStore,
  ($store) => $store.ageVerified,
);

// =============================================================================
// Google Analytics Integration Helper
// =============================================================================

/**
 * Initialize Google Analytics only if statistics consent is given.
 * Call this function when setting up analytics.
 *
 * @param measurementId - Google Analytics 4 Measurement ID (e.g., "G-XXXXXXXXXX")
 */
export function initializeAnalytics(measurementId: string): void {
  const state = get(complianceStore);

  if (!state.cookieConsent.statistics) {
    console.log("[Compliance] Analytics blocked - no consent");
    return;
  }

  // Only initialize if not already loaded
  if (typeof window.gtag !== "undefined") {
    console.log("[Compliance] Analytics already initialized");
    return;
  }

  // Load Google Analytics script
  const script = document.createElement("script");
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
  document.head.appendChild(script);

  // Initialize gtag
  window.dataLayer = window.dataLayer || [];
  function gtag(...args: unknown[]) {
    window.dataLayer.push(args);
  }
  window.gtag = gtag;

  gtag("js", new Date());
  gtag("config", measurementId, {
    anonymize_ip: true, // GDPR requirement
    cookie_flags: "SameSite=Lax;Secure",
  });
}

// Type declarations for Google Analytics
declare global {
  interface Window {
    dataLayer: unknown[];
    gtag: (...args: unknown[]) => void;
  }
}
