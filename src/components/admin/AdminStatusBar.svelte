<script lang="ts">
  import { onDestroy, onMount } from "svelte";
  import { adminAutoUpdate, adminEventBus } from "../../stores/adminData";

  export let className = "";
  export let dense = false;

  let isAutoUpdateEnabled = false;
  let lastUpdateTime = "";
  let updateInterval: NodeJS.Timeout | null = null;

  onMount(() => {
    // Initial state
    isAutoUpdateEnabled = adminAutoUpdate.enableAutoRefresh;
    updateLastUpdateTime();

    // Listen for event bus updates
    const unsubscribeEventBus = adminEventBus.subscribe((event) => {
      if (event?.data?.autoRefresh) {
        updateLastUpdateTime();
      }
    });

    // Update time display every second
    updateInterval = setInterval(() => {
      updateLastUpdateTime();
    }, 1000);

    return () => {
      unsubscribeEventBus();
    };
  });

  onDestroy(() => {
    if (updateInterval) {
      clearInterval(updateInterval);
    }
  });

  function updateLastUpdateTime() {
    const now = new Date();
    lastUpdateTime = now.toLocaleTimeString("de-DE");
  }

  function toggleAutoUpdate() {
    adminAutoUpdate.toggle();
    isAutoUpdateEnabled = adminAutoUpdate.enableAutoRefresh;
  }
</script>

<div
  class={`${
    dense ? "px-3 py-2" : "px-4 py-3"
  } flex flex-wrap items-center gap-3 sm:gap-5 text-xs sm:text-sm text-slate-600 dark:text-smoke-400 bg-white dark:bg-charcoal-800 shadow-sm border-t border-gray-200 dark:border-charcoal-700 ${className}`}
>
  <!-- Auto-Update Status -->
  <div class="flex items-center gap-2 sm:gap-3">
    <button
      on:click={toggleAutoUpdate}
      class="flex items-center gap-1 rounded-md px-2 py-1 hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
      title={isAutoUpdateEnabled
        ? "Auto-Update deaktivieren"
        : "Auto-Update aktivieren"}
      aria-pressed={isAutoUpdateEnabled}
    >
      <div class="relative">
        {#if isAutoUpdateEnabled}
          <svg
            class="w-4 h-4 text-green-500"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
              clip-rule="evenodd"
            />
          </svg>
          <!-- Rotation animation for active state -->
          <div class="absolute inset-0 animate-spin">
            <svg
              class="w-4 h-4 text-green-400 opacity-50"
              fill="none"
              viewBox="0 0 20 20"
            >
              <circle
                cx="10"
                cy="10"
                r="8"
                stroke="currentColor"
                stroke-width="2"
                stroke-dasharray="10 6"
              />
            </svg>
          </div>
        {:else}
          <svg
            class="w-4 h-4 text-gray-400 dark:text-smoke-500"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
              clip-rule="evenodd"
            />
          </svg>
        {/if}
      </div>

      <span class="text-xs font-medium uppercase tracking-wide sm:text-sm">
        {isAutoUpdateEnabled ? "Auto-Update" : "Manuell"}
      </span>
    </button>
  </div>

  <!-- Last Update Time -->
  <div
    class="flex items-center gap-1 whitespace-nowrap rounded-md bg-gray-50 dark:bg-charcoal-700 px-2 py-1 text-[11px] font-medium text-slate-600 dark:text-smoke-300 sm:text-xs"
  >
    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
      <path
        fill-rule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
        clip-rule="evenodd"
      />
    </svg>
    <span class="font-semibold">Zuletzt aktualisiert: {lastUpdateTime}</span>
  </div>

  <!-- Manual Refresh Button -->
  <button
    on:click={() =>
      adminEventBus.set({
        type: "event",
        action: "update",
        data: { manualRefresh: true },
      })}
    class="flex items-center gap-1 rounded-md border border-blue-100 dark:border-blue-800 px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 transition-colors hover:border-blue-200 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 sm:text-sm"
    title="Manuell aktualisieren"
  >
    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
      <path
        fill-rule="evenodd"
        d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
        clip-rule="evenodd"
      />
    </svg>
    <span class="text-xs font-medium">Aktualisieren</span>
  </button>
</div>

<style>
  /* Custom animation for auto-update indicator */
  @keyframes gentle-pulse {
    0%,
    100% {
      opacity: 0.7;
    }
    50% {
      opacity: 1;
    }
  }
</style>
