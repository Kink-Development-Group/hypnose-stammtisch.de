<script lang="ts">
  import { onMount } from "svelte";
  import {
    deletePasskey,
    isPasskeySupported,
    listPasskeys,
    registerPasskey,
    type PasskeyCredential,
  } from "../../utils/webauthn";

  let credentials: PasskeyCredential[] = [];
  let maxCredentials = 0;
  let nickname = "";
  let supported = false;
  let loading = true;
  let registering = false;
  let deletingId: string | null = null;
  let message = "";
  let error = "";

  $: limitReached = maxCredentials > 0 && credentials.length >= maxCredentials;

  onMount(async () => {
    supported = isPasskeySupported();
    await refresh();
  });

  async function refresh() {
    loading = true;
    // A previous load error must not survive a successful reload, otherwise the
    // stale red banner sits next to the green success message.
    error = "";
    const result = await listPasskeys();
    if (result.success && result.data) {
      credentials = result.data.credentials;
      maxCredentials = result.data.max;
    } else if (!result.success) {
      error = result.message || "Passkeys konnten nicht geladen werden.";
    }
    loading = false;
  }

  async function addPasskey() {
    const name = nickname.trim();
    if (!name) {
      error = "Bitte gib dem Passkey einen Namen (z. B. „MacBook Touch ID“).";
      message = "";
      return;
    }

    registering = true;
    error = "";
    message = "";

    const result = await registerPasskey(name);
    if (result.success) {
      message = `Passkey „${name}“ wurde registriert.`;
      nickname = "";
      await refresh();
    } else {
      error = result.message;
    }

    registering = false;
  }

  async function removePasskey(credential: PasskeyCredential) {
    const confirmed = window.confirm(
      `Passkey „${credential.nickname}“ wirklich löschen? ` +
        "Die Anmeldung mit diesem Gerät ist danach nicht mehr möglich.",
    );
    if (!confirmed) return;

    deletingId = credential.id;
    error = "";
    message = "";

    const result = await deletePasskey(credential.id);
    if (result.success) {
      message = `Passkey „${credential.nickname}“ wurde gelöscht.`;
      await refresh();
    } else {
      error = result.message;
    }

    deletingId = null;
  }

  function formatDate(value: string | null): string {
    if (!value) return "noch nie";
    return new Date(value).toLocaleString("de-DE", {
      dateStyle: "medium",
      timeStyle: "short",
    });
  }
</script>

<section
  class="rounded-xl border border-gray-200 dark:border-charcoal-700 bg-white/80 dark:bg-charcoal-800/80 backdrop-blur-sm shadow-sm hover:shadow transition overflow-hidden"
  aria-labelledby="passkey-heading"
>
  <div
    class="px-6 pt-5 pb-2 flex items-center gap-3 border-b dark:border-charcoal-700 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-charcoal-700 dark:to-charcoal-800"
  >
    <div
      class="h-10 w-10 flex items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400"
    >
      <svg
        class="h-6 w-6"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M15 7a4 4 0 11-8 0 4 4 0 018 0zM5 21v-2a4 4 0 014-4h1m8 1v6m0 0l2-2m-2 2l-2-2m4-5a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
    </div>
    <div>
      <h3
        id="passkey-heading"
        class="text-lg font-medium leading-tight text-slate-700 dark:text-smoke-200"
      >
        Passkeys
      </h3>
      <p class="text-xs text-slate-600 dark:text-smoke-400">
        Kennwortlose, phishing-resistente Anmeldung
      </p>
    </div>
  </div>

  <div class="px-6 py-5 space-y-5">
    <p class="text-sm text-gray-700 dark:text-smoke-300 leading-relaxed">
      Ein Passkey ersetzt bei der Anmeldung Passwort und 2FA-Code. Er bleibt auf
      deinem Gerät und lässt sich nicht abphishen. Die Anmeldung mit Passwort
      und 2FA-Code funktioniert unverändert weiter – auch wenn du alle Passkeys
      löschst.
    </p>

    {#if message}
      <p
        class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-800 dark:text-green-200"
        role="status"
      >
        {message}
      </p>
    {/if}
    {#if error}
      <p
        class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-800 dark:text-red-200"
        role="alert"
      >
        {error}
      </p>
    {/if}

    {#if !supported}
      <p
        class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200"
      >
        Dieser Browser unterstützt keine Passkeys. Bestehende Passkeys bleiben
        erhalten und können hier weiterhin gelöscht werden.
      </p>
    {/if}

    {#if loading}
      <p class="text-sm text-gray-700 dark:text-smoke-300">Lade Passkeys…</p>
    {:else if credentials.length === 0}
      <p class="text-sm text-gray-700 dark:text-smoke-300">
        Noch kein Passkey hinterlegt.
      </p>
    {:else}
      <ul class="divide-y divide-gray-200 dark:divide-charcoal-700">
        {#each credentials as credential (credential.id)}
          <li class="flex items-start justify-between gap-4 py-3">
            <div class="min-w-0">
              <p
                class="text-sm font-medium text-gray-900 dark:text-smoke-50 break-words"
              >
                {credential.nickname}
              </p>
              <p class="text-xs text-gray-700 dark:text-smoke-300">
                Angelegt {formatDate(credential.created_at)} · Zuletzt genutzt {formatDate(
                  credential.last_used_at,
                )}
              </p>
            </div>
            <button
              type="button"
              on:click={() => removePasskey(credential)}
              disabled={deletingId === credential.id}
              class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-red-300 dark:border-red-700 bg-white dark:bg-charcoal-700 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-red-500/50 disabled:opacity-50"
            >
              {deletingId === credential.id ? "Lösche…" : "Löschen"}
              <span class="sr-only">Passkey {credential.nickname}</span>
            </button>
          </li>
        {/each}
      </ul>
    {/if}

    {#if supported}
      <div class="space-y-2 border-t dark:border-charcoal-700 pt-4">
        <label
          for="passkey-nickname"
          class="block text-sm font-medium text-gray-800 dark:text-smoke-200"
        >
          Name für den neuen Passkey
        </label>
        <div class="flex flex-col gap-2 sm:flex-row">
          <input
            id="passkey-nickname"
            type="text"
            bind:value={nickname}
            maxlength="100"
            placeholder="z. B. MacBook Touch ID"
            autocomplete="off"
            disabled={registering || limitReached}
            class="flex-1 rounded-lg border border-gray-300 dark:border-charcoal-600 bg-white dark:bg-charcoal-700 px-3 py-2 text-sm text-gray-900 dark:text-smoke-50 placeholder-gray-500 dark:placeholder-smoke-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40 transition disabled:opacity-50"
          />
          <button
            type="button"
            on:click={addPasskey}
            disabled={registering || limitReached}
            aria-busy={registering}
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 dark:bg-emerald-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 dark:hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 disabled:opacity-50"
          >
            {registering ? "Warte auf Gerät…" : "Passkey hinzufügen"}
          </button>
        </div>
        {#if limitReached}
          <p class="text-[11px] text-amber-800 dark:text-amber-300">
            Maximal {maxCredentials} Passkeys pro Konto. Bitte zuerst einen bestehenden
            Passkey löschen.
          </p>
        {/if}
      </div>
    {/if}
  </div>
</section>
