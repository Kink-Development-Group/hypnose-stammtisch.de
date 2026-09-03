import type {
  KnownEventSeriesStatus,
  NextEventSource,
} from "../enums/knownEventSeries";

/**
 * The next date shown on a series card.
 *
 * `text` carries a manually typed date, `datetime` an ISO timestamp the backend
 * computed from the linked recurring series. Both are null when the card shows
 * no date.
 */
export interface KnownEventSeriesNextEvent {
  source: NextEventSource;
  text: string | null;
  datetime: string | null;
}

/**
 * A series as the public home page receives it — the next date already
 * resolved, no editorial metadata.
 */
export interface PublicKnownEventSeries {
  id: string;
  title: string;
  slug: string;
  location: string;
  frequency: string;
  description: string;
  formats: string[];
  price: string | null;
  tags: string[];
  detailUrl: string;
  nextEvent: KnownEventSeriesNextEvent;
}

/**
 * A series as the admin area receives it, including drafts and the fields that
 * decide where the next date comes from.
 */
export interface AdminKnownEventSeries {
  id: string;
  title: string;
  slug: string;
  location: string;
  frequency: string;
  description: string;
  formats: string[];
  price: string | null;
  tags: string[];
  detailUrl: string;
  nextEventSource: NextEventSource;
  nextEventText: string | null;
  linkedSeriesId: string | null;
  linkedSeriesTitle: string | null;
  sortOrder: number;
  status: KnownEventSeriesStatus;
  isActive: boolean;
  createdBy: number | null;
  createdByUsername: string | null;
  createdAt: string | null;
  lastUpdated: string | null;
}

/**
 * A recurring series an entry can link its next date to.
 */
export interface LinkableEventSeries {
  id: string;
  title: string;
  status: string;
}

/**
 * Payload of the admin form, matching the snake_case API contract.
 */
export interface KnownEventSeriesFormData {
  title: string;
  location: string;
  frequency: string;
  description: string;
  formats: string[];
  price: string;
  tags: string[];
  detail_url: string;
  next_event_source: NextEventSource;
  next_event_text: string;
  linked_series_id: string;
  status: KnownEventSeriesStatus;
  is_active: boolean;
}
