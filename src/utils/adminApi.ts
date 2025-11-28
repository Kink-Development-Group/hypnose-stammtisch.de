/**
 * Admin API Client with CSRF protection
 *
 * This module provides a secure API client for admin operations
 * that automatically handles CSRF tokens for all mutating requests.
 */

const API_BASE = "/api/admin";

// CSRF token cache
let csrfToken: string | null = null;
let csrfTokenPromise: Promise<string> | null = null;

/**
 * Fetch CSRF token from the server
 */
async function fetchCsrfToken(): Promise<string> {
  const response = await fetch(`${API_BASE}/auth/csrf`, {
    method: "GET",
    credentials: "include",
  });

  if (!response.ok) {
    throw new Error("Failed to fetch CSRF token");
  }

  const result = await response.json();

  if (result.success && result.data?.csrf_token) {
    return result.data.csrf_token;
  }

  throw new Error("Invalid CSRF token response");
}

/**
 * Get or refresh CSRF token
 */
export async function getCsrfToken(): Promise<string> {
  // Return cached token if available
  if (csrfToken) {
    return csrfToken;
  }

  // Prevent concurrent fetches
  if (csrfTokenPromise) {
    return csrfTokenPromise;
  }

  csrfTokenPromise = fetchCsrfToken()
    .then((token) => {
      csrfToken = token;
      csrfTokenPromise = null;
      return token;
    })
    .catch((error) => {
      csrfTokenPromise = null;
      throw error;
    });

  return csrfTokenPromise;
}

/**
 * Clear cached CSRF token (call on logout)
 */
export function clearCsrfToken(): void {
  csrfToken = null;
  csrfTokenPromise = null;
}

/**
 * Request options with CSRF token
 */
interface AdminApiOptions extends Omit<RequestInit, "body"> {
  body?: Record<string, unknown> | FormData | null;
  skipCsrf?: boolean;
}

/**
 * Admin API response type
 */
export interface AdminApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

/**
 * Make an authenticated admin API request
 * Automatically includes CSRF token for mutating requests (POST, PUT, DELETE, PATCH)
 */
export async function adminApi<T = unknown>(
  endpoint: string,
  options: AdminApiOptions = {},
): Promise<AdminApiResponse<T>> {
  const { body, skipCsrf = false, ...fetchOptions } = options;

  const method = (fetchOptions.method || "GET").toUpperCase();
  const isMutating = ["POST", "PUT", "DELETE", "PATCH"].includes(method);

  // Build headers
  const headers: HeadersInit = {
    ...(fetchOptions.headers as Record<string, string>),
  };

  // Add CSRF token for mutating requests
  if (isMutating && !skipCsrf) {
    try {
      const token = await getCsrfToken();
      headers["X-CSRF-Token"] = token;
    } catch (error) {
      console.error("Failed to get CSRF token:", error);
      return {
        success: false,
        message: "Security token error. Please refresh the page.",
      };
    }
  }

  // Set content type and serialize body
  let serializedBody: string | FormData | undefined;

  if (body) {
    if (body instanceof FormData) {
      serializedBody = body;
      // Don't set Content-Type for FormData (browser sets it with boundary)
    } else {
      headers["Content-Type"] = "application/json";
      serializedBody = JSON.stringify(body);
    }
  }

  try {
    const url = endpoint.startsWith("/") ? endpoint : `${API_BASE}/${endpoint}`;

    const response = await fetch(url, {
      ...fetchOptions,
      method,
      headers,
      credentials: "include",
      body: serializedBody,
    });

    // Handle CSRF token refresh on 403
    if (response.status === 403 && isMutating && !skipCsrf) {
      const result = (await response.json()) as AdminApiResponse<T>;

      // Check if it's a CSRF error and retry once with a fresh token
      if (
        result.message?.toLowerCase().includes("csrf") ||
        result.message?.toLowerCase().includes("token")
      ) {
        console.warn("CSRF token expired, fetching new token...");
        csrfToken = null;

        // Retry the request with a fresh token
        const newToken = await getCsrfToken();
        headers["X-CSRF-Token"] = newToken;

        const retryResponse = await fetch(url, {
          ...fetchOptions,
          method,
          headers,
          credentials: "include",
          body: serializedBody,
        });

        return (await retryResponse.json()) as AdminApiResponse<T>;
      }

      return result;
    }

    return (await response.json()) as AdminApiResponse<T>;
  } catch (error) {
    console.error("Admin API error:", error);
    return {
      success: false,
      message:
        error instanceof Error
          ? error.message
          : "Network error. Please try again.",
    };
  }
}

/**
 * Shorthand methods for common HTTP verbs
 */
export const adminGet = <T = unknown>(
  endpoint: string,
  options?: Omit<AdminApiOptions, "method" | "body">,
) => adminApi<T>(endpoint, { ...options, method: "GET" });

export const adminPost = <T = unknown>(
  endpoint: string,
  body?: Record<string, unknown>,
  options?: Omit<AdminApiOptions, "method" | "body">,
) => adminApi<T>(endpoint, { ...options, method: "POST", body });

export const adminPut = <T = unknown>(
  endpoint: string,
  body?: Record<string, unknown>,
  options?: Omit<AdminApiOptions, "method" | "body">,
) => adminApi<T>(endpoint, { ...options, method: "PUT", body });

export const adminPatch = <T = unknown>(
  endpoint: string,
  body?: Record<string, unknown>,
  options?: Omit<AdminApiOptions, "method" | "body">,
) => adminApi<T>(endpoint, { ...options, method: "PATCH", body });

export const adminDelete = <T = unknown>(
  endpoint: string,
  options?: Omit<AdminApiOptions, "method">,
) => adminApi<T>(endpoint, { ...options, method: "DELETE" });
