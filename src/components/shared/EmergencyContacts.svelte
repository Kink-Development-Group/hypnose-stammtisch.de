<script lang="ts">
  type EmergencyVariant = "default" | "compact";

  export let variant: EmergencyVariant = "default";
  export let showTitle = true;

  type ContactEntry = {
    label: string;
    value: string;
  };

  const contacts: ContactEntry[] = [
    {
      label: "Telefonseelsorge (24/7)",
      value: "0800 111 0 111 oder 0800 111 0 222",
    },
    {
      label: "Nummer gegen Kummer",
      value: "116 111 (für Kinder und Jugendliche)",
    },
    {
      label: "Ärztlicher Bereitschaftsdienst",
      value: "116 117",
    },
  ];
</script>

<div class="card bg-caution/10 border border-caution/30">
  {#if showTitle}
    <h3 class="text-xl font-semibold text-smoke-50 mb-4">
      🚨 Notfall &amp; Krisenunterstützung
    </h3>
  {/if}

  <div class="space-y-4 text-sm text-smoke-300">
    {#each contacts as contact (contact.label)}
      <div>
        <strong class="text-smoke-50">{contact.label}:</strong>
        <p>{contact.value}</p>
      </div>
    {/each}
  </div>

  {#if variant === "default"}
    <div
      class="mt-4 p-3 bg-boundaries/20 border border-boundaries/40 rounded-lg"
    >
      <p class="text-xs text-smoke-400">
        <strong>Bei akuten Notfällen:</strong>
        Wähle 112 oder wende dich an die nächste Notaufnahme.
      </p>
    </div>
  {/if}
</div>
