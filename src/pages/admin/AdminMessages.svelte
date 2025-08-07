<script lang="ts">
  import { onMount } from "svelte";
  import AdminLayout from "../../components/admin/AdminLayout.svelte";
  import { AdminAPI } from "../../stores/admin";

  let messages: any[] = [];
  let loading = true;
  let error = "";
  let selectedMessage: any = null;
  let deleteConfirm: any = null;
  let currentPage = 1;
  let totalPages = 1;
  let filters = {
    status: "",
    subject: "",
  };
  let stats: any = {};

  onMount(async () => {
    await loadMessages();
    await loadStats();
  });

  async function loadMessages() {
    try {
      loading = true;
      const params = {
        page: currentPage,
        limit: 20,
        ...Object.fromEntries(
          Object.entries(filters).filter(([_, v]) => v !== ""),
        ),
      };

      const result = await AdminAPI.getMessages(params);

      if (result.success) {
        messages = result.data.messages || [];
        totalPages = result.data.pagination?.pages || 1;
      } else {
        error = result.message || "Fehler beim Laden der Nachrichten";
      }
    } catch (e) {
      error = "Netzwerkfehler beim Laden der Nachrichten";
    } finally {
      loading = false;
    }
  }

  async function loadStats() {
    try {
      const result = await AdminAPI.getMessageStats();
      if (result.success) {
        stats = result.data;
      }
    } catch (e) {
      console.error("Failed to load stats:", e);
    }
  }

  async function updateMessageStatus(messageId: number, status: string) {
    try {
      const result = await AdminAPI.updateMessageStatus(messageId, status);

      if (result.success) {
        await loadMessages();
        await loadStats();
      } else {
        error = result.message || "Fehler beim Aktualisieren des Status";
      }
    } catch (e) {
      error = "Netzwerkfehler beim Aktualisieren des Status";
    }
  }

  async function markAsResponded(messageId: number) {
    try {
      const result = await AdminAPI.markMessageResponded(messageId);

      if (result.success) {
        await loadMessages();
        await loadStats();
      } else {
        error = result.message || "Fehler beim Markieren als beantwortet";
      }
    } catch (e) {
      error = "Netzwerkfehler beim Markieren als beantwortet";
    }
  }

  async function deleteMessage(messageId: number) {
    try {
      const result = await AdminAPI.deleteMessage(messageId);

      if (result.success) {
        deleteConfirm = null;
        await loadMessages();
        await loadStats();
        if (selectedMessage?.id === messageId) {
          selectedMessage = null;
        }
      } else {
        error = result.message || "Fehler beim Löschen der Nachricht";
      }
    } catch (e) {
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
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Nachrichten</h1>
      <p class="mt-1 text-sm text-gray-600">
        Verwalten Sie Kontaktformular-Nachrichten
      </p>
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

    <!-- Statistics -->
    {#if stats.status_counts}
      <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-2xl font-bold text-blue-600">
            {stats.unread_count || 0}
          </div>
          <div class="text-sm text-gray-600">Ungelesen</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-2xl font-bold text-yellow-600">
            {stats.status_counts.find((s: any) => s.status === "in_progress")
              ?.count || 0}
          </div>
          <div class="text-sm text-gray-600">In Bearbeitung</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-2xl font-bold text-green-600">
            {stats.status_counts.find((s: any) => s.status === "resolved")
              ?.count || 0}
          </div>
          <div class="text-sm text-gray-600">Erledigt</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-2xl font-bold text-gray-600">
            {stats.recent_count || 0}
          </div>
          <div class="text-sm text-gray-600">Letzte 7 Tage</div>
        </div>
      </div>
    {/if}

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Messages List -->
      <div class="lg:w-1/2">
        <!-- Filters -->
        <div class="mb-4 bg-white p-4 rounded-lg shadow">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label
                for="status-filter"
                class="block text-sm font-medium text-gray-700 mb-2"
              >
                Status
              </label>
              <select
                id="status-filter"
                bind:value={filters.status}
                on:change={handleFilterChange}
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
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
                class="block text-sm font-medium text-gray-700 mb-2"
              >
                Betreff
              </label>
              <select
                id="subject-filter"
                bind:value={filters.subject}
                on:change={handleFilterChange}
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
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
              class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
            ></div>
          </div>
        {:else}
          <div class="bg-white shadow overflow-hidden sm:rounded-md">
            {#if messages.length === 0}
              <div class="p-6 text-center text-gray-500">
                Keine Nachrichten gefunden
              </div>
            {:else}
              <ul class="divide-y divide-gray-200">
                {#each messages as message}
                  <li class="px-6 py-4">
                    <button
                      class="w-full text-left hover:bg-gray-50 {selectedMessage?.id ===
                      message.id
                        ? 'bg-blue-50'
                        : ''}"
                      on:click={() => (selectedMessage = message)}
                    >
                      <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                          <div class="flex items-center space-x-3">
                            <p
                              class="text-sm font-medium text-gray-900 truncate"
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
                            <p class="text-sm text-gray-600">
                              {getSubjectLabel(message.subject)}
                            </p>
                            <p class="text-sm text-gray-500 mt-1 truncate">
                              {message.message.substring(0, 100)}...
                            </p>
                          </div>
                          <div class="mt-1 text-xs text-gray-500">
                            {message.submitted_at_formatted}
                          </div>
                        </div>
                      </div>
                    </button>
                  </li>
                {/each}
              </ul>

              <!-- Pagination -->
              {#if totalPages > 1}
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                  <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                      Seite {currentPage} von {totalPages}
                    </div>
                    <div class="flex space-x-1">
                      <button
                        on:click={() => changePage(currentPage - 1)}
                        disabled={currentPage === 1}
                        class="px-3 py-1 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Zurück
                      </button>
                      <button
                        on:click={() => changePage(currentPage + 1)}
                        disabled={currentPage === totalPages}
                        class="px-3 py-1 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
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
          <div class="bg-white shadow rounded-lg p-6">
            <div class="border-b border-gray-200 pb-4 mb-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-medium text-gray-900">
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

              <div class="text-sm text-gray-600 space-y-1">
                <p><strong>E-Mail:</strong> {selectedMessage.email}</p>
                <p>
                  <strong>Betreff:</strong>
                  {getSubjectLabel(selectedMessage.subject)}
                </p>
                <p>
                  <strong>Eingegangen:</strong>
                  {selectedMessage.submitted_at_formatted}
                </p>
                {#if selectedMessage.processed_at_formatted}
                  <p>
                    <strong>Bearbeitet:</strong>
                    {selectedMessage.processed_at_formatted}
                  </p>
                {/if}
                {#if selectedMessage.assigned_to}
                  <p>
                    <strong>Zugewiesen an:</strong>
                    {selectedMessage.assigned_to}
                  </p>
                {/if}
              </div>
            </div>

            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-900 mb-2">Nachricht</h4>
              <div
                class="bg-gray-50 rounded-lg p-4 text-sm text-gray-800 whitespace-pre-wrap"
              >
                {selectedMessage.message}
              </div>
            </div>

            <!-- Actions -->
            <div class="space-y-4">
              <div>
                <h4 class="block text-sm font-medium text-gray-700 mb-2">
                  Status ändern
                </h4>
                <div class="flex space-x-2">
                  <button
                    on:click={() =>
                      updateMessageStatus(selectedMessage.id, "new")}
                    class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded hover:bg-blue-200"
                    disabled={selectedMessage.status === "new"}
                  >
                    Neu
                  </button>
                  <button
                    on:click={() =>
                      updateMessageStatus(selectedMessage.id, "in_progress")}
                    class="px-3 py-1 text-sm bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200"
                    disabled={selectedMessage.status === "in_progress"}
                  >
                    In Bearbeitung
                  </button>
                  <button
                    on:click={() =>
                      updateMessageStatus(selectedMessage.id, "resolved")}
                    class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded hover:bg-green-200"
                    disabled={selectedMessage.status === "resolved"}
                  >
                    Erledigt
                  </button>
                  <button
                    on:click={() =>
                      updateMessageStatus(selectedMessage.id, "spam")}
                    class="px-3 py-1 text-sm bg-red-100 text-red-800 rounded hover:bg-red-200"
                    disabled={selectedMessage.status === "spam"}
                  >
                    Spam
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

                <a
                  href="mailto:{selectedMessage.email}?subject=Re: {getSubjectLabel(
                    selectedMessage.subject,
                  )}"
                  class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700"
                >
                  E-Mail antworten
                </a>
              </div>
            </div>
          </div>
        {:else}
          <div class="bg-white shadow rounded-lg p-6">
            <div class="text-center text-gray-500">
              <svg
                class="mx-auto h-12 w-12 text-gray-400"
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
    <div
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
      >
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900">Nachricht löschen</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              Sind Sie sicher, dass Sie diese Nachricht von {deleteConfirm.name}
              löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
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
              on:click={() => deleteMessage(deleteConfirm.id)}
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
