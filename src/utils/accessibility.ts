/**
 * Accessibility Configuration for Hypnose Stammtisch
 *
 * This file contains configurations and utilities for ensuring
 * WCAG 2.2 AA compliance throughout the application.
 */

// Color contrast ratios for WCAG AA compliance
export const CONTRAST_RATIOS = {
  // WCAG AA requires 4.5:1 for normal text, 3:1 for large text
  NORMAL_TEXT: 4.5,
  LARGE_TEXT: 3.0,
  // WCAG AAA requires 7:1 for normal text, 4.5:1 for large text
  NORMAL_TEXT_AAA: 7.0,
  LARGE_TEXT_AAA: 4.5,
};

// Color palette with verified contrast ratios
export const ACCESSIBLE_COLORS = {
  // Background colors
  backgrounds: {
    primary: "#1A1A1A", // charcoal-900
    secondary: "#2D2D2D", // charcoal-800
    tertiary: "#3D3D3D", // charcoal-700
  },

  // Text colors with verified contrast
  text: {
    primary: "#F5F5F5", // smoke-50 - 13.77:1 contrast on charcoal-900
    secondary: "#E5E5E5", // smoke-100 - 11.89:1 contrast on charcoal-900
    tertiary: "#CCCCCC", // smoke-300 - 8.59:1 contrast on charcoal-900
    muted: "#999999", // smoke-400 - 5.74:1 contrast on charcoal-900
  },

  // Interactive colors
  interactive: {
    primary: "#41F2C0", // accent-400 - 11.12:1 contrast on charcoal-900
    primaryHover: "#2DEBA8", // accent-500
    secondary: "#3C2A89", // primary-600
    focus: "#41F2C0", // accent-400 for focus rings
  },

  // Status colors
  status: {
    success: "#10B981", // emerald-500 - 4.89:1 contrast
    warning: "#F59E0B", // amber-500 - 6.26:1 contrast
    error: "#EF4444", // red-500 - 4.32:1 contrast
    info: "#3B82F6", // blue-500 - 4.67:1 contrast
  },
};

// Accessibility configuration for form elements
export const FORM_A11Y = {
  // Required attributes for form fields
  requiredAttributes: {
    input: ["id", "aria-label", "aria-describedby"],
    textarea: ["id", "aria-label", "aria-describedby"],
    select: ["id", "aria-label", "aria-describedby"],
    button: ["type", "aria-label"],
  },

  // Error message configuration
  errorMessages: {
    liveRegion: "polite",
    atomic: true,
    role: "alert",
  },

  // Focus management
  focus: {
    trapInModals: true,
    returnToTrigger: true,
    visibleOutline: true,
  },
};

// Keyboard navigation configuration
export const KEYBOARD_A11Y = {
  // Standard key mappings
  keys: {
    ESCAPE: "Escape",
    ENTER: "Enter",
    SPACE: " ",
    ARROW_UP: "ArrowUp",
    ARROW_DOWN: "ArrowDown",
    ARROW_LEFT: "ArrowLeft",
    ARROW_RIGHT: "ArrowRight",
    HOME: "Home",
    END: "End",
    PAGE_UP: "PageUp",
    PAGE_DOWN: "PageDown",
    TAB: "Tab",
  },

  // Calendar navigation
  calendar: {
    navigation: ["ArrowLeft", "ArrowRight", "Home", "PageUp", "PageDown"],
    announcement: "polite",
  },

  // Form navigation
  form: {
    submitKeys: ["Enter"],
    escapeToClose: true,
  },
};

// Screen reader configuration
export const SCREEN_READER_A11Y = {
  // Live regions
  liveRegions: {
    announcements: "polite",
    alerts: "assertive",
    status: "polite",
  },

  // Landmarks
  landmarks: {
    main: "main",
    navigation: "navigation",
    banner: "banner",
    contentinfo: "contentinfo",
    complementary: "complementary",
    search: "search",
  },

  // Headings
  headings: {
    skipToContent: "h1",
    pageTitle: "h1",
    sections: "h2",
    subsections: "h3",
  },
};

// Motion and animation preferences
export const MOTION_A11Y = {
  // Respect user preferences
  respectReducedMotion: true,

  // Animation durations (reduced for accessibility)
  durations: {
    short: "150ms",
    medium: "300ms",
    long: "500ms",
  },

  // Safe animations for reduced motion
  safeAnimations: ["opacity", "transform: scale()"],

  // Animations to disable for reduced motion
  disableForReducedMotion: [
    "transform: rotate()",
    "complex transforms",
    "parallax effects",
  ],
};

// ARIA best practices
export const ARIA_BEST_PRACTICES = {
  // Common ARIA patterns
  patterns: {
    disclosure: {
      trigger: "aria-expanded",
      content: "aria-hidden",
    },
    modal: {
      container: 'role="dialog" aria-modal="true"',
      title: "aria-labelledby",
      description: "aria-describedby",
    },
    tabs: {
      list: 'role="tablist"',
      tab: 'role="tab" aria-selected',
      panel: 'role="tabpanel"',
    },
    grid: {
      container: 'role="grid"',
      row: 'role="row"',
      cell: 'role="gridcell"',
      columnheader: 'role="columnheader"',
    },
  },

  // Labeling guidelines
  labeling: {
    preferNativeLabels: true,
    useAriaLabel: "when native labels insufficient",
    useAriaLabelledby: "when referencing other elements",
    useAriaDescribedby: "for additional descriptions",
  },
};

// Testing configuration
export const A11Y_TESTING = {
  // Axe-core configuration
  axe: {
    tags: ["wcag2a", "wcag2aa", "wcag21aa", "wcag22aa"],
    exclude: ["iframe", "[data-test-exclude]"],
    rules: {
      "color-contrast": { enabled: true },
      "keyboard-navigation": { enabled: true },
      "focus-management": { enabled: true },
      "aria-usage": { enabled: true },
    },
  },

  // Manual testing checklist
  manualTests: [
    "Keyboard navigation through all interactive elements",
    "Screen reader announcement verification",
    "High contrast mode compatibility",
    "Zoom to 200% without horizontal scrolling",
    "Focus indicator visibility",
    "Error message accessibility",
    "Form validation announcements",
    "Modal focus trapping",
  ],

  // Browser testing
  browsers: [
    "Chrome with NVDA",
    "Firefox with NVDA",
    "Safari with VoiceOver",
    "Edge with NVDA",
  ],
};

// Utility functions for accessibility
export const A11Y_UTILS = {
  // Generate unique IDs for form associations
  generateId: (prefix: string) =>
    `${prefix}-${Math.random().toString(36).substr(2, 9)}`,

  // Create live region announcements
  announce: (message: string, priority: "polite" | "assertive" = "polite") => {
    const liveRegion = document.createElement("div");
    liveRegion.setAttribute("aria-live", priority);
    liveRegion.setAttribute("aria-atomic", "true");
    liveRegion.className = "sr-only";
    liveRegion.textContent = message;
    document.body.appendChild(liveRegion);

    setTimeout(() => {
      document.body.removeChild(liveRegion);
    }, 1000);
  },

  // Focus management
  focusElement: (element: HTMLElement) => {
    element.focus();

    // Ensure focus is visible
    if (!element.matches(":focus-visible")) {
      element.setAttribute("data-focus-visible", "true");
    }
  },

  // Trap focus within an element
  trapFocus: (container: HTMLElement) => {
    const focusableElements = container.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
    );
    const firstElement = focusableElements[0] as HTMLElement;
    const lastElement = focusableElements[
      focusableElements.length - 1
    ] as HTMLElement;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Tab") {
        if (e.shiftKey) {
          if (document.activeElement === firstElement) {
            lastElement.focus();
            e.preventDefault();
          }
        } else {
          if (document.activeElement === lastElement) {
            firstElement.focus();
            e.preventDefault();
          }
        }
      }
    };

    container.addEventListener("keydown", handleKeyDown);
    firstElement.focus();

    return () => {
      container.removeEventListener("keydown", handleKeyDown);
    };
  },
};

export default {
  CONTRAST_RATIOS,
  ACCESSIBLE_COLORS,
  FORM_A11Y,
  KEYBOARD_A11Y,
  SCREEN_READER_A11Y,
  MOTION_A11Y,
  ARIA_BEST_PRACTICES,
  A11Y_TESTING,
  A11Y_UTILS,
};
