<script lang="ts">
  import { onMount } from "svelte";
  import { link, push } from "svelte-spa-router";
  import { adminAuth } from "../../stores/admin";
  import BrandLogo from "../ui/BrandLogo.svelte";
  import AdminNotifications from "./AdminNotifications.svelte";
  import AdminStatusBar from "./AdminStatusBar.svelte";

  let isAuthenticated = false;
  let currentUser: any = null;
  let currentPath = "";

  onMount(async () => {
    // Check authentication status
    const status = await adminAuth.checkStatus();
    if (!status.success) {
      push("/admin/login");
      return;
    }

    isAuthenticated = true;
    currentUser = status.data;

    // Track current path for navigation highlighting
    currentPath = window.location.hash.substring(1) || window.location.pathname;
  });

  async function handleLogout() {
    const result = await adminAuth.logout();
    if (result.success) {
      push("/admin/login");
    }
  }

  $: currentPath =
    typeof window !== "undefined" ? window.location.pathname : "";
</script>

{#if isAuthenticated}
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center space-x-3">
            <BrandLogo size="sm" />
            <h1 class="text-xl font-semibold text-gray-900">
              Hypnose-Stammtisch.de - Admin
            </h1>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Admin Status Bar -->
            <AdminStatusBar />

            <span class="text-sm text-gray-600">
              Angemeldet als: <strong>{currentUser?.username}</strong>
            </span>
            <button
              on:click={handleLogout}
              class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition-colors"
            >
              Abmelden
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex">
      <!-- Sidebar -->
      <aside class="w-64 bg-white shadow-sm min-h-screen">
        <nav class="mt-5 px-2">
          <a
            href="/admin/events"
            use:link
            class="group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md transition-colors {currentPath ===
            '/admin/events'
              ? 'bg-blue-100 text-blue-700'
              : 'text-gray-700 hover:bg-gray-50'}"
          >
            <svg
              class="mr-4 h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            Veranstaltungen
          </a>

          <a
            href="/admin/messages"
            use:link
            class="group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md transition-colors {currentPath ===
            '/admin/messages'
              ? 'bg-blue-100 text-blue-700'
              : 'text-gray-700 hover:bg-gray-50'}"
          >
            <svg
              class="mr-4 h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            Nachrichten
          </a>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
        <div class="container mx-auto px-6 py-8">
          <slot />
        </div>
      </main>
    </div>

    <!-- Admin Notifications -->
    <AdminNotifications />
  </div>
{:else}
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"
      ></div>
      <p class="mt-4 text-gray-600 text-center">Überprüfe Anmeldestatus...</p>
    </div>
  </div>
{/if}
