import { derived, get, writable } from "svelte/store";

export type AdminTheme = "light" | "dark" | "system";

interface AdminThemeState {
  theme: AdminTheme;
  resolvedTheme: "light" | "dark";
}

// Get initial theme from localStorage or default to system
function getInitialTheme(): AdminTheme {
  if (typeof window === "undefined") return "system";

  const stored = localStorage.getItem("admin-theme");
  if (stored === "light" || stored === "dark" || stored === "system") {
    return stored;
  }
  return "system";
}

// Get resolved theme (actual light/dark based on system preference if theme is "system")
function getResolvedTheme(theme: AdminTheme): "light" | "dark" {
  if (theme === "system") {
    if (typeof window === "undefined") return "light";
    return window.matchMedia("(prefers-color-scheme: dark)").matches
      ? "dark"
      : "light";
  }
  return theme;
}

function createAdminThemeStore() {
  const initialTheme = getInitialTheme();
  const { subscribe, set, update } = writable<AdminThemeState>({
    theme: initialTheme,
    resolvedTheme: getResolvedTheme(initialTheme),
  });

  // Watch for system theme changes
  if (typeof window !== "undefined") {
    const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
    mediaQuery.addEventListener("change", (e) => {
      update((state) => {
        if (state.theme === "system") {
          return {
            ...state,
            resolvedTheme: e.matches ? "dark" : "light",
          };
        }
        return state;
      });
    });
  }

  return {
    subscribe,

    setTheme(theme: AdminTheme) {
      const resolvedTheme = getResolvedTheme(theme);

      // Store preference
      if (typeof window !== "undefined") {
        localStorage.setItem("admin-theme", theme);
      }

      set({ theme, resolvedTheme });

      // Apply to document
      this.applyTheme(resolvedTheme);
    },

    toggleTheme() {
      const current = get({ subscribe });
      const nextTheme: AdminTheme =
        current.theme === "light"
          ? "dark"
          : current.theme === "dark"
            ? "system"
            : "light";
      this.setTheme(nextTheme);
    },

    cycleBetweenLightAndDark() {
      const current = get({ subscribe });
      const nextTheme: AdminTheme =
        current.resolvedTheme === "light" ? "dark" : "light";
      this.setTheme(nextTheme);
    },

    applyTheme(resolvedTheme: "light" | "dark") {
      if (typeof document === "undefined") return;

      const root = document.documentElement;

      // Remove both classes first
      root.classList.remove("light", "dark");
      // Add the current theme class
      root.classList.add(resolvedTheme);

      // Also set a data attribute for more specific styling
      root.setAttribute("data-admin-theme", resolvedTheme);
    },

    initialize() {
      const current = get({ subscribe });
      this.applyTheme(current.resolvedTheme);
    },
  };
}

export const adminTheme = createAdminThemeStore();

// Derived stores for convenience
export const isDarkMode = derived(
  adminTheme,
  ($theme) => $theme.resolvedTheme === "dark",
);

export const currentTheme = derived(adminTheme, ($theme) => $theme.theme);

export const resolvedTheme = derived(
  adminTheme,
  ($theme) => $theme.resolvedTheme,
);

// Theme-aware class helpers
export function themeClass(
  lightClass: string,
  darkClass: string,
  isDark: boolean,
): string {
  return isDark ? darkClass : lightClass;
}

// CSS variable based theme tokens
export const themeTokens = {
  // Backgrounds
  bgPrimary: "var(--admin-bg-primary)",
  bgSecondary: "var(--admin-bg-secondary)",
  bgTertiary: "var(--admin-bg-tertiary)",
  bgAccent: "var(--admin-bg-accent)",
  bgMuted: "var(--admin-bg-muted)",

  // Text
  textPrimary: "var(--admin-text-primary)",
  textSecondary: "var(--admin-text-secondary)",
  textMuted: "var(--admin-text-muted)",
  textAccent: "var(--admin-text-accent)",

  // Borders
  borderPrimary: "var(--admin-border-primary)",
  borderSecondary: "var(--admin-border-secondary)",

  // Interactive
  hoverBg: "var(--admin-hover-bg)",
  activeBg: "var(--admin-active-bg)",

  // Status colors
  success: "var(--admin-success)",
  warning: "var(--admin-warning)",
  error: "var(--admin-error)",
  info: "var(--admin-info)",
} as const;
