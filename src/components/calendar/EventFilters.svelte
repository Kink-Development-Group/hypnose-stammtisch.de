<script lang="ts">
  import {
    activeFilterCount,
    availableEventTags,
    clearFilters,
    eventFilters,
    events,
    filteredEvents,
    updateFilter,
  } from "../../stores/calendar";
  import type {
    DifficultyLevel,
    EventCategory,
    LocationType,
    TimeOfDay,
  } from "../../types/calendar";
  import {
    CATEGORY_OPTIONS,
    DIFFICULTY_OPTIONS,
    LOCATION_TYPE_OPTIONS,
    TIME_OF_DAY_OPTIONS,
    WEEKDAY_OPTIONS,
    isTagSelected,
    normalizeTag,
    normalizeText,
    toggleInList,
    toggleTagInList,
  } from "../../utils/eventFilters";

  const TAG_PREVIEW_LIMIT = 16;

  let showAdvanced = false;
  let tagQuery = "";
  let showAllTags = false;
  let searchTimer: ReturnType<typeof setTimeout> | undefined;
  let searchValue = $eventFilters.searchTerm;

  // Suchfeld synchron halten, wenn Filter von außen (URL, Zurücksetzen) kommen.
  $: if (
    $eventFilters.searchTerm !== searchValue &&
    searchTimer === undefined
  ) {
    searchValue = $eventFilters.searchTerm;
  }

  const handleSearchInput = (e: Event) => {
    searchValue = (e.target as HTMLInputElement).value;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      updateFilter("searchTerm", searchValue);
      searchTimer = undefined;
    }, 200);
  };

  const clearSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = undefined;
    searchValue = "";
    updateFilter("searchTerm", "");
  };

  // Anzahl je Option (bezogen auf alle geladenen Events)
  $: locationCounts = countBy($events.map((e) => e.locationType));
  $: categoryCounts = countBy(
    $events.map((e) => e.category).filter(Boolean) as string[],
  );
  $: difficultyCounts = countBy($events.map((e) => e.difficultyLevel ?? "all"));
  $: visibleCategories = CATEGORY_OPTIONS.filter(
    (option) =>
      categoryCounts[option.value] ||
      $eventFilters.categories.includes(option.value),
  );

  function countBy(values: string[]): Record<string, number> {
    const counts: Record<string, number> = {};
    for (const value of values) counts[value] = (counts[value] ?? 0) + 1;
    return counts;
  }

  // Tag-Auswahl
  $: normalizedTagQuery = normalizeText(tagQuery);
  $: matchingTags = $availableEventTags.filter(
    (stat) => !normalizedTagQuery || stat.key.includes(normalizedTagQuery),
  );
  // Gewählte Tags, die im aktuellen Zeitraum nicht vorkommen, trotzdem zeigen.
  $: orphanTags = [...$eventFilters.tags, ...$eventFilters.excludedTags]
    .filter(
      (tag) =>
        !$availableEventTags.some((stat) => stat.key === normalizeTag(tag)),
    )
    .map((tag) => ({ tag, key: normalizeTag(tag), count: 0 }));
  $: tagList = [...orphanTags, ...matchingTags];
  $: visibleTags =
    showAllTags || normalizedTagQuery
      ? tagList
      : tagList.slice(0, TAG_PREVIEW_LIMIT);

  type TagState = "include" | "exclude" | "none";
  const getTagState = (
    tag: string,
    included: string[],
    excluded: string[],
  ): TagState =>
    isTagSelected(included, tag)
      ? "include"
      : isTagSelected(excluded, tag)
        ? "exclude"
        : "none";

  // Klick: neutral → einschließen → ausschließen → neutral
  const cycleTag = (tag: string) => {
    const state = getTagState(
      tag,
      $eventFilters.tags,
      $eventFilters.excludedTags,
    );
    if (state === "none") {
      updateFilter("tags", toggleTagInList($eventFilters.tags, tag));
    } else if (state === "include") {
      updateFilter("tags", toggleTagInList($eventFilters.tags, tag));
      updateFilter(
        "excludedTags",
        toggleTagInList($eventFilters.excludedTags, tag),
      );
    } else {
      updateFilter(
        "excludedTags",
        toggleTagInList($eventFilters.excludedTags, tag),
      );
    }
  };

  const handleTagQueryKeydown = (e: KeyboardEvent) => {
    if (e.key === "Enter" && matchingTags.length > 0) {
      e.preventDefault();
      const first = matchingTags[0];
      if (!isTagSelected($eventFilters.tags, first.tag)) cycleTag(first.tag);
      tagQuery = "";
    }
  };

  const toggleLocation = (value: LocationType) =>
    updateFilter(
      "locationTypes",
      toggleInList($eventFilters.locationTypes, value),
    );
  const toggleCategory = (value: EventCategory) =>
    updateFilter("categories", toggleInList($eventFilters.categories, value));
  const toggleDifficulty = (value: DifficultyLevel) =>
    updateFilter(
      "difficultyLevels",
      toggleInList($eventFilters.difficultyLevels, value),
    );
  const toggleWeekday = (value: number) =>
    updateFilter("weekdays", toggleInList($eventFilters.weekdays, value));
  const toggleTimeOfDay = (value: TimeOfDay) =>
    updateFilter("timesOfDay", toggleInList($eventFilters.timesOfDay, value));

  const setWeekdayPreset = (preset: "weekdays" | "weekend") =>
    updateFilter("weekdays", preset === "weekend" ? [6, 0] : [1, 2, 3, 4, 5]);

  const handleClearFilters = () => {
    clearSearch();
    tagQuery = "";
    clearFilters();
  };

  const labelOf = <T,>(
    options: { value: T; label: string }[],
    value: T,
  ): string => options.find((o) => o.value === value)?.label ?? String(value);

  // Aktive Filter als entfernbare Chips
  type ActiveChip = { id: string; label: string; remove: () => void };
  $: activeChips = buildChips($eventFilters);

  function buildChips(f: typeof $eventFilters): ActiveChip[] {
    const chips: ActiveChip[] = [];
    if (f.searchTerm.trim())
      chips.push({
        id: "q",
        label: `Suche: „${f.searchTerm.trim()}“`,
        remove: clearSearch,
      });
    f.tags.forEach((tag) =>
      chips.push({
        id: `tag-${tag}`,
        label: `#${tag}`,
        remove: () => updateFilter("tags", toggleTagInList(f.tags, tag)),
      }),
    );
    f.excludedTags.forEach((tag) =>
      chips.push({
        id: `not-${tag}`,
        label: `ohne #${tag}`,
        remove: () =>
          updateFilter("excludedTags", toggleTagInList(f.excludedTags, tag)),
      }),
    );
    f.locationTypes.forEach((v) =>
      chips.push({
        id: `loc-${v}`,
        label: labelOf(LOCATION_TYPE_OPTIONS, v),
        remove: () => toggleLocation(v),
      }),
    );
    f.categories.forEach((v) =>
      chips.push({
        id: `cat-${v}`,
        label: labelOf(CATEGORY_OPTIONS, v),
        remove: () => toggleCategory(v),
      }),
    );
    f.difficultyLevels.forEach((v) =>
      chips.push({
        id: `lvl-${v}`,
        label: labelOf(DIFFICULTY_OPTIONS, v),
        remove: () => toggleDifficulty(v),
      }),
    );
    WEEKDAY_OPTIONS.filter((o) => f.weekdays.includes(o.value)).forEach((o) =>
      chips.push({
        id: `day-${o.value}`,
        label: o.label,
        remove: () => toggleWeekday(o.value),
      }),
    );
    f.timesOfDay.forEach((v) =>
      chips.push({
        id: `time-${v}`,
        label: labelOf(TIME_OF_DAY_OPTIONS, v),
        remove: () => toggleTimeOfDay(v),
      }),
    );
    if (f.beginnerFriendly !== null)
      chips.push({
        id: "beginner",
        label: f.beginnerFriendly
          ? "Anfängerfreundlich"
          : "Nicht anfängerfreundlich",
        remove: () => updateFilter("beginnerFriendly", null),
      });
    if (f.onlyUpcoming)
      chips.push({
        id: "upcoming",
        label: "Nur kommende",
        remove: () => updateFilter("onlyUpcoming", false),
      });
    if (f.hideCancelled)
      chips.push({
        id: "cancelled",
        label: "Ohne Absagen",
        remove: () => updateFilter("hideCancelled", false),
      });
    return chips;
  }

  // Erweiterte Filter automatisch öffnen, wenn welche aktiv sind (z. B. via URL)
  let autoOpened = false;
  $: if (!autoOpened && advancedActive($eventFilters)) {
    showAdvanced = true;
    autoOpened = true;
  }

  function advancedActive(f: typeof $eventFilters): boolean {
    return (
      f.categories.length +
        f.difficultyLevels.length +
        f.weekdays.length +
        f.timesOfDay.length +
        f.excludedTags.length >
      0
    );
  }
</script>

<div class="space-y-4" data-testid="event-filters">
  <!-- Suche -->
  <div>
    <label for="event-search" class="sr-only">Nach Events suchen</label>
    <div class="relative">
      <div
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
      >
        <svg
          class="h-5 w-5 text-smoke-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>
      <input
        id="event-search"
        type="search"
        class="form-input pl-10 pr-10 w-full"
        placeholder="Titel, Ort, Veranstalter:in oder Tag suchen …"
        value={searchValue}
        on:input={handleSearchInput}
        on:keydown={(e) => e.key === "Escape" && clearSearch()}
        autocomplete="off"
        aria-describedby="event-search-help"
      />
    </div>
    <p id="event-search-help" class="form-help text-xs">
      Mehrere Begriffe werden kombiniert. „Phrase in Anführungszeichen“ sucht
      exakt, <code>-wort</code> schließt aus.
    </p>
  </div>

  <div class="bg-charcoal-800 border border-charcoal-700 rounded-lg p-4 md:p-6">
    <!-- Kopfzeile -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="flex items-center gap-3">
        <h2 class="text-lg font-semibold text-smoke-50">Filter</h2>
        <span class="text-sm text-smoke-400" aria-live="polite">
          {$filteredEvents.length} von {$events.length} Events
        </span>
      </div>
      <button
        class="btn btn-ghost text-sm"
        on:click={handleClearFilters}
        disabled={$activeFilterCount === 0}
      >
        Alle zurücksetzen{$activeFilterCount > 0
          ? ` (${$activeFilterCount})`
          : ""}
      </button>
    </div>

    <!-- Schnellfilter -->
    <div
      class="flex flex-wrap gap-2 mb-5"
      role="group"
      aria-label="Schnellfilter"
    >
      <button
        type="button"
        class="chip"
        class:chip-active={$eventFilters.onlyUpcoming}
        aria-pressed={$eventFilters.onlyUpcoming}
        on:click={() =>
          updateFilter("onlyUpcoming", !$eventFilters.onlyUpcoming)}
      >
        ⏭️ Nur kommende
      </button>
      <button
        type="button"
        class="chip"
        class:chip-active={$eventFilters.hideCancelled}
        aria-pressed={$eventFilters.hideCancelled}
        on:click={() =>
          updateFilter("hideCancelled", !$eventFilters.hideCancelled)}
      >
        🚫 Abgesagte ausblenden
      </button>
      <button
        type="button"
        class="chip"
        class:chip-active={$eventFilters.beginnerFriendly === true}
        aria-pressed={$eventFilters.beginnerFriendly === true}
        on:click={() =>
          updateFilter(
            "beginnerFriendly",
            $eventFilters.beginnerFriendly === true ? null : true,
          )}
      >
        🌱 Anfängerfreundlich
      </button>
      {#each LOCATION_TYPE_OPTIONS as option (option.value)}
        <button
          type="button"
          class="chip"
          class:chip-active={$eventFilters.locationTypes.includes(option.value)}
          aria-pressed={$eventFilters.locationTypes.includes(option.value)}
          on:click={() => toggleLocation(option.value)}
        >
          {option.value === "physical"
            ? "📍"
            : option.value === "online"
              ? "💻"
              : "🔀"}
          {option.label}
          <span class="chip-count">{locationCounts[option.value] ?? 0}</span>
        </button>
      {/each}
    </div>

    <!-- Tags -->
    <fieldset class="mb-2">
      <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <legend class="form-label">Tags</legend>
        {#if $eventFilters.tags.length > 1}
          <div
            class="inline-flex rounded-md overflow-hidden border border-charcoal-600 text-xs"
            role="group"
            aria-label="Verknüpfung der Tags"
          >
            <button
              type="button"
              class="px-2 py-1 {$eventFilters.tagMatchMode === 'any'
                ? 'bg-accent-400 text-gray-900'
                : 'text-smoke-300 hover:bg-charcoal-700'}"
              aria-pressed={$eventFilters.tagMatchMode === "any"}
              on:click={() => updateFilter("tagMatchMode", "any")}
            >
              Mindestens einer
            </button>
            <button
              type="button"
              class="px-2 py-1 {$eventFilters.tagMatchMode === 'all'
                ? 'bg-accent-400 text-gray-900'
                : 'text-smoke-300 hover:bg-charcoal-700'}"
              aria-pressed={$eventFilters.tagMatchMode === "all"}
              on:click={() => updateFilter("tagMatchMode", "all")}
            >
              Alle
            </button>
          </div>
        {/if}
      </div>

      {#if $availableEventTags.length === 0 && orphanTags.length === 0}
        <p class="text-sm text-smoke-400">
          Im geladenen Zeitraum haben die Events keine Tags.
        </p>
      {:else}
        {#if $availableEventTags.length > 8}
          <div class="mb-3 max-w-sm">
            <label for="tag-search" class="sr-only">Tags durchsuchen</label>
            <input
              id="tag-search"
              type="search"
              class="form-input text-sm py-1.5"
              placeholder="Tags durchsuchen … (Enter wählt aus)"
              bind:value={tagQuery}
              on:keydown={handleTagQueryKeydown}
              autocomplete="off"
            />
          </div>
        {/if}

        <div class="flex flex-wrap gap-2">
          {#each visibleTags as stat (stat.key)}
            {@const state = getTagState(
              stat.tag,
              $eventFilters.tags,
              $eventFilters.excludedTags,
            )}
            <button
              type="button"
              class="chip"
              class:chip-active={state === "include"}
              class:chip-excluded={state === "exclude"}
              on:click={() => cycleTag(stat.tag)}
              aria-label={state === "include"
                ? `Tag ${stat.tag}: eingeschlossen. Klicken zum Ausschließen.`
                : state === "exclude"
                  ? `Tag ${stat.tag}: ausgeschlossen. Klicken zum Entfernen.`
                  : `Tag ${stat.tag} filtern (${stat.count} Events)`}
            >
              {#if state === "exclude"}<span aria-hidden="true">−</span>{/if}
              #{stat.tag}
              <span class="chip-count">{stat.count}</span>
            </button>
          {:else}
            <p class="text-sm text-smoke-400">
              Kein Tag passt zu „{tagQuery}“.
            </p>
          {/each}
        </div>

        {#if !normalizedTagQuery && tagList.length > TAG_PREVIEW_LIMIT}
          <button
            type="button"
            class="mt-3 text-sm text-accent-400 hover:underline"
            on:click={() => (showAllTags = !showAllTags)}
            aria-expanded={showAllTags}
          >
            {showAllTags
              ? "Weniger Tags anzeigen"
              : `Alle ${tagList.length} Tags anzeigen`}
          </button>
        {/if}
        <p class="form-help text-xs">
          Einmal klicken = Tag muss vorkommen, zweimal = Tag ausschließen,
          dreimal = Auswahl aufheben.
        </p>
      {/if}
    </fieldset>

    <!-- Erweiterte Filter -->
    <button
      type="button"
      class="mt-4 flex items-center gap-2 text-sm font-medium text-smoke-200 hover:text-smoke-50"
      aria-expanded={showAdvanced}
      aria-controls="advanced-event-filters"
      on:click={() => (showAdvanced = !showAdvanced)}
    >
      <span
        class="inline-block transition-transform"
        class:rotate-90={showAdvanced}
        aria-hidden="true">▶</span
      >
      Weitere Filter (Kategorie, Erfahrung, Wochentag, Uhrzeit)
    </button>

    {#if showAdvanced}
      <div
        id="advanced-event-filters"
        class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6"
      >
        {#if visibleCategories.length > 0}
          <fieldset>
            <legend class="form-label mb-2">Kategorie</legend>
            <div class="flex flex-wrap gap-2">
              {#each visibleCategories as option (option.value)}
                <button
                  type="button"
                  class="chip"
                  class:chip-active={$eventFilters.categories.includes(
                    option.value,
                  )}
                  aria-pressed={$eventFilters.categories.includes(option.value)}
                  on:click={() => toggleCategory(option.value)}
                >
                  {option.label}
                  <span class="chip-count"
                    >{categoryCounts[option.value] ?? 0}</span
                  >
                </button>
              {/each}
            </div>
          </fieldset>
        {/if}

        <fieldset>
          <legend class="form-label mb-2">Erfahrungsstufe</legend>
          <div class="flex flex-wrap gap-2">
            {#each DIFFICULTY_OPTIONS as option (option.value)}
              <button
                type="button"
                class="chip"
                class:chip-active={$eventFilters.difficultyLevels.includes(
                  option.value,
                )}
                aria-pressed={$eventFilters.difficultyLevels.includes(
                  option.value,
                )}
                on:click={() => toggleDifficulty(option.value)}
              >
                {option.label}
                <span class="chip-count"
                  >{difficultyCounts[option.value] ?? 0}</span
                >
              </button>
            {/each}
          </div>
          <p class="form-help text-xs">
            Events „für alle Stufen“ erscheinen bei jeder Auswahl.
          </p>
        </fieldset>

        <fieldset>
          <legend class="form-label mb-2">Wochentag</legend>
          <div class="flex flex-wrap gap-1.5">
            {#each WEEKDAY_OPTIONS as option (option.value)}
              <button
                type="button"
                class="chip chip-square"
                class:chip-active={$eventFilters.weekdays.includes(
                  option.value,
                )}
                aria-pressed={$eventFilters.weekdays.includes(option.value)}
                aria-label={option.label}
                title={option.label}
                on:click={() => toggleWeekday(option.value)}
              >
                {option.short}
              </button>
            {/each}
          </div>
          <div class="mt-2 flex gap-3 text-xs">
            <button
              type="button"
              class="text-accent-400 hover:underline"
              on:click={() => setWeekdayPreset("weekdays")}>Werktags</button
            >
            <button
              type="button"
              class="text-accent-400 hover:underline"
              on:click={() => setWeekdayPreset("weekend")}>Wochenende</button
            >
          </div>
        </fieldset>

        <fieldset>
          <legend class="form-label mb-2">Uhrzeit (Beginn)</legend>
          <div class="flex flex-wrap gap-2">
            {#each TIME_OF_DAY_OPTIONS as option (option.value)}
              <button
                type="button"
                class="chip"
                class:chip-active={$eventFilters.timesOfDay.includes(
                  option.value,
                )}
                aria-pressed={$eventFilters.timesOfDay.includes(option.value)}
                title={option.hint}
                on:click={() => toggleTimeOfDay(option.value)}
              >
                {option.label}
                <span class="chip-count">{option.hint}</span>
              </button>
            {/each}
          </div>
          <p class="form-help text-xs">
            Ganztägige Events werden immer angezeigt.
          </p>
        </fieldset>
      </div>
    {/if}

    <!-- Aktive Filter -->
    {#if activeChips.length > 0}
      <div class="mt-5 pt-4 border-t border-charcoal-700">
        <div class="form-label mb-2">Aktive Filter</div>
        <ul class="flex flex-wrap gap-2">
          {#each activeChips as chip (chip.id)}
            <li>
              <button
                type="button"
                class="badge badge-accent gap-1 hover:opacity-80"
                on:click={chip.remove}
                aria-label={`Filter entfernen: ${chip.label}`}
              >
                {chip.label}
                <span aria-hidden="true">✕</span>
              </button>
            </li>
          {/each}
        </ul>
      </div>
    {/if}
  </div>
</div>

<style>
  .chip {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.3rem 0.75rem;
    border-radius: 9999px;
    border: 1px solid rgb(255 255 255 / 0.12);
    background: rgb(255 255 255 / 0.04);
    color: var(--color-smoke-200, #d4d4d8);
    font-size: 0.8125rem;
    line-height: 1.25rem;
    transition:
      background-color 0.15s ease,
      border-color 0.15s ease,
      color 0.15s ease;
    cursor: pointer;
  }

  .chip:hover {
    border-color: rgb(255 255 255 / 0.3);
    background: rgb(255 255 255 / 0.08);
  }

  .chip:focus-visible {
    outline: 2px solid var(--color-accent-400, #4be3c0);
    outline-offset: 2px;
  }

  .chip-active {
    background: var(--color-accent-400, #4be3c0);
    border-color: var(--color-accent-400, #4be3c0);
    color: #111827;
  }

  .chip-active:hover {
    background: var(--color-accent-300, #7ff0d4);
  }

  .chip-excluded,
  .chip-excluded:hover {
    background: rgb(127 29 29 / 0.6);
    border-color: rgb(248 113 113 / 0.6);
    color: #fee2e2;
    text-decoration: line-through;
  }

  .chip-square {
    min-width: 2.5rem;
    justify-content: center;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }

  .chip-count {
    font-size: 0.7rem;
    opacity: 0.7;
    font-variant-numeric: tabular-nums;
  }

  code {
    font-size: 0.75rem;
    padding: 0 0.25rem;
    border-radius: 0.25rem;
    background: rgb(255 255 255 / 0.08);
  }
</style>
