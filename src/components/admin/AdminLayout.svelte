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
  let permissions: any = {};

  onMount(async () => {
    // Initialize with loading state
    isAuthenticated = false;
    currentUser = null;
    permissions = {};

    // Check authentication status
    console.log("AdminLayout: Checking authentication status...");
    const status = await adminAuth.checkStatus();

    console.log("AdminLayout: Auth status result:", status);

    if (!status.success) {
      console.log("AdminLayout: Authentication failed, redirecting to login");
      isAuthenticated = false;
      push("/admin/login");
      return;
    }

    console.log("AdminLayout: Authentication successful, setting up admin UI");
    isAuthenticated = true;
    currentUser = status.data;

    // Check permissions only after successful authentication
    await checkPermissions();

    // Track current path for navigation highlighting
    currentPath = window.location.hash.substring(1) || window.location.pathname;

    console.log("AdminLayout: Setup complete", { currentUser, permissions });
  });

  async function checkPermissions() {
    try {
      console.log("AdminLayout: Checking permissions...");
      const response = await fetch("/api/admin/users/permissions", {
        credentials: "include",
      });

      console.log("AdminLayout: Permissions response status:", response.status);

      if (response.ok) {
        permissions = await response.json();
        console.log("AdminLayout: Permissions loaded:", permissions);
      } else {
        console.error(
          "AdminLayout: Permissions request failed:",
          response.status,
          response.statusText,
        );

        // If we get 401, user is not authenticated
        if (response.status === 401) {
          console.log(
            "AdminLayout: Permissions check failed - not authenticated, redirecting",
          );
          isAuthenticated = false;
          currentUser = null;
          permissions = {};
          push("/admin/login");
          return;
        }

        const errorText = await response.text();
        console.error("AdminLayout: Error response:", errorText);
      }
    } catch (err) {
      console.error("AdminLayout: Failed to check permissions:", err);
    }
  }

  async function handleLogout() {
    await adminAuth.logout();

    // Force a complete cleanup and redirect
    isAuthenticated = false;
    currentUser = null;
    permissions = {};

    // Force page reload to clear any cached state
    if (typeof window !== "undefined") {
      window.location.href = "/admin/login";
    } else {
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

            <!-- Debug Information -->
            <div class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
              Role: {currentUser?.role || "unknown"} | Can manage users: {permissions.can_manage_users
                ? "Yes"
                : "No"}
            </div>

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

          {#if permissions.can_manage_users}
            <a
              href="/admin/users"
              use:link
              class="group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md transition-colors {currentPath ===
              '/admin/users'
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
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                />
              </svg>
              Admin-Benutzer
            </a>
          {/if}
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
