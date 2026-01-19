<script lang="ts">
  import { onMount } from "svelte";
  import { SvelteDate } from "svelte/reactivity";
  import { AdminAPI } from "../../stores/admin";
  import { adminNotifications } from "../../stores/adminData";
  import { DatePicker, TimePicker } from "../forms";
  export let seriesItem: any;

  // Callback prop instead of createEventDispatcher (Svelte 5 pattern)
  export let ondatachanged: (() => void) | undefined = undefined;

  let overrides: any[] = [];
  let exdates: string[] = [];
  let upcomingInstances: any[] = [];
  let loading = false;
  let savingOverride = false;
  let savingExdate = false;
  let cancellingInstance = false;
  let restoringInstance: string | null = null; // track which instance is being restored

  // Override Form
  let newOverrideDate = "";
  let newOverrideStart = "";
  let newOverrideEnd = "";
  let newOverrideTitle = "";
  let newOverrideDescription = "";

  // EXDATE Form
  let newExdate = "";
  let exdateError = "";

  // Cancellation Form
  let selectedCancelInstance = "";
  let cancelReason = "";
  let cancelError = "";

  // Accordion sections state (local, not reset on data load)
  let sectionsExpanded = {
    overrides: true,
    exdates: true,
    cancel: false,
  };

  function toggleSection(section: keyof typeof sectionsExpanded) {
    sectionsExpanded[section] = !sectionsExpanded[section];
  }

  /**
   * Safely parse exdates from API response.
   * Handles edge cases like double-encoded JSON or invalid data.
   */
  function parseExdates(data: unknown): string[] {
    // Already a valid array of strings
    if (Array.isArray(data)) {
      // Filter to only valid date strings (YYYY-MM-DD format)
      return data.filter(
        (d): d is string =>
          typeof d === "string" && /^\d{4}-\d{2}-\d{2}$/.test(d),
      );
    }

    // If it's a string, try to parse it as JSON (handles double-encoded)
    if (typeof data === "string") {
      try {
        const parsed = JSON.parse(data);
        return parseExdates(parsed);
      } catch {
        return [];
      }
    }

    return [];
  }

  async function loadData() {
    loading = true;
    try {
      const [ovRes, exRes, upcomingRes] = await Promise.all([
        AdminAPI.getSeriesOverrides(seriesItem.id),
        AdminAPI.getSeriesExdates(seriesItem.id),
        AdminAPI.getUpcomingSeriesInstances(seriesItem.id, 12),
      ]);
      if (ovRes.success) overrides = ovRes.data.overrides || [];
      if (exRes.success) exdates = parseExdates(exRes.data.exdates);
      if (upcomingRes.success)
        upcomingInstances = upcomingRes.data?.instances || [];
    } finally {
      loading = false;
    }
  }

  onMount(loadData);

  // Validation helpers
  function isDateInPast(dateStr: string): boolean {
    if (!dateStr) return false;
    const date = new SvelteDate(dateStr);
    const today = new SvelteDate();
    today.setHours(0, 0, 0, 0);
    return date < today;
  }

  function isExdateDuplicate(dateStr: string): boolean {
    return exdates.includes(dateStr);
  }

  async function addOverride() {
    if (!newOverrideDate) {
      adminNotifications.error("Bitte wählen Sie ein Datum für den Override.");
      return;
    }

    savingOverride = true;
    try {
      const payload: any = { instance_date: newOverrideDate };
      if (newOverrideTitle.trim()) payload.title = newOverrideTitle.trim();
      if (newOverrideDescription.trim())
        payload.description = newOverrideDescription.trim();
      if (newOverrideStart) payload.start_time = newOverrideStart;
      if (newOverrideEnd) payload.end_time = newOverrideEnd;

      const res = await AdminAPI.createSeriesOverride(seriesItem.id, payload);
      if (res.success) {
        // Add to local list without full reload (prevents accordion collapse)
        overrides = [
          ...overrides,
          { ...payload, id: res.data?.id || res.data?.data?.id },
        ];
        // Reset form
        newOverrideDate = "";
        newOverrideStart = "";
        newOverrideEnd = "";
        newOverrideTitle = "";
        newOverrideDescription = "";
        ondatachanged?.();
      }
    } finally {
      savingOverride = false;
    }
  }

  async function addExdate() {
    exdateError = "";

    if (!newExdate) {
      exdateError = "Bitte wählen Sie ein Datum.";
      return;
    }

    // Client-side validation
    if (isExdateDuplicate(newExdate)) {
      exdateError = `Das Datum ${newExdate} ist bereits als Ausnahme hinterlegt.`;
      adminNotifications.error(exdateError);
      return;
    }

    if (isDateInPast(newExdate)) {
      exdateError =
        "Hinweis: Das gewählte Datum liegt in der Vergangenheit. Fortfahren?";
      // Still allow past dates but show warning
    }

    savingExdate = true;
    try {
      const res = await AdminAPI.addSeriesExdate(seriesItem.id, newExdate);
      if (res.success) {
        exdates =
          parseExdates(res.data?.exdates) || [...exdates, newExdate].sort();
        newExdate = "";
        exdateError = "";
        ondatachanged?.();
      } else {
        // Parse specific API errors
        const errorMsg = res.message || res.error || "";
        if (errorMsg.includes("duplicate") || errorMsg.includes("already")) {
          exdateError = `Das Datum ${newExdate} existiert bereits.`;
        } else if (errorMsg.includes("not found")) {
          exdateError = "Serie nicht gefunden. Bitte Seite neu laden.";
        } else {
          exdateError =
            errorMsg || "Fehler beim Hinzufügen. Bitte erneut versuchen.";
        }
      }
    } catch (err: any) {
      exdateError = "Netzwerkfehler. Bitte Verbindung prüfen.";
      console.error("addExdate error:", err);
    } finally {
      savingExdate = false;
    }
  }

  async function removeExdate(date: string) {
    const res = await AdminAPI.removeSeriesExdate(seriesItem.id, date);
    if (res.success) {
      exdates =
        parseExdates(res.data?.exdates) || exdates.filter((d) => d !== date);
      ondatachanged?.();
    }
  }

  async function deleteOverride(ovId: string) {
    const res = await AdminAPI.deleteSeriesOverride(seriesItem.id, ovId);
    if (res.success) {
      overrides = overrides.filter((o) => o.id !== ovId);
      ondatachanged?.();
    }
  }

  async function cancelInstance(instanceDate: string) {
    cancelError = "";
    cancellingInstance = true;

    try {
      const res = await AdminAPI.cancelSeriesInstance(
        seriesItem.id,
        instanceDate,
        cancelReason.trim() || undefined,
      );
      if (res.success) {
        // Reload data to get the new cancelled instance and updated upcoming instances
        await loadData();
        cancelReason = "";
        cancelError = "";
        selectedCancelInstance = "";
        ondatachanged?.();
      } else {
        // Parse specific errors
        const errorMsg = res.message || res.error || "";
        if (errorMsg.includes("start_time") || errorMsg.includes("end_time")) {
          cancelError =
            "Diese Serie hat keine Start-/Endzeit definiert. Bitte Serie bearbeiten.";
        } else if (errorMsg.includes("not found")) {
          cancelError = "Serie nicht gefunden.";
        } else if (errorMsg.includes("kein gültiger Termin")) {
          cancelError = errorMsg;
        } else {
          cancelError =
            errorMsg || "Fehler beim Absagen. Bitte erneut versuchen.";
        }
        adminNotifications.error(cancelError);
      }
    } catch (err: any) {
      cancelError = "Netzwerkfehler. Bitte Verbindung prüfen.";
      console.error("cancelInstance error:", err);
      adminNotifications.error(cancelError);
    } finally {
      cancellingInstance = false;
    }
  }

  async function restoreInstance(instanceDate: string) {
    restoringInstance = instanceDate;
    try {
      const res = await AdminAPI.restoreSeriesInstance(
        seriesItem.id,
        instanceDate,
      );
      if (res.success) {
        await loadData();
        ondatachanged?.();
      }
    } finally {
      restoringInstance = null;
    }
  }

  // Get cancelled instances for display
  $: cancelledOverrides = overrides.filter(
    (o) => o.override_type === "cancelled" || o.status === "cancelled",
  );
  $: changedOverrides = overrides.filter(
    (o) =>
      o.override_type === "changed" ||
      (o.override_type !== "cancelled" && o.status !== "cancelled"),
  );
</script>

<div class="mt-3 space-y-6">
  {#if loading}
    <div
      class="flex items-center gap-2 text-sm text-slate-500 dark:text-smoke-400"
    >
      <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
          fill="none"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
      Lade Daten...
    </div>
  {:else}
    <!-- Section: Overrides (individuelle Instanzen) -->
    <div class="space-y-3">
      <button
        type="button"
        class="flex items-center gap-2 w-full text-left"
        on:click={() => toggleSection("overrides")}
        aria-expanded={sectionsExpanded.overrides}
      >
        <svg
          class="w-4 h-4 text-slate-500 dark:text-smoke-400 transition-transform {sectionsExpanded.overrides
            ? 'rotate-90'
            : ''}"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
        <h4 class="text-sm font-semibold text-slate-700 dark:text-smoke-200">
          Overrides (individuelle Instanzen)
        </h4>
        <span class="text-xs text-slate-500 dark:text-smoke-400">
          ({changedOverrides.length})
        </span>
      </button>

      {#if sectionsExpanded.overrides}
        <div class="pl-6 space-y-3">
          <!-- Override Form - Improved Layout -->
          <div
            class="bg-slate-50 dark:bg-charcoal-700/50 rounded-lg p-4 space-y-3"
          >
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <DatePicker
                  id="ov-date-{seriesItem.id}"
                  label="Datum *"
                  bind:value={newOverrideDate}
                />
              </div>
              <div>
                <TimePicker
                  id="ov-start-{seriesItem.id}"
                  label="Startzeit"
                  bind:value={newOverrideStart}
                />
              </div>
              <div>
                <TimePicker
                  id="ov-end-{seriesItem.id}"
                  label="Endzeit"
                  bind:value={newOverrideEnd}
                />
              </div>
            </div>

            <!-- Improved Title Field - Full Width -->
            <div>
              <label
                for="ov-title-{seriesItem.id}"
                class="block text-xs font-medium text-slate-600 dark:text-smoke-400 mb-1"
              >
                Abweichender Titel
              </label>
              <input
                id="ov-title-{seriesItem.id}"
                type="text"
                bind:value={newOverrideTitle}
                placeholder="Leer lassen für Originaltitel: {seriesItem.title}"
                class="w-full border dark:border-charcoal-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-charcoal-700 text-slate-900 dark:text-smoke-50 placeholder:text-slate-400 dark:placeholder:text-smoke-500"
              />
            </div>

            <!-- New Description Field - Full Width, Multiline -->
            <div>
              <label
                for="ov-desc-{seriesItem.id}"
                class="block text-xs font-medium text-slate-600 dark:text-smoke-400 mb-1"
              >
                Abweichende Beschreibung
              </label>
              <textarea
                id="ov-desc-{seriesItem.id}"
                bind:value={newOverrideDescription}
                placeholder="Leer lassen für Originalbeschreibung"
                rows="3"
                class="w-full border dark:border-charcoal-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-charcoal-700 text-slate-900 dark:text-smoke-50 placeholder:text-slate-400 dark:placeholder:text-smoke-500 resize-y"
              ></textarea>
            </div>

            <div class="flex justify-end">
              <button
                on:click={addOverride}
                disabled={savingOverride || !newOverrideDate}
                class="bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 dark:hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                {#if savingOverride}
                  <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                      fill="none"
                    />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                  </svg>
                {/if}
                Override hinzufügen
              </button>
            </div>
          </div>

          <!-- Existing Overrides List -->
          {#if changedOverrides.length === 0}
            <div class="text-xs text-slate-600 dark:text-smoke-400 italic">
              Keine Overrides vorhanden.
            </div>
          {:else}
            <ul
              class="text-sm divide-y divide-gray-200 dark:divide-charcoal-700 border dark:border-charcoal-600 rounded-lg bg-white dark:bg-charcoal-800 overflow-hidden"
            >
              {#each changedOverrides as ov (ov.id || ov.instance_date)}
                <li
                  class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-charcoal-700/50"
                >
                  <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 space-y-1">
                      <div class="flex items-center gap-2 flex-wrap">
                        <span
                          class="font-medium text-slate-900 dark:text-smoke-50"
                        >
                          {ov.instance_date}
                        </span>
                        {#if ov.title && ov.title !== seriesItem.title}
                          <span
                            class="text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded"
                          >
                            {ov.title}
                          </span>
                        {:else}
                          <span
                            class="text-xs text-slate-500 dark:text-smoke-400"
                          >
                            (Originaltitel)
                          </span>
                        {/if}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-smoke-400">
                        {#if ov.start_datetime && ov.end_datetime}
                          {ov.start_datetime.slice(11, 16)} – {ov.end_datetime.slice(
                            11,
                            16,
                          )}
                        {:else if ov.start_time && ov.end_time}
                          {ov.start_time.slice(0, 5)} – {ov.end_time.slice(
                            0,
                            5,
                          )}
                        {:else}
                          Standardzeiten
                        {/if}
                      </div>
                      {#if ov.description}
                        <div
                          class="text-xs text-slate-600 dark:text-smoke-300 mt-1 line-clamp-2"
                        >
                          {ov.description}
                        </div>
                      {/if}
                    </div>
                    <button
                      class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium shrink-0"
                      aria-label="Override löschen"
                      title="Override löschen"
                      on:click={() => deleteOverride(ov.id)}
                    >
                      Löschen
                    </button>
                  </div>
                </li>
              {/each}
            </ul>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Section: Ausnahmedaten (EXDATE) -->
    <div class="space-y-3">
      <button
        type="button"
        class="flex items-center gap-2 w-full text-left"
        on:click={() => toggleSection("exdates")}
        aria-expanded={sectionsExpanded.exdates}
      >
        <svg
          class="w-4 h-4 text-slate-500 dark:text-smoke-400 transition-transform {sectionsExpanded.exdates
            ? 'rotate-90'
            : ''}"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
        <h4 class="text-sm font-semibold text-slate-700 dark:text-smoke-200">
          Ausnahmedaten (EXDATE)
        </h4>
        <span class="text-xs text-slate-500 dark:text-smoke-400">
          ({exdates.length})
        </span>
      </button>

      {#if sectionsExpanded.exdates}
        <div class="pl-6 space-y-3">
          <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="w-full sm:w-48">
              <DatePicker
                id="exdate-{seriesItem.id}"
                label="Datum"
                bind:value={newExdate}
              />
            </div>
            <button
              on:click={addExdate}
              disabled={savingExdate || !newExdate}
              class="bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shrink-0"
            >
              {#if savingExdate}
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                    fill="none"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  />
                </svg>
              {/if}
              EXDATE hinzufügen
            </button>
          </div>

          <!-- Error Message -->
          {#if exdateError}
            <div
              class="flex items-start gap-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-3 py-2"
            >
              <svg
                class="w-4 h-4 shrink-0 mt-0.5"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
              <span>{exdateError}</span>
            </div>
          {/if}

          {#if exdates.length === 0}
            <div class="text-xs text-slate-600 dark:text-smoke-400 italic">
              Keine Ausnahmedaten definiert. Diese Daten werden von der Serie
              ausgeschlossen.
            </div>
          {:else}
            <ul
              class="text-sm divide-y divide-gray-200 dark:divide-charcoal-700 border dark:border-charcoal-600 rounded-lg bg-white dark:bg-charcoal-800 overflow-hidden"
            >
              {#each exdates as d (d)}
                <li
                  class="px-4 py-2 flex justify-between items-center hover:bg-slate-50 dark:hover:bg-charcoal-700/50"
                >
                  <span
                    class="text-slate-900 dark:text-smoke-50 font-mono text-sm"
                    >{d}</span
                  >
                  <button
                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium"
                    on:click={() => removeExdate(d)}
                  >
                    Entfernen
                  </button>
                </li>
              {/each}
            </ul>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Section: Instanz absagen -->
    <div class="space-y-3">
      <button
        type="button"
        class="flex items-center gap-2 w-full text-left"
        on:click={() => toggleSection("cancel")}
        aria-expanded={sectionsExpanded.cancel}
      >
        <svg
          class="w-4 h-4 text-slate-500 dark:text-smoke-400 transition-transform {sectionsExpanded.cancel
            ? 'rotate-90'
            : ''}"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
        <h4 class="text-sm font-semibold text-slate-700 dark:text-smoke-200">
          Termine absagen / wiederherstellen
        </h4>
        {#if cancelledOverrides.length > 0}
          <span
            class="text-xs bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 px-2 py-0.5 rounded"
          >
            {cancelledOverrides.length} abgesagt
          </span>
        {/if}
      </button>

      {#if sectionsExpanded.cancel}
        <div class="pl-6 space-y-4">
          <!-- Upcoming instances to cancel -->
          <div class="space-y-2">
            <h5 class="text-xs font-medium text-slate-600 dark:text-smoke-400">
              Kommende Termine:
            </h5>

            {#if upcomingInstances.length === 0}
              <p class="text-xs text-slate-500 dark:text-smoke-400 italic">
                Keine kommenden Termine für diese Serie gefunden.
              </p>
            {:else}
              <ul
                class="text-sm divide-y divide-gray-200 dark:divide-charcoal-700 border dark:border-charcoal-600 rounded-lg bg-white dark:bg-charcoal-800 overflow-hidden"
              >
                {#each upcomingInstances as instance (instance.date)}
                  <li
                    class="px-4 py-3 flex justify-between items-center hover:bg-slate-50 dark:hover:bg-charcoal-700/50"
                  >
                    <div class="flex items-center gap-3">
                      <span
                        class="text-slate-900 dark:text-smoke-50 font-mono text-sm"
                      >
                        {instance.date}
                      </span>
                      <span class="text-xs text-slate-500 dark:text-smoke-400">
                        {instance.start_datetime?.slice(11, 16) || ""}
                      </span>
                    </div>
                    <button
                      on:click={() => {
                        selectedCancelInstance = instance.date;
                      }}
                      disabled={cancellingInstance}
                      class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                      aria-label="Termin {instance.date} absagen"
                    >
                      Absagen
                    </button>
                  </li>
                {/each}
              </ul>
            {/if}
          </div>

          <!-- Cancellation confirmation dialog -->
          {#if selectedCancelInstance}
            <div
              class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 space-y-3"
            >
              <div class="flex items-start gap-2">
                <svg
                  class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                <div>
                  <p class="text-sm font-medium text-red-800 dark:text-red-200">
                    Termin am {selectedCancelInstance} absagen?
                  </p>
                </div>
              </div>

              <div>
                <label
                  for="cancel-reason-{seriesItem.id}"
                  class="block text-xs font-medium text-slate-600 dark:text-smoke-400 mb-1"
                >
                  Grund (optional)
                </label>
                <input
                  id="cancel-reason-{seriesItem.id}"
                  type="text"
                  bind:value={cancelReason}
                  placeholder="z.B. Ausfall Referent, Raumprobleme..."
                  class="w-full border dark:border-charcoal-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-charcoal-700 text-slate-900 dark:text-smoke-50"
                />
              </div>

              <!-- Cancel Error Message -->
              {#if cancelError}
                <div
                  class="flex items-start gap-2 text-sm text-red-600 dark:text-red-400"
                >
                  <svg
                    class="w-4 h-4 shrink-0 mt-0.5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <span>{cancelError}</span>
                </div>
              {/if}

              <div class="flex gap-2 justify-end">
                <button
                  on:click={() => {
                    selectedCancelInstance = "";
                    cancelReason = "";
                    cancelError = "";
                  }}
                  class="px-3 py-1.5 text-sm text-slate-600 dark:text-smoke-400 hover:bg-slate-100 dark:hover:bg-charcoal-700 rounded-lg"
                >
                  Abbrechen
                </button>
                <button
                  on:click={() => cancelInstance(selectedCancelInstance)}
                  disabled={cancellingInstance}
                  class="bg-red-600 dark:bg-red-500 text-white px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-red-700 dark:hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  {#if cancellingInstance}
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                      <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                        fill="none"
                      />
                      <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                      />
                    </svg>
                  {/if}
                  Bestätigen
                </button>
              </div>
            </div>
          {/if}

          <!-- Show cancelled instances with restore button -->
          {#if cancelledOverrides.length > 0}
            <div class="space-y-2">
              <h5
                class="text-xs font-medium text-slate-600 dark:text-smoke-400"
              >
                Abgesagte Termine:
              </h5>
              <ul
                class="text-sm divide-y divide-gray-200 dark:divide-charcoal-700 border dark:border-charcoal-600 rounded-lg bg-white dark:bg-charcoal-800 overflow-hidden"
              >
                {#each cancelledOverrides as ov (ov.id || ov.instance_date)}
                  <li
                    class="px-4 py-2 flex justify-between items-center bg-red-50 dark:bg-red-900/10"
                  >
                    <div class="flex items-center gap-2 flex-wrap">
                      <span
                        class="text-slate-900 dark:text-smoke-50 font-mono text-sm"
                      >
                        {ov.instance_date}
                      </span>
                      {#if ov.cancellation_reason}
                        <span
                          class="text-xs text-slate-500 dark:text-smoke-400"
                        >
                          – {ov.cancellation_reason}
                        </span>
                      {/if}
                      <span
                        class="text-xs bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200 px-2 py-0.5 rounded"
                      >
                        abgesagt
                      </span>
                    </div>
                    <button
                      on:click={() => restoreInstance(ov.instance_date)}
                      disabled={restoringInstance === ov.instance_date}
                      class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 p-1.5 rounded hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors disabled:opacity-50"
                      aria-label="Absage für {ov.instance_date} zurücknehmen"
                      title="Absage zurücknehmen"
                    >
                      {#if restoringInstance === ov.instance_date}
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                          <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                            fill="none"
                          />
                          <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                          />
                        </svg>
                      {:else}
                        <!-- Trash/Restore icon -->
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                          />
                        </svg>
                      {/if}
                    </button>
                  </li>
                {/each}
              </ul>
            </div>
          {/if}
        </div>
      {/if}
    </div>
  {/if}
</div>
