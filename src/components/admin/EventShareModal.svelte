<script lang="ts">
  import { createEventDispatcher, onMount } from "svelte";
  import { AdminAPI } from "../../stores/admin";
  import Portal from "../ui/Portal.svelte";

  // Which record is being shared and how to address it on the API.
  export let targetType: "event" | "series" = "event";
  export let targetId: string;
  export let title = "";
  // Head admin only: allow reassigning the owner.
  export let canReassign = false;

  const dispatch = createEventDispatcher();

  let loading = true;
  let error = "";
  let ownerUsername: string | null = null;
  let managers: { username: string }[] = [];

  let usernameInput = "";
  let adding = false;
  let suggestions: string[] = [];
  let searchTimer: ReturnType<typeof setTimeout> | null = null;

  // Reassign-owner state (head admin only)
  let reassignInput = "";
  let reassigning = false;
  let reassignSuggestions: string[] = [];
  let reassignTimer: ReturnType<typeof setTimeout> | null = null;

  onMount(load);

  async function load() {
    loading = true;
    error = "";
    const res = await AdminAPI.getEventManagers(targetType, targetId);
    if (res.success) {
      ownerUsername = res.data?.owner_username ?? null;
      managers = res.data?.managers ?? [];
    } else {
      error = res.message || "Fehler beim Laden der Freigaben";
    }
    loading = false;
  }

  function onInput() {
    if (searchTimer) clearTimeout(searchTimer);
    const q = usernameInput.trim();
    if (q.length < 2) {
      suggestions = [];
      return;
    }
    searchTimer = setTimeout(async () => {
      const res = await AdminAPI.searchManagerCandidates(q);
      if (res.success) {
        const taken = new Set(managers.map((m) => m.username));
        suggestions = (res.data?.usernames ?? []).filter(
          (u: string) => !taken.has(u) && u !== ownerUsername,
        );
      }
    }, 200);
  }

  async function addManager(name?: string) {
    const username = (name ?? usernameInput).trim();
    if (!username || adding) return;
    adding = true;
    error = "";
    const res = await AdminAPI.addEventManager(targetType, targetId, username);
    if (res.success) {
      const added = res.data?.username ?? username;
      if (!managers.some((m) => m.username === added)) {
        managers = [...managers, { username: added }].sort((a, b) =>
          a.username.localeCompare(b.username),
        );
      }
      usernameInput = "";
      suggestions = [];
    } else {
      error = res.message || "Fehler beim Teilen";
    }
    adding = false;
  }

  async function removeManager(username: string) {
    error = "";
    const res = await AdminAPI.removeEventManager(
      targetType,
      targetId,
      username,
    );
    if (res.success) {
      managers = managers.filter((m) => m.username !== username);
    } else {
      error = res.message || "Fehler beim Entziehen des Zugriffs";
    }
  }

  function onReassignInput() {
    if (reassignTimer) clearTimeout(reassignTimer);
    const q = reassignInput.trim();
    if (q.length < 2) {
      reassignSuggestions = [];
      return;
    }
    reassignTimer = setTimeout(async () => {
      const res = await AdminAPI.searchManagerCandidates(q);
      if (res.success) {
        reassignSuggestions = (res.data?.usernames ?? []).filter(
          (u: string) => u !== ownerUsername,
        );
      }
    }, 200);
  }

  async function reassignOwner(name?: string) {
    const username = (name ?? reassignInput).trim();
    if (!username || reassigning) return;
    reassigning = true;
    error = "";
    const res = await AdminAPI.reassignEventOwner(targetType, targetId, username);
    if (res.success) {
      reassignInput = "";
      reassignSuggestions = [];
      // Owner changed → refresh owner + grantees and let the parent reload its list.
      await load();
      dispatch("changed");
    } else {
      error = res.message || "Fehler beim Neuzuweisen";
    }
    reassigning = false;
  }

  function close() {
    dispatch("close");
  }
</script>

<Portal>
  <div
    class="fixed inset-0 bg-gray-700/50 dark:bg-charcoal-900/80 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999]"
  >
    <div
      class="relative top-16 mx-auto p-6 border dark:border-charcoal-600 w-11/12 max-w-md shadow-2xl rounded-lg bg-white dark:bg-charcoal-800"
    >
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-smoke-50">
            Veranstaltung teilen
          </h3>
          {#if title}
            <p class="text-xs text-slate-600 dark:text-smoke-400 mt-1">
              {title}
            </p>
          {/if}
        </div>
        <button
          type="button"
          class="text-xs px-2 py-1 rounded border dark:border-charcoal-500 shadow-sm hover:bg-gray-100 dark:hover:bg-charcoal-600 text-gray-700 dark:text-smoke-200"
          on:click={close}>Schließen</button
        >
      </div>

      {#if error}
        <div
          class="mt-4 rounded-md border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-200"
          role="alert"
        >
          {error}
        </div>
      {/if}

      {#if loading}
        <div class="flex justify-center py-8">
          <div
            class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400"
          ></div>
        </div>
      {:else}
        {#if ownerUsername}
          <p class="mt-4 text-sm text-slate-600 dark:text-smoke-400">
            Eigentümer: <span class="font-medium text-gray-900 dark:text-smoke-100"
              >@{ownerUsername}</span
            >
          </p>
        {/if}

        <!-- Add by username -->
        <div class="mt-4">
          <label
            for="share-username"
            class="block text-sm font-medium text-gray-700 dark:text-smoke-300"
            >Event-Manager über Benutzernamen hinzufügen</label
          >
          <div class="mt-1 flex gap-2">
            <input
              id="share-username"
              type="text"
              autocomplete="off"
              bind:value={usernameInput}
              on:input={onInput}
              on:keydown={(e) => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  addManager();
                }
              }}
              class="flex-1 rounded-md border-gray-300 dark:border-charcoal-500 focus:ring-blue-500 focus:border-blue-500 px-3 py-2 text-sm bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
              placeholder="Benutzername"
            />
            <button
              type="button"
              on:click={() => addManager()}
              disabled={adding || usernameInput.trim().length === 0}
              class="px-3 py-2 rounded-md text-sm font-medium text-white bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-600 disabled:opacity-50"
              >Hinzufügen</button
            >
          </div>
          {#if suggestions.length}
            <ul
              class="mt-1 border border-gray-200 dark:border-charcoal-600 rounded-md divide-y divide-gray-100 dark:divide-charcoal-700 overflow-hidden"
            >
              {#each suggestions as s (s)}
                <li>
                  <button
                    type="button"
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-smoke-200 hover:bg-blue-50 dark:hover:bg-charcoal-700"
                    on:click={() => addManager(s)}>@{s}</button
                  >
                </li>
              {/each}
            </ul>
          {/if}
          <p class="mt-1 text-[11px] text-slate-500 dark:text-smoke-500">
            Andere Manager sehen nur Benutzernamen – keine persönlichen Daten.
          </p>
        </div>

        <!-- Current grantees -->
        <div class="mt-5">
          <h4
            class="text-sm font-medium text-gray-700 dark:text-smoke-300 mb-2"
          >
            Freigegeben für
          </h4>
          {#if managers.length === 0}
            <p class="text-sm text-slate-500 dark:text-smoke-500">
              Noch nicht geteilt.
            </p>
          {:else}
            <ul class="space-y-2">
              {#each managers as m (m.username)}
                <li
                  class="flex items-center justify-between rounded-md border border-gray-200 dark:border-charcoal-600 px-3 py-2"
                >
                  <span class="text-sm text-gray-900 dark:text-smoke-100"
                    >@{m.username}</span
                  >
                  <button
                    type="button"
                    class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline"
                    on:click={() => removeManager(m.username)}>Entfernen</button
                  >
                </li>
              {/each}
            </ul>
          {/if}
        </div>

        {#if canReassign}
          <!-- Reassign owner (head admin only) -->
          <div class="mt-5 pt-4 border-t border-gray-200 dark:border-charcoal-600">
            <h4
              class="text-sm font-medium text-gray-700 dark:text-smoke-300 mb-2"
            >
              Besitzer neu zuweisen
            </h4>
            <div class="flex gap-2">
              <input
                type="text"
                autocomplete="off"
                bind:value={reassignInput}
                on:input={onReassignInput}
                on:keydown={(e) => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    reassignOwner();
                  }
                }}
                class="flex-1 rounded-md border-gray-300 dark:border-charcoal-500 focus:ring-blue-500 focus:border-blue-500 px-3 py-2 text-sm bg-white dark:bg-charcoal-700 text-gray-900 dark:text-smoke-100"
                placeholder="Neuer Besitzer (Benutzername)"
              />
              <button
                type="button"
                on:click={() => reassignOwner()}
                disabled={reassigning || reassignInput.trim().length === 0}
                class="px-3 py-2 rounded-md text-sm font-medium text-white bg-amber-600 dark:bg-amber-700 hover:bg-amber-700 dark:hover:bg-amber-600 disabled:opacity-50"
                >Zuweisen</button
              >
            </div>
            {#if reassignSuggestions.length}
              <ul
                class="mt-1 border border-gray-200 dark:border-charcoal-600 rounded-md divide-y divide-gray-100 dark:divide-charcoal-700 overflow-hidden"
              >
                {#each reassignSuggestions as s (s)}
                  <li>
                    <button
                      type="button"
                      class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-smoke-200 hover:bg-amber-50 dark:hover:bg-charcoal-700"
                      on:click={() => reassignOwner(s)}>@{s}</button
                    >
                  </li>
                {/each}
              </ul>
            {/if}
            <p class="mt-1 text-[11px] text-slate-500 dark:text-smoke-500">
              Überträgt das Eigentum vollständig an einen anderen Manager.
            </p>
          </div>
        {/if}
      {/if}
    </div>
  </div>
</Portal>
