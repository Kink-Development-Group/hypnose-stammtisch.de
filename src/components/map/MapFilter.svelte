<script lang="ts">
  import {
    availableRegions,
    availableTags,
    mapFilter,
    resetMapFilters,
    toggleCountryFilter,
    toggleRegionFilter,
    toggleTagFilter,
  } from "../../stores/map";

  let isExpanded = false;

  function toggleFilter() {
    isExpanded = !isExpanded;
  }

  const countries = [
    { code: "DE", name: "Deutschland", flag: "🇩🇪" },
    { code: "AT", name: "Österreich", flag: "🇦🇹" },
    { code: "CH", name: "Schweiz", flag: "🇨🇭" },
  ];
</script>

<div class="map-filters">
  <!-- Filter Toggle Button -->
  <button
    class="filter-toggle btn btn-outline"
    on:click={toggleFilter}
    aria-expanded={isExpanded}
    aria-controls="filter-panel"
  >
    🔍 Filter
    <span class="toggle-icon" class:rotate={isExpanded}>▼</span>
  </button>

  <!-- Active Filter Count -->
  {#if $mapFilter.regions.length > 0 || $mapFilter.tags.length > 0 || $mapFilter.countries.length < 3}
    <span class="filter-count">
      {$mapFilter.regions.length +
        $mapFilter.tags.length +
        (3 - $mapFilter.countries.length)} aktiv
    </span>
  {/if}

  <!-- Filter Panel -->
  {#if isExpanded}
    <div
      id="filter-panel"
      class="filter-panel"
      role="region"
      aria-label="Filter-Optionen"
    >
      <!-- Country Filters -->
      <div class="filter-section">
        <h3 class="filter-title">Länder</h3>
        <div class="filter-grid">
          {#each countries as country (country.name)}
            <label class="filter-checkbox">
              <input
                type="checkbox"
                checked={$mapFilter.countries.includes(country.code)}
                on:change={() => toggleCountryFilter(country.code)}
              />
              <span class="checkbox-label">
                {country.flag}
                {country.name}
              </span>
            </label>
          {/each}
        </div>
      </div>

      <!-- Region Filters -->
      {#if $availableRegions.length > 0}
        <div class="filter-section">
          <h3 class="filter-title">Regionen</h3>
          <div class="filter-grid">
            {#each $availableRegions as region (region)}
              <label class="filter-checkbox">
                <input
                  type="checkbox"
                  checked={$mapFilter.regions.includes(region)}
                  on:change={() => toggleRegionFilter(region)}
                />
                <span class="checkbox-label">{region}</span>
              </label>
            {/each}
          </div>
        </div>
      {/if}

      <!-- Tag Filters -->
      {#if $availableTags.length > 0}
        <div class="filter-section">
          <h3 class="filter-title">Tags</h3>
          <div class="filter-tags">
            {#each $availableTags as tag (tag)}
              <button
                class="tag-filter"
                class:active={$mapFilter.tags.includes(tag)}
                on:click={() => toggleTagFilter(tag)}
                aria-pressed={$mapFilter.tags.includes(tag)}
              >
                {tag}
              </button>
            {/each}
          </div>
        </div>
      {/if}

      <!-- Active Only Filter -->
      <div class="filter-section">
        <label class="filter-checkbox">
          <input type="checkbox" bind:checked={$mapFilter.activeOnly} />
          <span class="checkbox-label">Nur aktive Stammtische anzeigen</span>
        </label>
      </div>

      <!-- Reset Button -->
      <div class="filter-actions">
        <button class="btn btn-secondary" on:click={resetMapFilters}>
          Filter zurücksetzen
        </button>
      </div>
    </div>
  {/if}
</div>

<style>
  .map-filters {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
  }

  .filter-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
  }

  .toggle-icon {
    transition: transform 0.2s ease;
    font-size: 0.75rem;
  }

  .toggle-icon.rotate {
    transform: rotate(180deg);
  }

  .filter-count {
    background-color: rgb(59 130 246);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .filter-panel {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    box-shadow:
      0 10px 15px -3px rgba(0, 0, 0, 0.1),
      0 4px 6px -2px rgba(0, 0, 0, 0.05);
    padding: 1rem;
    z-index: 60;
    max-width: 600px;
    margin-top: 0.5rem;
  }

  .filter-section {
    margin-bottom: 1rem;
  }

  .filter-section:last-child {
    margin-bottom: 0;
  }

  .filter-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.5rem;
  }

  .filter-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .filter-checkbox input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: rgb(59 130 246);
  }

  .checkbox-label {
    color: #374151;
    user-select: none;
  }

  .filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .tag-filter {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 9999px;
    background-color: white;
    color: #374151;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .tag-filter:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
  }

  .tag-filter.active {
    background-color: rgb(59 130 246);
    color: white;
    border-color: rgb(59 130 246);
  }

  .filter-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
  }

  /* Dark theme support */
  :global(.dark) .filter-panel {
    background-color: #1f2937;
    border-color: #374151;
    color: #e5e7eb;
  }

  :global(.dark) .filter-title {
    color: #e5e7eb;
  }

  :global(.dark) .checkbox-label {
    color: #d1d5db;
  }

  :global(.dark) .tag-filter {
    background-color: #374151;
    color: #d1d5db;
    border-color: #4b5563;
  }

  :global(.dark) .tag-filter:hover {
    background-color: #4b5563;
    border-color: #6b7280;
  }

  :global(.dark) .filter-actions {
    border-color: #374151;
  }
</style>
