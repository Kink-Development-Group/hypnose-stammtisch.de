import { writable } from "svelte/store";

export interface AdminUser {
  id: number;
  username: string;
  email: string;
  role: string;
}

export interface AdminAuthState {
  isAuthenticated: boolean;
  user: AdminUser | null;
  loading: boolean;
}

// Store for admin authentication state
export const adminAuthState = writable<AdminAuthState>({
  isAuthenticated: false,
  user: null,
  loading: false,
});

// Admin API base URL
const API_BASE = "/api/admin";

class AdminAuthStore {
  /**
   * Login with email and password
   */
  async login(email: string, password: string) {
    adminAuthState.update((state) => ({ ...state, loading: true }));

    try {
      const response = await fetch(`${API_BASE}/auth/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({ email, password }),
      });

      const result = await response.json();

      if (result.success) {
        adminAuthState.update((state) => ({
          ...state,
          isAuthenticated: true,
          user: result.data,
          loading: false,
        }));
      } else {
        adminAuthState.update((state) => ({ ...state, loading: false }));
      }

      return result;
    } catch (error) {
      adminAuthState.update((state) => ({ ...state, loading: false }));
      return {
        success: false,
        message: "Network error occurred",
      };
    }
  }

  /**
   * Logout current user
   */
  async logout() {
    try {
      const response = await fetch(`${API_BASE}/auth/logout`, {
        method: "POST",
        credentials: "include",
      });

      const result = await response.json();

      adminAuthState.update((state) => ({
        ...state,
        isAuthenticated: false,
        user: null,
      }));

      return result;
    } catch (error) {
      return {
        success: false,
        message: "Network error occurred",
      };
    }
  }

  /**
   * Check authentication status
   */
  async checkStatus() {
    try {
      const response = await fetch(`${API_BASE}/auth/status`, {
        method: "GET",
        credentials: "include",
      });

      const result = await response.json();

      if (result.success) {
        adminAuthState.update((state) => ({
          ...state,
          isAuthenticated: true,
          user: result.data,
        }));
      } else {
        adminAuthState.update((state) => ({
          ...state,
          isAuthenticated: false,
          user: null,
        }));
      }

      return result;
    } catch (error) {
      adminAuthState.update((state) => ({
        ...state,
        isAuthenticated: false,
        user: null,
      }));

      return {
        success: false,
        message: "Network error occurred",
      };
    }
  }
}

export const adminAuth = new AdminAuthStore();

// Admin API helper functions
export class AdminAPI {
  private static async request(endpoint: string, options: RequestInit = {}) {
    const response = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
    });

    const result = await response.json();

    // If unauthorized, redirect to login
    if (response.status === 401) {
      adminAuthState.update((state) => ({
        ...state,
        isAuthenticated: false,
        user: null,
      }));

      if (typeof window !== "undefined") {
        window.location.href = "/admin/login";
      }
    }

    return result;
  }

  // Events API
  static async getEvents() {
    return this.request("/events");
  }

  static async createEvent(eventData: any) {
    return this.request("/events", {
      method: "POST",
      body: JSON.stringify(eventData),
    });
  }

  static async updateEvent(id: number, eventData: any) {
    return this.request(`/events/${id}`, {
      method: "PUT",
      body: JSON.stringify(eventData),
    });
  }

  static async deleteEvent(id: number) {
    return this.request(`/events/${id}`, {
      method: "DELETE",
    });
  }

  // Messages API
  static async getMessages(params: any = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.request(`/messages${queryString ? "?" + queryString : ""}`);
  }

  static async getMessage(id: number) {
    return this.request(`/messages/${id}`);
  }

  static async updateMessageStatus(id: number, status: string) {
    return this.request(`/messages/${id}/status`, {
      method: "PATCH",
      body: JSON.stringify({ status }),
    });
  }

  static async markMessageResponded(id: number) {
    return this.request(`/messages/${id}/responded`, {
      method: "PATCH",
    });
  }

  static async deleteMessage(id: number) {
    return this.request(`/messages/${id}`, {
      method: "DELETE",
    });
  }

  static async getMessageStats() {
    return this.request("/messages/stats");
  }
}
