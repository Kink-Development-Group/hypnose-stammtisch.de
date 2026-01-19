<script lang="ts">
  import dayjs from "dayjs";
  import "dayjs/locale/de";
  import { currentDate, navigateCalendar } from "../../stores/calendar";
  import type { Event } from "../../types/calendar";
  import EventCard from "./EventCard.svelte";

  export let events: Event[] = [];

  dayjs.locale("de");

  // Current month display
  $: currentMonth = dayjs($currentDate).format("MMMM YYYY");

  // Navigation handlers
  const goToPrevious = () => {
    navigateCalendar("prev");
  };

  const goToNext = () => {
    navigateCalendar("next");
  };

  const goToToday = () => {
    currentDate.set(new Date());
  };

  // Group events by date
  $: groupedEvents = events
    // Sort: cancelled last within same date
    .slice()
    .sort((a, b) => {
      if (a.startDate.getTime() !== b.startDate.getTime()) return 0; // grouping handles date difference
      if (a.isCancelled && !b.isCancelled) return 1;
      if (!a.isCancelled && b.isCancelled) return -1;
      return 0;
    })
    .reduce(
      (groups, event) => {
        const dateKey = dayjs(event.startDate).format("YYYY-MM-DD");
        if (!groups[dateKey]) {
          groups[dateKey] = [];
        }
        groups[dateKey].push(event);
        return groups;
      },
      {} as Record<string, Event[]>,
    );

  $: sortedDateKeys = Object.keys(groupedEvents).sort();

  // Format date for display
  const formatDateHeader = (dateKey: string) => {
    const date = dayjs(dateKey);
    const today = dayjs();
    const tomorrow = today.add(1, "day");

    if (date.isSame(today, "day")) {
      return "Heute";
    } else if (date.isSame(tomorrow, "day")) {
      return "Morgen";
    } else {
      return date.format("dddd, DD. MMMM YYYY");
    }
  };

  // Get relative date info
  const getRelativeDate = (dateKey: string) => {
    const date = dayjs(dateKey);
    const today = dayjs();

    if (date.isBefore(today, "day")) {
      return "Vergangen";
    } else if (date.isSame(today, "day")) {
      return "Heute";
    } else if (date.isSame(today.add(1, "day"), "day")) {
      return "Morgen";
    } else {
      const diffDays = date.diff(today, "day");
      return `In ${diffDays} Tag${diffDays !== 1 ? "en" : ""}`;
    }
  };
</script>

<div class="space-y-8">
  <!-- Navigation header -->
  <header class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-display font-semibold text-smoke-50">
      {currentMonth}
    </h2>

    <div
      class="flex items-center space-x-2"
      role="toolbar"
      aria-label="Listen Navigation"
    >
      <button
        class="btn btn-ghost p-2 focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-900"
        on:click={goToPrevious}
        aria-label="Vorheriger Monat"
      >
        <svg
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
      </button>

      <button
        class="btn btn-outline px-4 py-2 focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-900"
        on:click={goToToday}
        aria-label="Zu heute navigieren"
      >
        Heute
      </button>

      <button
        class="btn btn-ghost p-2 focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-900"
        on:click={goToNext}
        aria-label="Nächster Monat"
      >
        <svg
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </button>
    </div>
  </header>

  {#if events.length > 0}
    {#each sortedDateKeys as dateKey (dateKey)}
      <section aria-labelledby="date-{dateKey}">
        <!-- Date header -->
        <header class="mb-4">
          <div class="flex items-center justify-between">
            <h3
              id="date-{dateKey}"
              class="text-2xl font-display font-semibold text-smoke-50"
            >
              {formatDateHeader(dateKey)}
            </h3>
            <span
              class="text-sm text-smoke-400 bg-charcoal-700 px-3 py-1 rounded-full"
            >
              {getRelativeDate(dateKey)}
            </span>
          </div>
          <div class="text-sm text-smoke-400 mt-1">
            {groupedEvents[dateKey].length} Veranstaltung{groupedEvents[dateKey]
              .length !== 1
              ? "en"
              : ""}
          </div>
        </header>

        <!-- Events for this date -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {#each groupedEvents[dateKey] as event (event.id)}
            <EventCard {event} showModal={true} />
          {/each}
        </div>
      </section>
    {/each}
  {:else}
    <!-- Empty state -->
    <div class="text-center py-16">
      <div class="text-6xl mb-4" aria-hidden="true">📅</div>
      <h3 class="text-2xl font-semibold text-smoke-50 mb-2">
        Keine Events gefunden
      </h3>
      <p class="text-smoke-300">
        Es gibt derzeit keine Veranstaltungen, die deinen Filterkriterien
        entsprechen.
      </p>
    </div>
  {/if}
</div>

<style>
  /* Ensure proper spacing between sections */
  section + section {
    border-top: 1px solid #3f4862; /* charcoal-700 */
    padding-top: 2rem;
  }
</style>
