<script lang="ts">
  /** Card component for security dashboard sections. */
  export let title: string;
  export let description: string | null = null;
  export let lastUpdated: string | null = null;
</script>

<section
  class="bg-white dark:bg-charcoal-800 shadow rounded-xl border border-gray-200 dark:border-charcoal-700"
>
  <header
    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 dark:border-charcoal-700 px-6 py-4"
  >
    <div>
      <h2 class="text-lg font-semibold text-gray-900 dark:text-smoke-50">
        {title}
      </h2>
      {#if description}
        <p class="text-sm text-slate-600 dark:text-smoke-400">{description}</p>
      {/if}
      {#if lastUpdated}
        <p class="mt-1 text-xs text-slate-500 dark:text-smoke-500">
          {lastUpdated}
        </p>
      {/if}
    </div>
    <div class="flex items-center gap-2">
      <slot name="actions" />
    </div>
  </header>
  <div class="px-6 py-5">
    <slot />
  </div>
</section>
