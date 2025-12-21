/**
 * Cookie management utilities
 * Handles cookie operations with proper GDPR compliance
 */

export interface CookieOptions {
  path?: string;
  domain?: string;
  maxAge?: number; // in seconds
  expires?: Date;
  secure?: boolean;
  sameSite?: "strict" | "lax" | "none";
}

/**
 * Set a cookie with the given name, value, and options
 */
export function setCookie(
  name: string,
  value: string,
  options: CookieOptions = {},
): void {
  if (typeof document === "undefined") return;

  const {
    path = "/",
    maxAge,
    expires,
    secure = true,
    sameSite = "lax",
  } = options;

  let cookieString = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`;

  if (path) cookieString += `; path=${path}`;
  if (maxAge !== undefined) cookieString += `; max-age=${maxAge}`;
  if (expires) cookieString += `; expires=${expires.toUTCString()}`;
  if (secure) cookieString += "; secure";
  if (sameSite) cookieString += `; samesite=${sameSite}`;

  document.cookie = cookieString;
}

/**
 * Get a cookie value by name
 */
export function getCookie(name: string): string | null {
  if (typeof document === "undefined") return null;

  const nameEQ = encodeURIComponent(name) + "=";
  const cookies = document.cookie.split(";");

  for (let i = 0; i < cookies.length; i++) {
    let cookie = cookies[i];
    while (cookie.charAt(0) === " ") {
      cookie = cookie.substring(1);
    }
    if (cookie.indexOf(nameEQ) === 0) {
      return decodeURIComponent(cookie.substring(nameEQ.length));
    }
  }
  return null;
}

/**
 * Delete a cookie by name
 */
export function deleteCookie(name: string, path: string = "/"): void {
  if (typeof document === "undefined") return;

  document.cookie = `${encodeURIComponent(name)}=; path=${path}; max-age=0`;
}

/**
 * Check if a cookie exists
 */
export function hasCookie(name: string): boolean {
  return getCookie(name) !== null;
}

/**
 * Get all cookies as an object
 */
export function getAllCookies(): Record<string, string> {
  if (typeof document === "undefined") return {};

  const cookies: Record<string, string> = {};
  const cookieArray = document.cookie.split(";");

  for (const cookie of cookieArray) {
    const [name, value] = cookie.split("=").map((s) => s.trim());
    if (name && value) {
      cookies[decodeURIComponent(name)] = decodeURIComponent(value);
    }
  }

  return cookies;
}

/**
 * Cookie consent categories
 */
export const COOKIE_CATEGORIES = {
  ESSENTIAL: "essential",
  FUNCTIONAL: "functional",
  ANALYTICS: "analytics",
  MARKETING: "marketing",
} as const;

/**
 * Cookie names used by the application
 */
export const COOKIE_NAMES = {
  AGE_VERIFIED: "age_verified",
  CONSENT_PREFERENCES: "cookie_consent",
  SESSION: "session_id",
} as const;

/**
 * Cookie expiry times in seconds
 */
export const COOKIE_EXPIRY = {
  ONE_YEAR: 365 * 24 * 60 * 60,
  SIX_MONTHS: 180 * 24 * 60 * 60,
  ONE_MONTH: 30 * 24 * 60 * 60,
  ONE_DAY: 24 * 60 * 60,
  SESSION: undefined, // Session cookie
} as const;
