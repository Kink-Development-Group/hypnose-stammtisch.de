<script lang="ts">
  import dayjs from "dayjs";
  import "dayjs/locale/de";
  import { onDestroy, onMount } from "svelte";

  dayjs.locale("de");

  // Props
  export let value: string = ""; // Format: "YYYY-MM-DD"
  export let id: string = "";
  export let label: string = "";
  export let required: boolean = false;
  export let disabled: boolean = false;
  export let error: string = "";
  export let minDate: string = "";
  export let maxDate: string = "";
  export let placeholder: string = "Datum auswählen";
  export let onchange: ((detail: { value: string }) => void) | undefined =
    undefined;

  // Internal state
  let isOpen = false;
  let containerRef: HTMLDivElement;
  let inputRef: HTMLButtonElement;

  // Parse current value
  $: parsedValue = value ? dayjs(value) : null;

  // Calendar state
  let viewDate = parsedValue || dayjs();
  $: viewMonth = viewDate.month();
  $: viewYear = viewDate.year();
  $: daysInMonth = viewDate.daysInMonth();
  $: firstDayOfMonth = viewDate.startOf("month").day(); // 0 = Sunday
  $: adjustedFirstDay = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1; // Convert to Monday start

  // Format display value
  $: displayValue = parsedValue?.format("DD.MM.YYYY") || "";

  // Generate calendar days
  $: calendarDays = generateCalendarDays(adjustedFirstDay, daysInMonth);

  function generateCalendarDays(
    firstDay: number,
    days: number,
  ): (number | null)[] {
    const result: (number | null)[] = [];
    for (let i = 0; i < firstDay; i++) {
      result.push(null);
    }
    for (let i = 1; i <= days; i++) {
      result.push(i);
    }
    return result;
  }

  // Weekday headers (German, starting Monday)
  const weekdays = ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"];
  const months = [
    "Januar",
    "Februar",
    "März",
    "April",
    "Mai",
    "Juni",
    "Juli",
    "August",
    "September",
    "Oktober",
    "November",
    "Dezember",
  ];

  function toggle() {
    if (disabled) return;
    isOpen = !isOpen;
    if (isOpen) {
      viewDate = parsedValue || dayjs();
    }
  }

  function close() {
    isOpen = false;
  }

  function selectDay(day: number | null) {
    if (day === null) return;
    const newDate = viewDate.date(day);
    value = newDate.format("YYYY-MM-DD");
    onchange?.({ value });
    close();
  }

  function prevMonth() {
    viewDate = viewDate.subtract(1, "month");
  }

  function nextMonth() {
    viewDate = viewDate.add(1, "month");
  }

  function prevYear() {
    viewDate = viewDate.subtract(1, "year");
  }

  function nextYear() {
    viewDate = viewDate.add(1, "year");
  }

  function selectToday() {
    const today = dayjs();
    viewDate = today;
    value = today.format("YYYY-MM-DD");
    onchange?.({ value });
    close();
  }

  function clear() {
    value = "";
    onchange?.({ value: "" });
    close();
  }

  function isToday(day: number): boolean {
    const today = dayjs();
    return (
      day === today.date() &&
      viewMonth === today.month() &&
      viewYear === today.year()
    );
  }

  function isSelected(day: number): boolean {
    if (!parsedValue) return false;
    return (
      day === parsedValue.date() &&
      viewMonth === parsedValue.month() &&
      viewYear === parsedValue.year()
    );
  }

  function isDisabledDate(day: number): boolean {
    const date = viewDate.date(day);
    if (minDate && date.isBefore(dayjs(minDate), "day")) return true;
    if (maxDate && date.isAfter(dayjs(maxDate), "day")) return true;
    return false;
  }

  // Mobile detection
  let isMobile = false;

  function checkMobile() {
    isMobile = window.innerWidth < 640;
  }

  // Click outside handler
  function handleClickOutside(event: MouseEvent) {
    if (isMobile) return;
    if (containerRef && !containerRef.contains(event.target as Node)) {
      close();
    }
  }

  // Keyboard navigation
  function handleKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") {
      close();
      inputRef?.focus();
    }
  }

  // Prevent body scroll when modal is open on mobile
  $: if (typeof document !== "undefined") {
    if (isOpen && isMobile) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
  }

  onMount(() => {
    checkMobile();
    window.addEventListener("resize", checkMobile);
    document.addEventListener("click", handleClickOutside);
    document.addEventListener("keydown", handleKeydown);
  });

  onDestroy(() => {
    window.removeEventListener("resize", checkMobile);
    document.removeEventListener("click", handleClickOutside);
    document.removeEventListener("keydown", handleKeydown);
    if (typeof document !== "undefined") {
      document.body.style.overflow = "";
    }
  });
</script>

<div class="relative" bind:this={containerRef}>
  {#if label}
    <label
      for={id}
      class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1.5"
    >
      {label}
      {#if required}
        <span class="text-boundaries" aria-label="Pflichtfeld">*</span>
      {/if}
    </label>
  {/if}

  <!-- Input Display -->
  <button
    type="button"
    {id}
    bind:this={inputRef}
    on:click={toggle}
    {disabled}
    aria-haspopup="dialog"
    aria-expanded={isOpen}
    aria-describedby={error ? `${id}-error` : undefined}
    class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-left text-sm rounded-lg border transition-all duration-200
           {disabled
      ? 'bg-gray-100 dark:bg-charcoal-800 text-gray-400 dark:text-smoke-600 cursor-not-allowed'
      : 'bg-charcoal-800 dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100 cursor-pointer hover:border-primary-400 dark:hover:border-primary-500'}
           {error
      ? 'border-boundaries ring-1 ring-boundaries'
      : 'border-gray-300 dark:border-charcoal-500'}
           focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
  >
    <span class="flex items-center gap-2">
      <svg
        class="w-5 h-5 text-gray-400 dark:text-smoke-500"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
        />
      </svg>
      <span class={displayValue ? "" : "text-gray-400 dark:text-smoke-600"}>
        {displayValue || placeholder}
      </span>
    </span>
    <svg
      class="w-4 h-4 text-gray-400 dark:text-smoke-500 transition-transform duration-200 {isOpen
        ? 'rotate-180'
        : ''}"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M19 9l-7 7-7-7"
      />
    </svg>
  </button>

  {#if error}
    <p id="{id}-error" class="mt-1.5 text-sm text-boundaries" role="alert">
      {error}
    </p>
  {/if}

  <!-- Mobile Backdrop -->
  {#if isOpen && isMobile}
    <div
      class="fixed inset-0 bg-black/50 z-40 animate-fade-in"
      on:click={close}
      on:keydown={(e) => e.key === "Escape" && close()}
      role="button"
      tabindex="-1"
      aria-label="Schließen"
    ></div>
  {/if}

  <!-- Dropdown Calendar -->
  {#if isOpen}
    <div
      role="dialog"
      aria-label="Datum auswählen"
      aria-modal={isMobile ? "true" : undefined}
      class="{isMobile
        ? 'fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-2xl'
        : 'absolute z-50 mt-2 rounded-xl'} bg-charcoal-800 dark:bg-charcoal-800 shadow-xl border border-gray-200 dark:border-charcoal-600 overflow-hidden animate-fade-in sm:min-w-[300px]"
    >
      <!-- Mobile Header -->
      {#if isMobile}
        <div
          class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-charcoal-600"
        >
          <h3 class="text-base font-semibold text-gray-900 dark:text-smoke-100">
            Datum auswählen
          </h3>
          <button
            type="button"
            on:click={close}
            class="p-2 -m-2 text-gray-400 hover:text-gray-600 dark:text-smoke-500 dark:hover:text-smoke-300"
            aria-label="Schließen"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      {/if}

      <div class="p-4">
        <!-- Month/Year Navigation -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-1">
            <button
              type="button"
              on:click={prevYear}
              class="p-1.5 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Vorheriges Jahr"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                />
              </svg>
            </button>
            <button
              type="button"
              on:click={prevMonth}
              class="p-1.5 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Vorheriger Monat"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>
          </div>

          <span class="text-sm font-semibold text-gray-900 dark:text-smoke-100">
            {months[viewMonth]}
            {viewYear}
          </span>

          <div class="flex items-center gap-1">
            <button
              type="button"
              on:click={nextMonth}
              class="p-1.5 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Nächster Monat"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
            <button
              type="button"
              on:click={nextYear}
              class="p-1.5 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Nächstes Jahr"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 5l7 7-7 7M5 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 gap-1 mb-2">
          {#each weekdays as day (day)}
            <div
              class="text-center text-xs font-medium text-gray-500 dark:text-smoke-500 py-1"
            >
              {day}
            </div>
          {/each}
        </div>

        <!-- Calendar Grid -->
        <div
          class="grid grid-cols-7 gap-0.5 sm:gap-1"
          role="grid"
          aria-label="Kalender"
        >
          {#each calendarDays as day, index (index)}
            {#if day === null}
              <div class="w-10 h-10 sm:w-9 sm:h-9"></div>
            {:else}
              <button
                type="button"
                on:click={() => selectDay(day)}
                disabled={isDisabledDate(day)}
                class="w-10 h-10 sm:w-9 sm:h-9 rounded-lg text-sm font-medium transition-all duration-150 touch-manipulation
                       {isSelected(day)
                  ? 'bg-primary-600 text-white shadow-sm'
                  : isToday(day)
                    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-400 dark:ring-primary-600'
                    : 'text-gray-700 dark:text-smoke-200 hover:bg-gray-100 dark:hover:bg-charcoal-700 active:bg-gray-200 dark:active:bg-charcoal-600'}
                       {isDisabledDate(day)
                  ? 'opacity-40 cursor-not-allowed'
                  : 'cursor-pointer'}"
                aria-label="{day}. {months[viewMonth]} {viewYear}{isToday(day)
                  ? ', heute'
                  : ''}{isSelected(day) ? ', ausgewählt' : ''}"
                aria-pressed={isSelected(day)}
              >
                {day}
              </button>
            {/if}
          {/each}
        </div>
      </div>

      <!-- Footer Actions -->
      <div
        class="flex items-center justify-between gap-2 p-3 border-t border-gray-200 dark:border-charcoal-600 bg-charcoal-800 dark:bg-charcoal-900"
      >
        <div class="flex gap-2">
          <button
            type="button"
            on:click={selectToday}
            class="px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
          >
            Heute
          </button>
          <button
            type="button"
            on:click={clear}
            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 rounded-lg transition-colors"
          >
            Löschen
          </button>
        </div>
        <button
          type="button"
          on:click={close}
          class="px-4 py-1.5 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors shadow-sm"
        >
          Schließen
        </button>
      </div>
    </div>
  {/if}
</div>
