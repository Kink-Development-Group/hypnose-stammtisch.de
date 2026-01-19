<script lang="ts">
  import { onDestroy, onMount } from "svelte";

  // Target element for portal (default: document.body)
  export let target: HTMLElement | string = "body";

  let portalContainer: HTMLDivElement;
  let targetElement: HTMLElement | null = null;

  onMount(() => {
    // Resolve target element
    if (typeof target === "string") {
      targetElement = document.querySelector(target);
    } else {
      targetElement = target;
    }

    // Move portal container to target
    if (targetElement && portalContainer) {
      targetElement.appendChild(portalContainer);
    }
  });

  onDestroy(() => {
    // Clean up: remove portal container from DOM
    if (portalContainer && portalContainer.parentNode) {
      portalContainer.parentNode.removeChild(portalContainer);
    }
  });
</script>

<div bind:this={portalContainer} class="portal-container">
  <slot />
</div>

<style>
  .portal-container {
    display: contents;
  }
</style>
