<script lang="ts">
  import { onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import { get } from "svelte/store";
  import BrandLogo from "../../components/ui/BrandLogo.svelte";
  import QrCode from "../../components/ui/QrCode.svelte";
  import { adminAuth, adminAuthState } from "../../stores/admin";

  let email = "";
  let password = "";
  let loading = false;
  let error = "";
  let twofaCode = ""; // used for both TOTP or backup code depending on toggle
  $: state = $adminAuthState;

  // Aufbereitete Darstellung des Secrets (Gruppierung für manuelle Eingabe)
  $: formattedSecret = state.twofaSecret
    ? state.twofaSecret
        .replace(/[^A-Z2-7=]/gi, "") // nur Base32 relevante Zeichen
        .toUpperCase()
        .replace(/=+$/, "") // Padding entfernen für bessere Lesbarkeit
        .match(/.{1,4}/g)
        ?.join(" ")
    : "";

  function copySecret() {
    if (state.twofaSecret) {
      navigator.clipboard.writeText(state.twofaSecret).catch(() => {});
    }
  }

  onMount(async () => {
    // Always clear authentication state when visiting login page
    adminAuth.reset();

    // Force logout to ensure clean state
    await adminAuth.logout();
  });

  async function handleLogin() {
    if (!email || !password) {
      error = "Bitte geben Sie E-Mail und Passwort ein.";
      return;
    }

    loading = true;
    error = "";

    const result = await adminAuth.login(email, password);

    if (result.success) {
      const st = get(adminAuthState);
      if (st.twofaPending) {
        // remain on page
      } else if (st.isAuthenticated) {
        push("/admin/events");
      }
    } else {
      error = result.message || "Anmeldung fehlgeschlagen.";
    }

    loading = false;
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === "Enter") {
      handleLogin();
    }
  }

  async function handleVerify2FA() {
    if (!twofaCode) {
      error = "Bitte Code eingeben";
      return;
    }
    error = "";
    const res = await adminAuth.twofaVerify(twofaCode.trim()); // endpoint akzeptiert auch Backup-Codes
    if (res.success) {
      push("/admin/events");
    } else {
      error = res.message || "Code ungültig";
    }
  }
</script>

<svelte:head>
  <title>Admin Login - Hypnose Stammtisch</title>
</svelte:head>

<div
  class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
>
  <div class="max-w-md w-full space-y-8">
    <div class="text-center">
      <div class="flex justify-center mb-6">
        <BrandLogo size="lg" />
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        Admin Anmeldung
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Hypnose Stammtisch Verwaltung
      </p>
    </div>

    {#if state.stage === "login"}
      <form class="mt-8 space-y-6" on:submit|preventDefault={handleLogin}>
        <div class="space-y-4">
          <div>
            <label for="email" class="sr-only">E-Mail-Adresse</label>
            <input
              id="email"
              name="email"
              type="email"
              required
              bind:value={email}
              on:keydown={handleKeydown}
              class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10"
              placeholder="E-Mail-Adresse"
              disabled={loading}
            />
          </div>

          <div>
            <label for="password" class="sr-only">Passwort</label>
            <input
              id="password"
              name="password"
              type="password"
              required
              bind:value={password}
              on:keydown={handleKeydown}
              class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10"
              placeholder="Passwort"
              disabled={loading}
            />
          </div>
        </div>

        {#if error}
          <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg
                  class="h-5 w-5 text-red-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                  {error}
                </h3>
              </div>
            </div>
          </div>
        {/if}

        <div>
          <button
            type="submit"
            disabled={loading}
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {#if loading}
              <svg
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              Anmelden...
            {:else}
              Anmelden
            {/if}
          </button>
        </div>
      </form>
    {:else if state.stage === "setup"}
      <div class="mt-8 space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">
          Zwei-Faktor Einrichtung
        </h3>
        <p class="text-sm text-gray-600">
          Scannen Sie den folgenden Code in Ihrer Authenticator-App (z.B. Aegis,
          Google Authenticator, etc.) oder geben Sie das Secret manuell ein.
        </p>
        {#if state.otpauthUri}
          <div class="flex justify-center">
            <QrCode text={state.otpauthUri} size={200} />
          </div>
        {/if}
        {#if state.twofaSecret}
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-700"
                >Alternativ: Secret manuell eingeben</span
              >
              <button
                type="button"
                on:click={copySecret}
                class="text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300 text-gray-800"
                >Kopieren</button
              >
            </div>
            <div
              class="p-2 bg-white border rounded font-mono text-sm select-all break-all"
            >
              {formattedSecret}
            </div>
            <p class="text-xs text-gray-500 leading-snug">
              Falls das Scannen nicht funktioniert, geben Sie dieses Secret
              (Base32) in Ihrer Authenticator-App ein. Leerzeichen ignorieren.
            </p>
          </div>
        {/if}
        <div class="space-y-2">
          <label
            class="block text-sm font-medium text-gray-700"
            for="twofa_code_setup">6-stelliger Code</label
          >
          <input
            id="twofa_code_setup"
            type="text"
            bind:value={twofaCode}
            maxlength="6"
            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            placeholder="123456"
          />
        </div>
        <button
          on:click={handleVerify2FA}
          class="w-full py-2 px-4 rounded bg-blue-600 text-white hover:bg-blue-700"
          >Verifizieren & Fertigstellen</button
        >
        <p class="text-xs text-gray-500">
          Ohne erfolgreiche Einrichtung ist kein Zugriff möglich.
        </p>
      </div>
    {:else if state.stage === "verify"}
      <div class="mt-8 space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">
          Zwei-Faktor Code eingeben
        </h3>
        <p class="text-sm text-gray-600">
          Bitte geben Sie den aktuellen 6-stelligen Code aus Ihrer
          Authenticator-App ein oder nutzen Sie einen Backup-Code.
        </p>
        <div class="space-y-2">
          <label
            class="block text-sm font-medium text-gray-700"
            for="twofa_code_verify"
            >{state.usingBackup ? "Backup-Code" : "6-stelliger Code"}</label
          >
          <input
            id="twofa_code_verify"
            type="text"
            bind:value={twofaCode}
            maxlength={state.usingBackup ? 12 : 6}
            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            placeholder={state.usingBackup ? "BACKUPCODE" : "123456"}
          />
          <button
            type="button"
            class="text-xs underline"
            on:click={() => adminAuth.toggleUsingBackup()}
          >
            {state.usingBackup
              ? "Stattdessen TOTP Code verwenden"
              : "Backup-Code verwenden"}
          </button>
        </div>
        <button
          on:click={handleVerify2FA}
          class="w-full py-2 px-4 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
          disabled={state.verifying}
          >{state.verifying ? "Prüfe..." : "Verifizieren"}</button
        >
        <button
          on:click={() => adminAuth.twofaSetup()}
          class="w-full py-2 px-4 rounded bg-gray-200 text-gray-800 hover:bg-gray-300 text-sm"
          >Neuen Setup-Code anfordern</button
        >
      </div>
    {:else if state.stage === "show-backups"}
      <div class="mt-8 space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">
          Backup-Codes sicher aufbewahren
        </h3>
        <p class="text-sm text-gray-600">
          Diese Einmal-Codes können verwendet werden, falls Sie keinen Zugriff
          auf Ihre Authenticator-App haben. Bewahren Sie sie an einem sicheren
          Ort auf. Jeder Code kann nur einmal genutzt werden.
        </p>
        {#if state.backupCodes?.length}
          <div
            class="grid grid-cols-2 gap-2 font-mono text-sm bg-white p-4 rounded border"
          >
            {#each state.backupCodes as c}
              <div class="px-2 py-1 bg-gray-100 rounded select-all">{c}</div>
            {/each}
          </div>
          <div class="flex gap-2">
            <button
              class="flex-1 py-2 px-4 rounded bg-blue-600 text-white hover:bg-blue-700"
              on:click={() => push("/admin/events")}
              >Weiter zum Dashboard</button
            >
            <button
              class="py-2 px-4 rounded bg-gray-200 text-gray-800 hover:bg-gray-300 text-sm"
              disabled={!state.backupCodes?.length}
              on:click={() => {
                if (state.backupCodes)
                  navigator.clipboard.writeText(state.backupCodes.join("\n"));
              }}>Kopieren</button
            >
          </div>
          <p class="text-xs text-gray-500">
            Sie können später neue Backup-Codes generieren (die alten werden
            dann ungültig).
          </p>
        {:else}
          <p class="text-sm text-gray-600">Keine Codes gefunden.</p>
          <button
            class="w-full py-2 px-4 rounded bg-blue-600 text-white"
            on:click={() => push("/admin/events")}>Weiter</button
          >
        {/if}
      </div>
    {/if}

    <!-- Duplicate verify block removed; using existing stage === 'verify' section above. To allow backup code usage, add toggle inside that section -->
  </div>
</div>
