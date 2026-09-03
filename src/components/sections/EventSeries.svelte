<script lang="ts">
  import dayjs from "dayjs";
  import "dayjs/locale/de";
  import { onMount } from "svelte";
  import { link } from "svelte-spa-router";
  import { NextEventSource } from "../../enums/knownEventSeries";
  import type { PublicKnownEventSeries } from "../../types/knownEventSeries";

  dayjs.locale("de");

  let series: PublicKnownEventSeries[] = [];
  let loaded = false;

  onMount(async () => {
    try {
      const response = await fetch("/api/known-event-series");

      if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
      }

      const result = await response.json();
      series = result.success && Array.isArray(result.data) ? result.data : [];
    } catch (error) {
      // The section is purely editorial: rather than show a broken card grid on
      // the landing page, keep it hidden and leave the reason in the console.
      console.error("Error loading known event series:", error);
      series = [];
    } finally {
      loaded = true;
    }
  });

  /**
   * The next date of a card, ready for display.
   *
   * A manual date is shown as typed, an automatic one is formatted from the ISO
   * timestamp the backend computed from the linked recurring series.
   */
  function formatNextEvent(entry: PublicKnownEventSeries): string | null {
    const { source, text, datetime } = entry.nextEvent;

    if (source === NextEventSource.MANUAL && text) {
      return `Nächster Termin: ${text}`;
    }

    if (source === NextEventSource.AUTO && datetime) {
      return `Nächster Termin: ${dayjs(datetime).format("dd, D. MMM YYYY")}`;
    }

    return null;
  }

  /**
   * Cities named in the section subtitle, taken from the series themselves so
   * the line cannot promise a region that is no longer represented.
   *
   * A location reads "Hamburg • Club Catonium" — the part before the separator
   * is the city.
   */
  function collectCities(entries: PublicKnownEventSeries[]): string[] {
    const cities: string[] = [];

    for (const entry of entries) {
      const city = entry.location.split("•")[0]?.trim();
      if (city && !cities.includes(city)) {
        cities.push(city);
      }
    }

    return cities;
  }

  $: cities = collectCities(series);
  $: subtitle =
    cities.length > 1
      ? `Regelmäßige Stammtische und Events in ${cities.slice(0, -1).join(", ")} und ${cities[cities.length - 1]}`
      : cities.length === 1
        ? `Regelmäßige Stammtische und Events in ${cities[0]}`
        : "Regelmäßige Stammtische und Events in der Community";
</script>

{#if loaded && series.length > 0}
  <section class="py-16 bg-charcoal-900" aria-labelledby="event-series-heading">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-12">
        <h2
          id="event-series-heading"
          class="text-3xl md:text-4xl font-display font-bold text-smoke-50 mb-4"
        >
          Bekannte Event-Reihen
        </h2>
        <p class="text-lg text-smoke-300 max-w-2xl mx-auto">
          {subtitle}
        </p>
      </div>

      <!-- Event Series Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {#each series as entry (entry.id)}
          <div class="card hover:shadow-glow transition-all duration-300 group">
            <!-- Header -->
            <div class="mb-4">
              <h3
                class="text-xl font-semibold text-smoke-50 mb-2 group-hover:text-accent-400 transition-colors"
              >
                {entry.title}
              </h3>
              <div class="text-sm text-smoke-400 space-y-1">
                <div class="flex items-center">
                  <svg
                    class="w-4 h-4 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                  {entry.location}
                </div>
                <div class="flex items-center">
                  <svg
                    class="w-4 h-4 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                  {entry.frequency}
                </div>
              </div>
            </div>

            <!-- Description -->
            {#if entry.description}
              <p class="text-smoke-300 mb-4 text-sm">
                {entry.description}
              </p>
            {/if}

            <!-- Formats -->
            {#if entry.formats.length > 0}
              <div class="mb-4">
                <ul class="space-y-1">
                  {#each entry.formats as format (format)}
                    <li class="text-sm text-smoke-400 flex items-start">
                      <span class="text-accent-400 mr-2 flex-shrink-0">•</span>
                      {format}
                    </li>
                  {/each}
                </ul>
              </div>
            {/if}

            <!-- Tags -->
            {#if entry.tags.length > 0}
              <div class="flex flex-wrap gap-2 mb-4">
                {#each entry.tags as tag (tag)}
                  <span class="badge badge-outline text-xs">
                    {tag}
                  </span>
                {/each}
              </div>
            {/if}

            <!-- Footer -->
            <div class="mt-auto pt-4 border-t border-charcoal-700">
              <div class="flex justify-between items-end">
                <div>
                  {#if entry.price}
                    <div class="text-lg font-semibold text-consent mb-1">
                      {entry.price}
                    </div>
                  {/if}
                  {#if formatNextEvent(entry)}
                    <div class="text-xs text-smoke-500">
                      {#if entry.nextEvent.source === NextEventSource.AUTO && entry.nextEvent.datetime}
                        <time datetime={entry.nextEvent.datetime}>
                          {formatNextEvent(entry)}
                        </time>
                      {:else}
                        {formatNextEvent(entry)}
                      {/if}
                    </div>
                  {/if}
                </div>
                {#if entry.detailUrl.startsWith("/")}
                  <a
                    href={entry.detailUrl}
                    use:link
                    class="btn btn-sm btn-outline hover:btn-primary transition-all duration-200"
                  >
                    Details
                  </a>
                {:else}
                  <a
                    href={entry.detailUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-sm btn-outline hover:btn-primary transition-all duration-200"
                  >
                    Details
                  </a>
                {/if}
              </div>
            </div>
          </div>
        {/each}
      </div>

      <!-- CTA -->
      <div class="text-center mt-12">
        <a href="/events" use:link class="btn btn-primary mr-4">
          Alle Events anzeigen
        </a>
        <a href="/submit-event" use:link class="btn btn-outline">
          Event vorschlagen
        </a>
      </div>
    </div>
  </section>
{/if}
