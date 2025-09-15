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
    tags: (() => {
      let result: string[] = [];
      if (Array.isArray(apiEvent.tags)) {
        result = (apiEvent.tags as string[]).filter(
          (t: string) => typeof t === "string" && t.trim() !== "",
        );
      } else if (typeof apiEvent.tags === "string") {
        try {
          const arr = JSON.parse(apiEvent.tags);
          if (Array.isArray(arr)) {
            result = arr.filter(
              (t: string) => typeof t === "string" && t.trim() !== "",
            );
          }
        } catch (e) {
          console.error(
            "Error parsing event tags:",
            e && e.message ? e.message : String(e),
          );
        }
        if (apiEvent.tags.trim() === "" || apiEvent.tags.trim() === "[]") {
          result = [];
        }
      }
      // Fallback: falls result kein Array ist, setze leeres Array
      if (!Array.isArray(result)) {
        result = [];
      }
      return result;
    })(),
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
    instanceDate: apiEvent.instance_date
      ? new Date(apiEvent.instance_date)
      : undefined,
    overrideType: apiEvent.override_type || undefined,
    cancellationReason: apiEvent.cancellation_reason || undefined,
    isCancelled: (apiEvent.override_type || apiEvent.status) === "cancelled",
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
