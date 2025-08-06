<script lang="ts">
  import { link } from "svelte-spa-router";
  import { events, isLoading } from "../../stores/calendar";
  import EventCard from "../calendar/EventCard.svelte";

  let upcomingEvents: any[] = [];

  // Subscribe to events store
  $: upcomingEvents = $events.slice(0, 6); // Show max 6 upcoming events
</script>

<div class="space-y-8">
  {#if $isLoading}
    <!-- Loading skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each Array(6) as _}
        <div class="card animate-pulse">
          <div class="h-6 bg-charcoal-700 rounded mb-4"></div>
          <div class="h-4 bg-charcoal-700 rounded mb-2"></div>
          <div class="h-4 bg-charcoal-700 rounded w-3/4 mb-4"></div>
          <div class="h-3 bg-charcoal-700 rounded w-1/2"></div>
        </div>
      {/each}
    </div>
  {:else if upcomingEvents.length > 0}
    <!-- Events grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each upcomingEvents as event (event.id)}
        <EventCard {event} showModal={true} />
      {/each}
    </div>
  {:else}
    <!-- No events state -->
    <div class="text-center py-12">
      <div class="text-6xl mb-4" aria-hidden="true">📅</div>
      <h3 class="text-xl font-semibold text-smoke-50 mb-2">
        Keine kommenden Events
      </h3>
      <p class="text-smoke-300 mb-6">
        Derzeit sind keine Veranstaltungen geplant. Schaue bald wieder vorbei!
      </p>
      <div class="space-y-4">
        <a href="/submit-event" use:link class="btn btn-primary inline-block">
          Event vorschlagen
        </a>
        <div>
          <a
            href="/contact"
            use:link
            class="text-accent-400 hover:text-accent-300 underline"
          >
            Benachrichtigung erhalten
          </a>
        </div>
      </div>
    </div>
  {/if}

  <!-- Newsletter signup hint -->
  <div
    class="bg-charcoal-700 border border-charcoal-600 rounded-lg p-6 text-center"
  >
    <h3 class="text-lg font-semibold text-smoke-50 mb-2">
      Verpasse keine Events
    </h3>
    <p class="text-smoke-300 mb-4">
      Abonniere unseren Kalender-Feed oder kontaktiere uns für Updates über neue
      Veranstaltungen.
    </p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a
        href="/calendar.ics?token=public"
        class="btn btn-outline text-sm"
        target="_blank"
        rel="noopener noreferrer"
      >
        📅 Kalender abonnieren
      </a>
      <a href="/contact" use:link class="btn btn-ghost text-sm"> 📧 Kontakt </a>
    </div>
  </div>
</div>
