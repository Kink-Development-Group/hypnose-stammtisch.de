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
  });

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
      <div class="flex items-center gap-2">
        <input
          id="reset2fa"
          type="checkbox"
          bind:checked={resetTwofa}
          class="h-4 w-4"
        />
        <label for="reset2fa" class="text-sm"
          >2FA zurücksetzen (erzwingt Neueinrichtung beim nächsten Login)</label
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
