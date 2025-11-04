<script lang="ts">
  import { onDestroy, onMount } from "svelte";
  import { adminAutoUpdate, adminEventBus } from "../../stores/adminData";

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
  class="flex h-16 items-center space-x-5 text-sm text-gray-600 bg-white px-4 py-2 shadow-sm border"
>
  <!-- Auto-Update Status -->
  <div class="flex items-center space-x-3">
    <button
      on:click={toggleAutoUpdate}
      class="flex items-center space-x-1 hover:text-blue-600 transition-colors"
      title={isAutoUpdateEnabled
        ? "Auto-Update deaktivieren"
        : "Auto-Update aktivieren"}
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
            class="w-4 h-4 text-gray-400"
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

      <span class="font-medium">
        {isAutoUpdateEnabled ? "Auto-Update AN" : "Auto-Update AUS"}
      </span>
    </button>
  </div>

  <!-- Last Update Time -->
  <div class="flex items-center space-x-1 text-xs">
    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
      <path
        fill-rule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
        clip-rule="evenodd"
      />
    </svg>
    <span>Zuletzt: {lastUpdateTime}</span>
  </div>

  <!-- Manual Refresh Button -->
  <button
    on:click={() =>
      adminEventBus.set({
        type: "event",
        action: "update",
        data: { manualRefresh: true },
      })}
    class="flex items-center space-x-1 text-blue-600 hover:text-blue-700 hover:bg-blue-50 px-2 py-1 rounded transition-colors"
    title="Manuell aktualisieren"
  >
    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
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
