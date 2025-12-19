export interface Event {
  id: string | number;
  title: string;
  description: string;
  startDate: Date;
  endDate: Date;
  isAllDay: boolean;
  timezone: string;
  locationType: "physical" | "online" | "hybrid";
  locationAddress?: string;
  onlineUrl?: string;
  tags: string[];
  beginnerFriendly: boolean;
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
  tags: string[];
  locationType: "physical" | "online" | "hybrid" | null;
  beginnerFriendly: boolean | null;
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
