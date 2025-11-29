<script lang="ts">
  import { onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import { adminAuth } from "../../stores/admin";

  export let component: any;
  export let params = {};

  let isAuthenticated = false;
  let isLoading = true;
  let authError = "";

  onMount(async () => {
    console.log("AdminGuard: Starting authentication check...");

    // Always start with a clean state
    adminAuth.reset();

    try {
      // Check authentication status with no cache
      const status = await adminAuth.checkStatus();

      console.log("AdminGuard: Auth check result:", status);

      if (status.success && status.data) {
        console.log("AdminGuard: Authentication successful", status.data);
        isAuthenticated = true;
        authError = "";
      } else {
        console.log(
          "AdminGuard: Authentication failed",
          status.message || "No data",
        );
        isAuthenticated = false;
        authError = status.message || "Authentication failed";

        // Force logout to clear any stale state
        await adminAuth.logout();

        // Redirect to login page
        push("/admin/login");
        return;
      }
    } catch (error) {
      console.error("AdminGuard: Authentication error:", error);
      isAuthenticated = false;
      authError = "Network error during authentication";

      // Force logout and redirect
      await adminAuth.logout();
      push("/admin/login");
      return;
    } finally {
      isLoading = false;
    }
  });
</script>

{#if isLoading}
  <div
    class="min-h-screen bg-gray-100 dark:bg-charcoal-900 flex items-center justify-center"
  >
    <div
      class="bg-white dark:bg-charcoal-800 p-8 rounded-lg shadow-md max-w-md w-full"
    >
      <div class="flex flex-col items-center">
        <div
          class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 dark:border-blue-400 mb-4"
        ></div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-smoke-50 mb-2">
          Authentifizierung prüfen
        </h2>
        <p class="text-slate-600 dark:text-smoke-400 text-center">
          Überprüfe Anmeldestatus...
        </p>
      </div>
    </div>
  </div>
{:else if !isAuthenticated}
  <div
    class="min-h-screen bg-gray-100 dark:bg-charcoal-900 flex items-center justify-center"
  >
    <div
      class="bg-white dark:bg-charcoal-800 p-8 rounded-lg shadow-md max-w-md w-full"
    >
      <div class="flex flex-col items-center">
        <div
          class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4"
        >
          <svg
            class="w-6 h-6 text-red-600 dark:text-red-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"
            />
          </svg>
        </div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-smoke-50 mb-2">
          Zugriff verweigert
        </h2>
        <p class="text-slate-600 dark:text-smoke-400 text-center mb-4">
          {authError ||
            "Sie sind nicht authentifiziert. Bitte melden Sie sich an."}
        </p>
        <button
          on:click={() => push("/admin/login")}
          class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
        >
          Zur Anmeldung
        </button>
      </div>
    </div>
  </div>
{:else}
  <!-- Render the protected component -->
  <svelte:component this={component} {params} />
{/if}
