import { describe, expect, it } from "vitest";
import type { Event } from "../types/calendar";
import {
  eventOccursOnDay,
  eventOverlapsMonth,
  eventOverlapsRange,
  getEventDayCount,
  getEventDaySegment,
  getEventSegmentsForDay,
  isMultiDayEvent,
} from "./eventDates";

function makeEvent(
  start: string,
  end: string,
  overrides: Partial<Event> = {},
): Event {
  return {
    id: overrides.id ?? 1,
    title: overrides.title ?? "Testevent",
    description: "",
    startDate: new Date(start),
    endDate: new Date(end),
    isAllDay: false,
    timezone: "Europe/Berlin",
    locationType: "physical",
    tags: [],
    beginnerFriendly: false,
    visibility: "public",
    createdAt: new Date("2026-01-01T00:00:00"),
    updatedAt: new Date("2026-01-01T00:00:00"),
    ...overrides,
  };
}

describe("getEventDayCount()", () => {
  it("zählt ein eintägiges Event als einen Tag", () => {
    const event = makeEvent("2026-09-10T18:00:00", "2026-09-10T21:00:00");
    expect(getEventDayCount(event)).toBe(1);
    expect(isMultiDayEvent(event)).toBe(false);
  });

  it("zählt alle Kalendertage eines mehrtägigen Events", () => {
    const event = makeEvent("2026-09-10T18:00:00", "2026-09-13T14:00:00");
    expect(getEventDayCount(event)).toBe(4);
    expect(isMultiDayEvent(event)).toBe(true);
  });

  it("behandelt ein Ende um Mitternacht als exklusiv", () => {
    const event = makeEvent("2026-09-10T00:00:00", "2026-09-11T00:00:00");
    expect(getEventDayCount(event)).toBe(1);
  });

  it("fällt bei ungültigem oder zu frühem Ende auf den Starttag zurück", () => {
    const invalidEnd = makeEvent("2026-09-10T18:00:00", "not-a-date");
    const endBeforeStart = makeEvent(
      "2026-09-10T18:00:00",
      "2026-09-09T18:00:00",
    );
    expect(getEventDayCount(invalidEnd)).toBe(1);
    expect(getEventDayCount(endBeforeStart)).toBe(1);
  });
});

describe("eventOccursOnDay()", () => {
  const event = makeEvent("2026-09-10T18:00:00", "2026-09-13T14:00:00");

  it("erkennt Start-, Zwischen- und Endtag", () => {
    expect(eventOccursOnDay(event, "2026-09-10")).toBe(true);
    expect(eventOccursOnDay(event, "2026-09-11")).toBe(true);
    expect(eventOccursOnDay(event, "2026-09-12")).toBe(true);
    expect(eventOccursOnDay(event, "2026-09-13")).toBe(true);
  });

  it("schließt Tage außerhalb des Zeitraums aus", () => {
    expect(eventOccursOnDay(event, "2026-09-09")).toBe(false);
    expect(eventOccursOnDay(event, "2026-09-14")).toBe(false);
  });
});

describe("eventOverlapsRange() / eventOverlapsMonth()", () => {
  it("erfasst Events, die über eine Monatsgrenze laufen", () => {
    const event = makeEvent("2026-08-31T18:00:00", "2026-09-02T12:00:00");
    expect(eventOverlapsMonth(event, "2026-08-15")).toBe(true);
    expect(eventOverlapsMonth(event, "2026-09-15")).toBe(true);
    expect(eventOverlapsMonth(event, "2026-10-15")).toBe(false);
  });

  it("prüft beliebige Zeiträume inklusive der Randtage", () => {
    const event = makeEvent("2026-09-10T18:00:00", "2026-09-13T14:00:00");
    expect(eventOverlapsRange(event, "2026-09-13", "2026-09-20")).toBe(true);
    expect(eventOverlapsRange(event, "2026-09-01", "2026-09-10")).toBe(true);
    expect(eventOverlapsRange(event, "2026-09-14", "2026-09-20")).toBe(false);
  });
});

describe("getEventDaySegment()", () => {
  const event = makeEvent("2026-09-10T18:00:00", "2026-09-13T14:00:00");

  it("markiert den Starttag", () => {
    const segment = getEventDaySegment(event, "2026-09-10");
    expect(segment).toMatchObject({
      isStart: true,
      isEnd: false,
      isMultiDay: true,
      dayIndex: 1,
      dayCount: 4,
    });
  });

  it("markiert Fortsetzungstage", () => {
    const segment = getEventDaySegment(event, "2026-09-12");
    expect(segment).toMatchObject({
      isStart: false,
      isEnd: false,
      dayIndex: 3,
      dayCount: 4,
    });
  });

  it("markiert den Endtag", () => {
    const segment = getEventDaySegment(event, "2026-09-13");
    expect(segment).toMatchObject({ isStart: false, isEnd: true, dayIndex: 4 });
  });

  it("liefert null für Tage außerhalb des Zeitraums", () => {
    expect(getEventDaySegment(event, "2026-09-14")).toBeNull();
  });
});

describe("getEventSegmentsForDay()", () => {
  it("sortiert mehrtägige Events vor eintägigen", () => {
    const multiDay = makeEvent("2026-09-10T18:00:00", "2026-09-13T14:00:00", {
      id: "multi",
    });
    const singleDay = makeEvent("2026-09-11T09:00:00", "2026-09-11T11:00:00", {
      id: "single",
    });

    const segments = getEventSegmentsForDay(
      [singleDay, multiDay],
      "2026-09-11",
    );

    expect(segments.map((segment) => segment.event.id)).toEqual([
      "multi",
      "single",
    ]);
  });

  it("liefert für jeden Tag des Zeitraums ein Segment", () => {
    const event = makeEvent("2026-09-10T18:00:00", "2026-09-13T14:00:00");
    const days = [
      "2026-09-10",
      "2026-09-11",
      "2026-09-12",
      "2026-09-13",
      "2026-09-14",
    ];

    const found = days.map(
      (day) => getEventSegmentsForDay([event], day).length,
    );

    expect(found).toEqual([1, 1, 1, 1, 0]);
  });
});
