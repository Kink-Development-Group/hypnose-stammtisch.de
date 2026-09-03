<script lang="ts">
  import { onMount, tick } from "svelte";
  import {
    KnownEventSeriesStatus,
    NextEventSource,
  } from "../../enums/knownEventSeries";
  import type {
    AdminKnownEventSeries,
    KnownEventSeriesFormData,
    LinkableEventSeries,
  } from "../../types/knownEventSeries";
  import {
    adminDelete,
    adminGet,
    adminPost,
    adminPut,
  } from "../../utils/adminApi";
  import Portal from "../ui/Portal.svelte";

  let series: AdminKnownEventSeries[] = [];
  let linkableSeries: LinkableEventSeries[] = [];
  let loading = true;
  let error = "";
  /** Announced to screen readers after every completed action. */
  let statusMessage = "";
  let showForm = false;
  let editingSeries: AdminKnownEventSeries | null = null;
  let saving = false;
  /** Blocks a second reorder while one is still in flight, so two quick clicks
   * cannot race and leave the list disagreeing with the stored order. */
  let reordering = false;
  let titleInput: HTMLInputElement | null = null;
  let createButton: HTMLButtonElement | null = null;
  /** The control that opened the dialog, to hand focus back to on close. */
  let formTrigger: HTMLElement | null = null;

  const emptyForm: KnownEventSeriesFormData = {
    title: "",
    location: "",
    frequency: "",
    description: "",
    formats: [],
    price: "",
    tags: [],
    detail_url: "/events",
    next_event_source: NextEventSource.NONE,
    next_event_text: "",
    linked_series_id: "",
    status: KnownEventSeriesStatus.DRAFT,
    is_active: true,
  };

  let formData: KnownEventSeriesFormData = { ...emptyForm };
  let formatInput = "";
  let tagInput = "";

  const statusOptions = [
    { value: KnownEventSeriesStatus.DRAFT, label: "Entwurf" },
    { value: KnownEventSeriesStatus.PUBLISHED, label: "Veröffentlicht" },
    { value: KnownEventSeriesStatus.ARCHIVED, label: "Archiviert" },
  ];

  const nextEventSourceOptions = [
    {
      value: NextEventSource.NONE,
      label: "Kein Termin anzeigen",
    },
    {
      value: NextEventSource.AUTO,
      label: "Automatisch aus verknüpfter Event-Serie",
    },
    {
      value: NextEventSource.MANUAL,
      label: "Manuell eingetragener Text",
    },
  ];

  onMount(async () => {
    await Promise.all([loadSeries(), loadLinkableSeries()]);
  });

  /**
   * Load every series, including drafts and archived ones.
   */
  async function loadSeries(): Promise<void> {
    try {
      loading = true;
      error = "";

      const result = await adminGet<AdminKnownEventSeries[]>(
        "/api/admin/known-event-series",
      );

      if (result.success) {
        series = result.data || [];
      } else {
        throw new Error(
          result.error ||
            result.message ||
            "Event-Reihen konnten nicht geladen werden",
        );
      }
    } catch (err) {
      error =
        err instanceof Error ? err.message : "Unbekannter Fehler beim Laden";
      console.error("Error loading known event series:", err);
    } finally {
      loading = false;
    }
  }

  /**
   * Load the recurring series available for an automatic next date.
   */
  async function loadLinkableSeries(): Promise<void> {
    try {
      const result = await adminGet<LinkableEventSeries[]>(
        "/api/admin/known-event-series/linkable-series",
      );

      if (result.success) {
        linkableSeries = result.data || [];
      }
    } catch (err) {
      console.error("Error loading linkable event series:", err);
    }
  }

  /**
   * Announce a completed action to screen readers.
   *
   * A live region only speaks when its text changes, so the message is cleared
   * first — otherwise moving the same entry twice would stay silent.
   */
  async function announce(message: string): Promise<void> {
    statusMessage = "";
    await tick();
    statusMessage = message;
  }

  function resetForm(): void {
    formData = { ...emptyForm, formats: [], tags: [] };
    formatInput = "";
    tagInput = "";
  }

  /**
   * Open the dialog and move focus into it, remembering where focus came from.
   */
  async function openForm(trigger: EventTarget | null): Promise<void> {
    formTrigger = trigger instanceof HTMLElement ? trigger : null;
    showForm = true;
    await tick();
    titleInput?.focus();
  }

  function startCreate(trigger: EventTarget | null = null): void {
    resetForm();
    editingSeries = null;
    void openForm(trigger);
  }

  function startEdit(
    entry: AdminKnownEventSeries,
    trigger: EventTarget | null = null,
  ): void {
    formData = {
      title: entry.title,
      location: entry.location,
      frequency: entry.frequency,
      description: entry.description,
      formats: [...entry.formats],
      price: entry.price ?? "",
      tags: [...entry.tags],
      detail_url: entry.detailUrl,
      next_event_source: entry.nextEventSource,
      next_event_text: entry.nextEventText ?? "",
      linked_series_id: entry.linkedSeriesId ?? "",
      status: entry.status,
      is_active: entry.isActive,
    };
    formatInput = "";
    tagInput = "";
    editingSeries = entry;
    void openForm(trigger);
  }

  function cancelForm(): void {
    showForm = false;
    editingSeries = null;
    resetForm();

    // Hand focus back where it came from; a reload may have replaced that row,
    // so fall back to the create button rather than dropping focus to the body.
    const trigger = formTrigger;
    formTrigger = null;
    void tick().then(() => {
      if (trigger?.isConnected) {
        trigger.focus();
      } else {
        createButton?.focus();
      }
    });
  }

  function addFormat(): void {
    const value = formatInput.trim();
    if (value && !formData.formats.includes(value)) {
      formData.formats = [...formData.formats, value];
    }
    formatInput = "";
  }

  function removeFormat(format: string): void {
    formData.formats = formData.formats.filter((entry) => entry !== format);
  }

  function addTag(): void {
    const value = tagInput.trim();
    if (value && !formData.tags.includes(value)) {
      formData.tags = [...formData.tags, value];
    }
    tagInput = "";
  }

  function removeTag(tag: string): void {
    formData.tags = formData.tags.filter((entry) => entry !== tag);
  }

  /**
   * Close the modal on Escape, as a dialog is expected to.
   */
  function handleModalKeydown(event: KeyboardEvent): void {
    if (!showForm) {
      return;
    }

    if (event.key === "Escape" || event.key === "Esc") {
      event.preventDefault();
      cancelForm();
    }
  }

  /**
   * Add an entry when the user presses Enter, without submitting the form.
   */
  function handleListKeydown(event: KeyboardEvent, add: () => void): void {
    if (event.key === "Enter") {
      event.preventDefault();
      add();
    }
  }

  async function saveSeries(): Promise<void> {
    try {
      saving = true;
      error = "";

      const payload: Record<string, unknown> = {
        ...formData,
        price: formData.price.trim(),
        next_event_text:
          formData.next_event_source === NextEventSource.MANUAL
            ? formData.next_event_text.trim()
            : "",
        linked_series_id:
          formData.next_event_source === NextEventSource.AUTO
            ? formData.linked_series_id
            : "",
      };

      const result = editingSeries
        ? await adminPut(
            `/api/admin/known-event-series/${editingSeries.id}`,
            payload,
          )
        : await adminPost("/api/admin/known-event-series", payload);

      if (!result.success) {
        throw new Error(
          result.error || result.message || "Speichern fehlgeschlagen",
        );
      }

      const savedTitle = formData.title;
      const wasEditing = Boolean(editingSeries);
      cancelForm();
      await loadSeries();
      await announce(
        wasEditing
          ? `Event-Reihe „${savedTitle}“ wurde gespeichert.`
          : `Event-Reihe „${savedTitle}“ wurde angelegt.`,
      );
    } catch (err) {
      error =
        err instanceof Error
          ? err.message
          : "Unbekannter Fehler beim Speichern";
      console.error("Error saving known event series:", err);
    } finally {
      saving = false;
    }
  }

  async function deleteSeries(entry: AdminKnownEventSeries): Promise<void> {
    if (
      !confirm(
        `Möchten Sie die Event-Reihe „${entry.title}“ wirklich löschen? Sie verschwindet damit von der Startseite.`,
      )
    ) {
      return;
    }

    try {
      error = "";
      const result = await adminDelete(
        `/api/admin/known-event-series/${entry.id}`,
      );

      if (!result.success) {
        throw new Error(
          result.error || result.message || "Löschen fehlgeschlagen",
        );
      }

      await loadSeries();
      await announce(`Event-Reihe „${entry.title}“ wurde gelöscht.`);
    } catch (err) {
      error =
        err instanceof Error ? err.message : "Unbekannter Fehler beim Löschen";
      console.error("Error deleting known event series:", err);
    }
  }

  /**
   * Publish or unpublish a single entry without opening the form.
   */
  async function setStatus(
    entry: AdminKnownEventSeries,
    status: KnownEventSeriesStatus,
  ): Promise<void> {
    try {
      error = "";
      const result = await adminPut(
        `/api/admin/known-event-series/${entry.id}`,
        { status },
      );

      if (!result.success) {
        throw new Error(
          result.error || result.message || "Statuswechsel fehlgeschlagen",
        );
      }

      await loadSeries();
      await announce(
        `Event-Reihe „${entry.title}“ ist jetzt ${statusLabel(status)}.`,
      );
    } catch (err) {
      error =
        err instanceof Error
          ? err.message
          : "Unbekannter Fehler beim Statuswechsel";
      console.error("Error updating known event series status:", err);
    }
  }

  /**
   * Move an entry one position up or down.
   *
   * Deliberately buttons rather than drag-and-drop: sorting has to work with a
   * keyboard alone.
   */
  async function move(index: number, direction: -1 | 1): Promise<void> {
    const target = index + direction;
    if (reordering || target < 0 || target >= series.length) {
      return;
    }

    reordering = true;
    const reordered = [...series];
    const [moved] = reordered.splice(index, 1);
    reordered.splice(target, 0, moved);
    series = reordered;

    try {
      error = "";
      const result = await adminPost("/api/admin/known-event-series/reorder", {
        ids: reordered.map((entry) => entry.id),
      });

      if (!result.success) {
        throw new Error(
          result.error || result.message || "Sortierung fehlgeschlagen",
        );
      }

      await announce(
        `„${moved.title}“ ist jetzt an Position ${target + 1} von ${reordered.length}.`,
      );
    } catch (err) {
      error =
        err instanceof Error
          ? err.message
          : "Unbekannter Fehler beim Sortieren";
      console.error("Error reordering known event series:", err);
      // The server rejected the new order — go back to what it actually stores.
      await loadSeries();
    } finally {
      reordering = false;
    }
  }

  function statusLabel(status: string): string {
    return (
      statusOptions.find((option) => option.value === status)?.label ?? status
    );
  }

  function statusBadgeClass(status: string): string {
    switch (status) {
      case KnownEventSeriesStatus.PUBLISHED:
        return "bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300";
      case KnownEventSeriesStatus.DRAFT:
        return "bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300";
      default:
        return "bg-gray-100 dark:bg-charcoal-700 text-gray-800 dark:text-smoke-300";
    }
  }

  /**
   * How the next date of an entry is filled, for the overview table.
   */
  function nextEventSummary(entry: AdminKnownEventSeries): string {
    switch (entry.nextEventSource) {
      case NextEventSource.AUTO:
        return entry.linkedSeriesTitle
          ? `Automatisch • ${entry.linkedSeriesTitle}`
          : "Automatisch • Serie fehlt";
      case NextEventSource.MANUAL:
        return `Manuell • ${entry.nextEventText ?? ""}`;
      default:
        return "Kein Termin";
    }
  }
</script>

<svelte:window on:keydown={handleModalKeydown} />

<div class="space-y-6">
  <!-- Header -->
  <div class="flex flex-wrap gap-4 justify-between items-center">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-smoke-50">
        Bekannte Event-Reihen
      </h1>
      <p class="text-slate-600 dark:text-smoke-400">
        Verwalten Sie die Event-Reihen, die auf der Startseite vorgestellt
        werden.
      </p>
    </div>
    <button
      bind:this={createButton}
      on:click={(event) => startCreate(event.currentTarget)}
      class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors"
    >
      Neue Event-Reihe
    </button>
  </div>

  <!-- Status announcements for screen readers -->
  <p aria-live="polite" class="sr-only">{statusMessage}</p>

  {#if error}
    <div
      role="alert"
      class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg"
    >
      {error}
    </div>
  {/if}

  <!-- Series table -->
  <div
    class="bg-white dark:bg-charcoal-800 shadow border dark:border-charcoal-700 rounded-lg overflow-hidden"
  >
    {#if loading}
      <div class="p-8 text-center">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
        ></div>
        <p class="mt-2 text-slate-600 dark:text-smoke-400">
          Lade Event-Reihen...
        </p>
      </div>
    {:else if series.length === 0}
      <div class="p-8 text-center text-slate-600 dark:text-smoke-400">
        <p>
          Noch keine Event-Reihen vorhanden. Solange keine Reihe veröffentlicht
          ist, wird die Sektion auf der Startseite ausgeblendet.
        </p>
        <button
          on:click={(event) => startCreate(event.currentTarget)}
          class="mt-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
        >
          Erste Event-Reihe anlegen
        </button>
      </div>
    {:else}
      <div class="overflow-x-auto">
        <table
          class="min-w-full divide-y divide-gray-200 dark:divide-charcoal-700"
        >
          <thead class="bg-gray-50 dark:bg-charcoal-700">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-slate-600 dark:text-smoke-400 uppercase tracking-wider"
              >
                Reihenfolge
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-slate-600 dark:text-smoke-400 uppercase tracking-wider"
              >
                Titel & Ort
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-slate-600 dark:text-smoke-400 uppercase tracking-wider"
              >
                Nächster Termin
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-slate-600 dark:text-smoke-400 uppercase tracking-wider"
              >
                Status
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-slate-600 dark:text-smoke-400 uppercase tracking-wider"
              >
                Aktionen
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-charcoal-800 divide-y divide-gray-200 dark:divide-charcoal-700"
          >
            {#each series as entry, index (entry.id)}
              <tr class="hover:bg-gray-50 dark:hover:bg-charcoal-700">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-600 dark:text-smoke-400">
                      {index + 1}
                    </span>
                    <button
                      type="button"
                      on:click={() => move(index, -1)}
                      disabled={index === 0 || reordering}
                      aria-label={`„${entry.title}“ nach oben verschieben`}
                      class="px-2 py-1 rounded border border-gray-300 dark:border-charcoal-500 text-gray-700 dark:text-smoke-200 hover:bg-gray-100 dark:hover:bg-charcoal-600 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                      ↑
                    </button>
                    <button
                      type="button"
                      on:click={() => move(index, 1)}
                      disabled={index === series.length - 1 || reordering}
                      aria-label={`„${entry.title}“ nach unten verschieben`}
                      class="px-2 py-1 rounded border border-gray-300 dark:border-charcoal-500 text-gray-700 dark:text-smoke-200 hover:bg-gray-100 dark:hover:bg-charcoal-600 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                      ↓
                    </button>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div
                    class="text-sm font-medium text-gray-900 dark:text-smoke-100"
                  >
                    {entry.title}
                  </div>
                  <div class="text-sm text-slate-600 dark:text-smoke-400">
                    {entry.location}
                  </div>
                  <div class="text-xs text-slate-500 dark:text-smoke-500">
                    {entry.frequency}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-smoke-100">
                    {nextEventSummary(entry)}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {statusBadgeClass(
                      entry.status,
                    )}"
                  >
                    {statusLabel(entry.status)}
                  </span>
                  {#if !entry.isActive}
                    <span
                      class="ml-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300"
                    >
                      Inaktiv
                    </span>
                  {/if}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex flex-wrap gap-2">
                    <button
                      on:click={(event) =>
                        startEdit(entry, event.currentTarget)}
                      class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                    >
                      Bearbeiten
                    </button>
                    {#if entry.status === KnownEventSeriesStatus.PUBLISHED}
                      <button
                        on:click={() =>
                          setStatus(entry, KnownEventSeriesStatus.DRAFT)}
                        class="text-yellow-700 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300"
                      >
                        Zurückziehen
                      </button>
                    {:else}
                      <button
                        on:click={() =>
                          setStatus(entry, KnownEventSeriesStatus.PUBLISHED)}
                        class="text-green-700 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"
                      >
                        Veröffentlichen
                      </button>
                    {/if}
                    <button
                      on:click={() => deleteSeries(entry)}
                      class="text-red-600 hover:text-red-900 dark:hover:text-red-400"
                    >
                      Löschen
                    </button>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  </div>
</div>

<!-- Create/Edit modal -->
{#if showForm}
  <Portal>
    <div
      class="fixed inset-0 bg-gray-700/50 dark:bg-charcoal-900/80 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999]"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="series-form-title"
        class="relative mx-auto mt-8 md:mt-12 border dark:border-charcoal-600 w-11/12 max-w-4xl shadow-2xl rounded-lg bg-white dark:bg-charcoal-800 flex flex-col max-h-[92vh]"
      >
        <div
          class="px-6 py-5 border-b dark:border-charcoal-600 bg-gradient-to-r from-gray-50 to-white dark:from-charcoal-700 dark:to-charcoal-800 sticky top-0 z-10 rounded-t-lg"
        >
          <div class="flex items-start justify-between flex-wrap gap-4">
            <h2
              id="series-form-title"
              class="text-xl font-semibold text-gray-900 dark:text-smoke-50 leading-tight"
            >
              {editingSeries
                ? "Event-Reihe bearbeiten"
                : "Neue Event-Reihe anlegen"}
            </h2>
            <button
              type="button"
              class="text-xs px-2 py-1 rounded border dark:border-charcoal-500 shadow-sm hover:bg-gray-100 dark:hover:bg-charcoal-600 text-gray-700 dark:text-smoke-200 focus:outline-none focus:ring"
              on:click={cancelForm}
            >
              Schließen
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6">
          <form on:submit|preventDefault={saveSeries} class="space-y-6">
            <fieldset class="space-y-4">
              <legend
                class="text-lg font-medium text-gray-900 dark:text-smoke-100 mb-2"
              >
                Grundinformationen
              </legend>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    for="series-title"
                    class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                  >
                    Titel
                    <span class="text-red-600 dark:text-red-400">*</span>
                  </label>
                  <input
                    id="series-title"
                    type="text"
                    bind:this={titleInput}
                    bind:value={formData.title}
                    required
                    minlength="3"
                    maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                    placeholder="z. B. Hamburger Hypnose Munch"
                  />
                </div>

                <div>
                  <label
                    for="series-location"
                    class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                  >
                    Ort
                    <span class="text-red-600 dark:text-red-400">*</span>
                  </label>
                  <input
                    id="series-location"
                    type="text"
                    bind:value={formData.location}
                    required
                    minlength="2"
                    maxlength="255"
                    aria-describedby="series-location-hint"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                    placeholder="z. B. Hamburg • Club Catonium"
                  />
                  <p
                    id="series-location-hint"
                    class="mt-1 text-xs text-slate-500 dark:text-smoke-400"
                  >
                    Stadt zuerst, dann der Veranstaltungsort — getrennt durch
                    „•“. Die Startseite bildet aus dem Teil vor dem Trenner ihre
                    Städteliste.
                  </p>
                </div>

                <div>
                  <label
                    for="series-frequency"
                    class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                  >
                    Rhythmus
                    <span class="text-red-600 dark:text-red-400">*</span>
                  </label>
                  <input
                    id="series-frequency"
                    type="text"
                    bind:value={formData.frequency}
                    required
                    minlength="2"
                    maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                    placeholder="z. B. Monatlich • Jeden 1. Freitag"
                  />
                </div>

                <div>
                  <label
                    for="series-price"
                    class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                  >
                    Preis
                  </label>
                  <input
                    id="series-price"
                    type="text"
                    bind:value={formData.price}
                    maxlength="100"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                    placeholder="z. B. 10€ Eintritt oder Kostenfrei"
                  />
                </div>
              </div>

              <div>
                <label
                  for="series-description"
                  class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                >
                  Beschreibung
                </label>
                <textarea
                  id="series-description"
                  bind:value={formData.description}
                  rows="3"
                  maxlength="2000"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                  placeholder="Einleitender Satz über die Reihe"></textarea>
              </div>

              <div>
                <label
                  for="series-detail-url"
                  class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                >
                  Ziel des „Details“-Buttons
                </label>
                <input
                  id="series-detail-url"
                  type="text"
                  bind:value={formData.detail_url}
                  maxlength="500"
                  aria-describedby="series-detail-url-hint"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                  placeholder="/events"
                />
                <p
                  id="series-detail-url-hint"
                  class="mt-1 text-xs text-slate-500 dark:text-smoke-400"
                >
                  Interner Pfad wie „/events“ oder eine vollständige
                  https-Adresse für eine fremde Reihe.
                </p>
              </div>
            </fieldset>

            <fieldset class="space-y-4">
              <legend
                class="text-lg font-medium text-gray-900 dark:text-smoke-100 mb-2"
              >
                Formate und Tags
              </legend>

              <div>
                <label
                  for="series-format-input"
                  class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                >
                  Formate
                </label>
                <div class="flex gap-2">
                  <input
                    id="series-format-input"
                    type="text"
                    bind:value={formatInput}
                    on:keydown={(event) => handleListKeydown(event, addFormat)}
                    maxlength="120"
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                    placeholder="z. B. Hypnose 101 – Einsteiger-Abende"
                  />
                  <button
                    type="button"
                    on:click={addFormat}
                    class="px-3 py-2 rounded-md border border-gray-300 dark:border-charcoal-500 text-gray-700 dark:text-smoke-200 hover:bg-gray-100 dark:hover:bg-charcoal-600"
                  >
                    Hinzufügen
                  </button>
                </div>
                {#if formData.formats.length > 0}
                  <ul class="mt-2 space-y-1">
                    {#each formData.formats as format (format)}
                      <li
                        class="flex items-center justify-between gap-2 text-sm text-gray-900 dark:text-smoke-100 bg-gray-50 dark:bg-charcoal-700 rounded px-3 py-1"
                      >
                        <span>{format}</span>
                        <button
                          type="button"
                          on:click={() => removeFormat(format)}
                          aria-label={`Format „${format}“ entfernen`}
                          class="text-red-600 dark:text-red-400 hover:text-red-800"
                        >
                          Entfernen
                        </button>
                      </li>
                    {/each}
                  </ul>
                {/if}
              </div>

              <div>
                <label
                  for="series-tag-input"
                  class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                >
                  Tags
                </label>
                <div class="flex gap-2">
                  <input
                    id="series-tag-input"
                    type="text"
                    bind:value={tagInput}
                    on:keydown={(event) => handleListKeydown(event, addTag)}
                    maxlength="120"
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                    placeholder="z. B. Alle Levels"
                  />
                  <button
                    type="button"
                    on:click={addTag}
                    class="px-3 py-2 rounded-md border border-gray-300 dark:border-charcoal-500 text-gray-700 dark:text-smoke-200 hover:bg-gray-100 dark:hover:bg-charcoal-600"
                  >
                    Hinzufügen
                  </button>
                </div>
                {#if formData.tags.length > 0}
                  <div class="mt-2 flex flex-wrap gap-2">
                    {#each formData.tags as tag (tag)}
                      <span
                        class="inline-flex items-center gap-2 px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 rounded"
                      >
                        {tag}
                        <button
                          type="button"
                          on:click={() => removeTag(tag)}
                          aria-label={`Tag „${tag}“ entfernen`}
                          class="text-blue-900 dark:text-blue-200 hover:text-red-700"
                        >
                          ×
                        </button>
                      </span>
                    {/each}
                  </div>
                {/if}
              </div>
            </fieldset>

            <fieldset class="space-y-4">
              <legend
                class="text-lg font-medium text-gray-900 dark:text-smoke-100 mb-2"
              >
                Nächster Termin
              </legend>

              <div>
                <label
                  for="series-next-source"
                  class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                >
                  Quelle
                </label>
                <select
                  id="series-next-source"
                  bind:value={formData.next_event_source}
                  class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                >
                  {#each nextEventSourceOptions as option (option.value)}
                    <option value={option.value}>{option.label}</option>
                  {/each}
                </select>
              </div>

              {#if formData.next_event_source === NextEventSource.AUTO}
                <div>
                  <label
                    for="series-linked-series"
                    class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                  >
                    Verknüpfte Event-Serie
                    <span class="text-red-600 dark:text-red-400">*</span>
                  </label>
                  <select
                    id="series-linked-series"
                    bind:value={formData.linked_series_id}
                    required
                    aria-describedby="series-linked-series-hint"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                  >
                    <option value="">Bitte wählen…</option>
                    {#each linkableSeries as option (option.id)}
                      <option value={option.id}>
                        {option.title}
                        {option.status !== "published"
                          ? ` (${option.status})`
                          : ""}
                      </option>
                    {/each}
                  </select>
                  <p
                    id="series-linked-series-hint"
                    class="mt-1 text-xs text-slate-500 dark:text-smoke-400"
                  >
                    Der Termin wird bei jedem Aufruf aus der Serie berechnet —
                    abgesagte Termine werden übersprungen. Nur veröffentlichte
                    Serien liefern einen Termin.
                  </p>
                </div>
              {/if}

              {#if formData.next_event_source === NextEventSource.MANUAL}
                <div>
                  <label
                    for="series-next-text"
                    class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                  >
                    Termin-Text
                    <span class="text-red-600 dark:text-red-400">*</span>
                  </label>
                  <input
                    id="series-next-text"
                    type="text"
                    bind:value={formData.next_event_text}
                    required
                    maxlength="255"
                    aria-describedby="series-next-text-hint"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                    placeholder="z. B. Fr, 6. Sep 2026"
                  />
                  <p
                    id="series-next-text-hint"
                    class="mt-1 text-xs text-slate-500 dark:text-smoke-400"
                  >
                    Die Startseite stellt „Nächster Termin:“ voran. Ein
                    manueller Text veraltet nicht von selbst — bitte
                    gelegentlich prüfen.
                  </p>
                </div>
              {/if}
            </fieldset>

            <fieldset class="space-y-4">
              <legend
                class="text-lg font-medium text-gray-900 dark:text-smoke-100 mb-2"
              >
                Sichtbarkeit
              </legend>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    for="series-status"
                    class="block text-sm font-medium text-gray-700 dark:text-smoke-300 mb-1"
                  >
                    Status
                  </label>
                  <select
                    id="series-status"
                    bind:value={formData.status}
                    class="w-full px-3 py-2 border border-gray-300 dark:border-charcoal-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                  >
                    {#each statusOptions as option (option.value)}
                      <option value={option.value}>{option.label}</option>
                    {/each}
                  </select>
                </div>

                <div class="flex items-end">
                  <label
                    class="flex items-center gap-2 text-sm text-gray-700 dark:text-smoke-300"
                  >
                    <input
                      type="checkbox"
                      bind:checked={formData.is_active}
                      class="rounded border-gray-300 dark:border-charcoal-600 text-blue-600 focus:ring-blue-500"
                    />
                    Aktiv (auf der Startseite sichtbar)
                  </label>
                </div>
              </div>
            </fieldset>

            <div
              class="flex justify-end gap-3 border-t dark:border-charcoal-600 pt-4"
            >
              <button
                type="button"
                on:click={cancelForm}
                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-charcoal-500 text-gray-700 dark:text-smoke-200 hover:bg-gray-100 dark:hover:bg-charcoal-600"
              >
                Abbrechen
              </button>
              <button
                type="submit"
                disabled={saving}
                class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors disabled:opacity-60"
              >
                {saving ? "Speichern…" : "Speichern"}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Portal>
{/if}
