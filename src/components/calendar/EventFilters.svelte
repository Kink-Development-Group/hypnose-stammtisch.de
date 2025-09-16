<script lang="ts">
  import {
    clearFilters,
    eventFilters,
    updateFilter,
  } from "../../stores/calendar";

  export let searchTerm: string = "";

  // Available tags (in a real app, these would come from the API)
  const availableTags = [
    "workshop",
    "beginner",
    "advanced",
    "theory",
    "practice",
    "group",
    "individual",
    "online",
    "hybrid",
  ];

  // Handle search input
  const handleSearchInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    searchTerm = target.value;
    updateFilter("searchTerm", target.value);
  };

  // Handle tag toggle
  const toggleTag = (tag: string) => {
    const currentTags = $eventFilters.tags;
    const newTags = currentTags.includes(tag)
      ? currentTags.filter((t) => t !== tag)
      : [...currentTags, tag];

    updateFilter("tags", newTags);
  };

  // Handle location type change
  const handleLocationTypeChange = (e: Event) => {
    const target = e.target as HTMLSelectElement;
    const value =
      target.value === ""
        ? null
        : (target.value as "physical" | "online" | "hybrid");
    updateFilter("locationType", value);
  };

  // Handle beginner friendly toggle
  const handleBeginnerFriendlyChange = (value: boolean | null) => {
    updateFilter("beginnerFriendly", value);
  };

  // Clear all filters
  const handleClearFilters = () => {
    searchTerm = "";
    clearFilters();
  };
</script>

<div class="space-y-6">
  <!-- Search -->
  <div class="relative">
    <label for="event-search" class="sr-only"> Nach Events suchen </label>
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
        class="form-input pl-10 w-full"
        placeholder="Nach Events, Beschreibungen oder Tags suchen..."
        value={searchTerm}
        on:input={handleSearchInput}
        autocomplete="off"
      />
    </div>
  </div>

  <!-- Filter section -->
  <div class="bg-charcoal-800 border border-charcoal-700 rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-smoke-50">Filter</h3>
      <button
        class="btn btn-ghost text-sm"
        on:click={handleClearFilters}
        disabled={$eventFilters.tags.length === 0 &&
          !$eventFilters.locationType &&
          $eventFilters.beginnerFriendly === null &&
          !$eventFilters.searchTerm}
      >
        Zurücksetzen
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Location Type Filter -->
      <div class="form-group">
        <label for="location-type" class="form-label">
          Veranstaltungsort
        </label>
        <select
          id="location-type"
          class="form-select"
          value={$eventFilters.locationType || ""}
          on:change={handleLocationTypeChange}
        >
          <option value="">Alle Orte</option>
          <option value="physical">Vor Ort</option>
          <option value="online">Online</option>
          <option value="hybrid">Hybrid</option>
        </select>
      </div>

      <!-- Beginner Friendly Filter -->
      <div class="form-group">
        <fieldset>
          <legend class="form-label"> Anfängerfreundlich </legend>
          <div class="space-y-2 mt-2">
            <label class="flex items-center">
              <input
                type="radio"
                name="beginner-friendly"
                class="mr-2"
                checked={$eventFilters.beginnerFriendly === null}
                on:change={() => handleBeginnerFriendlyChange(null)}
              />
              <span class="text-sm text-smoke-300">Alle Events</span>
            </label>
            <label class="flex items-center">
              <input
                type="radio"
                name="beginner-friendly"
                class="mr-2"
                checked={$eventFilters.beginnerFriendly === true}
                on:change={() => handleBeginnerFriendlyChange(true)}
              />
              <span class="text-sm text-smoke-300">Nur anfängerfreundlich</span>
            </label>
            <label class="flex items-center">
              <input
                type="radio"
                name="beginner-friendly"
                class="mr-2"
                checked={$eventFilters.beginnerFriendly === false}
                on:change={() => handleBeginnerFriendlyChange(false)}
              />
              <span class="text-sm text-smoke-300">Fortgeschritten</span>
            </label>
          </div>
        </fieldset>
      </div>

      <!-- Active Filters Summary -->
      <div class="form-group">
        <div class="form-label">Aktive Filter</div>
        <div class="flex flex-wrap gap-2 mt-2">
          {#if $eventFilters.locationType}
            <span class="badge badge-primary">
              {$eventFilters.locationType === "physical"
                ? "Vor Ort"
                : $eventFilters.locationType === "online"
                  ? "Online"
                  : "Hybrid"}
            </span>
          {/if}

          {#if $eventFilters.beginnerFriendly === true}
            <span class="badge badge-consent"> Anfängerfreundlich </span>
          {:else if $eventFilters.beginnerFriendly === false}
            <span class="badge badge-caution"> Fortgeschritten </span>
          {/if}

          {#each $eventFilters.tags as tag (tag)}
            <span class="badge badge-accent">
              {tag}
            </span>
          {/each}

          {#if $eventFilters.searchTerm}
            <span class="badge badge-secondary">
              Suche: "{$eventFilters.searchTerm}"
            </span>
          {/if}
        </div>
      </div>
    </div>

    <!-- Tag Filter -->
    <div class="mt-6">
      <div class="form-label mb-3">Tags</div>
      <div class="flex flex-wrap gap-2">
        {#each availableTags as tag, index (index)}
          <button
            class="badge transition-all duration-200 {$eventFilters.tags.includes(
              tag,
            )
              ? 'badge-accent'
              : 'badge-primary opacity-60 hover:opacity-100'}"
            on:click={() => toggleTag(tag)}
            aria-pressed={$eventFilters.tags.includes(tag)}
          >
            {tag}
            {#if $eventFilters.tags.includes(tag)}
              <svg
                class="w-3 h-3 ml-1"
                fill="currentColor"
                viewBox="0 0 20 20"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            {/if}
          </button>
        {/each}
      </div>
    </div>
  </div>
</div>
