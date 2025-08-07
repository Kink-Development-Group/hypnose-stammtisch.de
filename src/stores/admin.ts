import { writable } from "svelte/store";
import { adminEventHelpers, adminNotifications } from "./adminData";

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

  // Enhanced Events API with optimistic updates
  static async getEvents() {
    const result = await this.request("/events");
    if (result.success) {
      adminEventHelpers.setEvents(result.data.events || []);
      adminEventHelpers.setSeries(result.data.series || []);
    }
    return result;
  }

  static async createEvent(eventData: any) {
    // Optimistische Aktualisierung: Event sofort zur Liste hinzufügen
    const tempEvent = {
      ...eventData,
      id: Date.now(), // Temporäre ID
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    adminEventHelpers.addEvent(tempEvent);
    adminNotifications.info("Event wird erstellt...");

    try {
      const result = await this.request("/events", {
        method: "POST",
        body: JSON.stringify(eventData),
      });

      if (result.success) {
        // Ersetze das temporäre Event mit den echten Daten
        adminEventHelpers.removeEvent(tempEvent.id);
        adminEventHelpers.addEvent(result.data);
        adminNotifications.success("Event erfolgreich erstellt!");
      } else {
        // Entferne das temporäre Event bei Fehler
        adminEventHelpers.removeEvent(tempEvent.id);
        adminNotifications.error(
          result.message || "Fehler beim Erstellen des Events",
        );
      }

      return result;
    } catch (error) {
      adminEventHelpers.removeEvent(tempEvent.id);
      adminNotifications.error("Netzwerkfehler beim Erstellen des Events");
      return { success: false, message: "Network error" };
    }
  }

  static async updateEvent(id: number, eventData: any) {
    // Optimistische Aktualisierung
    const updates = {
      ...eventData,
      updated_at: new Date().toISOString(),
    };

    adminEventHelpers.updateEvent(id, updates);
    adminNotifications.info("Event wird aktualisiert...");

    try {
      const result = await this.request(`/events/${id}`, {
        method: "PUT",
        body: JSON.stringify(eventData),
      });

      if (result.success) {
        // Update mit echten Server-Daten
        adminEventHelpers.updateEvent(id, result.data);
        adminNotifications.success("Event erfolgreich aktualisiert!");
      } else {
        // Lade Events neu bei Fehler (könnte optimiert werden)
        this.getEvents();
        adminNotifications.error(
          result.message || "Fehler beim Aktualisieren des Events",
        );
      }

      return result;
    } catch (error) {
      this.getEvents();
      adminNotifications.error("Netzwerkfehler beim Aktualisieren des Events");
      return { success: false, message: "Network error" };
    }
  }

  static async deleteEvent(id: number) {
    // Optimistische Aktualisierung: Event sofort entfernen
    adminEventHelpers.removeEvent(id);
    adminNotifications.info("Event wird gelöscht...");

    try {
      const result = await this.request(`/events/${id}`, {
        method: "DELETE",
      });

      if (result.success) {
        adminNotifications.success("Event erfolgreich gelöscht!");
      } else {
        // Lade Events neu bei Fehler
        this.getEvents();
        adminNotifications.error(
          result.message || "Fehler beim Löschen des Events",
        );
      }

      return result;
    } catch (error) {
      this.getEvents();
      adminNotifications.error("Netzwerkfehler beim Löschen des Events");
      return { success: false, message: "Network error" };
    }
  }

  // Enhanced Messages API with optimistic updates
  static async getMessages(params: any = {}) {
    const queryString = new URLSearchParams(params).toString();
    const result = await this.request(
      `/messages${queryString ? "?" + queryString : ""}`,
    );
    if (result.success) {
      adminEventHelpers.setMessages(result.data.messages || []);
    }
    return result;
  }

  static async getMessage(id: number) {
    return this.request(`/messages/${id}`);
  }

  static async updateMessageStatus(id: number, status: string) {
    // Optimistische Aktualisierung
    adminEventHelpers.updateMessage(id, {
      status,
      updated_at: new Date().toISOString(),
    });
    adminNotifications.info("Status wird aktualisiert...");

    try {
      const result = await this.request(`/messages/${id}/status`, {
        method: "PATCH",
        body: JSON.stringify({ status }),
      });

      if (result.success) {
        adminNotifications.success("Status erfolgreich aktualisiert!");
        // Lade Statistiken neu
        this.getMessageStats();
      } else {
        this.getMessages();
        adminNotifications.error(
          result.message || "Fehler beim Aktualisieren des Status",
        );
      }

      return result;
    } catch (error) {
      this.getMessages();
      adminNotifications.error("Netzwerkfehler beim Aktualisieren des Status");
      return { success: false, message: "Network error" };
    }
  }

  static async markMessageResponded(id: number) {
    // Optimistische Aktualisierung
    adminEventHelpers.updateMessage(id, {
      response_sent: true,
      updated_at: new Date().toISOString(),
    });
    adminNotifications.info("Nachricht wird als beantwortet markiert...");

    try {
      const result = await this.request(`/messages/${id}/responded`, {
        method: "PATCH",
      });

      if (result.success) {
        adminNotifications.success("Nachricht als beantwortet markiert!");
      } else {
        this.getMessages();
        adminNotifications.error(
          result.message || "Fehler beim Markieren als beantwortet",
        );
      }

      return result;
    } catch (error) {
      this.getMessages();
      adminNotifications.error("Netzwerkfehler beim Markieren als beantwortet");
      return { success: false, message: "Network error" };
    }
  }

  static async deleteMessage(id: number) {
    // Optimistische Aktualisierung
    adminEventHelpers.removeMessage(id);
    adminNotifications.info("Nachricht wird gelöscht...");

    try {
      const result = await this.request(`/messages/${id}`, {
        method: "DELETE",
      });

      if (result.success) {
        adminNotifications.success("Nachricht erfolgreich gelöscht!");
        // Lade Statistiken neu
        this.getMessageStats();
      } else {
        this.getMessages();
        adminNotifications.error(
          result.message || "Fehler beim Löschen der Nachricht",
        );
      }

      return result;
    } catch (error) {
      this.getMessages();
      adminNotifications.error("Netzwerkfehler beim Löschen der Nachricht");
      return { success: false, message: "Network error" };
    }
  }

  static async getMessageStats() {
    const result = await this.request("/messages/stats");
    if (result.success) {
      adminEventHelpers.setStats(result.data);
    }
    return result;
  }
}
