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
  <div class="max-w-xl space-y-6">
    <h2 class="text-2xl font-semibold">Mein Profil</h2>

    {#if message}
      <div class="p-3 rounded bg-green-100 text-green-800 text-sm">
        {message}
      </div>
    {/if}
    {#if error}
      <div class="p-3 rounded bg-red-100 text-red-800 text-sm">{error}</div>
    {/if}

    <div class="space-y-4 bg-white p-5 rounded shadow">
      <div>
        <label class="block text-sm font-medium mb-1" for="username"
          >Benutzername</label
        >
        <input
          id="username"
          class="w-full border rounded px-3 py-2"
          bind:value={username}
        />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="email">E-Mail</label>
        <input
          id="email"
          type="email"
          class="w-full border rounded px-3 py-2"
          bind:value={email}
        />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="password"
          >Neues Passwort (optional)</label
        >
        <input
          id="password"
          type="password"
          class="w-full border rounded px-3 py-2"
          bind:value={password}
          placeholder="Leer lassen für unverändert"
        />
      </div>
      <div class="space-y-2">
        <div class="flex items-center gap-2">
          <input
            id="reset2fa"
            type="checkbox"
            bind:checked={resetTwofa}
            class="h-4 w-4"
            on:change={() => {
              if (resetTwofa) showConfirmReset = true;
            }}
          />
          <label for="reset2fa" class="text-sm"
            >2FA zurücksetzen (erzwingt Neueinrichtung beim nächsten Login)</label
          >
        </div>
        {#if showConfirmReset && resetTwofa}
          <div
            class="p-3 bg-red-50 border border-red-200 rounded text-xs space-y-2"
          >
            <p>
              <strong>Achtung:</strong> Sie müssen 2FA beim nächsten Login neu einrichten.
              Fortfahren?
            </p>
            <div class="flex gap-2">
              <button
                type="button"
                class="px-3 py-1 bg-red-600 text-white rounded text-xs"
                on:click={() => {
                  showConfirmReset = false;
                }}>Ja</button
              >
              <button
                type="button"
                class="px-3 py-1 bg-gray-200 rounded text-xs"
                on:click={() => {
                  resetTwofa = false;
                  showConfirmReset = false;
                }}>Abbrechen</button
              >
            </div>
          </div>
        {/if}
      </div>
      <div class="space-y-1">
        <div class="flex justify-between text-xs text-gray-600">
          <span>Passwort-Stärke</span><span>{passwordStrength}/6</span>
        </div>
        <div class="h-2 bg-gray-200 rounded overflow-hidden">
          <div
            class="h-full transition-all"
            style="width: {(passwordStrength / 6) * 100}%"
            class:bg-red-500={passwordStrength < 3}
            class:bg-yellow-500={passwordStrength >= 3 && passwordStrength < 5}
            class:bg-green-600={passwordStrength >= 5}
          ></div>
        </div>
      </div>
      <div class="space-y-2 border-t pt-4">
        <div class="flex items-center justify-between">
          <span class="text-sm">Verbleibende Backup-Codes:</span>
          <span class="text-sm font-mono">{backupRemaining ?? "–"}</span>
        </div>
        <button
          type="button"
          on:click={regenerateBackupCodes}
          class="text-xs px-3 py-1 rounded bg-indigo-600 text-white disabled:opacity-50"
          disabled={regenLoading}
          >{regenLoading
            ? "Generiere..."
            : "Backup-Codes neu generieren"}</button
        >
      </div>
      <button
        on:click={saveProfile}
        class="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50"
        disabled={loading}>{loading ? "Speichere..." : "Speichern"}</button
      >
    </div>
  </div>
</AdminLayout>
