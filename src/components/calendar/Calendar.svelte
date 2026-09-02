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
  import {
    getEventSegmentsForDay,
    type EventDaySegment,
  } from "../../utils/eventDates";

  export let view: CalendarView = "month";
  export let events: Event[] = [];

  dayjs.locale("de");

  let calendarGrid: HTMLElement;

  // Maximale Anzahl an Zeilen, die eine Tageszelle der Monatsansicht darstellt.
  const MAX_VISIBLE_ROWS_PER_DAY = 3;

  // Eine Zeile einer Tageszelle: entweder ein Event-Segment oder ein
  // Platzhalter, damit ein mehrtägiger Balken in allen Tageszellen einer Woche
  // auf derselben Höhe steht.
  type DayCellRow = EventDaySegment | null;

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
      // Mehrtägige Events belegen jede Tageszelle ihres Zeitraums, nicht nur
      // den Starttag.
      const segments = getEventSegmentsForDay(events, current);
      const isWeekStart = current.isSame(current.startOf("week"), "day");
      const isWeekEnd = current.isSame(current.endOf("week"), "day");

      days.push({
        date: current.toDate(),
        dayNumber: current.date(),
        isCurrentMonth: current.isSame($currentDate, "month"),
        isToday: current.isSame(dayjs(), "day"),
        isWeekend: current.day() === 0 || current.day() === 6,
        isWeekStart,
        isWeekEnd,
        segments,
      });

      current = current.add(1, "day");
    }

    return days;
  })();

  const segmentKey = (segment: EventDaySegment) =>
    `${segment.event.id}|${segment.event.startDate.getTime()}`;

  /**
   * Verteilt die mehrtägigen Events einer Kalenderwoche auf feste Zeilen
   * ("Lanes"). Ein Event belegt dieselbe Zeile in jeder Tageszelle, über die es
   * läuft – erst dadurch ergeben die einzelnen Segmente einen fortlaufenden
   * Balken. Freie Zeilen werden mit Platzhaltern aufgefüllt.
   */
  const assignWeekLanes = (week: typeof monthDays) => {
    const lanes: DayCellRow[][] = [];

    // Mehrtägige Segmente der Woche nach Event gruppieren. Eine Woche enthält
    // höchstens sieben Tage, deshalb genügt die lineare Suche über die Liste.
    const grouped: {
      key: string;
      entries: { segment: EventDaySegment; dayIndex: number }[];
    }[] = [];

    week.forEach((day, dayIndex) => {
      day.segments
        .filter((segment) => segment.isMultiDay)
        .forEach((segment) => {
          const key = segmentKey(segment);
          const group = grouped.find((candidate) => candidate.key === key);

          if (group) {
            group.entries.push({ segment, dayIndex });
          } else {
            grouped.push({ key, entries: [{ segment, dayIndex }] });
          }
        });
    });

    // Früher beginnende und längere Events bekommen die oberen Zeilen.
    const ordered = grouped
      .map((group) => group.entries)
      .sort((a, b) => {
        if (a[0].dayIndex !== b[0].dayIndex) {
          return a[0].dayIndex - b[0].dayIndex;
        }
        if (a[0].segment.dayCount !== b[0].segment.dayCount) {
          return b[0].segment.dayCount - a[0].segment.dayCount;
        }
        return (
          a[0].segment.event.startDate.getTime() -
          b[0].segment.event.startDate.getTime()
        );
      });

    ordered.forEach((entries) => {
      const dayIndexes = entries.map((entry) => entry.dayIndex);
      let laneIndex = lanes.findIndex((lane) =>
        dayIndexes.every((dayIndex) => lane[dayIndex] === null),
      );

      if (laneIndex === -1) {
        lanes.push(Array.from({ length: 7 }, () => null));
        laneIndex = lanes.length - 1;
      }

      entries.forEach((entry) => {
        lanes[laneIndex][entry.dayIndex] = entry.segment;
      });
    });

    return week.map((day, dayIndex) => {
      const rows: DayCellRow[] = lanes.map((lane) => lane[dayIndex]);
      rows.push(...day.segments.filter((segment) => !segment.isMultiDay));

      // Nachlaufende Platzhalter erzeugen nur leeren Raum.
      while (rows.length > 0 && rows[rows.length - 1] === null) {
        rows.pop();
      }

      return {
        ...day,
        rows: rows.slice(0, MAX_VISIBLE_ROWS_PER_DAY),
        hiddenCount: rows
          .slice(MAX_VISIBLE_ROWS_PER_DAY)
          .filter((row) => row !== null).length,
      };
    });
  };

  // Group month days into weeks for proper ARIA grid structure
  $: monthWeeks = (() => {
    const weeks = [];
    for (let i = 0; i < monthDays.length; i += 7) {
      weeks.push(assignWeekLanes(monthDays.slice(i, i + 7)));
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

      days.push({
        date: current.toDate(),
        dayNumber: current.date(),
        dayName: current.format("dddd"),
        isToday: current.isSame(dayjs(), "day"),
        isWeekend: current.day() === 0 || current.day() === 6,
        segments: getEventSegmentsForDay(events, current),
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

  const formatEventTime = (event: Event) =>
    dayjs(event.startDate).format("HH:mm");

  // Zeitraum eines Segments als Fließtext – bei mehrtägigen Events inklusive
  // Start- und Endtag.
  const formatSegmentRange = (segment: EventDaySegment) => {
    const { event } = segment;
    const start = dayjs(event.startDate);

    if (!segment.isMultiDay) {
      return event.isAllDay
        ? `${start.format("DD.MM.YYYY")}, ganztägig`
        : `${start.format("DD.MM.YYYY")}, ${start.format("HH:mm")} Uhr`;
    }

    const lastDay = start.add(segment.dayCount - 1, "day");

    return event.isAllDay
      ? `${start.format("DD.MM.YYYY")} bis ${lastDay.format("DD.MM.YYYY")}, ganztägig`
      : `${start.format("DD.MM.")} ${start.format("HH:mm")} Uhr bis ${lastDay.format("DD.MM.YYYY")} ${dayjs(event.endDate).format("HH:mm")} Uhr`;
  };

  // Accessible Name eines Segments. Fortsetzungstage nennen ihre Position im
  // Zeitraum, damit Screenreader den Zusammenhang erkennen.
  const getSegmentLabel = (segment: EventDaySegment) => {
    const base = `${segment.event.title}, ${formatSegmentRange(segment)}`;

    return segment.isMultiDay
      ? `${base} (Tag ${segment.dayIndex} von ${segment.dayCount})`
      : base;
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
              aria-label="{dayjs(day.date).format('DD. MMMM YYYY')}{day.segments
                .length > 0
                ? `, ${day.segments.length} Event${day.segments.length !== 1 ? 's' : ''}`
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
                {#each day.rows as row, rowIndex (rowIndex)}
                  {#if row === null}
                    <!-- Platzhalter hält die Lane eines mehrtägigen Events frei -->
                    <div
                      class="calendar-event calendar-event--spacer w-full text-left text-xs px-2 py-1"
                      aria-hidden="true"
                    >
                      <span class="calendar-event-title">&nbsp;</span>
                    </div>
                  {:else}
                    <button
                      class="calendar-event w-full text-left text-xs bg-primary-800 text-primary-100 px-2 py-1 rounded hover:bg-primary-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 md:truncate"
                      class:calendar-event--span={row.isMultiDay}
                      class:calendar-event--continues-left={row.isMultiDay &&
                        !row.isStart &&
                        !day.isWeekStart}
                      class:calendar-event--continues-right={row.isMultiDay &&
                        !row.isEnd &&
                        !day.isWeekEnd}
                      on:click={() => handleEventClick(row.event)}
                      title={getSegmentLabel(row)}
                      aria-label={getSegmentLabel(row)}
                    >
                      {#if row.isStart && !row.event.isAllDay}
                        <span class="calendar-event-time md:hidden">
                          {formatEventTime(row.event)}
                        </span>
                      {/if}
                      <span class="calendar-event-title">
                        {#if row.isStart || day.isWeekStart}{row.event
                            .title}{:else}&nbsp;{/if}
                      </span>
                    </button>
                  {/if}
                {/each}

                {#if day.hiddenCount > 0}
                  <div class="text-xs text-smoke-400 px-2">
                    +{day.hiddenCount} weitere
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
              {#each day.segments as segment (segmentKey(segment))}
                <button
                  class="w-full text-left p-3 bg-charcoal-800 border-l-4 border border-charcoal-700 rounded-lg hover:bg-charcoal-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 {segment.isMultiDay
                    ? 'border-l-primary-500'
                    : 'border-l-charcoal-700'}"
                  on:click={() => handleEventClick(segment.event)}
                  aria-label={getSegmentLabel(segment)}
                >
                  <div class="font-medium text-smoke-50 mb-1 text-sm">
                    {segment.event.title}
                  </div>
                  <div class="text-xs text-smoke-400">
                    {#if segment.isMultiDay}
                      Tag {segment.dayIndex} von {segment.dayCount} · {formatSegmentRange(
                        segment,
                      )}
                    {:else if segment.event.isAllDay}
                      Ganztägig
                    {:else}
                      {dayjs(segment.event.startDate).format("HH:mm")} Uhr
                    {/if}
                  </div>
                </button>
              {/each}
            </div>
          </div>
        {/each}
      </div>
    </div>
  {/if}
</div>

<style>
  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    grid-auto-rows: minmax(60px, auto);
    gap: 0;
    width: 100%;

    /* Abstand zwischen den Inhaltsflächen zweier Tageszellen:
       Innenabstand links + rechts plus die beiden Zellenrahmen. Ein
       fortgesetzter Balken überbrückt genau diesen Abstand. */
    --calendar-day-gutter: calc(0.5rem + 2px);
  }

  @media (min-width: 768px) {
    .calendar-grid {
      --calendar-day-gutter: calc(1rem + 2px);
    }
  }

  /* Blocklayout statt inline-block: sonst erzeugt der Zeilenkasten je nach
     Zellinhalt einen unterschiedlichen Versatz und die Balken benachbarter
     Tageszellen liegen nicht mehr auf einer Höhe. */
  .calendar-event {
    display: block;
  }

  /* Mehrtägige Events werden als fortlaufender Balken über alle betroffenen
     Tageszellen dargestellt. Das Segment ragt in den Zwischenraum zur
     Vortageszelle hinein, sodass keine sichtbare Lücke entsteht. */
  .calendar-event--span {
    position: relative;
    z-index: 1;
  }

  .calendar-event--continues-left {
    margin-left: calc(var(--calendar-day-gutter) * -1);
    /* Der Balken wird verlängert, nicht nur verschoben – "w-full" setzt eine
       feste Breite von 100 %. */
    width: calc(100% + var(--calendar-day-gutter));
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
  }

  .calendar-event--continues-right {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
  }

  /* Hält die Zeile eines mehrtägigen Balkens in Tageszellen frei, in denen
     das Event nicht läuft. */
  .calendar-event--spacer {
    visibility: hidden;
    pointer-events: none;
  }

  .calendar-day {
    aspect-ratio: auto;
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
    .calendar-container {
      --calendar-mobile-event-gap: 0.35rem;
      --calendar-mobile-event-min-height: 1.75rem;
      --calendar-mobile-event-padding-inline: 0.375rem;
    }

    .calendar-grid {
      font-size: 0.75rem;
    }

    .week-view .grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .calendar-event {
      display: flex;
      align-items: center;
      gap: var(--calendar-mobile-event-gap);
      min-height: var(--calendar-mobile-event-min-height);
      padding: 0.25rem var(--calendar-mobile-event-padding-inline);
      /* Nur die vertikalen Abstände zurücksetzen – der negative Margin
         fortlaufender Balken muss erhalten bleiben. */
      margin-block: 0;
      overflow: hidden;
    }

    .calendar-day .space-y-1 {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .calendar-event-time {
      flex-shrink: 0;
      font-variant-numeric: tabular-nums;
      font-weight: 600;
    }

    .calendar-event-title {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* Override tailwind space-y-1 */
    .calendar-day .space-y-1 > :not([hidden]) ~ :not([hidden]) {
      margin-top: 0;
    }
  }

  /* Very small screens */
  @media (max-width: 480px) {
    .calendar-day {
      min-height: 50px;
    }
  }
</style>
