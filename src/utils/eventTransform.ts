import type { Event } from "../types/calendar";

/**
 * Transform API event data to frontend Event interface
 */
export function transformApiEvent(apiEvent: any): Event {
  return {
    id: apiEvent.id || 0,
    title: apiEvent.title || "",
    description: apiEvent.description || "",
    startDate: new Date(apiEvent.start_datetime || apiEvent.startDate),
    endDate: new Date(apiEvent.end_datetime || apiEvent.endDate),
    isAllDay: apiEvent.is_all_day || false,
    timezone: apiEvent.timezone || "Europe/Berlin",
    locationType:
      apiEvent.location_type === "online"
        ? "online"
        : apiEvent.location_type === "hybrid"
          ? "hybrid"
          : "physical",
    locationAddress: apiEvent.location_address || apiEvent.venue_address || "",
    onlineUrl: apiEvent.location_url || "",
    tags: Array.isArray(apiEvent.tags)
      ? apiEvent.tags
      : typeof apiEvent.tags === "string"
        ? apiEvent.tags.split(",").map((t: string) => t.trim())
        : [],
    beginnerFriendly:
      apiEvent.beginner_friendly ||
      apiEvent.difficulty_level === "beginner" ||
      false,
    visibility: "public" as const,
    seriesId: apiEvent.series_id || undefined,
    rrule: apiEvent.rrule || undefined,
    exdates: apiEvent.exdates
      ? apiEvent.exdates.map((d: string) => new Date(d))
      : [],
    createdAt: new Date(apiEvent.created_at || Date.now()),
    updatedAt: new Date(apiEvent.updated_at || Date.now()),
  };
}

/**
 * Transform array of API events
 */
export function transformApiEvents(apiEvents: any[]): Event[] {
  if (!Array.isArray(apiEvents)) {
    return [];
  }

  return apiEvents.map(transformApiEvent);
}
