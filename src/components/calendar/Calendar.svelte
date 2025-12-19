<script lang="ts">
  import dayjs from "dayjs";
  import "dayjs/locale/de";
  import { onMount } from "svelte";
  import {
    currentDate,
    navigateCalendar,
    openEventModal,
  } from "../../stores/calendar";
  import type { CalendarView, Event } from "../../types/calendar";

  export let view: CalendarView = "month";
  export let events: Event[] = [];

  dayjs.locale("de");

  let calendarGrid: HTMLElement;

  // Calendar state
  $: currentMonth = dayjs($currentDate).format("MMMM YYYY");
  $: currentWeek = `Woche vom ${dayjs($currentDate).startOf("week").format("DD.MM.")} - ${dayjs($currentDate).endOf("week").format("DD.MM.YYYY")}`;

  // Generate calendar days for month view
  $: monthDays = (() => {
    if (view !== "month") return [];

    const startOfMonth = dayjs($currentDate).startOf("month");
    const endOfMonth = dayjs($currentDate).endOf("month");
    const startOfWeek = startOfMonth.startOf("week");
    const endOfWeek = endOfMonth.endOf("week");

    const days = [];
    let current = startOfWeek;

    while (current.isBefore(endOfWeek) || current.isSame(endOfWeek, "day")) {
      const dayEvents = events.filter((event) =>
        dayjs(event.startDate).isSame(current, "day"),
      );

      days.push({
        date: current.toDate(),
        dayNumber: current.date(),
        isCurrentMonth: current.isSame($currentDate, "month"),
        isToday: current.isSame(dayjs(), "day"),
        isWeekend: current.day() === 0 || current.day() === 6,
        events: dayEvents,
      });

      current = current.add(1, "day");
    }

    return days;
  })();

  // Group month days into weeks for proper ARIA grid structure
  $: monthWeeks = (() => {
    const weeks = [];
    for (let i = 0; i < monthDays.length; i += 7) {
      weeks.push(monthDays.slice(i, i + 7));
    }
    return weeks;
  })();

  // Generate week days for week view
  $: weekDays = (() => {
    if (view !== "week") return [];

    const startOfWeek = dayjs($currentDate).startOf("week");
    const days = [];

    for (let i = 0; i < 7; i++) {
      const current = startOfWeek.add(i, "day");
      const dayEvents = events.filter((event) =>
        dayjs(event.startDate).isSame(current, "day"),
      );

      days.push({
        date: current.toDate(),
        dayNumber: current.date(),
        dayName: current.format("dddd"),
        isToday: current.isSame(dayjs(), "day"),
        isWeekend: current.day() === 0 || current.day() === 6,
        events: dayEvents,
      });
    }

    return days;
  })();

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

  // Event handlers
  const handleEventClick = (event: Event) => {
    openEventModal(event);
  };

  // Keyboard navigation with better accessibility
  const handleKeydown = (e: KeyboardEvent) => {
    const target = e.target as HTMLElement;

    // Only handle keys when focus is on calendar or its children
    if (!calendarGrid?.contains(target) && target !== calendarGrid) {
      return;
    }

    let handled = false;

    switch (e.key) {
      case "ArrowLeft":
        e.preventDefault();
        goToPrevious();
        handled = true;
        break;
      case "ArrowRight":
        e.preventDefault();
        goToNext();
        handled = true;
        break;
      case "Home":
        e.preventDefault();
        goToToday();
        handled = true;
        break;
      case "PageUp":
        e.preventDefault();
        if (view === "month") {
          currentDate.update((date) =>
            dayjs(date).subtract(1, "month").toDate(),
          );
        } else {
          currentDate.update((date) =>
            dayjs(date).subtract(1, "week").toDate(),
          );
        }
        handled = true;
        break;
      case "PageDown":
        e.preventDefault();
        if (view === "month") {
          currentDate.update((date) => dayjs(date).add(1, "month").toDate());
        } else {
          currentDate.update((date) => dayjs(date).add(1, "week").toDate());
        }
        handled = true;
        break;
    }

    if (handled) {
      // Announce navigation to screen readers
      const announcement =
        view === "month"
          ? `Kalenderansicht für ${dayjs($currentDate).format("MMMM YYYY")}`
          : `Wochenansicht für ${dayjs($currentDate).startOf("week").format("DD.MM.")} bis ${dayjs($currentDate).endOf("week").format("DD.MM.YYYY")}`;

      // Create temporary live region for announcements
      const liveRegion = document.createElement("div");
      liveRegion.setAttribute("aria-live", "polite");
      liveRegion.setAttribute("aria-atomic", "true");
      liveRegion.className = "sr-only";
      liveRegion.textContent = announcement;
      document.body.appendChild(liveRegion);

      setTimeout(() => {
        document.body.removeChild(liveRegion);
      }, 1000);
    }
  };

  onMount(() => {
    document.addEventListener("keydown", handleKeydown);
    return () => {
      document.removeEventListener("keydown", handleKeydown);
    };
  });
</script>

<div
  class="calendar-container"
  role="application"
  aria-label="Hypnose Stammtisch Kalender"
>
  <!-- Calendar header -->
  <header class="flex items-center justify-between mb-6">
    <div>
      <h2
        class="text-2xl font-display font-semibold text-smoke-50"
        id="calendar-title"
      >
        {view === "month" ? currentMonth : currentWeek}
      </h2>
      <p class="text-sm text-smoke-400 mt-1" id="calendar-instructions">
        Tastaturnavigation: Pfeiltasten für vor/zurück, Bild hoch/runter für
        Monat/Woche, Pos1 für heute
      </p>
    </div>

    <div
      class="flex items-center space-x-2"
      role="toolbar"
      aria-label="Kalender Navigation"
    >
      <button
        class="btn btn-ghost p-2 focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-900"
        on:click={goToPrevious}
        aria-label="Vorherige {view === 'month' ? 'Monat' : 'Woche'}"
        aria-describedby="calendar-instructions"
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
        aria-describedby="calendar-instructions"
      >
        Heute
      </button>

      <button
        class="btn btn-ghost p-2 focus:ring-2 focus:ring-accent-400 focus:ring-offset-2 focus:ring-offset-charcoal-900"
        on:click={goToNext}
        aria-label="Nächste {view === 'month' ? 'Monat' : 'Woche'}"
        aria-describedby="calendar-instructions"
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

  {#if view === "month"}
    <!-- Month view -->
    <div
      bind:this={calendarGrid}
      class="calendar-grid"
      role="grid"
      aria-labelledby="calendar-title"
      aria-describedby="calendar-instructions"
      tabindex="0"
    >
      <!-- Day headers -->
      <div class="contents" role="row">
        {#each [{ full: "Montag", short: "Mo" }, { full: "Dienstag", short: "Di" }, { full: "Mittwoch", short: "Mi" }, { full: "Donnerstag", short: "Do" }, { full: "Freitag", short: "Fr" }, { full: "Samstag", short: "Sa" }, { full: "Sonntag", short: "So" }] as day, index (day.full)}
          <div
            class="calendar-day-header p-1 md:p-3 text-center text-xs md:text-sm font-medium text-smoke-400 border-b border-charcoal-700 truncate"
            role="columnheader"
            aria-label={day.full}
            id="day-header-{index}"
          >
            <span class="hidden sm:inline">{day.full}</span>
            <span class="sm:hidden">{day.short}</span>
          </div>
        {/each}
      </div>

      <!-- Calendar weeks and days -->
      {#each monthWeeks as week, weekIndex (weekIndex)}
        <div class="contents" role="row">
          {#each week as day (day.date)}
            <div
              class="calendar-day min-h-[60px] md:min-h-[120px] p-1 md:p-2 border border-charcoal-700 {day.isCurrentMonth
                ? 'bg-charcoal-800'
                : 'bg-charcoal-900 opacity-50'} {day.isToday
                ? 'ring-2 ring-accent-400'
                : ''} hover:bg-charcoal-700 transition-colors"
              role="gridcell"
              tabindex="0"
              aria-label="{dayjs(day.date).format('DD. MMMM YYYY')}{day.events
                .length > 0
                ? `, ${day.events.length} Event${day.events.length !== 1 ? 's' : ''}`
                : ''}"
            >
              <!-- Day number -->
              <div class="text-right mb-2">
                <span
                  class="text-sm {day.isToday
                    ? 'bg-accent-400 text-gray-900 w-6 h-6 rounded-full inline-flex items-center justify-center font-medium'
                    : day.isCurrentMonth
                      ? 'text-smoke-200'
                      : 'text-smoke-500'}"
                >
                  {day.dayNumber}
                </span>
              </div>

              <!-- Events -->
              <div class="space-y-1">
                {#each day.events.slice(0, 3) as event (event.id)}
                  <button
                    class="calendar-event w-full text-left text-xs bg-primary-800 text-primary-100 px-2 py-1 rounded truncate hover:bg-primary-700 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-accent-400"
                    on:click={() => handleEventClick(event)}
                    title="{event.title} - {dayjs(event.startDate).format(
                      'HH:mm',
                    )} Uhr"
                  >
                    {event.title}
                  </button>
                {/each}

                {#if day.events.length > 3}
                  <div class="text-xs text-smoke-400 px-2">
                    +{day.events.length - 3} weitere
                  </div>
                {/if}
              </div>
            </div>
          {/each}
        </div>
      {/each}
    </div>
  {:else if view === "week"}
    <!-- Week view -->
    <div class="week-view">
      <div class="grid grid-cols-7 gap-4">
        {#each weekDays as day (day.date)}
          <div class="week-day">
            <!-- Day header -->
            <div class="text-center mb-4 pb-2 border-b border-charcoal-700">
              <div class="text-sm text-smoke-400 mb-1">
                {day.dayName}
              </div>
              <div
                class="text-lg {day.isToday
                  ? 'bg-accent-400 text-gray-900 w-8 h-8 rounded-full inline-flex items-center justify-center font-medium'
                  : 'text-smoke-200'}"
              >
                {day.dayNumber}
              </div>
            </div>

            <!-- Events for this day -->
            <div class="space-y-2">
              {#each day.events as event (event.id)}
                <button
                  class="w-full text-left p-3 bg-charcoal-800 border border-charcoal-700 rounded-lg hover:bg-charcoal-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400"
                  on:click={() => handleEventClick(event)}
                >
                  <div class="font-medium text-smoke-50 mb-1 text-sm">
                    {event.title}
                  </div>
                  <div class="text-xs text-smoke-400">
                    {dayjs(event.startDate).format("HH:mm")} Uhr
                  </div>
                </button>
              {/each}
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}

  <!-- Events summary -->
  <footer
    class="mt-6 text-center text-sm text-smoke-400"
    aria-live="polite"
    aria-atomic="true"
  >
    <span id="events-summary">
      {events.length} Veranstaltung{events.length !== 1 ? "en" : ""}
      {view === "month" ? "in diesem Monat" : "in dieser Woche"}
    </span>
  </footer>
</div>

<style>
  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0;
    width: 100%;
  }

  .contents {
    display: contents;
  }

  .calendar-day-header {
    font-weight: 500;
  }

  .calendar-day:focus {
    outline: 2px solid #41f2c0; /* accent-400 */
    outline-offset: -2px;
  }

  .calendar-day:focus-within {
    outline: 2px solid #41f2c0; /* accent-400 */
    outline-offset: -2px;
  }

  /* Screen reader only class */
  :global(.sr-only) {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  .week-view {
    min-height: 500px;
  }

  .week-day {
    min-height: 200px;
  }

  /* High contrast mode support */
  @media (prefers-contrast: high) {
    .calendar-day {
      border: 2px solid;
    }

    .calendar-day:focus,
    .calendar-day:focus-within {
      outline: 3px solid;
    }
  }

  /* Mobile styles */
  @media (max-width: 768px) {
    .calendar-grid {
      font-size: 0.75rem;
    }

    .week-view .grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    /* Turn event bars into dots on mobile to save space */
    .calendar-event {
      width: 6px;
      height: 6px;
      padding: 0;
      border-radius: 50%;
      color: transparent;
      overflow: hidden;
      margin: 0 auto 2px auto;
      display: inline-block;
    }

    /* Center the dots container */
    .calendar-day .space-y-1 {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2px;
    }

    /* Override tailwind space-y-1 */
    .calendar-day .space-y-1 > :not([hidden]) ~ :not([hidden]) {
      margin-top: 0;
    }

    /* Remove the "+X more" text on mobile */
    .calendar-day .text-xs.text-smoke-400 {
      display: none;
    }
  }

  /* Very small screens */
  @media (max-width: 480px) {
    .calendar-day {
      min-height: 50px;
    }
  }
</style>
