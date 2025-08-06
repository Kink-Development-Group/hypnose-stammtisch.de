<script lang="ts">
  import dayjs from "dayjs";
  import "dayjs/locale/de";
  import { createEventDispatcher } from "svelte";
  import { openEventModal } from "../../stores/calendar";
  import type { Event } from "../../types/calendar";

  export let event: Event;
  export let showModal: boolean = true;

  const dispatch = createEventDispatcher();

  // Format dates
  dayjs.locale("de");

  $: formattedDate = dayjs(event.startDate).format("DD. MMM YYYY");
  $: formattedTime = event.isAllDay
    ? "Ganztägig"
    : `${dayjs(event.startDate).format("HH:mm")} - ${dayjs(event.endDate).format("HH:mm")}`;

  // Handle card click
  const handleClick = () => {
    if (showModal) {
      openEventModal(event);
    } else {
      dispatch("click", event);
    }
  };

  // Handle keyboard navigation
  const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      handleClick();
    }
  };

  // Get location display text
  $: locationText = (() => {
    switch (event.locationType) {
      case "online":
        return "Online";
      case "hybrid":
        return "Hybrid (Online & Vor Ort)";
      case "physical":
        return event.locationAddress || "Vor Ort";
      default:
        return "Ort TBA";
    }
  })();

  // Get tag colors
  const getTagColor = (tag: string) => {
    const colors = {
      beginner: "badge-consent",
      workshop: "badge-primary",
      advanced: "badge-caution",
      theory: "badge-accent",
      practice: "badge-secondary",
    };
    return colors[tag.toLowerCase() as keyof typeof colors] || "badge-primary";
  };
</script>

<button
  class="card cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-medium focus-within:ring-2 focus-within:ring-accent-400 focus-within:ring-offset-2 focus-within:ring-offset-charcoal-900 w-full text-left"
  aria-label="Event: {event.title}. Klicken für Details."
  on:click={handleClick}
  on:keydown={handleKeydown}
>
  <!-- Event header -->
  <header class="mb-4">
    <div class="flex items-start justify-between mb-2">
      <h3 class="text-lg font-semibold text-smoke-50 leading-tight">
        {event.title}
      </h3>
      {#if event.beginnerFriendly}
        <span
          class="badge badge-consent ml-2 flex-shrink-0"
          aria-label="Anfängerfreundlich"
        >
          ✨
        </span>
      {/if}
    </div>

    <!-- Date and time -->
    <div class="flex items-center text-sm text-smoke-300 mb-2">
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
      <time datetime={event.startDate.toISOString()}>
        {formattedDate}
      </time>
    </div>

    <div class="flex items-center text-sm text-smoke-300 mb-2">
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
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      <span>{formattedTime}</span>
    </div>

    <!-- Location -->
    <div class="flex items-center text-sm text-smoke-300 mb-3">
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
      <span>{locationText}</span>
    </div>
  </header>

  <!-- Description -->
  <div class="mb-4">
    <p class="text-smoke-300 text-sm leading-relaxed line-clamp-3">
      {event.description}
    </p>
  </div>

  <!-- Tags -->
  {#if event.tags.length > 0}
    <div class="flex flex-wrap gap-2 mb-4">
      {#each event.tags.slice(0, 3) as tag}
        <span class="badge {getTagColor(tag)}">
          {tag}
        </span>
      {/each}
      {#if event.tags.length > 3}
        <span class="badge badge-primary">
          +{event.tags.length - 3}
        </span>
      {/if}
    </div>
  {/if}

  <!-- Footer -->
  <footer
    class="flex items-center justify-between text-xs text-smoke-400 border-t border-charcoal-700 pt-3"
  >
    <span>
      {#if event.seriesId}
        <svg
          class="w-3 h-3 inline mr-1"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
            clip-rule="evenodd"
          />
        </svg>
        Wiederkehrend
      {:else}
        <svg
          class="w-3 h-3 inline mr-1"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
            clip-rule="evenodd"
          />
        </svg>
        Einzelveranstaltung
      {/if}
    </span>

    <span class="text-accent-400" aria-hidden="true"> Details → </span>
  </footer>
</button>

<style type="text/css">
  .line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* Hover effects */
  button:hover .text-accent-400 {
    color: rgb(var(--accent-300) / 1);
  }

  /* Focus styles are handled by Tailwind classes */
</style>
