<script lang="ts">
  import { onMount } from "svelte";
  // @ts-ignore - types may not be present yet
  import QRCode from "qrcode";
  export let text: string;
  export let size: number = 180;
  let canvas: HTMLCanvasElement;
  let error: string = "";
  onMount(async () => {
    try {
      // @ts-ignore
      await QRCode.toCanvas(canvas, text, { width: size, margin: 1 });
    } catch (e: any) {
      error = e?.message || "QR Fehler";
    }
  });
</script>

{#if error}
  <div class="text-red-600 text-sm">{error}</div>
{:else}
  <canvas bind:this={canvas} class="rounded shadow"></canvas>
{/if}
