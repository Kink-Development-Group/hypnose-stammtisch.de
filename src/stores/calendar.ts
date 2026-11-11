import { derived, writable } from "svelte/store";
import type { CalendarView, Event, EventFilter } from "../types/calendar";
import {
  collectTagStats,
  countActiveFilters,
  createDefaultFilter,
  filterEvents,
  filterFromSearchParams,
  filterToSearchParams,
} from "../utils/eventFilters";

// Calendar state
export const currentDate = writable(new Date());
export const calendarView = writable<CalendarView>("month");
export const events = writable<Event[]>([]);
export const isLoading = writable(false);

// Event modal state
export const selectedEvent = writable<Event | null>(null);
export const showEventModal = writable(false);

// Filters
export const eventFilters = writable<EventFilter>(createDefaultFilter());

// Derived stores
export const filteredEvents = derived(
  [events, eventFilters],
  ([$events, $filters]) => filterEvents($events, $filters),
);

/** Tags aller geladenen Events inkl. Häufigkeit (für die Tag-Auswahl). */
export const availableEventTags = derived(events, ($events) =>
  collectTagStats($events),
);

export const activeFilterCount = derived(eventFilters, ($filters) =>
  countActiveFilters($filters),
);

// Calendar navigation helpers
export const navigateCalendar = (direction: "prev" | "next") => {
  currentDate.update((date) => {
    const newDate = new Date(date);

    calendarView.subscribe((view) => {
      switch (view) {
        case "month":
          newDate.setMonth(
            newDate.getMonth() + (direction === "next" ? 1 : -1),
          );
          break;
        case "week":
          newDate.setDate(newDate.getDate() + (direction === "next" ? 7 : -7));
          break;
        case "list":
          // For list view, navigate by month
          newDate.setMonth(
            newDate.getMonth() + (direction === "next" ? 1 : -1),
          );
          break;
      }
    })();

    return newDate;
  });
};

// Event modal helpers
export const openEventModal = (event: Event) => {
  selectedEvent.set(event);
  showEventModal.set(true);

  // Update URL for deep linking (using hash-based routing)
  if (typeof window !== "undefined") {
    const newHash = `#/events/${event.id}`;
    window.history.pushState({}, "", `/${newHash}`);
  }
};

export const closeEventModal = () => {
  selectedEvent.set(null);
  showEventModal.set(false);

  // Restore URL (using hash-based routing)
  if (typeof window !== "undefined") {
    window.history.pushState({}, "", `/#/events${getFilterQueryString()}`);
  }
};

// Filter helpers
export const updateFilter = <K extends keyof EventFilter>(
  key: K,
  value: EventFilter[K],
) => {
  eventFilters.update((filters) => ({
    ...filters,
    [key]: value,
  }));
};

export const clearFilters = () => {
  eventFilters.set(createDefaultFilter());
};

// URL-Synchronisierung (Hash-Routing: #/events?tags=...)
const getFilterQueryString = (): string => {
  let query = "";
  eventFilters.subscribe((filters) => {
    query = filterToSearchParams(filters).toString();
  })();
  return query ? `?${query}` : "";
};

/** Liest Filter aus der aktuellen URL (z. B. geteilter Link). */
export const loadFiltersFromUrl = (): boolean => {
  if (typeof window === "undefined") return false;
  const hash = window.location.hash;
  const queryIndex = hash.indexOf("?");
  if (queryIndex === -1) return false;
  const params = new URLSearchParams(hash.slice(queryIndex + 1));
  const filter = filterFromSearchParams(params);
  eventFilters.set(filter);
  return true;
};

/** Schreibt die aktiven Filter in die URL, ohne einen History-Eintrag anzulegen. */
export const writeFiltersToUrl = (filters: EventFilter): void => {
  if (typeof window === "undefined") return;
  const hash = window.location.hash;
  const path = hash.split("?")[0];
  // Nur auf der Event-Übersicht, nicht bei geöffnetem Event-Deep-Link.
  if (path !== "#/events" && path !== "#/events/") return;
  const query = filterToSearchParams(filters).toString();
  const nextHash = `#/events${query ? `?${query}` : ""}`;
  if (nextHash !== hash) {
    window.history.replaceState(window.history.state, "", `/${nextHash}`);
  }
};
