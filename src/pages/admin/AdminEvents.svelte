<script lang="ts">
  import { onDestroy, onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import User from "../../classes/User";
  import AdminLayout from "../../components/admin/AdminLayout.svelte";
  import RecurrenceBuilder from "../../components/admin/recurrence/RecurrenceBuilder.svelte";
  import SeriesManagement from "../../components/admin/SeriesManagement.svelte";
  import { AdminAPI, adminAuth } from "../../stores/admin";
  import {
    adminAutoUpdate,
    adminEventBus,
    adminEvents,
    adminLoading,
    adminSeries,
  } from "../../stores/adminData";

  let error = "";
  let showCreateModal = false;
  let showEditModal = false;
  let editingItem: any = null;
  let deleteConfirm: any = null;
  let currentUser: User | null = null;

  // Reactive subscriptions
  $: events = $adminEvents;
  $: series = $adminSeries;
  $: loading = $adminLoading;

  let newEvent = {
    event_type: "single",
    title: "",
    description: "",
    content: "",
    start_datetime: "",
    end_datetime: "",
    timezone: "Europe/Berlin",
    location_type: "physical",
    location_name: "",
    location_address: "",
    location_url: "",
    category: "stammtisch",
    difficulty_level: "all",
    max_participants: null,
    status: "draft",
    is_featured: false,
    requires_registration: true,
    organizer_name: "",
    organizer_email: "",
    tags: [] as string[],
    // Series specific
    rrule: "",
    start_date: "",
    end_date: "",
    exdates: [],
    start_time: "",
    end_time: "",
  };

  // Sync-Logik: Wenn ein Enddatum (Serie) geändert wird, RecurrenceBuilder-UNTIL spiegeln (und umgekehrt)
  // Annahme: RecurrenceBuilder value (RRULE) enthält ggf. ein UNTIL=YYYYMMDD oder COUNT.
  $: if (newEvent.event_type === "series" && newEvent.end_date) {
    // Wenn end_date vorhanden aber RRULE kein UNTIL oder anderes Datum -> aktualisieren
    if (newEvent.rrule) {
      const untilMatch = newEvent.rrule.match(/UNTIL=(\d{8})/);
      const iso = newEvent.end_date.replace(/-/g, "");
      if (untilMatch && untilMatch[1] !== iso) {
        newEvent.rrule = newEvent.rrule.replace(/UNTIL=\d{8}/, "UNTIL=" + iso);
      } else if (!untilMatch) {
        // Falls COUNT existiert, nicht automatisch ersetzen; nur hinzufügen wenn weder UNTIL noch COUNT
        if (!/COUNT=\d+/.test(newEvent.rrule)) {
          // Füge UNTIL am Ende vor evtl. nachgestellten Leerzeichen hinzu
          if (/;$/.test(newEvent.rrule)) {
            newEvent.rrule += "UNTIL=" + iso;
          } else {
            newEvent.rrule +=
              (newEvent.rrule.endsWith("\n") ? "" : ";") + "UNTIL=" + iso;
          }
        }
      }
    }
  }

  // Wenn RRULE-Ende (UNTIL) geändert wurde und ein anderes end_date gesetzt ist -> angleichen
  $: if (newEvent.event_type === "series" && newEvent.rrule) {
    const untilMatch = newEvent.rrule.match(/UNTIL=(\d{8})/);
    if (untilMatch) {
      const rruleDate = untilMatch[1];
      const formatted =
        rruleDate.slice(0, 4) +
        "-" +
        rruleDate.slice(4, 6) +
        "-" +
        rruleDate.slice(6, 8);
      if (newEvent.end_date && newEvent.end_date !== formatted) {
        newEvent.end_date = formatted;
      }
    }
  }

  onMount(() => {
    let unsubscribeEventBus: (() => void) | null = null;

    const initializeComponent = async () => {
      // Check authentication and permissions
      const status = await adminAuth.checkStatus();
      if (!status.success) {
        push("/admin/login");
        return;
      }

      currentUser = User.fromApiData(status.data);

      // Check if user has permission to manage events
      if (!currentUser.canManageEvents()) {
        error = "Sie haben keine Berechtigung, Veranstaltungen zu verwalten.";
        push("/admin/messages"); // Redirect to messages instead
        return;
      }

      loadEvents();

      // Starte Auto-Update
      adminAutoUpdate.start(30000); // 30 Sekunden

      // Event Bus Listener für automatische Updates
      unsubscribeEventBus = adminEventBus.subscribe((event) => {
        if (event?.data?.autoRefresh || event?.data?.manualRefresh) {
          loadEvents();
        }
      });
    };

    initializeComponent();

    return () => {
      if (unsubscribeEventBus) {
        unsubscribeEventBus();
      }
    };
  });

  onDestroy(() => {
    // Stoppe Auto-Update beim Verlassen der Komponente
    adminAutoUpdate.stop();
  });

  async function loadEvents() {
    adminLoading.set(true);
    try {
      const result = await AdminAPI.getEvents();
      console.log("[DEBUG] Events API Response:", result);
      if (result.success) {
        console.log(
          `[DEBUG] Anzahl Events: ${result.data.events?.length ?? 0}`,
        );
        console.log(
          `[DEBUG] Anzahl Serien: ${result.data.series?.length ?? 0}`,
        );
        if (result.data.series) {
          result.data.series.forEach((serie: any, idx: number) => {
            console.log(`[DEBUG] Serie #${idx + 1}:`, {
              id: serie.id,
              title: serie.title,
              rrule: serie.rrule,
              exdates: serie.exdates,
              start_date: serie.start_date,
              end_date: serie.end_date,
            });
          });
        }
      }
      if (!result.success) {
        error = result.message || "Fehler beim Laden der Veranstaltungen";
      }
    } catch (e) {
      error = "Netzwerkfehler beim Laden der Veranstaltungen";
      console.error("[DEBUG] Events API Fehler:", e);
    } finally {
      adminLoading.set(false);
    }
  }

  function openCreateModal() {
    resetForm();
    showCreateModal = true;
  }

  function openEditModal(item: any, type: "event" | "series") {
    resetForm();
    editingItem = {
      ...item,
      event_type: type === "series" ? "series" : "single",
    };

    // Populate form with existing data
    Object.keys(newEvent).forEach((key) => {
      let value = editingItem[key];
      if (key === "tags") {
        // Tags immer als Array normalisieren
        if (Array.isArray(value)) {
          value = value.filter(
            (t: string) => typeof t === "string" && t.trim() !== "",
          );
        } else if (typeof value === "string") {
          try {
            const arr = JSON.parse(value);
            if (Array.isArray(arr)) {
              value = arr.filter(
                (t: string) => typeof t === "string" && t.trim() !== "",
              );
            } else {
              value = [];
            }
          } catch {
            value = [];
          }
        } else {
          value = [];
        }
      }
      if (value !== undefined) {
        (newEvent as any)[key] = value;
      }
    });

    showEditModal = true;
  }

  function resetForm() {
    newEvent = {
      event_type: "single",
      title: "",
      description: "",
      content: "",
      start_datetime: "",
      end_datetime: "",
      timezone: "Europe/Berlin",
      location_type: "physical",
      location_name: "",
      location_address: "",
      location_url: "",
      category: "stammtisch",
      difficulty_level: "all",
      max_participants: null,
      status: "draft",
      is_featured: false,
      requires_registration: true,
      organizer_name: "",
      organizer_email: "",
      tags: [],
      rrule: "",
      start_date: "",
      end_date: "",
      exdates: [],
      start_time: "",
      end_time: "",
    };
    editingItem = null;
  }

  async function handleSave() {
    try {
      let result;

      if (editingItem) {
        result = await AdminAPI.updateEvent(editingItem.id, newEvent);
      } else {
        result = await AdminAPI.createEvent(newEvent);
      }

      if (result.success) {
        showCreateModal = false;
        showEditModal = false;
        resetForm();
        // Die Store-Updates werden automatisch durch optimistische Updates gehandhabt
      } else {
        error = result.message || "Fehler beim Speichern";
      }
    } catch {
      error = "Netzwerkfehler beim Speichern";
    }
  }

  async function handleDelete(item: any) {
    try {
      const result = await AdminAPI.deleteEvent(item.id);

      if (result.success) {
        deleteConfirm = null;
        // Die Store-Updates werden automatisch durch optimistische Updates gehandhabt
      } else {
        error = result.message || "Fehler beim Löschen";
      }
    } catch {
      error = "Netzwerkfehler beim Löschen";
    }
  }

  function canDelete(_item: any): boolean {
    if (!currentUser) return false;
    // Head & Admin: alles
    if (currentUser.role === "head" || currentUser.role === "admin")
      return true;
    // Event-Manager: jetzt volle Verwaltung inkl. Serien-Löschung
    if (currentUser.role === "event_manager") return true;
    return false;
  }

  // --- Erweiterte Formularlogik für neues Multi-Sektions-Formular ---
  let activeSection: "basis" | "zeit" | "ort" | "erweitert" = "basis";
  let formErrors: string[] = [];
  let tagInput = "";

  function addTag() {
    const t = tagInput.trim();
    if (t && !(newEvent.tags as string[]).includes(t))
      newEvent.tags = [...(newEvent.tags as string[]), t];
    tagInput = "";
  }
  function removeTag(tag: string) {
    newEvent.tags = newEvent.tags.filter((t: string) => t !== tag);
  }
  function validateForm(): boolean {
    formErrors = [];
    if (!newEvent.title || newEvent.title.trim().length < 3)
      formErrors.push("Titel mindestens 3 Zeichen");
    if (newEvent.event_type === "single") {
      if (!newEvent.start_datetime) formErrors.push("Startzeit fehlt (Einzel)");
      if (!newEvent.end_datetime) formErrors.push("Endzeit fehlt (Einzel)");
      if (
        newEvent.start_datetime &&
        newEvent.end_datetime &&
        newEvent.end_datetime <= newEvent.start_datetime
      )
        formErrors.push("Endzeit muss nach Startzeit liegen");
    } else {
      if (!newEvent.start_date) formErrors.push("Startdatum fehlt (Serie)");
      if (!newEvent.rrule) formErrors.push("Wiederholungsregel (RRULE) fehlt");
      if (
        newEvent.start_time &&
        newEvent.end_time &&
        newEvent.end_time <= newEvent.start_time
      )
        formErrors.push("Serien-Endzeit muss nach Serien-Startzeit liegen");
    }
    return formErrors.length === 0;
  }
  const originalHandleSave = handleSave;
  async function enhancedHandleSave() {
    if (!validateForm()) {
      activeSection = formErrors.some((e) => e.toLowerCase().includes("titel"))
        ? "basis"
        : activeSection;
      return;
    }
    await originalHandleSave();
  }
  function switchSection(s: typeof activeSection) {
    activeSection = s;
  }

  function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString("de-DE");
  }

  function getStatusBadge(status: string): string {
    const statusClasses: Record<string, string> = {
      draft: "bg-gray-100 text-gray-800",
      published: "bg-green-100 text-green-800",
      cancelled: "bg-red-100 text-red-800",
      completed: "bg-blue-100 text-blue-800",
    };
    return statusClasses[status] || "bg-gray-100 text-gray-800";
  }
</script>

<svelte:head>
  <title>Veranstaltungen verwalten - Admin</title>
</svelte:head>

<AdminLayout>
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Veranstaltungen</h1>
          <p class="mt-1 text-sm text-gray-600">
            Verwalten Sie Einzelveranstaltungen und Veranstaltungsreihen
          </p>
        </div>
        <button
          on:click={openCreateModal}
          class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          Neue Veranstaltung
        </button>
      </div>
    </div>

    {#if error}
      <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
        <div class="text-red-800">{error}</div>
        <button
          on:click={() => (error = "")}
          class="mt-2 text-red-600 text-sm underline"
        >
          Schließen
        </button>
      </div>
    {/if}

    {#if loading}
      <div class="flex justify-center py-12">
        <div
          class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
        ></div>
      </div>
    {:else}
      <!-- Single Events -->
      <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          Einzelveranstaltungen
        </h2>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
          {#if events.length === 0}
            <div class="p-6 text-center text-gray-500">
              Keine Einzelveranstaltungen vorhanden
            </div>
          {:else}
            <ul class="divide-y divide-gray-200">
              {#each events as event}
                <li class="px-6 py-4">
                  <div class="flex items-center justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-3">
                        <h3 class="text-sm font-medium text-gray-900">
                          {event.title}
                        </h3>
                        <span
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getStatusBadge(
                            event.status,
                          )}"
                        >
                          {event.status}
                        </span>
                        {#if event.is_featured}
                          <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                          >
                            Featured
                          </span>
                        {/if}
                      </div>
                      <div class="mt-1 text-sm text-gray-600">
                        {formatDate(event.start_datetime)} - {formatDate(
                          event.end_datetime,
                        )}
                      </div>
                      <div class="mt-1 text-sm text-gray-500">
                        {event.category} • {event.location_type} • {event.difficulty_level}
                      </div>
                    </div>

                    <div class="flex items-center space-x-2">
                      <button
                        on:click={() => openEditModal(event, "event")}
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                      >
                        Bearbeiten
                      </button>
                      {#if canDelete(event)}
                        <button
                          on:click={() => (deleteConfirm = event)}
                          class="text-red-600 hover:text-red-800 text-sm font-medium"
                        >
                          Löschen
                        </button>
                      {:else if currentUser?.role === "event_manager"}
                        <span
                          class="text-xs text-gray-400"
                          title="Nur vergangene Veranstaltungen können gelöscht werden"
                          >(nur vergangene löschbar)</span
                        >
                      {/if}
                    </div>
                  </div>
                </li>
              {/each}
            </ul>
          {/if}
        </div>
      </div>

      <!-- Event Series -->
      <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          Veranstaltungsreihen
        </h2>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
          {#if series.length === 0}
            <div class="p-6 text-center text-gray-500">
              Keine Veranstaltungsreihen vorhanden
            </div>
          {:else}
            <ul class="divide-y divide-gray-200">
              {#each series as seriesItem}
                <li class="px-6 py-4">
                  <div class="flex items-center justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-3">
                        <h3 class="text-sm font-medium text-gray-900">
                          {seriesItem.title}
                        </h3>
                        <span
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getStatusBadge(
                            seriesItem.status,
                          )}"
                        >
                          {seriesItem.status}
                        </span>
                        <span
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                        >
                          Serie
                        </span>
                      </div>
                      <div class="mt-1 text-sm text-gray-600">
                        {seriesItem.rrule}
                      </div>
                      <div class="mt-1 text-sm text-gray-500">
                        Ab {seriesItem.start_date} • {seriesItem.generated_events_count ||
                          0} generierte Events
                      </div>
                    </div>

                    <div class="flex items-center space-x-2">
                      <button
                        on:click={() => openEditModal(seriesItem, "series")}
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                      >
                        Bearbeiten
                      </button>
                      {#if canDelete(seriesItem)}
                        <button
                          on:click={() => (deleteConfirm = seriesItem)}
                          class="text-red-600 hover:text-red-800 text-sm font-medium"
                        >
                          Löschen
                        </button>
                      {/if}
                    </div>
                  </div>
                  <!-- Overrides & EXDATE Management -->
                  <details class="mt-4 bg-gray-50 rounded p-4">
                    <summary
                      class="cursor-pointer text-sm font-semibold text-gray-700"
                      >Instanzen & Ausnahmen verwalten</summary
                    >
                    <SeriesManagement {seriesItem} />
                  </details>
                </li>
              {/each}
            </ul>
          {/if}
        </div>
      </div>
    {/if}
  </div>

  <!-- Create/Edit Modal (Neu strukturiert) -->
  {#if showCreateModal || showEditModal}
    <div
      class="fixed inset-0 bg-gray-700/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50"
    >
      <div
        class="relative mx-auto mt-8 md:mt-12 border w-11/12 max-w-5xl shadow-2xl rounded-lg bg-white flex flex-col max-h-[92vh]"
      >
        <!-- Header -->
        <div
          class="px-6 py-5 border-b bg-gradient-to-r from-gray-50 to-white sticky top-0 z-10 rounded-t-lg"
        >
          <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
              <h3 class="text-xl font-semibold text-gray-900 leading-tight">
                {editingItem
                  ? "Veranstaltung bearbeiten"
                  : "Neue Veranstaltung erstellen"}
              </h3>
              <p class="text-xs text-gray-500 mt-1">
                {newEvent.event_type === "single"
                  ? "Einzelner Termin mit genauer Start-/Endzeit"
                  : "Wiederkehrende Serie mit RRULE"}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="text-xs px-2 py-1 rounded border shadow-sm hover:bg-gray-100 focus:outline-none focus:ring"
                on:click={() => {
                  showCreateModal = false;
                  showEditModal = false;
                }}>Schließen</button
              >
              <button
                type="button"
                class="text-xs px-2 py-1 rounded border shadow-sm hover:bg-gray-100 focus:outline-none focus:ring"
                on:click={() => resetForm()}>Zurücksetzen</button
              >
            </div>
          </div>
          <!-- Typ Umschalter -->
          <div
            class="mt-4 inline-flex rounded-md overflow-hidden border border-gray-300 bg-white shadow-sm"
          >
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium transition-colors {newEvent.event_type ===
              'single'
                ? 'bg-blue-600 text-white'
                : 'text-gray-600 hover:bg-gray-50'}"
              on:click={() => {
                newEvent.event_type = "single";
              }}>Einzel</button
            >
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium transition-colors {newEvent.event_type ===
              'series'
                ? 'bg-blue-600 text-white'
                : 'text-gray-600 hover:bg-gray-50'}"
              on:click={() => {
                newEvent.event_type = "series";
              }}>Serie</button
            >
          </div>
          <!-- Sektionen Nav -->
          <nav
            class="mt-4 flex flex-wrap gap-2 text-xs"
            aria-label="Formularbereiche"
          >
            {#each ["basis", "zeit", "ort", "erweitert"] as sec}
              <button
                type="button"
                class="px-3 py-1 rounded-full border {activeSection === sec
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'bg-white text-gray-600 hover:border-gray-400'}"
                on:click={() => switchSection(sec as any)}
              >
                {sec === "basis"
                  ? "Basis"
                  : sec === "zeit"
                    ? "Zeit"
                    : sec === "ort"
                      ? "Ort"
                      : "Erweitert"}
              </button>
            {/each}
          </nav>
          {#if formErrors.length}
            <div
              class="mt-4 rounded-md border border-red-300 bg-red-50 p-3 text-red-700 text-xs space-y-1"
              role="alert"
              aria-live="assertive"
            >
              <strong class="block font-semibold text-red-800"
                >Bitte korrigieren:</strong
              >
              <ul class="list-disc ml-5 space-y-0.5">
                {#each formErrors as err}<li>{err}</li>{/each}
              </ul>
            </div>
          {/if}
        </div>

        <!-- Body -->
        <form
          on:submit|preventDefault={enhancedHandleSave}
          class="flex-1 overflow-y-auto px-6 py-6 space-y-10"
        >
          <!-- BASIS -->
          {#if activeSection === "basis"}
            <fieldset class="space-y-6" aria-labelledby="basis-heading">
              <legend id="basis-heading" class="sr-only"
                >Basisinformationen</legend
              >
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <label
                    for="event-title"
                    class="block text-sm font-medium text-gray-700"
                    >Titel *</label
                  >
                  <input
                    id="event-title"
                    type="text"
                    bind:value={newEvent.title}
                    required
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    placeholder="z.B. Offener Hypnose-Stammtisch"
                  />
                </div>
                <div class="md:col-span-2">
                  <label
                    for="event-description"
                    class="block text-sm font-medium text-gray-700"
                    >Kurzbeschreibung</label
                  >
                  <textarea
                    id="event-description"
                    bind:value={newEvent.description}
                    rows="4"
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    placeholder="Worum geht es? Ziel, Ablauf, Besonderheiten..."
                  ></textarea>
                  <p class="text-[11px] text-gray-400 mt-1">
                    Markdown / Rich-Content später möglich.
                  </p>
                </div>
                <div>
                  <label
                    for="event-category"
                    class="block text-sm font-medium text-gray-700"
                    >Kategorie</label
                  >
                  <select
                    id="event-category"
                    bind:value={newEvent.category}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                  >
                    <option value="stammtisch">Stammtisch</option>
                    <option value="workshop">Workshop</option>
                    <option value="practice">Praxis</option>
                    <option value="lecture">Vortrag</option>
                    <option value="special">Special</option>
                  </select>
                </div>
                <div>
                  <label
                    for="event-difficulty"
                    class="block text-sm font-medium text-gray-700">Level</label
                  >
                  <select
                    id="event-difficulty"
                    bind:value={newEvent.difficulty_level}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                  >
                    <option value="all">Alle</option>
                    <option value="beginner">Anfänger</option>
                    <option value="intermediate">Fortgeschritten</option>
                    <option value="advanced">Experte</option>
                  </select>
                </div>
                <div class="md:col-span-2">
                  <label
                    for="tag-input"
                    class="block text-sm font-medium text-gray-700">Tags</label
                  >
                  <div class="mt-1 flex flex-wrap gap-2">
                    {#each newEvent.tags as t}
                      <span
                        class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 text-xs flex items-center gap-1"
                        >{t}<button
                          type="button"
                          class="hover:text-red-600"
                          aria-label="Tag entfernen"
                          on:click={() => removeTag(t)}>×</button
                        ></span
                      >
                    {/each}
                    <input
                      id="tag-input"
                      type="text"
                      bind:value={tagInput}
                      class="flex-1 min-w-[120px] border rounded px-2 py-1 text-xs focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Tag & Enter"
                      on:keydown={(e) => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          addTag();
                        }
                      }}
                    />
                    <button
                      type="button"
                      class="text-xs border px-2 py-1 rounded hover:bg-gray-50"
                      on:click={addTag}>Hinzufügen</button
                    >
                  </div>
                </div>
              </div>
            </fieldset>
          {/if}

          <!-- ZEIT -->
          {#if activeSection === "zeit"}
            <fieldset class="space-y-6" aria-labelledby="zeit-heading">
              <legend id="zeit-heading" class="sr-only"
                >Zeit & Wiederholung</legend
              >
              {#if newEvent.event_type === "single"}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label
                      for="start-datetime"
                      class="block text-sm font-medium text-gray-700"
                      >Start *</label
                    >
                    <input
                      id="start-datetime"
                      type="datetime-local"
                      bind:value={newEvent.start_datetime}
                      required
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                  <div>
                    <label
                      for="end-datetime"
                      class="block text-sm font-medium text-gray-700"
                      >Ende *</label
                    >
                    <input
                      id="end-datetime"
                      type="datetime-local"
                      bind:value={newEvent.end_datetime}
                      required
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                </div>
              {:else}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label
                      for="start-date"
                      class="block text-sm font-medium text-gray-700"
                      >Startdatum *</label
                    >
                    <input
                      id="start-date"
                      type="date"
                      bind:value={newEvent.start_date}
                      required
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                  <div>
                    <label
                      for="end-date"
                      class="block text-sm font-medium text-gray-700"
                      >Enddatum (optional)</label
                    >
                    <input
                      id="end-date"
                      type="date"
                      bind:value={newEvent.end_date}
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                  <div>
                    <label
                      for="series-start-time"
                      class="block text-sm font-medium text-gray-700"
                      >Serien-Start (HH:MM)</label
                    >
                    <input
                      id="series-start-time"
                      type="time"
                      bind:value={newEvent.start_time}
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                  <div>
                    <label
                      for="series-end-time"
                      class="block text-sm font-medium text-gray-700"
                      >Serien-Ende (HH:MM)</label
                    >
                    <input
                      id="series-end-time"
                      type="time"
                      bind:value={newEvent.end_time}
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label
                      for="rrule-builder"
                      class="block text-sm font-medium text-gray-700"
                      >Wiederholung *</label
                    >
                    <RecurrenceBuilder
                      value={newEvent.rrule}
                      startDate={newEvent.start_date}
                      on:change={(e) => (newEvent.rrule = e.detail.value)}
                    />
                    {#if !newEvent.rrule}
                      <p class="text-[11px] text-red-600 mt-1">
                        RRULE erforderlich.
                      </p>
                    {/if}
                  </div>
                </div>
              {/if}
            </fieldset>
          {/if}

          <!-- ORT -->
          {#if activeSection === "ort"}
            <fieldset class="space-y-6" aria-labelledby="ort-heading">
              <legend id="ort-heading" class="sr-only">Ort & Teilnahme</legend>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label
                    for="location-type"
                    class="block text-sm font-medium text-gray-700"
                    >Ort-Typ</label
                  >
                  <select
                    id="location-type"
                    bind:value={newEvent.location_type}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                  >
                    <option value="physical">Vor Ort</option>
                    <option value="online">Online</option>
                    <option value="hybrid">Hybrid</option>
                  </select>
                </div>
                <div>
                  <label
                    for="max-participants"
                    class="block text-sm font-medium text-gray-700"
                    >Max. Teilnehmende</label
                  >
                  <input
                    id="max-participants"
                    type="number"
                    min="1"
                    bind:value={newEvent.max_participants}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                  />
                </div>
                <div>
                  <label
                    for="event-status"
                    class="block text-sm font-medium text-gray-700"
                    >Status</label
                  >
                  <select
                    id="event-status"
                    bind:value={newEvent.status}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                  >
                    <option value="draft">Entwurf</option>
                    <option value="published">Veröffentlicht</option>
                    <option value="cancelled">Abgesagt</option>
                  </select>
                </div>
              </div>
              {#if newEvent.location_type === "physical" || newEvent.location_type === "hybrid"}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label
                      for="location-name"
                      class="block text-sm font-medium text-gray-700"
                      >Ort Name</label
                    >
                    <input
                      id="location-name"
                      type="text"
                      bind:value={newEvent.location_name}
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                  <div>
                    <label
                      for="location-address"
                      class="block text-sm font-medium text-gray-700"
                      >Adresse</label
                    >
                    <input
                      id="location-address"
                      type="text"
                      bind:value={newEvent.location_address}
                      class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    />
                  </div>
                </div>
              {/if}
              {#if newEvent.location_type === "online" || newEvent.location_type === "hybrid"}
                <div>
                  <label
                    for="location-url"
                    class="block text-sm font-medium text-gray-700"
                    >Online-URL</label
                  >
                  <input
                    id="location-url"
                    type="url"
                    bind:value={newEvent.location_url}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                    placeholder="https://…"
                  />
                </div>
              {/if}
              <div class="flex flex-wrap gap-6 items-center">
                <label class="flex items-center gap-2 text-sm text-gray-700"
                  ><input
                    type="checkbox"
                    bind:checked={newEvent.is_featured}
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  /> Featured</label
                >
                <label class="flex items-center gap-2 text-sm text-gray-700"
                  ><input
                    type="checkbox"
                    bind:checked={newEvent.requires_registration}
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  /> Anmeldung erforderlich</label
                >
              </div>
            </fieldset>
          {/if}

          <!-- ERWEITERT -->
          {#if activeSection === "erweitert"}
            <fieldset class="space-y-6" aria-labelledby="adv-heading">
              <legend id="adv-heading" class="sr-only"
                >Erweiterte Angaben</legend
              >
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label
                    for="organizer-name"
                    class="block text-sm font-medium text-gray-700"
                    >Organisator Name</label
                  >
                  <input
                    id="organizer-name"
                    type="text"
                    bind:value={newEvent.organizer_name}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                  />
                </div>
                <div>
                  <label
                    for="organizer-email"
                    class="block text-sm font-medium text-gray-700"
                    >Organisator E-Mail</label
                  >
                  <input
                    id="organizer-email"
                    type="email"
                    bind:value={newEvent.organizer_email}
                    class="mt-1 w-full rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-4 py-2.5 text-sm"
                  />
                </div>
              </div>
              <div
                class="rounded-md border border-gray-200 bg-gray-50 p-4 text-xs text-gray-500 leading-relaxed"
              >
                <p>
                  <strong>Hinweis:</strong> Weitere Felder (Inhalte, Sicherheits-/Anforderungstexte)
                  können in einem späteren Editor ergänzt werden.
                </p>
              </div>
            </fieldset>
          {/if}
        </form>

        <!-- Footer -->
        <div
          class="sticky bottom-0 bg-white border-t px-6 py-4 flex justify-between items-center rounded-b-lg gap-4"
        >
          <div class="text-[11px] text-gray-400">
            {newEvent.event_type === "single"
              ? "Einzeltermin"
              : "Serien-Termin"} • Änderungen werden erst nach Speichern wirksam.
          </div>
          <div class="flex gap-3">
            <button
              type="button"
              on:click={() => {
                showCreateModal = false;
                showEditModal = false;
              }}
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
              >Abbrechen</button
            >
            <button
              type="button"
              on:click={enhancedHandleSave}
              class="px-5 py-2 rounded-md text-sm font-medium text-white shadow bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >{editingItem ? "Speichern" : "Erstellen"}</button
            >
          </div>
        </div>
      </div>
    </div>
  {/if}

  <!-- Delete Confirmation Modal -->
  {#if deleteConfirm}
    <div
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
      >
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900">
            Veranstaltung löschen
          </h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              Sind Sie sicher, dass Sie "{deleteConfirm.title}" löschen möchten?
              Diese Aktion kann nicht rückgängig gemacht werden.
            </p>
          </div>
          <div class="flex justify-center space-x-3 px-4 py-3">
            <button
              on:click={() => (deleteConfirm = null)}
              class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md hover:bg-gray-400 transition-colors"
            >
              Abbrechen
            </button>
            <button
              on:click={() => handleDelete(deleteConfirm)}
              class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md hover:bg-red-700 transition-colors"
            >
              Löschen
            </button>
          </div>
        </div>
      </div>
    </div>
  {/if}
</AdminLayout>
