import { derived, writable } from "svelte/store";
import type { CalendarView, Event, EventFilter } from "../types/calendar";

// Calendar state
export const currentDate = writable(new Date());
export const calendarView = writable<CalendarView>("month");
export const events = writable<Event[]>([]);
export const isLoading = writable(false);

// Event modal state
export const selectedEvent = writable<Event | null>(null);
export const showEventModal = writable(false);

// Filters
export const eventFilters = writable<EventFilter>({
  tags: [],
  locationType: null,
  beginnerFriendly: null,
  searchTerm: "",
});

// Derived stores
export const filteredEvents = derived(
  [events, eventFilters],
  ([$events, $filters]) => {
    const filtered = $events.filter((event) => {
      // Tag filter
      if ($filters.tags.length > 0) {
        const hasMatchingTag = $filters.tags.some((tag) =>
          event.tags.includes(tag),
        );
        if (!hasMatchingTag) return false;
      }

      // Location type filter
      if (
        $filters.locationType &&
        event.locationType !== $filters.locationType
      ) {
        return false;
      }

      // Beginner friendly filter
      if (
        $filters.beginnerFriendly !== null &&
        event.beginnerFriendly !== $filters.beginnerFriendly
      ) {
        return false;
      }

      // Search term filter
      if ($filters.searchTerm) {
        const searchTerm = $filters.searchTerm.toLowerCase();
        const searchableText = [
          event.title,
          event.description,
          event.locationAddress || "",
          ...event.tags,
        ]
          .join(" ")
          .toLowerCase();

        if (!searchableText.includes(searchTerm)) {
          return false;
        }
      }

      return true;
    });

    return filtered;
  },
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
    window.history.pushState({}, "", "/#/events");
  }
};

// Filter helpers
export const updateFilter = (key: keyof EventFilter, value: any) => {
  eventFilters.update((filters) => ({
    ...filters,
    [key]: value,
  }));
};

export const clearFilters = () => {
  eventFilters.set({
    tags: [],
    locationType: null,
    beginnerFriendly: null,
    searchTerm: "",
  });
};
