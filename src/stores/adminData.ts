import { writable } from "svelte/store";

// Admin Data Stores für automatische Updates
export interface AdminEvent {
  id: string | number;
  title: string;
  description: string;
  content: string;
  start_datetime: string;
  end_datetime: string;
  timezone: string;
  location_type: string;
  location_name: string;
  location_address: string;
  location_url: string;
  category: string;
  difficulty_level: string;
  max_participants: number | null;
  status: string;
  is_featured: boolean;
  requires_registration: boolean;
  organizer_name: string;
  organizer_email: string;
  tags: string[];
  created_at: string;
  updated_at: string;
  series_id?: string;
  instance_date?: string; // falls Override einer Serie
  created_by?: number | null; // owning user id
  owner_username?: string | null; // username of the owner (display only)
  is_owner?: boolean; // true if the current user owns this event
}

export interface AdminMessage {
  id: number;
  name: string;
  email: string;
  subject: string;
  message: string;
  status: string;
  response_sent: boolean;
  created_at: string;
  updated_at: string;
}

export interface AdminStats {
  unread_count: number;
  status_counts: Array<{ status: string; count: number }>;
}

// Stores für Admin-Daten
export const adminEvents = writable<AdminEvent[]>([]);
export const adminSeries = writable<any[]>([]);
export const adminMessages = writable<AdminMessage[]>([]);
export const adminStats = writable<AdminStats>({
  unread_count: 0,
  status_counts: [],
});
export const adminLoading = writable<boolean>(false);

// Event Bus für globale Updates
export interface AdminUpdateEvent {
  type: "event" | "message";
  action: "create" | "update" | "delete";
  data?: any;
  id?: string | number;
}

export const adminEventBus = writable<AdminUpdateEvent | null>(null);

// Helper-Funktionen für optimistische Updates
export const adminEventHelpers = {
  // Events
  addEvent: (event: AdminEvent) => {
    adminEvents.update((events) => [event, ...events]);
    adminEventBus.set({ type: "event", action: "create", data: event });
  },

  updateEvent: (id: string | number, updates: Partial<AdminEvent>) => {
    const idStr = String(id);
    adminEvents.update((events) =>
      events.map((event) =>
        String(event.id) === idStr ? { ...event, ...updates } : event,
      ),
    );
    adminEventBus.set({ type: "event", action: "update", id, data: updates });
  },

  removeEvent: (id: string | number) => {
    const idStr = String(id);
    adminEvents.update((events) => events.filter((event) => String(event.id) !== idStr));
    adminEventBus.set({ type: "event", action: "delete", id });
  },

  updateSeries: (id: string, updates: Record<string, unknown>) => {
    adminSeries.update((series) =>
      series.map((s) => (s.id === id ? { ...s, ...updates } : s)),
    );
  },

  // Messages
  addMessage: (message: AdminMessage) => {
    adminMessages.update((messages) => [message, ...messages]);
    adminEventBus.set({ type: "message", action: "create", data: message });
  },

  updateMessage: (id: number, updates: Partial<AdminMessage>) => {
    adminMessages.update((messages) =>
      messages.map((message) =>
        message.id === id ? { ...message, ...updates } : message,
      ),
    );
    adminEventBus.set({ type: "message", action: "update", id, data: updates });

    // Update stats if status changed
    if (updates.status) {
      updateStatsAfterStatusChange(updates.status);
    }
  },

  removeMessage: (id: number) => {
    adminMessages.update((messages) =>
      messages.filter((message) => message.id !== id),
    );
    adminEventBus.set({ type: "message", action: "delete", id });
  },

  // Bulk operations
  setEvents: (events: AdminEvent[]) => {
    adminEvents.set(events);
  },

  setSeries: (series: any[]) => {
    adminSeries.set(series);
  },

  setMessages: (messages: AdminMessage[]) => {
    adminMessages.set(messages);
  },

  setStats: (stats: AdminStats) => {
    adminStats.set(stats);
  },
};

// Helper-Funktion für Statistik-Updates
function updateStatsAfterStatusChange(newStatus: string) {
  adminStats.update((stats) => {
    // Vereinfachte Statistik-Aktualisierung
    // In einer echten Anwendung würde man hier präziser rechnen
    if (newStatus === "new") {
      stats.unread_count += 1;
    } else {
      stats.unread_count = Math.max(0, stats.unread_count - 1);
    }
    return stats;
  });
}

// Auto-Update Mechanismus
export const adminAutoUpdate = {
  enableAutoRefresh: false,
  intervalId: null as NodeJS.Timeout | null,

  start: (intervalMs: number = 30000) => {
    if (adminAutoUpdate.intervalId) {
      adminAutoUpdate.stop();
    }

    adminAutoUpdate.enableAutoRefresh = true;
    adminAutoUpdate.intervalId = setInterval(() => {
      if (adminAutoUpdate.enableAutoRefresh) {
        // Trigger refresh event
        adminEventBus.set({
          type: "event",
          action: "update",
          data: { autoRefresh: true },
        });
      }
    }, intervalMs);
  },

  stop: () => {
    if (adminAutoUpdate.intervalId) {
      clearInterval(adminAutoUpdate.intervalId);
      adminAutoUpdate.intervalId = null;
    }
    adminAutoUpdate.enableAutoRefresh = false;
  },

  toggle: () => {
    if (adminAutoUpdate.enableAutoRefresh) {
      adminAutoUpdate.stop();
    } else {
      adminAutoUpdate.start();
    }
  },
};

// Notification Helper für Success/Error Messages
export const adminNotifications = {
  success: (message: string) => {
    // Diese Funktion kann erweitert werden, um Toast-Notifications zu zeigen
    console.log("✅ Success:", message);
  },

  error: (message: string) => {
    console.error("❌ Error:", message);
  },

  info: (message: string) => {
    console.log("ℹ️ Info:", message);
  },
};
