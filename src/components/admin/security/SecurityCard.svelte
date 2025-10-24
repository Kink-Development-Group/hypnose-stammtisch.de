<script lang="ts">
  /** Card component for security dashboard sections. */
  export let title: string;
  export let description: string | null = null;
  export let lastUpdated: string | null = null;
</script>

<section class="bg-white shadow rounded-xl border border-gray-200">
  <header
    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 px-6 py-4"
  >
    <div>
      <h2 class="text-lg font-semibold text-gray-900">{title}</h2>
      {#if description}
        <p class="text-sm text-gray-600">{description}</p>
      {/if}
      {#if lastUpdated}
        <p class="mt-1 text-xs text-gray-500">{lastUpdated}</p>
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
