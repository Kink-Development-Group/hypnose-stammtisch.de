import dayjs, { type Dayjs } from "dayjs";
import type { Event } from "../types/calendar";

/**
 * Segment eines Events an einem konkreten Kalendertag.
 *
 * Mehrtägige Events belegen mehrere Tageszellen. Damit alle Ansichten dieselbe
 * fortlaufende Markierung rendern können, beschreibt ein Segment, an welcher
 * Position innerhalb des Zeitraums der jeweilige Tag liegt.
 */
export interface EventDaySegment {
  event: Event;
  /** Tag, für den das Segment berechnet wurde. */
  date: Date;
  /** Erster Tag des Events. */
  isStart: boolean;
  /** Letzter Tag des Events. */
  isEnd: boolean;
  /** Event erstreckt sich über mehr als einen Kalendertag. */
  isMultiDay: boolean;
  /** 1-basierte Position des Tages innerhalb des Zeitraums. */
  dayIndex: number;
  /** Gesamtzahl der Tage, die das Event belegt. */
  dayCount: number;
}

/**
 * Ende eines Events als Kalendertag.
 *
 * Endet ein Event exakt um Mitternacht, gehört der Folgetag inhaltlich nicht
 * mehr dazu (typisch für ganztägige Events mit exklusivem Enddatum). Ein
 * fehlendes oder ungültiges Enddatum fällt auf den Starttag zurück.
 */
function resolveEndDay(event: Event): Dayjs {
  const start = dayjs(event.startDate).startOf("day");
  // `dayjs(undefined)` ist *jetzt*, nicht Invalid Date: ohne die Prüfung auf
  // einen gesetzten Wert liefe ein Event ohne Enddatum vom Starttag bis heute.
  const end = event.endDate ? dayjs(event.endDate) : null;

  if (!end || !end.isValid() || end.isBefore(event.startDate)) {
    return start;
  }

  const endDay =
    end.hour() === 0 && end.minute() === 0 && end.second() === 0
      ? end.subtract(1, "millisecond").startOf("day")
      : end.startOf("day");

  return endDay.isBefore(start) ? start : endDay;
}

/**
 * Kalendertage, die ein Event belegt – inklusive Start- und Endtag.
 */
export function getEventDayRange(event: Event): { start: Dayjs; end: Dayjs } {
  return {
    start: dayjs(event.startDate).startOf("day"),
    end: resolveEndDay(event),
  };
}

/**
 * Anzahl der Kalendertage, über die sich ein Event erstreckt (mindestens 1).
 */
export function getEventDayCount(event: Event): number {
  const { start, end } = getEventDayRange(event);
  return end.diff(start, "day") + 1;
}

/**
 * Läuft das Event über mehr als einen Kalendertag?
 */
export function isMultiDayEvent(event: Event): boolean {
  return getEventDayCount(event) > 1;
}

/**
 * Läuft das Event an dem übergebenen Kalendertag?
 */
export function eventOccursOnDay(
  event: Event,
  day: Dayjs | Date | string,
): boolean {
  const target = dayjs(day).startOf("day");
  const { start, end } = getEventDayRange(event);

  return !target.isBefore(start) && !target.isAfter(end);
}

/**
 * Überschneidet sich das Event mit dem (inklusiven) Zeitraum?
 */
export function eventOverlapsRange(
  event: Event,
  rangeStart: Dayjs | Date | string,
  rangeEnd: Dayjs | Date | string,
): boolean {
  const from = dayjs(rangeStart).startOf("day");
  const to = dayjs(rangeEnd).endOf("day");
  const { start, end } = getEventDayRange(event);

  return !start.isAfter(to) && !end.isBefore(from);
}

/**
 * Überschneidet sich das Event mit dem Monat des übergebenen Datums?
 */
export function eventOverlapsMonth(
  event: Event,
  monthDate: Dayjs | Date | string,
): boolean {
  const month = dayjs(monthDate);
  return eventOverlapsRange(
    event,
    month.startOf("month"),
    month.endOf("month"),
  );
}

/**
 * Segment eines Events für einen Kalendertag – oder `null`, wenn das Event an
 * diesem Tag nicht läuft.
 */
export function getEventDaySegment(
  event: Event,
  day: Dayjs | Date | string,
): EventDaySegment | null {
  const target = dayjs(day).startOf("day");
  const { start, end } = getEventDayRange(event);

  if (target.isBefore(start) || target.isAfter(end)) {
    return null;
  }

  const dayCount = end.diff(start, "day") + 1;

  return {
    event,
    date: target.toDate(),
    isStart: target.isSame(start, "day"),
    isEnd: target.isSame(end, "day"),
    isMultiDay: dayCount > 1,
    dayIndex: target.diff(start, "day") + 1,
    dayCount,
  };
}

/**
 * Alle Segmente, die an einem Kalendertag laufen.
 *
 * Sortierung: mehrtägige Events zuerst, danach nach Startzeitpunkt und Dauer.
 * Dadurch stehen fortlaufende Markierungen in benachbarten Tageszellen auf
 * derselben Zeile und ergeben einen durchgehenden Balken.
 */
export function getEventSegmentsForDay(
  events: Event[],
  day: Dayjs | Date | string,
): EventDaySegment[] {
  return events
    .map((event) => getEventDaySegment(event, day))
    .filter((segment): segment is EventDaySegment => segment !== null)
    .sort((a, b) => {
      if (a.isMultiDay !== b.isMultiDay) return a.isMultiDay ? -1 : 1;
      if (a.dayCount !== b.dayCount) return b.dayCount - a.dayCount;
      return a.event.startDate.getTime() - b.event.startDate.getTime();
    });
}
