import { describe, expect, it } from "vitest";
import type { Event, EventFilter } from "../types/calendar";
import {
  collectTagStats,
  countActiveFilters,
  createDefaultFilter,
  filterEvents,
  filterFromSearchParams,
  filterToSearchParams,
  getEventWeekdays,
  parseSearchQuery,
} from "./eventFilters";

const makeEvent = (overrides: Partial<Event> = {}): Event => ({
  id: overrides.id ?? "1",
  title: "Stammtisch Berlin",
  description: "Gemütliches Treffen",
  // Mittwoch, 11.11.2026, 19:00 lokale Zeit
  startDate: new Date(2026, 10, 11, 19, 0),
  endDate: new Date(2026, 10, 11, 22, 0),
  isAllDay: false,
  timezone: "Europe/Berlin",
  locationType: "physical",
  tags: [],
  beginnerFriendly: false,
  visibility: "public",
  createdAt: new Date(),
  updatedAt: new Date(),
  ...overrides,
});

const filterWith = (overrides: Partial<EventFilter>): EventFilter => ({
  ...createDefaultFilter(),
  ...overrides,
});

const ids = (events: Event[]) => events.map((e) => e.id);

describe("eventFilters", () => {
  const events = [
    makeEvent({ id: "a", tags: ["Anfänger", "Praxis"], category: "workshop" }),
    makeEvent({
      id: "b",
      tags: ["praxis"],
      locationType: "online",
      title: "Online Übungsabend",
      category: "practice",
      difficultyLevel: "advanced",
    }),
    makeEvent({
      id: "c",
      tags: ["Theorie"],
      locationType: "hybrid",
      difficultyLevel: "all",
      startDate: new Date(2026, 10, 14, 10, 0), // Samstag Vormittag
      endDate: new Date(2026, 10, 14, 12, 0),
      isCancelled: true,
    }),
  ];

  it("returns everything with the default filter", () => {
    expect(filterEvents(events, createDefaultFilter())).toHaveLength(3);
  });

  it("matches tags case- and accent-insensitively", () => {
    expect(ids(filterEvents(events, filterWith({ tags: ["PRAXIS"] })))).toEqual(
      ["a", "b"],
    );
    expect(
      ids(filterEvents(events, filterWith({ tags: ["anfanger"] }))),
    ).toEqual(["a"]);
  });

  it("supports any/all tag matching and excluded tags", () => {
    const tags = ["praxis", "anfänger"];
    expect(ids(filterEvents(events, filterWith({ tags })))).toEqual(["a", "b"]);
    expect(
      ids(filterEvents(events, filterWith({ tags, tagMatchMode: "all" }))),
    ).toEqual(["a"]);
    expect(
      ids(filterEvents(events, filterWith({ excludedTags: ["Praxis"] }))),
    ).toEqual(["c"]);
  });

  it("filters by multiple location types and categories", () => {
    expect(
      ids(
        filterEvents(
          events,
          filterWith({ locationTypes: ["online", "hybrid"] }),
        ),
      ),
    ).toEqual(["b", "c"]);
    expect(
      ids(filterEvents(events, filterWith({ categories: ["practice"] }))),
    ).toEqual(["b"]);
  });

  it("treats 'all levels' events as matching every difficulty", () => {
    expect(
      ids(filterEvents(events, filterWith({ difficultyLevels: ["beginner"] }))),
    ).toEqual(["a", "c"]);
  });

  it("filters by weekday and time of day", () => {
    expect(ids(filterEvents(events, filterWith({ weekdays: [6, 0] })))).toEqual(
      ["c"],
    );
    expect(
      ids(filterEvents(events, filterWith({ timesOfDay: ["evening"] }))),
    ).toEqual(["a", "b"]);
  });

  it("counts every weekday a multi-day event covers", () => {
    const weekend = makeEvent({
      startDate: new Date(2026, 10, 13, 18, 0), // Freitag
      endDate: new Date(2026, 10, 15, 14, 0), // Sonntag
    });
    expect([...getEventWeekdays(weekend)].sort()).toEqual([0, 5, 6]);
  });

  it("hides past and cancelled events on request", () => {
    const now = new Date(2026, 10, 12);
    expect(
      ids(filterEvents(events, filterWith({ onlyUpcoming: true }), now)),
    ).toEqual(["c"]);
    expect(
      ids(filterEvents(events, filterWith({ hideCancelled: true }))),
    ).toEqual(["a", "b"]);
  });

  it("supports multi-term, phrase and negated search", () => {
    expect(parseSearchQuery('online -"berlin mitte" Übung')).toEqual({
      include: ["online", "ubung"],
      exclude: ["berlin mitte"],
    });
    expect(
      ids(filterEvents(events, filterWith({ searchTerm: "online übung" }))),
    ).toEqual(["b"]);
    expect(
      ids(filterEvents(events, filterWith({ searchTerm: "-online" }))),
    ).toEqual(["a", "c"]);
    // Kategorien sind über ihren deutschen Namen auffindbar
    expect(
      ids(filterEvents(events, filterWith({ searchTerm: "workshop" }))),
    ).toEqual(["a"]);
  });

  it("collects tag stats merging spelling variants", () => {
    const stats = collectTagStats(events);
    expect(stats[0]).toMatchObject({ key: "praxis", count: 2 });
    expect(stats.map((s) => s.key)).toEqual(["praxis", "anfanger", "theorie"]);
  });

  it("round-trips filters through URL parameters", () => {
    const filter = filterWith({
      searchTerm: "hypnose",
      tags: ["praxis", "theorie"],
      tagMatchMode: "all",
      excludedTags: ["online"],
      locationTypes: ["physical"],
      categories: ["workshop"],
      difficultyLevels: ["beginner"],
      beginnerFriendly: true,
      weekdays: [1, 5],
      timesOfDay: ["evening"],
      onlyUpcoming: true,
      hideCancelled: true,
    });
    const params = filterToSearchParams(filter);
    expect(
      filterFromSearchParams(new URLSearchParams(params.toString())),
    ).toEqual(filter);
    expect(countActiveFilters(filter)).toBe(13);
  });

  it("ignores invalid URL values", () => {
    const filter = filterFromSearchParams(
      new URLSearchParams("ort=moon,online&tage=9,2&zeit=night"),
    );
    expect(filter.locationTypes).toEqual(["online"]);
    expect(filter.weekdays).toEqual([2]);
    expect(filter.timesOfDay).toEqual([]);
  });
});
