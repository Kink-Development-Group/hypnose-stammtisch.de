export type LocationType = "physical" | "online" | "hybrid";
export type EventCategory =
  "workshop" | "stammtisch" | "practice" | "lecture" | "special";
export type DifficultyLevel = "beginner" | "intermediate" | "advanced" | "all";
export type TimeOfDay = "morning" | "afternoon" | "evening";
export type TagMatchMode = "any" | "all";

export interface Event {
  id: string | number;
  title: string;
  description: string;
  startDate: Date;
  endDate: Date;
  isAllDay: boolean;
  timezone: string;
  locationType: "physical" | "online" | "hybrid";
  locationName?: string;
  locationAddress?: string;
  onlineUrl?: string;
  tags: string[];
  beginnerFriendly: boolean;
  category?: EventCategory;
  difficultyLevel?: DifficultyLevel;
  organizerName?: string;
  visibility: "public" | "private";
  seriesId?: string | number;
  rrule?: string;
  exdates?: Date[];
  createdAt: Date;
  updatedAt: Date;
  instanceDate?: Date; // für einzelne Serien-Instanz
  overrideType?: "changed" | "cancelled";
  cancellationReason?: string;
  isCancelled?: boolean; // abgeleitet
}

export interface EventSeries {
  id: string | number;
  rrule: string;
  dtstart: Date;
  until?: Date;
  exdates: Date[];
  timezone: string;
  titleDefault: string;
  descriptionDefault?: string;
  visibilityDefault: "public" | "private";
  createdAt: Date;
  updatedAt: Date;
}

export interface EventFilter {
  /** Tags, die ein Event tragen muss (Vergleich ohne Groß-/Kleinschreibung). */
  tags: string[];
  /** "any": mindestens ein Tag passt, "all": alle gewählten Tags müssen passen. */
  tagMatchMode: TagMatchMode;
  /** Tags, die ein Event nicht tragen darf. */
  excludedTags: string[];
  /** Leer = alle Veranstaltungsorte. */
  locationTypes: LocationType[];
  /** Leer = alle Kategorien. */
  categories: EventCategory[];
  /** Leer = alle Erfahrungsstufen. */
  difficultyLevels: DifficultyLevel[];
  beginnerFriendly: boolean | null;
  /** Wochentage nach Date#getDay() (0 = Sonntag). Leer = alle. */
  weekdays: number[];
  /** Leer = ganztägig/alle Uhrzeiten. */
  timesOfDay: TimeOfDay[];
  /** Bereits beendete Events ausblenden. */
  onlyUpcoming: boolean;
  /** Abgesagte Events ausblenden. */
  hideCancelled: boolean;
  searchTerm: string;
}

export type CalendarView = "month" | "week" | "list";

export interface CalendarDay {
  date: Date;
  isCurrentMonth: boolean;
  isToday: boolean;
  events: Event[];
}

export interface CalendarWeek {
  days: CalendarDay[];
  weekNumber: number;
}

export interface CalendarToken {
  id: number;
  userId?: number;
  token: string;
  active: boolean;
  createdAt: Date;
}

// Form types
export interface EventSubmission {
  title: string;
  description: string;
  startDate: string;
  endDate: string;
  isAllDay: boolean;
  timezone: string;
  locationType: "physical" | "online" | "hybrid";
  locationAddress?: string;
  onlineUrl?: string;
  tags: string[];
  beginnerFriendly: boolean;
  rrule?: string;
  consent: {
    dataProcessing: boolean;
    publicDisplay: boolean;
    photography: boolean;
  };
}

export interface ContactForm {
  name: string;
  email: string;
  message: string;
  privacyConsent: boolean;
}
