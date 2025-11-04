<script lang="ts"><script lang="ts"></script>

  interface Props {  export let variant: "default" | "compact" = "default";

    variant?: "default" | "compact";  export let showTitle = true;

    showTitle?: boolean;</script>

  }

<div class="card bg-caution/10 border-caution/30">

  let { variant = "default", showTitle = true }: Props = $props();  {#if showTitle}

</script>    <h3 class="text-xl font-semibold text-smoke-50 mb-4">

      🚨 Notfall & Krisenunterstützung

<div class="card bg-caution/10 border-caution/30">    </h3>

  {#if showTitle}  {/if}

    <h3 class="text-xl font-semibold text-smoke-50 mb-4">  <div class="space-y-4 text-sm text-smoke-300">

      🚨 Notfall & Krisenunterstützung    <div>

    </h3>      <strong class="text-smoke-50">Telefonseelsorge (24/7):</strong>

  {/if}      <p>0800 111 0 111 oder 0800 111 0 222</p>

  <div class="space-y-4 text-sm text-smoke-300">    </div>

    <div>    <div>

      <strong class="text-smoke-50">Telefonseelsorge (24/7):</strong>      <strong class="text-smoke-50">Nummer gegen Kummer:</strong>

      <p>0800 111 0 111 oder 0800 111 0 222</p>      <p>116 111 (für Kinder und Jugendliche)</p>

    </div>    </div>

    <div>    <div>

      <strong class="text-smoke-50">Nummer gegen Kummer:</strong>      <strong class="text-smoke-50">Ärztlicher Bereitschaftsdienst:</strong>

      <p>116 111 (für Kinder und Jugendliche)</p>      <p>116 117</p>

    </div>    </div>

    <div>  </div>

      <strong class="text-smoke-50">Ärztlicher Bereitschaftsdienst:</strong>  {#if variant === "default"}

      <p>116 117</p>    <div class="mt-4 p-3 bg-boundaries/20 border border-boundaries/40 rounded-lg">

    </div>      <p class="text-xs text-smoke-400">

  </div>        <strong>Bei akuten Notfällen:</strong> Wähle 112 oder wende dich an die nächste

  {#if variant === "default"}        Notaufnahme.

    <div class="mt-4 p-3 bg-boundaries/20 border border-boundaries/40 rounded-lg">      </p>

      <p class="text-xs text-smoke-400">    </div>

        <strong>Bei akuten Notfällen:</strong> Wähle 112 oder wende dich an die nächste  {/if}

        Notaufnahme.</div>

      </p>
    </div>
  {/if}
</div>
