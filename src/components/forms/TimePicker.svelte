<script lang="ts">
  import { onDestroy, onMount } from "svelte";

  // Props
  export let value: string = ""; // Format: "HH:mm"
  export let id: string = "";
  export let label: string = "";
  export let required: boolean = false;
  export let disabled: boolean = false;
  export let error: string = "";
  export let placeholder: string = "Zeit auswählen";
  export let step: number = 5; // Minute step
  export let onchange: ((detail: { value: string }) => void) | undefined =
    undefined;

  // Internal state
  let isOpen = false;
  let containerRef: HTMLDivElement;
  let inputRef: HTMLButtonElement;

  // Parse current value
  $: parsedHours = value ? parseInt(value.split(":")[0]) || 0 : 12;
  $: parsedMinutes = value ? parseInt(value.split(":")[1]) || 0 : 0;

  let hours = parsedHours;
  let minutes = parsedMinutes;

  // Sync when value changes externally
  $: {
    if (value) {
      hours = parsedHours;
      minutes = parsedMinutes;
    }
  }

  // Format display value
  $: displayValue = value ? `${value} Uhr` : "";

  function toggle() {
    if (disabled) return;
    isOpen = !isOpen;
    if (isOpen) {
      hours = parsedHours;
      minutes = parsedMinutes;
    }
  }

  function close() {
    isOpen = false;
  }

  function updateHours(newHours: number) {
    hours = Math.max(0, Math.min(23, newHours));
    emitChange();
  }

  function updateMinutes(newMinutes: number) {
    minutes = Math.max(0, Math.min(59, newMinutes));
    emitChange();
  }

  function incrementHours() {
    updateHours((hours + 1) % 24);
  }

  function decrementHours() {
    updateHours(hours === 0 ? 23 : hours - 1);
  }

  function incrementMinutes() {
    updateMinutes((Math.floor((minutes + step) / step) * step) % 60);
  }

  function decrementMinutes() {
    updateMinutes(
      minutes < step ? 60 - step : Math.floor((minutes - 1) / step) * step,
    );
  }

  function emitChange() {
    const newValue = `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
    value = newValue;
    onchange?.({ value: newValue });
  }

  function clear() {
    value = "";
    onchange?.({ value: "" });
    close();
  }

  function confirm() {
    emitChange();
    close();
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

  // Quick time presets for events
  const timePresets = [
    { label: "09:00", hours: 9, minutes: 0 },
    { label: "10:00", hours: 10, minutes: 0 },
    { label: "12:00", hours: 12, minutes: 0 },
    { label: "14:00", hours: 14, minutes: 0 },
    { label: "16:00", hours: 16, minutes: 0 },
    { label: "18:00", hours: 18, minutes: 0 },
    { label: "19:00", hours: 19, minutes: 0 },
    { label: "20:00", hours: 20, minutes: 0 },
    { label: "21:00", hours: 21, minutes: 0 },
    { label: "22:00", hours: 22, minutes: 0 },
  ];

  function selectTimePreset(preset: { hours: number; minutes: number }) {
    hours = preset.hours;
    minutes = preset.minutes;
    emitChange();
  }
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
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
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

  <!-- Dropdown Picker -->
  {#if isOpen}
    <div
      role="dialog"
      aria-label="Uhrzeit auswählen"
      aria-modal={isMobile ? "true" : undefined}
      class="{isMobile
        ? 'fixed inset-x-0 bottom-0 z-50 rounded-t-2xl'
        : 'absolute z-50 mt-2 rounded-xl'} bg-charcoal-800 dark:bg-charcoal-800 shadow-xl border border-gray-200 dark:border-charcoal-600 overflow-hidden animate-fade-in sm:min-w-[280px]"
    >
      <!-- Mobile Header -->
      {#if isMobile}
        <div
          class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-charcoal-600"
        >
          <h3 class="text-base font-semibold text-gray-900 dark:text-smoke-100">
            Uhrzeit auswählen
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

      <!-- Time Spinners -->
      <div class="p-4 sm:p-4">
        <div class="flex items-center justify-center gap-3 sm:gap-3">
          <!-- Hours -->
          <div class="flex flex-col items-center">
            <button
              type="button"
              on:click={incrementHours}
              class="p-2 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Stunde erhöhen"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 15l7-7 7 7"
                />
              </svg>
            </button>
            <input
              type="text"
              inputmode="numeric"
              value={String(hours).padStart(2, "0")}
              on:change={(e) =>
                updateHours(parseInt(e.currentTarget.value) || 0)}
              class="w-16 h-14 text-center text-3xl font-bold rounded-lg border border-gray-300 dark:border-charcoal-500 bg-charcoal-800 dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              aria-label="Stunden"
            />
            <button
              type="button"
              on:click={decrementHours}
              class="p-2 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Stunde verringern"
            >
              <svg
                class="w-5 h-5"
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
          </div>

          <span
            class="text-4xl font-bold text-gray-300 dark:text-smoke-600 pb-1"
            >:</span
          >

          <!-- Minutes -->
          <div class="flex flex-col items-center">
            <button
              type="button"
              on:click={incrementMinutes}
              class="p-2 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Minuten erhöhen"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 15l7-7 7 7"
                />
              </svg>
            </button>
            <input
              type="text"
              inputmode="numeric"
              value={String(minutes).padStart(2, "0")}
              on:change={(e) =>
                updateMinutes(parseInt(e.currentTarget.value) || 0)}
              class="w-16 h-14 text-center text-3xl font-bold rounded-lg border border-gray-300 dark:border-charcoal-500 bg-charcoal-800 dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              aria-label="Minuten"
            />
            <button
              type="button"
              on:click={decrementMinutes}
              class="p-2 rounded-lg text-gray-500 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 transition-colors"
              aria-label="Minuten verringern"
            >
              <svg
                class="w-5 h-5"
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
          </div>

          <span
            class="text-lg font-medium text-gray-400 dark:text-smoke-500 ml-1"
            >Uhr</span
          >
        </div>
      </div>

      <!-- Time Presets -->
      <div class="px-4 pb-4">
        <div
          class="text-xs font-medium text-gray-500 dark:text-smoke-400 mb-2 uppercase tracking-wide"
        >
          Schnellauswahl
        </div>
        <div class="grid grid-cols-5 gap-1.5">
          {#each timePresets as preset (preset.label)}
            <button
              type="button"
              on:click={() => selectTimePreset(preset)}
              class="px-2 py-1.5 text-xs font-medium rounded-lg transition-colors
                     {hours === preset.hours && minutes === preset.minutes
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 dark:bg-charcoal-700 text-gray-700 dark:text-smoke-300 hover:bg-gray-200 dark:hover:bg-charcoal-600'}"
            >
              {preset.label}
            </button>
          {/each}
        </div>
      </div>

      <!-- Footer Actions -->
      <div
        class="flex items-center justify-between gap-2 p-3 border-t border-gray-200 dark:border-charcoal-600 bg-charcoal-800 dark:bg-charcoal-900"
      >
        <button
          type="button"
          on:click={clear}
          class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-smoke-400 hover:bg-gray-100 dark:hover:bg-charcoal-700 rounded-lg transition-colors"
        >
          Löschen
        </button>
        <button
          type="button"
          on:click={confirm}
          class="px-4 py-1.5 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors shadow-sm"
        >
          Übernehmen
        </button>
      </div>
    </div>
  {/if}
</div>
