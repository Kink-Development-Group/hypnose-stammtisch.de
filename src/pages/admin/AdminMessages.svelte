<script lang="ts">
  import { onDestroy, onMount } from "svelte";
  import AdminLayout from "../../components/admin/AdminLayout.svelte";
  import AdminMessageNotes from "../../components/admin/AdminMessageNotes.svelte";
  import AdminMessageResponse from "../../components/admin/AdminMessageResponse.svelte";
  import Portal from "../../components/ui/Portal.svelte";
  import { AdminAPI } from "../../stores/admin";
  import {
    adminAutoUpdate,
    adminEventBus,
    adminLoading,
    adminMessages,
    adminStats,
  } from "../../stores/adminData";

  let error = "";
  let selectedMessage: any = null;
  let deleteConfirm: any = null;
  let currentPage = 1;
  let totalPages = 1;
  let showNotes = false;
  let showEmailComposer = false;
  let filters = {
    status: "",
    subject: "",
  };

  // Reactive subscriptions
  $: messages = $adminMessages;
  $: stats = $adminStats;
  $: loading = $adminLoading;

  // Automatische Aktualisierung der selectedMessage wenn sich die Nachrichtenliste ändert
  $: if (selectedMessage && messages) {
    const updatedMessage = messages.find((m) => m.id === selectedMessage.id);
    if (updatedMessage && updatedMessage.status !== selectedMessage.status) {
      selectedMessage = updatedMessage;
    }
  }

  onMount(() => {
    // Async initialization
    (async () => {
      await loadMessages();
      await loadStats();

      // Starte Auto-Update
      adminAutoUpdate.start(30000); // 30 Sekunden
    })();

    // Event Bus Listener für automatische Updates
    const unsubscribeEventBus = adminEventBus.subscribe((event) => {
      if (event?.data?.autoRefresh || event?.data?.manualRefresh) {
        loadMessages();
        loadStats();
      }
    });

    return () => {
      unsubscribeEventBus();
    };
  });

  onDestroy(() => {
    // Stoppe Auto-Update beim Verlassen der Komponente
    adminAutoUpdate.stop();
  });

  async function loadMessages() {
    adminLoading.set(true);
    try {
      const params = {
        page: currentPage,
        limit: 20,
        ...Object.fromEntries(
          Object.entries(filters).filter(([_, v]) => v !== ""),
        ),
      };

      const result = await AdminAPI.getMessages(params);

      if (result.success) {
        totalPages = result.data.pagination?.pages || 1;
      } else {
        error = result.message || "Fehler beim Laden der Nachrichten";
      }
    } catch {
      error = "Netzwerkfehler beim Laden der Nachrichten";
    } finally {
      adminLoading.set(false);
    }
  }

  async function loadStats() {
    try {
      const result = await AdminAPI.getMessageStats();
      if (!result.success) {
        console.error("Fehler beim Laden der Statistiken:", result.message);
      }
    } catch {
      console.error("Netzwerkfehler beim Laden der Statistiken");
    }
  }

  async function updateMessageStatus(messageId: number, status: string) {
    try {
      // Sofortige lokale Aktualisierung der selectedMessage
      if (selectedMessage && selectedMessage.id === messageId) {
        selectedMessage = {
          ...selectedMessage,
          status,
          updated_at: new Date().toISOString(),
        };
      }

      const result = await AdminAPI.updateMessageStatus(messageId, status);
      // Optimistische Updates werden automatisch durch AdminAPI gehandhabt
      if (!result.success) {
        error = result.message || "Fehler beim Aktualisieren des Status";
        // Bei Fehler die ursprünglichen Daten zurücksetzen
        await loadMessages();
      }
    } catch {
      error = "Netzwerkfehler beim Aktualisieren des Status";
      // Bei Fehler die ursprünglichen Daten zurücksetzen
      await loadMessages();
    }
  }

  async function markAsResponded(messageId: number) {
    try {
      // Sofortige lokale Aktualisierung der selectedMessage
      if (selectedMessage && selectedMessage.id === messageId) {
        selectedMessage = {
          ...selectedMessage,
          status: "responded",
          updated_at: new Date().toISOString(),
        };
      }

      const result = await AdminAPI.markMessageResponded(messageId);
      // Optimistische Updates werden automatisch durch AdminAPI gehandhabt
      if (!result.success) {
        error = result.message || "Fehler beim Markieren als beantwortet";
        // Bei Fehler die ursprünglichen Daten zurücksetzen
        await loadMessages();
      }
    } catch {
      error = "Netzwerkfehler beim Markieren als beantwortet";
      // Bei Fehler die ursprünglichen Daten zurücksetzen
      await loadMessages();
    }
  }

  async function deleteMessage(messageId: number) {
    try {
      const result = await AdminAPI.deleteMessage(messageId);

      if (result.success) {
        deleteConfirm = null;
        // Reset selected message if it was deleted
        if (selectedMessage?.id === messageId) {
          selectedMessage = null;
        }
      } else {
        error = result.message || "Fehler beim Löschen der Nachricht";
      }
    } catch {
      error = "Netzwerkfehler beim Löschen der Nachricht";
    }
  }

  function getStatusBadge(status: string): string {
    const statusClasses: { [key: string]: string } = {
      new: "bg-blue-100 text-blue-800",
      in_progress: "bg-yellow-100 text-yellow-800",
      resolved: "bg-green-100 text-green-800",
      spam: "bg-red-100 text-red-800",
    };
    return statusClasses[status] || "bg-gray-100 text-gray-800";
  }

  function getStatusLabel(status: string): string {
    const statusLabels: { [key: string]: string } = {
      new: "Neu",
      in_progress: "In Bearbeitung",
      resolved: "Erledigt",
      spam: "Spam",
    };
    return statusLabels[status] || status;
  }

  function getSubjectLabel(subject: string): string {
    const subjectLabels: { [key: string]: string } = {
      teilnahme: "Teilnahme",
      organisation: "Organisation",
      feedback: "Feedback",
      partnership: "Partnerschaft",
      support: "Support",
      conduct: "Verhaltenskodex",
      other: "Sonstiges",
    };
    return subjectLabels[subject] || subject;
  }

  function handleFilterChange() {
    currentPage = 1;
    loadMessages();
  }

  function changePage(page: number) {
    if (page >= 1 && page <= totalPages) {
      currentPage = page;
      loadMessages();
    }
  }
</script>

<svelte:head>
  <title>Nachrichten verwalten - Admin</title>
</svelte:head>

<AdminLayout>
  <div class="max-w-7xl mx-auto">
    <header
      class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <h1
          class="text-3xl font-semibold tracking-tight text-slate-800 dark:text-smoke-50"
        >
          Nachrichten
        </h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-smoke-400">
          Verfolgen und beantworten Sie eingehende Kontaktanfragen.
        </p>
      </div>
      <button
        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-charcoal-600 px-3 py-2 text-sm font-semibold text-slate-600 dark:text-smoke-300 transition hover:border-blue-200 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30"
        on:click={loadMessages}
        title="Nachrichten neu laden"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
          <path
            d="M4 4v4h4M20 20v-4h-4"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M5.64 18.36A9 9 0 1118.36 5.64 9 9 0 015.64 18.36z"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        Aktualisieren
      </button>
    </header>

    {#if error}
      <div
        class="rounded-xl border border-red-200 dark:border-red-700 bg-red-50/90 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200 shadow-sm"
        role="alert"
      >
        <div class="flex items-start gap-2">
          <svg class="mt-0.5 h-5 w-5 text-red-500" viewBox="0 0 24 24">
            <path
              fill="currentColor"
              d="M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2zm1 14h-2v-2h2zm0-4h-2V7h2z"
            />
          </svg>
          <div class="flex-1">
            <p class="font-medium">{error}</p>
            <button
              on:click={() => (error = "")}
              class="mt-2 inline-flex items-center text-xs font-semibold text-red-700 underline-offset-2 hover:underline"
            >
              Ausblenden
            </button>
          </div>
        </div>
      </div>
    {/if}

    <!-- Statistics -->
    {#if stats.status_counts}
      <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div
          class="bg-white dark:bg-charcoal-800 rounded-lg shadow dark:shadow-charcoal-900/30 p-6 border border-transparent dark:border-charcoal-700"
        >
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {stats.unread_count || 0}
          </div>
          <div class="text-sm text-slate-600 dark:text-smoke-400">
            Ungelesen
          </div>
        </div>
        <div
          class="bg-white dark:bg-charcoal-800 rounded-lg shadow dark:shadow-charcoal-900/30 p-6 border border-transparent dark:border-charcoal-700"
        >
          <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
            {stats.status_counts.find((s: any) => s.status === "in_progress")
              ?.count || 0}
          </div>
          <div class="text-sm text-slate-600 dark:text-smoke-400">
            In Bearbeitung
          </div>
        </div>
        <div
          class="bg-white dark:bg-charcoal-800 rounded-lg shadow dark:shadow-charcoal-900/30 p-6 border border-transparent dark:border-charcoal-700"
        >
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">
            {stats.status_counts.find((s: any) => s.status === "resolved")
              ?.count || 0}
          </div>
          <div class="text-sm text-slate-600 dark:text-smoke-400">Erledigt</div>
        </div>
        <div
          class="bg-white dark:bg-charcoal-800 rounded-lg shadow dark:shadow-charcoal-900/30 p-6 border border-transparent dark:border-charcoal-700"
        >
          <div class="text-2xl font-bold text-slate-600 dark:text-smoke-300">
            {stats.status_counts?.reduce((sum, item) => sum + item.count, 0) ||
              0}
          </div>
          <div class="text-sm text-slate-600 dark:text-smoke-400">
            Letzte 7 Tage
          </div>
        </div>
      </div>
    {/if}

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Messages List -->
      <div class="lg:w-1/2">
        <!-- Filters -->
        <div
          class="mb-4 bg-white dark:bg-charcoal-800 p-4 rounded-lg shadow dark:shadow-charcoal-900/30 border border-transparent dark:border-charcoal-700"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label
                for="status-filter"
                class="block text-sm font-medium text-slate-700 dark:text-smoke-300 mb-2"
              >
                Status
              </label>
              <select
                id="status-filter"
                bind:value={filters.status}
                on:change={handleFilterChange}
                class="w-full px-3 py-2 border border-slate-300 dark:border-charcoal-600 bg-white dark:bg-charcoal-700 text-slate-900 dark:text-smoke-100 rounded-md focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Alle Status</option>
                <option value="new">Neu</option>
                <option value="in_progress">In Bearbeitung</option>
                <option value="resolved">Erledigt</option>
                <option value="spam">Spam</option>
              </select>
            </div>

            <div>
              <label
                for="subject-filter"
                class="block text-sm font-medium text-slate-700 dark:text-smoke-300 mb-2"
              >
                Betreff
              </label>
              <select
                id="subject-filter"
                bind:value={filters.subject}
                on:change={handleFilterChange}
                class="w-full px-3 py-2 border border-slate-300 dark:border-charcoal-600 bg-white dark:bg-charcoal-700 text-slate-900 dark:text-smoke-100 rounded-md focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Alle Betreffe</option>
                <option value="teilnahme">Teilnahme</option>
                <option value="organisation">Organisation</option>
                <option value="feedback">Feedback</option>
                <option value="partnership">Partnerschaft</option>
                <option value="support">Support</option>
                <option value="conduct">Verhaltenskodex</option>
                <option value="other">Sonstiges</option>
              </select>
            </div>
          </div>
        </div>

        {#if loading}
          <div class="flex justify-center py-12">
            <div
              class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 dark:border-blue-400"
            ></div>
          </div>
        {:else}
          <div
            class="bg-white dark:bg-charcoal-800 shadow dark:shadow-charcoal-900/30 overflow-hidden sm:rounded-md border border-transparent dark:border-charcoal-700"
          >
            {#if messages.length === 0}
              <div class="p-6 text-center text-slate-600 dark:text-smoke-400">
                Keine Nachrichten gefunden
              </div>
            {:else}
              <ul class="divide-y divide-gray-200 dark:divide-charcoal-700">
                {#each messages as message (message.id)}
                  <li class="px-6 py-4">
                    <button
                      class="w-full text-left hover:bg-slate-50 {selectedMessage?.id ===
                      message.id
                        ? 'bg-blue-50'
                        : ''}"
                      on:click={() => (selectedMessage = message)}
                    >
                      <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                          <div class="flex items-center space-x-3">
                            <p
                              class="text-sm font-medium text-slate-900 truncate"
                            >
                              {message.name}
                            </p>
                            <span
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getStatusBadge(
                                message.status,
                              )}"
                            >
                              {getStatusLabel(message.status)}
                            </span>
                            {#if message.response_sent}
                              <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                              >
                                Beantwortet
                              </span>
                            {/if}
                          </div>
                          <div class="mt-1">
                            <p class="text-sm text-slate-700">
                              {getSubjectLabel(message.subject)}
                            </p>
                            <p class="text-sm text-slate-600 mt-1 truncate">
                              {message.message.substring(0, 100)}...
                            </p>
                          </div>
                          <div class="mt-1 text-xs text-slate-500">
                            {new Date(message.created_at).toLocaleString()}
                          </div>
                        </div>
                      </div>
                    </button>
                  </li>
                {/each}
              </ul>

              <!-- Pagination -->
              {#if totalPages > 1}
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200">
                  <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-700">
                      Seite {currentPage} von {totalPages}
                    </div>
                    <div class="flex space-x-1">
                      <button
                        on:click={() => changePage(currentPage - 1)}
                        disabled={currentPage === 1}
                        class="px-3 py-1 text-sm border rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Zurück
                      </button>
                      <button
                        on:click={() => changePage(currentPage + 1)}
                        disabled={currentPage === totalPages}
                        class="px-3 py-1 text-sm border rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Weiter
                      </button>
                    </div>
                  </div>
                </div>
              {/if}
            {/if}
          </div>
        {/if}
      </div>

      <!-- Message Detail -->
      <div class="lg:w-1/2">
        {#if selectedMessage}
          <div
            class="bg-white dark:bg-charcoal-800 shadow dark:shadow-charcoal-900/30 rounded-lg border border-transparent dark:border-charcoal-700"
          >
            <!-- Message Header -->
            <div
              class="border-b border-slate-200 dark:border-charcoal-700 px-6 py-4"
            >
              <div class="flex items-center justify-between mb-2">
                <h3
                  class="text-lg font-medium text-slate-900 dark:text-smoke-50"
                >
                  {selectedMessage.name}
                </h3>
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getStatusBadge(
                    selectedMessage.status,
                  )}"
                >
                  {getStatusLabel(selectedMessage.status)}
                </span>
              </div>

              <div class="text-sm text-slate-600 dark:text-smoke-400 space-y-1">
                <p><strong>E-Mail:</strong> {selectedMessage.email}</p>
                <p>
                  <strong>Betreff:</strong>
                  {getSubjectLabel(selectedMessage.subject)}
                </p>
                <p>
                  <strong>Eingegangen:</strong>
                  {new Date(
                    selectedMessage.created_at || selectedMessage.createdAt,
                  ).toLocaleString()}
                </p>
                {#if selectedMessage.processedAt || selectedMessage.processed_at}
                  <p>
                    <strong>Bearbeitet:</strong>
                    {new Date(
                      selectedMessage.processedAt ||
                        selectedMessage.processed_at,
                    ).toLocaleString()}
                  </p>
                {/if}
                {#if selectedMessage.assignedTo || selectedMessage.assigned_to}
                  <p>
                    <strong>Zugewiesen an:</strong>
                    {selectedMessage.assignedTo || selectedMessage.assigned_to}
                  </p>
                {/if}
              </div>
            </div>

            <!-- Tab Navigation -->
            <div class="px-6">
              <nav
                class="flex space-x-8 border-b border-slate-200 dark:border-charcoal-700"
                aria-label="Tabs"
              >
                <button
                  on:click={() => {
                    showNotes = false;
                    showEmailComposer = false;
                  }}
                  class="py-2 px-1 border-b-2 font-medium text-sm {!showNotes &&
                  !showEmailComposer
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-slate-500 dark:text-smoke-400 hover:text-slate-700 dark:hover:text-smoke-200 hover:border-slate-300 dark:hover:border-charcoal-500'}"
                >
                  Nachricht
                </button>
                <button
                  on:click={() => {
                    showNotes = true;
                    showEmailComposer = false;
                  }}
                  class="py-2 px-1 border-b-2 font-medium text-sm {showNotes &&
                  !showEmailComposer
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-slate-500 dark:text-smoke-400 hover:text-slate-700 dark:hover:text-smoke-200 hover:border-slate-300 dark:hover:border-charcoal-500'}"
                >
                  Notizen
                </button>
                <button
                  on:click={() => {
                    showNotes = false;
                    showEmailComposer = true;
                  }}
                  class="py-2 px-1 border-b-2 font-medium text-sm {showEmailComposer &&
                  !showNotes
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-slate-500 dark:text-smoke-400 hover:text-slate-700 dark:hover:text-smoke-200 hover:border-slate-300 dark:hover:border-charcoal-500'}"
                >
                  E-Mail Antwort
                </button>
              </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
              {#if !showNotes && !showEmailComposer}
                <!-- Original Message Content -->
                <div class="mb-6">
                  <h4
                    class="text-sm font-medium text-slate-900 dark:text-smoke-50 mb-2"
                  >
                    Nachricht
                  </h4>
                  <div
                    class="bg-slate-50 dark:bg-charcoal-700 rounded-lg p-4 text-sm text-slate-800 dark:text-smoke-200 whitespace-pre-wrap"
                  >
                    {selectedMessage.message}
                  </div>
                </div>

                <!-- Actions -->
                <div class="space-y-4">
                  <div>
                    <h4
                      class="block text-sm font-medium text-slate-700 dark:text-smoke-300 mb-2"
                    >
                      Status ändern
                    </h4>
                    <div class="flex space-x-2">
                      <button
                        on:click={() =>
                          updateMessageStatus(selectedMessage.id, "new")}
                        class="px-3 py-1 text-sm rounded transition-colors {selectedMessage.status ===
                        'new'
                          ? 'bg-blue-200 text-blue-900 font-medium'
                          : 'bg-blue-100 text-blue-800 hover:bg-blue-200'}"
                        disabled={selectedMessage.status === "new"}
                      >
                        {selectedMessage.status === "new" ? "✓ " : ""}Neu
                      </button>
                      <button
                        on:click={() =>
                          updateMessageStatus(
                            selectedMessage.id,
                            "in_progress",
                          )}
                        class="px-3 py-1 text-sm rounded transition-colors {selectedMessage.status ===
                        'in_progress'
                          ? 'bg-yellow-200 text-yellow-900 font-medium'
                          : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'}"
                        disabled={selectedMessage.status === "in_progress"}
                      >
                        {selectedMessage.status === "in_progress" ? "✓ " : ""}In
                        Bearbeitung
                      </button>
                      <button
                        on:click={() =>
                          updateMessageStatus(selectedMessage.id, "resolved")}
                        class="px-3 py-1 text-sm rounded transition-colors {selectedMessage.status ===
                        'resolved'
                          ? 'bg-green-200 text-green-900 font-medium'
                          : 'bg-green-100 text-green-800 hover:bg-green-200'}"
                        disabled={selectedMessage.status === "resolved"}
                      >
                        {selectedMessage.status === "resolved"
                          ? "✓ "
                          : ""}Erledigt
                      </button>
                      <button
                        on:click={() =>
                          updateMessageStatus(selectedMessage.id, "spam")}
                        class="px-3 py-1 text-sm rounded transition-colors {selectedMessage.status ===
                        'spam'
                          ? 'bg-red-200 text-red-900 font-medium'
                          : 'bg-red-100 text-red-800 hover:bg-red-200'}"
                        disabled={selectedMessage.status === "spam"}
                      >
                        {selectedMessage.status === "spam" ? "✓ " : ""}Spam
                      </button>
                    </div>
                  </div>

                  <div class="flex space-x-3">
                    {#if !selectedMessage.response_sent}
                      <button
                        on:click={() => markAsResponded(selectedMessage.id)}
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700"
                      >
                        Als beantwortet markieren
                      </button>
                    {/if}

                    <button
                      on:click={() => (deleteConfirm = selectedMessage)}
                      class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700"
                    >
                      Löschen
                    </button>
                  </div>
                </div>
              {:else if showNotes}
                <!-- Notes Tab -->
                <AdminMessageNotes messageId={selectedMessage.id} />
              {:else if showEmailComposer}
                <!-- Email Response Tab -->
                <AdminMessageResponse
                  messageId={selectedMessage.id}
                  contactEmail={selectedMessage.email}
                  contactName={selectedMessage.name}
                  originalSubject={getSubjectLabel(selectedMessage.subject)}
                />
              {/if}
            </div>
          </div>
        {:else}
          <div
            class="bg-white dark:bg-charcoal-800 shadow dark:shadow-charcoal-900/30 rounded-lg p-6 border border-transparent dark:border-charcoal-700"
          >
            <div class="text-center text-slate-500 dark:text-smoke-400">
              <svg
                class="mx-auto h-12 w-12 text-slate-400 dark:text-smoke-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 8h10m0 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m0 0v10a2 2 0 002 2h6a2 2 0 002-2V8m0 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m0 0v10a2 2 0 002 2h6a2 2 0 002-2V8"
                />
              </svg>
              <p class="mt-2 text-sm">
                Wählen Sie eine Nachricht aus der Liste aus, um sie zu lesen und
                zu bearbeiten.
              </p>
            </div>
          </div>
        {/if}
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  {#if deleteConfirm}
    <Portal>
      <div
        class="fixed inset-0 bg-slate-700/50 dark:bg-charcoal-900/80 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999]"
      >
        <div
          class="relative top-20 mx-auto p-5 border dark:border-charcoal-600 w-96 shadow-lg rounded-md bg-white dark:bg-charcoal-800"
        >
          <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-slate-900 dark:text-smoke-50">
              Nachricht löschen
            </h3>
            <div class="mt-2 px-7 py-3">
              <p class="text-sm text-slate-600 dark:text-smoke-300">
                Sind Sie sicher, dass Sie diese Nachricht von {deleteConfirm.name}
                löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
              </p>
            </div>
            <div class="flex justify-center space-x-3 px-4 py-3">
              <button
                on:click={() => (deleteConfirm = null)}
                class="px-4 py-2 bg-slate-200 dark:bg-charcoal-600 text-slate-800 dark:text-smoke-200 text-base font-medium rounded-md hover:bg-slate-300 dark:hover:bg-charcoal-500 transition-colors"
              >
                Abbrechen
              </button>
              <button
                on:click={() => deleteMessage(deleteConfirm.id)}
                class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-base font-medium rounded-md hover:bg-red-700 dark:hover:bg-red-600 transition-colors"
              >
                Löschen
              </button>
            </div>
          </div>
        </div>
      </div>
    </Portal>
  {/if}
</AdminLayout>
