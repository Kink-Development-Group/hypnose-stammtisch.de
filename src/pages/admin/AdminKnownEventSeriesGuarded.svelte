<script lang="ts">
  import { onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import { adminAuth, adminAuthState } from "../../stores/admin";
  import AdminKnownEventSeriesPage from "./AdminKnownEventSeriesPage.svelte";

  let loading = true;

  $: isAuthenticated = $adminAuthState.isAuthenticated;
  $: user = $adminAuthState.user;
  // Head admins and admins only — an event manager may curate events but not the
  // home page section. The backend enforces the same rule; this only hides UI.
  $: hasPermission = user ? user.canManageKnownEventSeries() : false;

  onMount(async () => {
    try {
      await adminAuth.checkStatus();
    } catch (error) {
      console.error(
        "AdminKnownEventSeriesGuarded: Error checking authentication:",
        error,
      );
      push("/admin/login");
    } finally {
      loading = false;
    }
  });

  $: {
    if (!loading) {
      if (!isAuthenticated) {
        push("/admin/login");
      } else if (!hasPermission) {
        push("/admin/events"); // Redirect to a page they can access
      }
    }
  }
</script>

{#if loading}
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"
      ></div>
      <p class="mt-4 text-slate-600 text-center">Überprüfe Berechtigung...</p>
    </div>
  </div>
{:else if isAuthenticated && hasPermission}
  <AdminKnownEventSeriesPage />
{:else}
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">
        Zugriff verweigert
      </h2>
      <p class="text-slate-600 mb-4">
        Sie haben keine Berechtigung, die Event-Reihen der Startseite zu
        verwalten. Diese Verwaltung steht nur Admins und Head-Admins offen.
      </p>
      <button
        on:click={() => push("/admin/events")}
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
      >
        Zurück zum Admin-Bereich
      </button>
    </div>
  </div>
{/if}
