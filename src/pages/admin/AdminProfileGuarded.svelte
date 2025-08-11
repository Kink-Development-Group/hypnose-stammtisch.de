<script lang="ts">
  import { onMount } from "svelte";
  import { get } from "svelte/store";
  import AdminLayout from "../../components/admin/AdminLayout.svelte";
  import { adminAuth, adminAuthState } from "../../stores/admin";

  let username = "";
  let email = "";
  let password = "";
  let loading = false;
  let message = "";
  let error = "";
  let resetTwofa = false;
  let backupRemaining: number | null = null;
  let regenLoading = false;
  let showConfirmReset = false;
  let passwordStrength = 0;

  onMount(async () => {
    const st = get(adminAuthState);
    if (!st.isAuthenticated) {
      const status = await adminAuth.checkStatus();
      if (!status.success) return;
    }
    const current = get(adminAuthState).user;
    if (current) {
      username = current.username;
      email = current.email;
    }
    // Load backup status
    try {
      const r = await fetch("/api/admin/auth/2fa/backup-codes/status", {
        credentials: "include",
      });
      if (r.ok) {
        const j = await r.json();
        if (j.success) backupRemaining = j.data.remaining;
      }
    } catch {
      /* ignore */
    }
  });

  $: passwordStrength = calcPasswordStrength(password);

  function calcPasswordStrength(pw: string): number {
    if (!pw) return 0;
    let s = 0;
    if (pw.length >= 8) s++;
    if (pw.length >= 12) s++;
    if (/[A-Z]/.test(pw)) s++;
    if (/[a-z]/.test(pw)) s++;
    if (/\d/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    return Math.min(s, 6);
  }

  async function regenerateBackupCodes() {
    regenLoading = true;
    error = "";
    message = "";
    try {
      const r = await fetch("/api/admin/auth/2fa/backup-codes/generate", {
        method: "POST",
        credentials: "include",
      });
      const j = await r.json();
      if (j.success) {
        message = "Neue Backup-Codes generiert. Alte wurden ungültig.";
        backupRemaining = j.data.codes.length;
        // optional: anzeigen / download
        const blob = new Blob([j.data.codes.join("\n")], {
          type: "text/plain",
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = "backup-codes.txt";
        a.click();
        URL.revokeObjectURL(url);
      } else {
        error = j.message || "Fehler beim Generieren";
      }
    } catch {
      error = "Netzwerkfehler";
    }
    regenLoading = false;
  }

  async function saveProfile() {
    loading = true;
    error = "";
    message = "";
    const body: any = {};
    if (username) body.username = username;
    if (email) body.email = email;
    if (password) body.password = password;
    if (resetTwofa) body.reset_twofa = true;

    const res = await fetch("/api/admin/users/me", {
      method: "PUT",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (json.success) {
      message = resetTwofa
        ? "Profil aktualisiert. 2FA wurde zurückgesetzt – bitte neu einrichten beim nächsten Login."
        : "Profil gespeichert.";
      password = "";
      resetTwofa = false;
      // Wenn 2FA reset: ausloggen damit Setup erzwungen wird
      if (body.reset_twofa) {
        await adminAuth.logout();
        window.location.href = "/admin/login";
      } else {
        // refresh status
        await adminAuth.checkStatus();
      }
    } else {
      error = json.message || "Fehler beim Speichern";
    }
    loading = false;
  }
</script>

<AdminLayout>
  <div class="max-w-5xl mx-auto space-y-8">
    <header class="flex flex-col gap-2">
      <h2 class="text-3xl font-semibold tracking-tight text-gray-700">
        Mein Profil
      </h2>
      <p class="text-sm text-gray-700">
        Verwalte deine persönlichen Zugangsdaten und Sicherheitsoptionen.
      </p>
    </header>

    {#if message}
      <div
        class="flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          /></svg
        >
        <span>{message}</span>
      </div>
    {/if}
    {#if error}
      <div
        class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M18.364 5.636A9 9 0 115.636 18.364 9 9 0 0118.364 5.636zM12 8v4m0 4h.01"
          /></svg
        >
        <span>{error}</span>
      </div>
    {/if}

    <form
      on:submit|preventDefault={saveProfile}
      class="grid gap-8 lg:grid-cols-3"
    >
      <!-- Account Data -->
      <fieldset
        class="lg:col-span-2 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm shadow-sm hover:shadow transition overflow-hidden"
      >
        <legend class="sr-only">Zugangsdaten</legend>
        <div
          class="px-6 pt-5 pb-2 flex items-center gap-3 border-b bg-gradient-to-r from-blue-50 to-indigo-50"
        >
          <div
            class="h-10 w-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-600"
          >
            <svg
              class="h-6 w-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"
              /></svg
            >
          </div>
          <div>
            <h3 class="text-lg font-medium leading-tight text-gray-700">
              Zugangsdaten
            </h3>
            <p class="text-xs text-gray-600">
              Benutzername & E‑Mail-Adresse für die Anmeldung
            </p>
          </div>
        </div>
        <div class="p-6 grid gap-6 md:grid-cols-2">
          <!-- Username -->
          <div class="space-y-1">
            <label
              class="block text-sm font-medium text-gray-800"
              for="username">Benutzername</label
            >
            <div class="relative">
              <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"
              >
                <svg
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  /></svg
                >
              </span>
              <input
                id="username"
                class="w-full rounded-lg border border-gray-300 bg-white px-10 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 transition"
                bind:value={username}
                autocomplete="username"
              />
            </div>
          </div>
          <!-- Email -->
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-800" for="email"
              >E‑Mail</label
            >
            <div class="relative">
              <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"
              >
                <svg
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 12H8m0 0l4 4m-4-4l4-4m8 8V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2z"
                  /></svg
                >
              </span>
              <input
                id="email"
                type="email"
                class="w-full rounded-lg border border-gray-300 bg-white px-10 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 transition"
                bind:value={email}
                autocomplete="email"
              />
            </div>
            <p class="text-xs text-gray-700">
              Änderungen erfordern Bestätigung über eine E‑Mail.
            </p>
          </div>
          <!-- Password -->
          <div class="space-y-1 md:col-span-2">
            <label
              class="block text-sm font-medium text-gray-800"
              for="password"
              >Neues Passwort <span class="text-gray-400 text-xs font-normal"
                >(optional)</span
              ></label
            >
            <div class="relative group">
              <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 group-focus-within:text-blue-600"
              >
                <svg
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                  /></svg
                >
              </span>
              <input
                id="password"
                type="password"
                class="w-full rounded-lg border border-gray-300 bg-white px-10 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 transition"
                bind:value={password}
                placeholder="Leer lassen für unverändert"
                autocomplete="new-password"
              />
            </div>
            <!-- Password strength meter -->
            <div class="space-y-1">
              <div
                class="flex justify-between text-[11px] tracking-wide text-gray-700 font-medium"
              >
                <span>Passwort-Stärke</span>
                <span>{passwordStrength}/6</span>
              </div>
              <div class="grid grid-cols-6 gap-1">
                {#each Array(6) as _, i}
                  <div
                    class="h-1.5 rounded-full transition-colors duration-300 {i <
                    passwordStrength
                      ? passwordStrength <= 2
                        ? 'bg-red-500'
                        : passwordStrength <= 4
                          ? 'bg-amber-400'
                          : 'bg-green-500'
                      : 'bg-gray-200'}"
                  ></div>
                {/each}
              </div>
              <ul
                class="mt-2 grid gap-1 text-[11px] text-gray-800 md:grid-cols-3"
              >
                <li
                  class="flex items-center gap-1 {password.length >= 8
                    ? 'text-green-600'
                    : ''}"
                >
                  <span
                    class="inline-block h-1.5 w-1.5 rounded-full {password.length >=
                    8
                      ? 'bg-green-600'
                      : 'bg-gray-300'}"
                  ></span> ≥ 8 Zeichen
                </li>
                <li
                  class="flex items-center gap-1 {/[A-Z]/.test(password)
                    ? 'text-green-600'
                    : ''}"
                >
                  <span
                    class="inline-block h-1.5 w-1.5 rounded-full {/[A-Z]/.test(
                      password,
                    )
                      ? 'bg-green-600'
                      : 'bg-gray-300'}"
                  ></span> Großbuchst.
                </li>
                <li
                  class="flex items-center gap-1 {/[a-z]/.test(password)
                    ? 'text-green-600'
                    : ''}"
                >
                  <span
                    class="inline-block h-1.5 w-1.5 rounded-full {/[a-z]/.test(
                      password,
                    )
                      ? 'bg-green-600'
                      : 'bg-gray-300'}"
                  ></span> Kleinbuchst.
                </li>
                <li
                  class="flex items-center gap-1 {/\d/.test(password)
                    ? 'text-green-600'
                    : ''}"
                >
                  <span
                    class="inline-block h-1.5 w-1.5 rounded-full {/\d/.test(
                      password,
                    )
                      ? 'bg-green-600'
                      : 'bg-gray-300'}"
                  ></span> Zahl
                </li>
                <li
                  class="flex items-center gap-1 {/[^A-Za-z0-9]/.test(password)
                    ? 'text-green-600'
                    : ''}"
                >
                  <span
                    class="inline-block h-1.5 w-1.5 rounded-full {/[^A-Za-z0-9]/.test(
                      password,
                    )
                      ? 'bg-green-600'
                      : 'bg-gray-300'}"
                  ></span> Symbol
                </li>
                <li
                  class="flex items-center gap-1 {password.length >= 12
                    ? 'text-green-600'
                    : ''}"
                >
                  <span
                    class="inline-block h-1.5 w-1.5 rounded-full {password.length >=
                    12
                      ? 'bg-green-600'
                      : 'bg-gray-300'}"
                  ></span> ≥ 12 Zeichen
                </li>
              </ul>
            </div>
          </div>
        </div>
      </fieldset>

      <!-- Security / 2FA -->
      <fieldset
        class="rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm shadow-sm hover:shadow transition overflow-hidden"
      >
        <legend class="sr-only">Sicherheit</legend>
        <div
          class="px-6 pt-5 pb-2 flex items-center gap-3 border-b bg-gradient-to-r from-rose-50 to-orange-50"
        >
          <div
            class="h-10 w-10 flex items-center justify-center rounded-full bg-rose-100 text-rose-600"
          >
            <svg
              class="h-6 w-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 11c.5304 0 1.0391-.2107 1.4142-.5858C13.7893 10.0391 14 9.5304 14 9s-.2107-1.0391-.5858-1.4142C13.0391 7.2107 12.5304 7 12 7s-1.0391.2107-1.4142.5858C10.2107 7.9609 10 8.4696 10 9s.2107 1.0391.5858 1.4142C10.9609 10.7893 11.4696 11 12 11zm0 2c-.7956 0-1.5587.3161-2.1213.8787C9.3161 14.4413 9 15.2044 9 16v1h6v-1c0-.7956-.3161-1.5587-.8787-2.1213C13.5587 13.3161 12.7956 13 12 13z"
              /></svg
            >
          </div>
          <div>
            <h3 class="text-lg font-medium leading-tight text-gray-700">
              Sicherheit
            </h3>
            <p class="text-xs text-gray-700">2FA-Verwaltung & Backup-Codes</p>
          </div>
        </div>
        <div class="p-6 space-y-6">
          <!-- 2FA Reset -->
          <div class="space-y-3">
            <label for="reset2fa" class="flex items-start gap-3 cursor-pointer">
              <input
                id="reset2fa"
                type="checkbox"
                bind:checked={resetTwofa}
                class="mt-1 h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500"
                on:change={() => {
                  if (resetTwofa) showConfirmReset = true;
                }}
              />
              <span class="text-sm text-gray-800">
                <span class="font-medium">2FA zurücksetzen</span>
                <span class="block text-xs text-gray-700"
                  >Erzwingt Neueinrichtung beim nächsten Login</span
                >
              </span>
            </label>
            {#if showConfirmReset && resetTwofa}
              <div
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs space-y-3"
              >
                <p>
                  <strong>Achtung:</strong> Nach dem Zurücksetzen muss 2FA sofort
                  neu eingerichtet werden. Fortfahren?
                </p>
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded bg-red-600 px-3 py-1.5 text-white text-xs font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                    on:click={() => {
                      showConfirmReset = false;
                    }}>Ja</button
                  >
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded bg-gray-200 px-3 py-1.5 text-gray-700 text-xs font-medium hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400/40"
                    on:click={() => {
                      resetTwofa = false;
                      showConfirmReset = false;
                    }}>Abbrechen</button
                  >
                </div>
              </div>
            {/if}
          </div>

          <!-- Backup Codes -->
          <div class="space-y-3 border-t pt-4">
            <div
              class="flex items-center justify-between text-sm text-gray-800"
            >
              <span class="text-gray-800">Verbleibende Backup-Codes</span>
              <span class="font-mono text-gray-900"
                >{backupRemaining ?? "–"}</span
              >
            </div>
            <button
              type="button"
              on:click={regenerateBackupCodes}
              class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 disabled:opacity-50"
              disabled={regenLoading}
            >
              {#if regenLoading}
                <svg
                  class="h-4 w-4 animate-spin"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 12a8 8 0 018-8v0m0 0a8 8 0 018 8m-8 8a8 8 0 01-8-8"
                  /></svg
                >
                <span>Generiere…</span>
              {:else}
                <svg
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M20 20v-5h-.581m-15.357-2a8.003 8.003 0 0015.357 2"
                  /></svg
                >
                <span>Backup-Codes neu generieren</span>
              {/if}
            </button>
            <p class="text-[11px] text-gray-700 leading-relaxed">
              Neue Codes ersetzen die alten sofort. Sichere sie an einem
              sicheren Ort (Offline-Dokument / Passwort-Manager).
            </p>
          </div>
        </div>
      </fieldset>

      <!-- Actions -->
      <div
        class="lg:col-span-3 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 pt-2"
      >
        <div class="text-xs text-gray-700 flex-1">
          Änderungen werden sofort wirksam. E‑Mail-Änderungen erfordern
          Bestätigung. 2FA-Reset führt zum Logout.
        </div>
        <div class="flex gap-3">
          <button
            type="reset"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400/40"
            on:click={() => {
              password = "";
              resetTwofa = false;
              showConfirmReset = false;
              error = "";
              message = "";
            }}>Zurücksetzen</button
          >
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 disabled:opacity-50"
            disabled={loading}
          >
            {#if loading}
              <svg
                class="h-4 w-4 animate-spin"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 12a8 8 0 018-8v0m0 0a8 8 0 018 8m-8 8a8 8 0 01-8-8"
                /></svg
              >
              <span>Speichere…</span>
            {:else}
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                /></svg
              >
              <span>Speichern</span>
            {/if}
          </button>
        </div>
      </div>
    </form>
  </div>
</AdminLayout>
