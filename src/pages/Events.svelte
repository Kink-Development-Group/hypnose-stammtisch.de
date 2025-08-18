<script lang="ts">
  import { onMount } from "svelte";
  import { link } from "svelte-spa-router";
  import Calendar from "../components/calendar/Calendar.svelte";
  import EventFilters from "../components/calendar/EventFilters.svelte";
  import EventList from "../components/calendar/EventList.svelte";
  import EventModal from "../components/calendar/EventModal.svelte";
  // Import stores
  import {
    calendarView,
    events,
    filteredEvents,
    isLoading,
    showEventModal,
  } from "../stores/calendar";
  import { addNotification } from "../stores/ui";
  import { transformApiEvents } from "../utils/eventTransform";

  let searchTerm = "";

  // Load events
  onMount(async () => {
    try {
      isLoading.set(true);

      // In a real app, this would be an API call
      // Zeitraum bestimmen (für Recurrence Expansion im Backend)
      const now = new Date();
      // Standard: aktueller Monat
      const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
      const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      // Zusätzlicher Puffer: eine Woche vorher/nachher für flüssiges Navigieren
      const fromDate = new Date(
        startOfMonth.getTime() - 7 * 24 * 60 * 60 * 1000,
      );
      const toDate = new Date(endOfMonth.getTime() + 7 * 24 * 60 * 60 * 1000);
      const fmt = (d: Date) => d.toISOString().slice(0, 10);
      const url = `/api/events?view=expanded&from_date=${fmt(fromDate)}&to_date=${fmt(toDate)}`;
      const response = await fetch(url);
      if (response.ok) {
        const result = await response.json();
        const apiEvents = result.success ? result.data : [];
        const transformedEvents = transformApiEvents(apiEvents);
        events.set(transformedEvents);
      } else {
        throw new Error("Failed to load events");
      }
    } catch (error) {
      console.error("Error loading events:", error);
      addNotification({
        type: "error",
        title: "Fehler beim Laden",
        message: "Die Veranstaltungen konnten nicht geladen werden.",
      });
    } finally {
      isLoading.set(false);
    }
  });

  // Handle view changes
  const handleViewChange = (newView: "month" | "week" | "list") => {
    calendarView.set(newView);
  };
</script>

<svelte:head>
  <title>Events - Hypnose Stammtisch</title>
  <meta
    name="description"
    content="Entdecke kommende Hypnose-Events, Workshops und Community-Treffen. Sichere und respektvolle Veranstaltungen für alle Erfahrungsstufen."
  />
  <meta property="og:title" content="Events - Hypnose Stammtisch" />
  <meta
    property="og:description"
    content="Entdecke kommende Hypnose-Events, Workshops und Community-Treffen."
  />
</svelte:head>

<main class="container mx-auto px-4 py-8">
  <!-- Page header -->
  <header class="mb-8">
    <h1 class="text-4xl md:text-5xl font-display font-bold text-smoke-50 mb-4">
      Events & Workshops
    </h1>
    <p class="text-xl text-smoke-300 mb-6 max-w-3xl">
      Entdecke unsere kommenden Veranstaltungen und werde Teil einer
      unterstützenden Community für sichere Hypnose-Erfahrungen.
    </p>

    <!-- Quick actions -->
    <div class="flex flex-col sm:flex-row gap-4">
      <a href="/submit-event" use:link class="btn btn-primary">
        📝 Event vorschlagen
      </a>
      <a
        href="/calendar.ics?token=public"
        class="btn btn-outline"
        target="_blank"
        rel="noopener noreferrer"
      >
        📅 Kalender abonnieren
      </a>
      <a href="/resources/safety-guide" use:link class="btn btn-ghost">
        🛡️ Sicherheitsleitfaden
      </a>
    </div>
  </header>

  <!-- Filters and search -->
  <section class="mb-8" aria-label="Event-Filter">
    <EventFilters bind:searchTerm />
  </section>

  <!-- View selector -->
  <section class="mb-6" aria-label="Ansicht wählen">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-semibold text-smoke-50">
        {#if $calendarView === "month"}
          Monatsansicht
        {:else if $calendarView === "week"}
          Wochenansicht
        {:else}
          Listenansicht
        {/if}
      </h2>

      <div class="flex rounded-lg overflow-hidden border border-charcoal-700">
        <button
          class="px-4 py-2 text-sm font-medium transition-colors {$calendarView ===
          'month'
            ? 'bg-primary-900 text-primary-100'
            : 'bg-charcoal-800 text-smoke-300 hover:bg-charcoal-700'}"
          on:click={() => handleViewChange("month")}
          aria-pressed={$calendarView === "month"}
        >
          Monat
        </button>
        <button
          class="px-4 py-2 text-sm font-medium transition-colors {$calendarView ===
          'week'
            ? 'bg-primary-900 text-primary-100'
            : 'bg-charcoal-800 text-smoke-300 hover:bg-charcoal-700'}"
          on:click={() => handleViewChange("week")}
          aria-pressed={$calendarView === "week"}
        >
          Woche
        </button>
        <button
          class="px-4 py-2 text-sm font-medium transition-colors {$calendarView ===
          'list'
            ? 'bg-primary-900 text-primary-100'
            : 'bg-charcoal-800 text-smoke-300 hover:bg-charcoal-700'}"
          on:click={() => handleViewChange("list")}
          aria-pressed={$calendarView === "list"}
        >
          Liste
        </button>
      </div>
    </div>
  </section>

  <!-- Calendar/List content -->
  <section aria-label="Veranstaltungen">
    {#if $isLoading}
      <!-- Loading state -->
      <div class="flex items-center justify-center py-16">
        <div class="text-center">
          <div
            class="w-12 h-12 border-4 border-accent-400 border-t-transparent rounded-full animate-spin mx-auto mb-4"
          ></div>
          <p class="text-smoke-300">Lade Veranstaltungen...</p>
        </div>
      </div>
    {:else if $calendarView === "list"}
      <EventList events={$filteredEvents} />
    {:else}
      <Calendar view={$calendarView} events={$filteredEvents} />
    {/if}
  </section>

  <!-- Event count and pagination info -->
  {#if !$isLoading && $filteredEvents.length > 0}
    <footer class="mt-8 text-center text-smoke-400 text-sm">
      {$filteredEvents.length} Veranstaltung{$filteredEvents.length !== 1
        ? "en"
        : ""}
      {#if $events.length !== $filteredEvents.length}
        von {$events.length} gesamt
      {/if}
    </footer>
  {/if}

  <!-- Empty state -->
  {#if !$isLoading && $filteredEvents.length === 0}
    <div class="text-center py-16">
      <div class="text-6xl mb-4" aria-hidden="true">🔍</div>
      <h3 class="text-2xl font-semibold text-smoke-50 mb-2">
        Keine Events gefunden
      </h3>
      <p class="text-smoke-300 mb-6">
        {#if $events.length === 0}
          Derzeit sind keine Veranstaltungen geplant.
        {:else}
          Keine Events entsprechen deinen aktuellen Filterkriterien.
        {/if}
      </p>
      <div class="space-y-4">
        {#if $events.length > 0}
          <button
            class="btn btn-outline"
            on:click={() => window.location.reload()}
          >
            Filter zurücksetzen
          </button>
        {/if}
        <div>
          <a href="/submit-event" use:link class="btn btn-primary">
            Event vorschlagen
          </a>
        </div>
      </div>
    </div>
  {/if}
</main>

<!-- Event Modal -->
{#if $showEventModal}
  <EventModal />
{/if}
