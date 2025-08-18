<script lang="ts">
  import { onMount } from "svelte";
  import { AdminAPI } from "../../stores/admin";
  export let seriesItem: any;

  let overrides: any[] = [];
  let exdates: string[] = [];
  let loading = false;
  let newOverrideDate = "";
  let newOverrideStart = "";
  let newOverrideEnd = "";
  let newOverrideTitle = "";
  let newExdate = "";
  // Cancellation
  let cancelDate = "";
  let cancelReason = "";
  let restoreDate = "";

  async function loadData() {
    loading = true;
    try {
      const [ovRes, exRes] = await Promise.all([
        AdminAPI.getSeriesOverrides(seriesItem.id),
        AdminAPI.getSeriesExdates(seriesItem.id),
      ]);
      if (ovRes.success) overrides = ovRes.data.overrides || [];
      if (exRes.success) exdates = exRes.data.exdates || [];
    } finally {
      loading = false;
    }
  }

  onMount(loadData);

  async function addOverride() {
    if (!newOverrideDate) return;
    const payload: any = { instance_date: newOverrideDate };
    if (newOverrideTitle) payload.title = newOverrideTitle;
    if (newOverrideStart) payload.start_time = newOverrideStart;
    if (newOverrideEnd) payload.end_time = newOverrideEnd;
    const res = await AdminAPI.createSeriesOverride(seriesItem.id, payload);
    if (res.success) {
      overrides.push({ ...payload, id: res.data.id });
      overrides = overrides.slice();
      newOverrideDate = "";
      newOverrideStart = "";
      newOverrideEnd = "";
      newOverrideTitle = "";
    }
  }

  async function addExdate() {
    if (!newExdate) return;
    const res = await AdminAPI.addSeriesExdate(seriesItem.id, newExdate);
    if (res.success) {
      exdates = res.data.exdates || [];
      newExdate = "";
    }
  }

  async function removeExdate(date: string) {
    const res = await AdminAPI.removeSeriesExdate(seriesItem.id, date);
    if (res.success) exdates = res.data.exdates || [];
  }

  async function deleteOverride(ovId: string) {
    const res = await AdminAPI.deleteSeriesOverride(seriesItem.id, ovId);
    if (res.success) {
      overrides = overrides.filter((o) => o.id !== ovId);
    }
  }

  async function cancelInstance() {
    if (!cancelDate) return;
    const res = await AdminAPI.cancelSeriesInstance(
      seriesItem.id,
      cancelDate,
      cancelReason || undefined,
    );
    if (res.success) {
      // Reload overrides to reflect cancellation (it appears as override with status cancelled)
      loadData();
      cancelDate = "";
      cancelReason = "";
    }
  }

  async function restoreInstance() {
    if (!restoreDate) return;
    const res = await AdminAPI.restoreSeriesInstance(
      seriesItem.id,
      restoreDate,
    );
    if (res.success) {
      loadData();
      restoreDate = "";
    }
  }
</script>

<div class="mt-3 space-y-6">
  {#if loading}
    <div class="text-sm text-gray-500">Lade Daten...</div>
  {:else}
    <div class="space-y-2">
      <h4 class="text-sm font-semibold text-gray-700">
        Overrides (individuelle Instanzen)
      </h4>
      <div class="flex flex-col md:flex-row gap-2 items-end">
        <div>
          <label
            for="ov-date-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Datum</label
          >
          <input
            id="ov-date-{seriesItem.id}"
            type="date"
            bind:value={newOverrideDate}
            class="border rounded px-2 py-1 text-sm"
          />
        </div>
        <div>
          <label
            for="ov-start-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Start (HH:MM)</label
          >
          <input
            id="ov-start-{seriesItem.id}"
            type="time"
            bind:value={newOverrideStart}
            class="border rounded px-2 py-1 text-sm"
          />
        </div>
        <div>
          <label
            for="ov-end-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Ende (HH:MM)</label
          >
          <input
            id="ov-end-{seriesItem.id}"
            type="time"
            bind:value={newOverrideEnd}
            class="border rounded px-2 py-1 text-sm"
          />
        </div>
        <div class="flex-1">
          <label
            for="ov-title-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Titel (optional)</label
          >
          <input
            id="ov-title-{seriesItem.id}"
            type="text"
            bind:value={newOverrideTitle}
            placeholder="Abweichender Titel"
            class="w-full border rounded px-2 py-1 text-sm"
          />
        </div>
        <button
          on:click={addOverride}
          class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700"
          >Override hinzufügen</button
        >
      </div>
      {#if overrides.length === 0}
        <div class="text-xs text-gray-500">Keine Overrides vorhanden.</div>
      {:else}
        <ul class="text-xs divide-y divide-gray-200 border rounded bg-white">
          {#each overrides as ov}
            <li class="px-2 py-1 flex justify-between items-center gap-3">
              <div class="flex flex-col">
                <span class="font-medium"
                  >{ov.instance_date} • {ov.title || seriesItem.title}</span
                >
                <span class="text-gray-400">
                  {#if ov.start_datetime && ov.end_datetime}
                    {ov.start_datetime.slice(11, 16)} - {ov.end_datetime.slice(
                      11,
                      16,
                    )}
                  {:else if ov.start_time && ov.end_time}
                    {ov.start_time} - {ov.end_time}
                  {/if}
                </span>
              </div>
              <button
                class="text-red-600 hover:text-red-800 text-xs"
                aria-label="Override löschen"
                title="Override löschen"
                on:click={() => deleteOverride(ov.id)}>Löschen</button
              >
            </li>
          {/each}
        </ul>
      {/if}
    </div>

    <div class="space-y-2">
      <h4 class="text-sm font-semibold text-gray-700">
        Ausnahmedaten (EXDATE)
      </h4>
      <div class="flex gap-2 items-end">
        <div>
          <label
            for="exdate-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Datum</label
          >
          <input
            id="exdate-{seriesItem.id}"
            type="date"
            bind:value={newExdate}
            class="border rounded px-2 py-1 text-sm"
          />
        </div>
        <button
          on:click={addExdate}
          class="bg-indigo-600 text-white px-3 py-2 rounded text-sm hover:bg-indigo-700"
          >EXDATE hinzufügen</button
        >
      </div>
      {#if exdates.length === 0}
        <div class="text-xs text-gray-500">Keine EXDATEs definiert.</div>
      {:else}
        <ul class="text-xs divide-y divide-gray-200 border rounded bg-white">
          {#each exdates as d}
            <li class="px-2 py-1 flex justify-between items-center">
              <span>{d}</span>
              <button
                class="text-red-600 hover:text-red-800"
                on:click={() => removeExdate(d)}>Entfernen</button
              >
            </li>
          {/each}
        </ul>
      {/if}
    </div>

    <div class="space-y-2">
      <h4 class="text-sm font-semibold text-gray-700">Instanz absagen</h4>
      <div class="flex flex-col md:flex-row gap-2 items-end">
        <div>
          <label
            for="cancel-date-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Datum</label
          >
          <input
            id="cancel-date-{seriesItem.id}"
            type="date"
            bind:value={cancelDate}
            class="border rounded px-2 py-1 text-sm"
          />
        </div>
        <div class="flex-1">
          <label
            for="cancel-reason-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Grund (optional)</label
          >
          <input
            id="cancel-reason-{seriesItem.id}"
            type="text"
            bind:value={cancelReason}
            placeholder="z.B. Ausfall Referent"
            class="w-full border rounded px-2 py-1 text-sm"
          />
        </div>
        <button
          on:click={cancelInstance}
          class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700"
          >Absagen</button
        >
      </div>
    </div>

    <div class="space-y-2">
      <h4 class="text-sm font-semibold text-gray-700">Absage zurücknehmen</h4>
      <div class="flex flex-col md:flex-row gap-2 items-end">
        <div>
          <label
            for="restore-date-{seriesItem.id}"
            class="block text-xs text-gray-600 mb-1">Datum</label
          >
          <input
            id="restore-date-{seriesItem.id}"
            type="date"
            bind:value={restoreDate}
            class="border rounded px-2 py-1 text-sm"
          />
        </div>
        <button
          on:click={restoreInstance}
          class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700"
          >Wiederherstellen</button
        >
      </div>
    </div>
  {/if}
</div>
