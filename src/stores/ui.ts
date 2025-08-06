import { writable } from "svelte/store";

// UI state
export const isMobileMenuOpen = writable(false);
export const isLoading = writable(false);
export const theme = writable<"light" | "dark">("dark");

// Notification system
export interface Notification {
  id: string;
  type: "success" | "error" | "warning" | "info";
  title: string;
  message: string;
  duration?: number;
  actions?: Array<{
    label: string;
    action: () => void;
  }>;
}

export const notifications = writable<Notification[]>([]);

// Helper functions
export const addNotification = (notification: Omit<Notification, "id">) => {
  const id = Math.random().toString(36).substr(2, 9);
  const newNotification: Notification = {
    ...notification,
    id,
    duration: notification.duration || 5000,
  };

  notifications.update((current) => [...current, newNotification]);

  // Auto-remove after duration
  if (newNotification.duration > 0) {
    setTimeout(() => {
      removeNotification(id);
    }, newNotification.duration);
  }

  return id;
};

export const removeNotification = (id: string) => {
  notifications.update((current) =>
    current.filter((notification) => notification.id !== id),
  );
};

export const clearAllNotifications = () => {
  notifications.set([]);
};

// Loading state helpers
export const setLoading = (loading: boolean) => {
  isLoading.set(loading);
};

// Theme helpers
export const toggleTheme = () => {
  theme.update((current) => (current === "light" ? "dark" : "light"));
};

export const setTheme = (newTheme: "light" | "dark") => {
  theme.set(newTheme);

  // Update document class
  if (typeof document !== "undefined") {
    document.documentElement.classList.toggle("dark", newTheme === "dark");
  }
};
