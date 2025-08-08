<script lang="ts">
  import { onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import BrandLogo from "../../components/ui/BrandLogo.svelte";
  import { adminAuth } from "../../stores/admin";

  let email = "";
  let password = "";
  let loading = false;
  let error = "";

  onMount(async () => {
    console.log("AdminLogin: Component mounted, clearing authentication state");

    // Always clear authentication state when visiting login page
    adminAuth.reset();

    // Force logout to ensure clean state
    await adminAuth.logout();

    console.log("AdminLogin: Ready for login");
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
      push("/admin/events");
    } else {
      error =
        result.message ||
        "Anmeldung fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.";
    }

    loading = false;
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === "Enter") {
      handleLogin();
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

    <div class="mt-6 text-center">
      <p class="text-xs text-gray-500">
        Standard-Anmeldedaten: admin@example.com / password
      </p>
    </div>
  </div>
</div>
