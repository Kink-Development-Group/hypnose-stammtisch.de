<script lang="ts">
  import { onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import { adminAuth, adminAuthState } from "../../stores/admin";
  import AdminStammtischLocationsPage from "./AdminStammtischLocationsPage.svelte";

  let loading = true;

  // Reactive variables based on auth state
  $: isAuthenticated = $adminAuthState.isAuthenticated;
  $: user = $adminAuthState.user;
  $: hasPermission = user
    ? user.canManageUsers() || user.canManageEvents()
    : false;

  onMount(async () => {
    try {
      console.log(
        "AdminStammtischLocationsGuarded: Checking authentication...",
      );

      // Check authentication status
      await adminAuth.checkStatus();
    } catch (error) {
      console.error(
        "AdminStammtischLocationsGuarded: Error checking authentication:",
        error,
      );
      push("/admin/login");
    } finally {
      loading = false;
    }
  });

  // Reactive statement to handle auth state changes
  $: {
    if (!loading) {
      if (!isAuthenticated) {
        console.log(
          "AdminStammtischLocationsGuarded: Not authenticated, redirecting to login",
        );
        push("/admin/login");
      } else if (!hasPermission) {
        console.log(
          "AdminStammtischLocationsGuarded: Insufficient permissions",
        );
        push("/admin/events"); // Redirect to a page they can access
      } else {
        console.log(
          "AdminStammtischLocationsGuarded: Authentication and permissions OK",
          {
            isAuthenticated,
            hasPermission,
            userRole: user?.role,
          },
        );
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
  <AdminStammtischLocationsPage />
{:else}
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">
        Zugriff verweigert
      </h2>
      <p class="text-slate-600 mb-4">
        Sie haben keine Berechtigung, auf die Stammtisch-Verwaltung zuzugreifen.
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
