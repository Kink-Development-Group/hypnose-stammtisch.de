<script lang="ts">
  import { onMount } from "svelte";
  import { adminTheme, type AdminTheme } from "../../stores/adminTheme";

  export let size: "sm" | "md" | "lg" = "md";
  export let showLabel = false;

  let currentTheme: AdminTheme = "system";
  let resolvedTheme: "light" | "dark" = "light";

  const sizeClasses = {
    sm: "h-8 w-8",
    md: "h-9 w-9",
    lg: "h-10 w-10",
  };

  const iconSizes = {
    sm: "h-4 w-4",
    md: "h-5 w-5",
    lg: "h-6 w-6",
  };

  onMount(() => {
    adminTheme.initialize();

    const unsubscribe = adminTheme.subscribe((state) => {
      currentTheme = state.theme;
      resolvedTheme = state.resolvedTheme;
    });

    return unsubscribe;
  });

  function handleToggle() {
    adminTheme.cycleBetweenLightAndDark();
  }

  function getThemeLabel(
    theme: AdminTheme,
    resolved: "light" | "dark",
  ): string {
    if (theme === "system") {
      return `System (${resolved === "dark" ? "Dunkel" : "Hell"})`;
    }
    return theme === "dark" ? "Dunkel" : "Hell";
  }
</script>

<button
  type="button"
  on:click={handleToggle}
  class="
    inline-flex items-center justify-center gap-2 rounded-lg
    bg-slate-100 dark:bg-charcoal-700
    text-slate-600 dark:text-smoke-300
    hover:bg-slate-200 dark:hover:bg-charcoal-600
    hover:text-slate-900 dark:hover:text-smoke-50
    transition-colors duration-200
    focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2
    dark:focus-visible:ring-offset-charcoal-900
    {sizeClasses[size]}
    {showLabel ? 'px-3 w-auto' : ''}
  "
  title={resolvedTheme === "dark" ? "Zu Hell wechseln" : "Zu Dunkel wechseln"}
  aria-label={resolvedTheme === "dark"
    ? "Zu Hell wechseln"
    : "Zu Dunkel wechseln"}
>
  {#if resolvedTheme === "dark"}
    <!-- Sun icon for switching to light -->
    <svg
      class={iconSizes[size]}
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      stroke-width="2"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
      />
    </svg>
  {:else}
    <!-- Moon icon for switching to dark -->
    <svg
      class={iconSizes[size]}
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      stroke-width="2"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
      />
    </svg>
  {/if}

  {#if showLabel}
    <span class="text-sm font-medium">
      {getThemeLabel(currentTheme, resolvedTheme)}
    </span>
  {/if}
</button>
