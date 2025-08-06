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

  // Keyboard navigation
  const handleKeydown = (e: KeyboardEvent) => {
    if (
      e.target !== calendarGrid &&
      !calendarGrid?.contains(e.target as Node)
    ) {
      return;
    }

    switch (e.key) {
      case "ArrowLeft":
        e.preventDefault();
        goToPrevious();
        break;
      case "ArrowRight":
        e.preventDefault();
        goToNext();
        break;
      case "Home":
        e.preventDefault();
        goToToday();
        break;
    }
  };

  onMount(() => {
    document.addEventListener("keydown", handleKeydown);
    return () => {
      document.removeEventListener("keydown", handleKeydown);
    };
  });
</script>

<div class="calendar-container">
  <!-- Calendar header -->
  <header class="flex items-center justify-between mb-6">
    <div>
      <h2 class="text-2xl font-display font-semibold text-smoke-50">
        {view === "month" ? currentMonth : currentWeek}
      </h2>
      <p class="text-sm text-smoke-400 mt-1">
        Verwende die Pfeiltasten zur Navigation, Home für heute
      </p>
    </div>

    <div class="flex items-center space-x-2">
      <button
        class="btn btn-ghost p-2"
        on:click={goToPrevious}
        aria-label="Vorherige {view === 'month' ? 'Monat' : 'Woche'}"
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

      <button class="btn btn-outline px-4 py-2" on:click={goToToday}>
        Heute
      </button>

      <button
        class="btn btn-ghost p-2"
        on:click={goToNext}
        aria-label="Nächste {view === 'month' ? 'Monat' : 'Woche'}"
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
      aria-label="Kalender für {currentMonth}"
      tabindex="0"
    >
      <!-- Day headers -->
      <div class="contents" role="row">
        {#each ["Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag"] as dayName}
          <div
            class="calendar-day-header p-3 text-center text-sm font-medium text-smoke-400 border-b border-charcoal-700"
            role="columnheader"
          >
            {dayName}
          </div>
        {/each}
      </div>

      <!-- Calendar days -->
      {#each monthDays as day}
        <div
          class="calendar-day min-h-[120px] p-2 border border-charcoal-700 {day.isCurrentMonth
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
                ? 'bg-accent-400 text-charcoal-900 w-6 h-6 rounded-full inline-flex items-center justify-center font-medium'
                : day.isCurrentMonth
                  ? 'text-smoke-200'
                  : 'text-smoke-500'}"
            >
              {day.dayNumber}
            </span>
          </div>

          <!-- Events -->
          <div class="space-y-1">
            {#each day.events.slice(0, 3) as event}
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
  {:else if view === "week"}
    <!-- Week view -->
    <div class="week-view">
      <div class="grid grid-cols-7 gap-4">
        {#each weekDays as day}
          <div class="week-day">
            <!-- Day header -->
            <div class="text-center mb-4 pb-2 border-b border-charcoal-700">
              <div class="text-sm text-smoke-400 mb-1">
                {day.dayName}
              </div>
              <div
                class="text-lg {day.isToday
                  ? 'bg-accent-400 text-charcoal-900 w-8 h-8 rounded-full inline-flex items-center justify-center font-medium'
                  : 'text-smoke-200'}"
              >
                {day.dayNumber}
              </div>
            </div>

            <!-- Events for this day -->
            <div class="space-y-2">
              {#each day.events as event}
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
  <footer class="mt-6 text-center text-sm text-smoke-400">
    {events.length} Veranstaltung{events.length !== 1 ? "en" : ""}
    {view === "month" ? "in diesem Monat" : "in dieser Woche"}
  </footer>
</div>

<style>
  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
  }

  .calendar-day:focus {
    outline: 2px solid theme("colors.accent.400");
    outline-offset: -2px;
  }

  .week-view {
    min-height: 500px;
  }

  @media (max-width: 768px) {
    .calendar-grid {
      font-size: 0.875rem;
    }

    .calendar-day {
      min-height: 80px;
    }

    .week-view .grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }
  }
</style>
