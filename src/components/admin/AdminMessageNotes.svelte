<script lang="ts">
  import { onMount } from "svelte";
  import { AdminAPI } from "../../stores/admin";

  export let messageId: number;

  interface Note {
    id: number;
    message_id: number;
    note: string;
    note_type?: string;
    is_internal: boolean;
    created_at: string;
    updated_at: string;
  }

  let notes: Note[] = [];
  let newNote = "";
  let editingNote: Note | null = null;
  let loading = false;
  let noteType = "processing"; // 'processing', 'communication', 'general'

  onMount(async () => {
    await loadNotes();
  });

  async function loadNotes() {
    loading = true;
    try {
      const result = await AdminAPI.getMessageNotes(messageId);
      if (result.success) {
        notes = result.data;
      }
    } catch (error) {
      console.error("Failed to load notes:", error);
    } finally {
      loading = false;
    }
  }

  async function addNote() {
    if (!newNote.trim()) return;

    const result = await AdminAPI.addMessageNote(
      messageId,
      newNote.trim(),
      noteType,
    );
    if (result.success) {
      newNote = "";
      await loadNotes();
    }
  }

  async function startEdit(note: Note) {
    editingNote = { ...note };
  }

  async function saveEdit() {
    if (!editingNote || !editingNote.note.trim()) return;

    const result = await AdminAPI.updateMessageNote(
      messageId,
      editingNote.id,
      editingNote.note.trim(),
    );
    if (result.success) {
      editingNote = null;
      await loadNotes();
    }
  }

  function cancelEdit() {
    editingNote = null;
  }

  async function deleteNote(noteId: number) {
    if (!confirm("Sind Sie sicher, dass Sie diese Notiz löschen möchten?"))
      return;

    const result = await AdminAPI.deleteMessageNote(messageId, noteId);
    if (result.success) {
      await loadNotes();
    }
  }

  function formatDate(dateString: string) {
    return new Date(dateString).toLocaleString("de-DE");
  }

  function getNoteTypeBadgeClass(noteType: string): string {
    switch (noteType) {
      case "processing":
        return "bg-blue-100 text-blue-800";
      case "communication":
        return "bg-green-100 text-green-800";
      case "general":
        return "bg-gray-100 text-gray-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  }

  function getNoteTypeLabel(noteType: string): string {
    switch (noteType) {
      case "processing":
        return "Bearbeitung";
      case "communication":
        return "Kommunikation";
      case "general":
        return "Allgemein";
      default:
        return "Allgemein";
    }
  }
</script>

<div class="admin-message-notes">
  <h3 class="text-lg font-semibold mb-4">Notizen</h3>

  <!-- Add new note -->
  <div class="mb-6 p-4 bg-gray-50 rounded-lg">
    <div class="mb-3">
      <label
        for="note-type-select"
        class="block text-sm font-medium text-gray-700 mb-2"
      >
        Notiz-Kategorie
      </label>
      <select
        id="note-type-select"
        bind:value={noteType}
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
        <option value="processing"
          >Bearbeitung - Workflow und Status-Notizen</option
        >
        <option value="communication"
          >Kundenkommunikation - Gesprächsnotizen</option
        >
        <option value="general">Allgemein - Sonstige Bemerkungen</option>
      </select>
    </div>

    <div class="space-y-3">
      <textarea
        bind:value={newNote}
        placeholder="Neue Notiz hinzufügen..."
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical min-h-[80px]"
        rows="3"
      ></textarea>

      <button
        on:click={addNote}
        disabled={!newNote.trim() || loading}
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Notiz hinzufügen
      </button>
    </div>
  </div>

  <!-- Notes list -->
  {#if loading}
    <div class="text-center py-4">
      <div
        class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"
      ></div>
      <span class="ml-2 text-gray-600">Lade Notizen...</span>
    </div>
  {:else if notes.length === 0}
    <div class="text-center py-8 text-gray-500">
      <p>Noch keine Notizen vorhanden.</p>
    </div>
  {:else}
    <div class="space-y-4">
      {#each notes as note (note.id)}
        <div class="border border-gray-200 rounded-lg p-4 bg-white">
          <div class="flex items-start justify-between mb-2">
            <div class="flex items-center space-x-2">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getNoteTypeBadgeClass(
                  note.note_type || 'general',
                )}"
              >
                {getNoteTypeLabel(note.note_type || "general")}
              </span>
            </div>

            <div class="flex items-center space-x-2">
              <button
                on:click={() => startEdit(note)}
                class="text-blue-600 hover:text-blue-800 text-sm"
                title="Bearbeiten"
              >
                Bearbeiten
              </button>
              <button
                on:click={() => deleteNote(note.id)}
                class="text-red-600 hover:text-red-800 text-sm"
                title="Löschen"
              >
                Löschen
              </button>
            </div>
          </div>

          {#if editingNote && editingNote.id === note.id}
            <div class="space-y-3">
              <textarea
                bind:value={editingNote.note}
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical min-h-[80px]"
                rows="3"
              ></textarea>

              <div class="flex space-x-2">
                <button
                  on:click={saveEdit}
                  class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                  Speichern
                </button>
                <button
                  on:click={cancelEdit}
                  class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                >
                  Abbrechen
                </button>
              </div>
            </div>
          {:else}
            <div class="text-gray-800 whitespace-pre-wrap">{note.note}</div>
          {/if}

          <div class="mt-3 text-xs text-gray-500">
            Erstellt: {formatDate(note.created_at)}
            {#if note.updated_at !== note.created_at}
              • Geändert: {formatDate(note.updated_at)}
            {/if}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
  .admin-message-notes {
    max-width: 100%;
  }
</style>
