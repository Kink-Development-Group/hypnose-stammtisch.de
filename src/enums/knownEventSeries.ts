/**
 * Publication status of a known event series ("Bekannte Event-Reihen").
 */
export enum KnownEventSeriesStatus {
  DRAFT = "draft",
  PUBLISHED = "published",
  ARCHIVED = "archived",
}

/**
 * Where the "next date" on a series card comes from.
 *
 * - `AUTO`: computed from the linked recurring series, so it cannot go stale
 * - `MANUAL`: a text typed by an admin, for series we do not run ourselves
 * - `NONE`: the card shows no date at all
 */
export enum NextEventSource {
  AUTO = "auto",
  MANUAL = "manual",
  NONE = "none",
}

/**
 * Status values the home page shows.
 */
export const PUBLIC_KNOWN_EVENT_SERIES_STATUSES: readonly KnownEventSeriesStatus[] =
  [KnownEventSeriesStatus.PUBLISHED];
