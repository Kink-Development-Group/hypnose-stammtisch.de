import type {
  DifficultyLevel,
  Event,
  EventCategory,
  EventFilter,
  LocationType,
  TagMatchMode,
  TimeOfDay,
} from "../types/calendar";

export const LOCATION_TYPE_OPTIONS: { value: LocationType; label: string }[] = [
  { value: "physical", label: "Vor Ort" },
  { value: "online", label: "Online" },
  { value: "hybrid", label: "Hybrid" },
];

export const CATEGORY_OPTIONS: { value: EventCategory; label: string }[] = [
  { value: "stammtisch", label: "Stammtisch" },
  { value: "workshop", label: "Workshop" },
  { value: "practice", label: "Übungsabend" },
  { value: "lecture", label: "Vortrag" },
  { value: "special", label: "Special" },
];

export const DIFFICULTY_OPTIONS: { value: DifficultyLevel; label: string }[] = [
  { value: "all", label: "Alle Stufen" },
  { value: "beginner", label: "Einsteiger" },
  { value: "intermediate", label: "Mittel" },
  { value: "advanced", label: "Fortgeschritten" },
];

/** Montag zuerst, Werte nach Date#getDay(). */
export const WEEKDAY_OPTIONS: {
  value: number;
  label: string;
  short: string;
}[] = [
  { value: 1, label: "Montag", short: "Mo" },
  { value: 2, label: "Dienstag", short: "Di" },
  { value: 3, label: "Mittwoch", short: "Mi" },
  { value: 4, label: "Donnerstag", short: "Do" },
  { value: 5, label: "Freitag", short: "Fr" },
  { value: 6, label: "Samstag", short: "Sa" },
  { value: 0, label: "Sonntag", short: "So" },
];

export const TIME_OF_DAY_OPTIONS: {
  value: TimeOfDay;
  label: string;
  hint: string;
}[] = [
  { value: "morning", label: "Vormittags", hint: "vor 12 Uhr" },
  { value: "afternoon", label: "Nachmittags", hint: "12–17 Uhr" },
  { value: "evening", label: "Abends", hint: "ab 17 Uhr" },
];

export const createDefaultFilter = (): EventFilter => ({
  tags: [],
  tagMatchMode: "any",
  excludedTags: [],
  locationTypes: [],
  categories: [],
  difficultyLevels: [],
  beginnerFriendly: null,
  weekdays: [],
  timesOfDay: [],
  onlyUpcoming: false,
  hideCancelled: false,
  searchTerm: "",
});

/**
 * Kleinschreibung, Umlaute/Akzente entfernen und Leerraum normalisieren, damit
 * z. B. "Anfanger", "anfänger" und "ANFÄNGER" gleich behandelt werden.
 */
export const normalizeText = (value: string): string =>
  value
    .toLocaleLowerCase("de-DE")
    .replace(/ß/g, "ss")
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .replace(/\s+/g, " ")
    .trim();

export const normalizeTag = (tag: string): string =>
  normalizeText(tag.replace(/^#/, ""));

export const getTimeOfDay = (date: Date): TimeOfDay => {
  const hour = date.getHours();
  if (hour < 12) return "morning";
  if (hour < 17) return "afternoon";
  return "evening";
};

const DAY_MS = 24 * 60 * 60 * 1000;

/** Alle Wochentage, die ein (ggf. mehrtägiges) Event berührt. */
export const getEventWeekdays = (event: Event): Set<number> => {
  const days = new Set<number>();
  const start = new Date(event.startDate);
  const end = new Date(event.endDate);
  if (Number.isNaN(start.getTime())) return days;

  const cursor = new Date(
    start.getFullYear(),
    start.getMonth(),
    start.getDate(),
  );
  const last = Number.isNaN(end.getTime()) || end < start ? start : end;
  // Endet ein Event exakt um Mitternacht, zählt der Folgetag nicht mit.
  const lastInclusive = new Date(last.getTime() - (last > start ? 1 : 0));

  for (let i = 0; i < 7 && cursor <= lastInclusive; i++) {
    days.add(cursor.getDay());
    cursor.setTime(cursor.getTime() + DAY_MS);
    cursor.setHours(0, 0, 0, 0);
  }
  if (days.size === 0) days.add(start.getDay());
  return days;
};

const matchesTags = (
  eventTags: Set<string>,
  wanted: string[],
  mode: TagMatchMode,
): boolean => {
  if (wanted.length === 0) return true;
  const normalized = wanted.map(normalizeTag);
  return mode === "all"
    ? normalized.every((tag) => eventTags.has(tag))
    : normalized.some((tag) => eventTags.has(tag));
};

const getSearchText = (event: Event): string =>
  normalizeText(
    [
      event.title,
      event.description,
      event.locationName ?? "",
      event.locationAddress ?? "",
      event.organizerName ?? "",
      event.category
        ? (CATEGORY_OPTIONS.find((c) => c.value === event.category)?.label ??
          event.category)
        : "",
      ...event.tags,
    ].join(" "),
  );

/**
 * Prüft ein Event gegen alle Filterkriterien. Die Suche unterstützt mehrere
 * Begriffe (alle müssen vorkommen), Phrasen in Anführungszeichen und
 * Ausschlüsse mit vorangestelltem "-" (z. B. `workshop -online`).
 */
export const matchesEventFilter = (
  event: Event,
  filter: EventFilter,
  now: Date = new Date(),
): boolean => {
  const eventTags = new Set(event.tags.map(normalizeTag));

  if (!matchesTags(eventTags, filter.tags, filter.tagMatchMode)) return false;

  if (
    filter.excludedTags.length > 0 &&
    filter.excludedTags.some((tag) => eventTags.has(normalizeTag(tag)))
  ) {
    return false;
  }

  if (
    filter.locationTypes.length > 0 &&
    !filter.locationTypes.includes(event.locationType)
  ) {
    return false;
  }

  if (filter.categories.length > 0) {
    if (!event.category || !filter.categories.includes(event.category)) {
      return false;
    }
  }

  if (filter.difficultyLevels.length > 0) {
    const level = event.difficultyLevel ?? "all";
    // Events für "Alle Stufen" passen zu jeder gewählten Stufe.
    if (level !== "all" && !filter.difficultyLevels.includes(level)) {
      return false;
    }
  }

  if (
    filter.beginnerFriendly !== null &&
    event.beginnerFriendly !== filter.beginnerFriendly
  ) {
    return false;
  }

  if (filter.weekdays.length > 0) {
    const days = getEventWeekdays(event);
    if (!filter.weekdays.some((day) => days.has(day))) return false;
  }

  if (filter.timesOfDay.length > 0 && !event.isAllDay) {
    if (!filter.timesOfDay.includes(getTimeOfDay(new Date(event.startDate)))) {
      return false;
    }
  }

  if (filter.onlyUpcoming && new Date(event.endDate).getTime() < now.getTime())
    return false;

  if (filter.hideCancelled && event.isCancelled) return false;

  const query = parseSearchQuery(filter.searchTerm);
  if (query.include.length > 0 || query.exclude.length > 0) {
    const text = getSearchText(event);
    if (!query.include.every((term) => text.includes(term))) return false;
    if (query.exclude.some((term) => text.includes(term))) return false;
  }

  return true;
};

export const filterEvents = (
  events: Event[],
  filter: EventFilter,
  now: Date = new Date(),
): Event[] => events.filter((event) => matchesEventFilter(event, filter, now));

export interface SearchQuery {
  include: string[];
  exclude: string[];
}

export const parseSearchQuery = (raw: string): SearchQuery => {
  const include: string[] = [];
  const exclude: string[] = [];
  const pattern = /(-?)"([^"]+)"|(-?)(\S+)/g;
  let match: RegExpExecArray | null;
  while ((match = pattern.exec(raw ?? "")) !== null) {
    const negated = (match[1] || match[3]) === "-";
    const term = normalizeText(match[2] ?? match[4] ?? "");
    if (!term) continue;
    (negated ? exclude : include).push(term);
  }
  return { include, exclude };
};

export interface TagStat {
  /** Anzeigename (häufigste Schreibweise). */
  tag: string;
  key: string;
  count: number;
}

/**
 * Sammelt alle Tags aus den geladenen Events, fasst Schreibvarianten
 * zusammen und sortiert nach Häufigkeit, dann alphabetisch.
 */
export const collectTagStats = (events: Event[]): TagStat[] => {
  const stats = new Map<
    string,
    { count: number; spellings: Map<string, number> }
  >();
  for (const event of events) {
    const seen = new Set<string>();
    for (const raw of event.tags) {
      const display = raw.trim().replace(/^#/, "");
      const key = normalizeTag(display);
      if (!key || seen.has(key)) continue;
      seen.add(key);
      const entry = stats.get(key) ?? { count: 0, spellings: new Map() };
      entry.count += 1;
      entry.spellings.set(display, (entry.spellings.get(display) ?? 0) + 1);
      stats.set(key, entry);
    }
  }
  return Array.from(stats.entries())
    .map(([key, entry]) => ({
      key,
      count: entry.count,
      tag: Array.from(entry.spellings.entries()).sort(
        (a, b) => b[1] - a[1] || a[0].localeCompare(b[0], "de"),
      )[0][0],
    }))
    .sort((a, b) => b.count - a.count || a.tag.localeCompare(b.tag, "de"));
};

export const isTagSelected = (list: string[], tag: string): boolean => {
  const key = normalizeTag(tag);
  return list.some((entry) => normalizeTag(entry) === key);
};

export const toggleInList = <T>(list: T[], value: T): T[] =>
  list.includes(value)
    ? list.filter((entry) => entry !== value)
    : [...list, value];

export const toggleTagInList = (list: string[], tag: string): string[] =>
  isTagSelected(list, tag)
    ? list.filter((entry) => normalizeTag(entry) !== normalizeTag(tag))
    : [...list, tag];

export const countActiveFilters = (filter: EventFilter): number =>
  filter.tags.length +
  filter.excludedTags.length +
  filter.locationTypes.length +
  filter.categories.length +
  filter.difficultyLevels.length +
  filter.weekdays.length +
  filter.timesOfDay.length +
  (filter.beginnerFriendly !== null ? 1 : 0) +
  (filter.onlyUpcoming ? 1 : 0) +
  (filter.hideCancelled ? 1 : 0) +
  (filter.searchTerm.trim() ? 1 : 0);

/* -------------------------------------------------------------------------- */
/* URL-Serialisierung, damit Filter geteilt und per Zurück-Taste erhalten      */
/* bleiben.                                                                    */
/* -------------------------------------------------------------------------- */

const pick = <T extends string | number>(
  values: string[],
  allowed: readonly T[],
): T[] => {
  const result: T[] = [];
  for (const value of values) {
    const match = allowed.find((option) => String(option) === value);
    if (match !== undefined && !result.includes(match)) result.push(match);
  }
  return result;
};

const splitParam = (params: URLSearchParams, key: string): string[] =>
  (params.get(key) ?? "")
    .split(",")
    .map((part) => part.trim())
    .filter(Boolean);

export const filterToSearchParams = (filter: EventFilter): URLSearchParams => {
  const params = new URLSearchParams();
  const setList = (key: string, list: (string | number)[]) => {
    if (list.length > 0) params.set(key, list.join(","));
  };
  if (filter.searchTerm.trim()) params.set("q", filter.searchTerm.trim());
  setList("tags", filter.tags);
  if (filter.tags.length > 1 && filter.tagMatchMode === "all")
    params.set("tagMode", "all");
  setList("notTags", filter.excludedTags);
  setList("ort", filter.locationTypes);
  setList("kategorie", filter.categories);
  setList("stufe", filter.difficultyLevels);
  if (filter.beginnerFriendly !== null)
    params.set("anfaenger", filter.beginnerFriendly ? "1" : "0");
  setList("tage", filter.weekdays);
  setList("zeit", filter.timesOfDay);
  if (filter.onlyUpcoming) params.set("kommend", "1");
  if (filter.hideCancelled) params.set("ohneAbgesagt", "1");
  return params;
};

export const filterFromSearchParams = (
  params: URLSearchParams,
): EventFilter => {
  const filter = createDefaultFilter();
  filter.searchTerm = (params.get("q") ?? "").slice(0, 200);
  filter.tags = splitParam(params, "tags").slice(0, 30);
  filter.tagMatchMode = params.get("tagMode") === "all" ? "all" : "any";
  filter.excludedTags = splitParam(params, "notTags").slice(0, 30);
  filter.locationTypes = pick(
    splitParam(params, "ort"),
    LOCATION_TYPE_OPTIONS.map((o) => o.value),
  );
  filter.categories = pick(
    splitParam(params, "kategorie"),
    CATEGORY_OPTIONS.map((o) => o.value),
  );
  filter.difficultyLevels = pick(
    splitParam(params, "stufe"),
    DIFFICULTY_OPTIONS.map((o) => o.value),
  );
  const beginner = params.get("anfaenger");
  filter.beginnerFriendly =
    beginner === "1" ? true : beginner === "0" ? false : null;
  filter.weekdays = pick(
    splitParam(params, "tage"),
    WEEKDAY_OPTIONS.map((o) => o.value),
  );
  filter.timesOfDay = pick(
    splitParam(params, "zeit"),
    TIME_OF_DAY_OPTIONS.map((o) => o.value),
  );
  filter.onlyUpcoming = params.get("kommend") === "1";
  filter.hideCancelled = params.get("ohneAbgesagt") === "1";
  return filter;
};
