<script lang="ts">
  import { onMount } from "svelte";
  import { link, push } from "svelte-spa-router";
  import User from "../../classes/User";
  import { adminAuth } from "../../stores/admin";
  import { UserHelpers } from "../../utils/userHelpers";
  import BrandLogo from "../ui/BrandLogo.svelte";
  import AdminNavigationLinks from "./AdminNavigationLinks.svelte";
  import AdminNotifications from "./AdminNotifications.svelte";
  import AdminStatusBar from "./AdminStatusBar.svelte";

  type PermissionState = ReturnType<typeof UserHelpers.getPermissions>;

  let isAuthenticated = false;
  let currentUser: User | null = null;
  let currentPath = "";
  let permissions: PermissionState = {
    can_manage_events: false,
    can_manage_messages: false,
    can_manage_users: false,
    can_manage_security: false,
  };
  let isNavOpen = false;

  $: roleDisplayName = currentUser
    ? UserHelpers.getRoleDisplayName(currentUser.role)
    : "";
  $: roleBadgeClass = currentUser
    ? UserHelpers.getRoleBadgeClass(currentUser.role)
    : "";

  function updateCurrentPath() {
    if (typeof window === "undefined") return;
    const hash = window.location.hash?.replace(/^#/, "");
    currentPath = hash || window.location.pathname || "";
  }

  onMount(() => {
    isAuthenticated = false;
    currentUser = null;
    permissions = {
      can_manage_events: false,
      can_manage_messages: false,
      can_manage_users: false,
      can_manage_security: false,
    };

    let popHandler: (() => void) | null = null;
    let isActive = true;

    const initialize = async () => {
      const status = await adminAuth.checkStatus();

      if (!status.success || !isActive) {
        if (!status.success) {
          push("/admin/login");
        }
        return;
      }

      isAuthenticated = true;
      currentUser = User.fromApiData(status.data);
      permissions = UserHelpers.getPermissions(currentUser);
      updateCurrentPath();
    };

    initialize();

    if (typeof window !== "undefined") {
      popHandler = () => updateCurrentPath();
      window.addEventListener("hashchange", popHandler);
      window.addEventListener("popstate", popHandler);
    }

    return () => {
      isActive = false;

      if (popHandler && typeof window !== "undefined") {
        window.removeEventListener("hashchange", popHandler);
        window.removeEventListener("popstate", popHandler);
      }
    };
  });

  function closeMobileNav() {
    isNavOpen = false;
  }

  function handleNavigate() {
    closeMobileNav();
    updateCurrentPath();
  }

  async function handleLogout() {
    await adminAuth.logout();

    closeMobileNav();
    isAuthenticated = false;
    currentUser = null;
    permissions = {
      can_manage_events: false,
      can_manage_messages: false,
      can_manage_users: false,
      can_manage_security: false,
    };

    if (typeof window !== "undefined") {
      window.location.href = "/admin/login";
    } else {
      push("/admin/login");
    }
  }
</script>

{#if isAuthenticated}
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <a
      href="#main-content"
      class="sr-only focus-visible:not-sr-only focus-visible:fixed focus-visible:left-4 focus-visible:top-4 focus-visible:z-50 focus-visible:rounded-lg focus-visible:bg-white focus-visible:px-4 focus-visible:py-2 focus-visible:text-sm focus-visible:font-semibold focus-visible:text-blue-600 focus-visible:shadow-lg"
      >Zum Inhalt springen</a
    >

    {#if isNavOpen}
      <div class="fixed inset-0 z-40 flex lg:hidden" role="dialog" aria-modal="true">
        <div
          class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
          on:click={closeMobileNav}
        ></div>
        <div
          class="relative ml-auto flex h-full w-72 max-w-xs flex-col bg-white shadow-2xl"
        >
          <div class="flex items-center justify-between border-b px-4 py-4">
            <div class="flex items-center gap-2">
              <BrandLogo size="sm" />
              <div>
                <p class="text-sm font-semibold text-slate-900">
                  {currentUser?.username || "Admin"}
                </p>
                {#if roleDisplayName}
                  <span
                    class={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ${roleBadgeClass}`}
                    >{roleDisplayName}</span
                  >
                {/if}
              </div>
            </div>
            <button
              type="button"
              class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
              on:click={closeMobileNav}
              aria-label="Navigation schließen"
            >
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fill-rule="evenodd"
                  d="M6.707 4.293a1 1 0 00-1.414 1.414L8.586 9.999l-3.293 3.293a1 1 0 001.414 1.414l3.293-3.293 3.293 3.293a1 1 0 001.414-1.414l-3.293-3.293 3.293-3.293A1 1 0 0013.293 4.293L10 7.586 6.707 4.293z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto px-4 py-4">
            <AdminNavigationLinks
              {currentPath}
              {permissions}
              onNavigate={handleNavigate}
            />
          </div>

          <div class="border-t px-4 py-3">
            <a
              href="/admin/profile"
              use:link
              class="flex items-center justify-center rounded-lg border border-blue-100 px-3 py-2 text-sm font-medium text-blue-600 transition hover:border-blue-200 hover:bg-blue-50"
              on:click={handleNavigate}
              >Profil öffnen</a
            >
          </div>
        </div>
      </div>
    {/if}

    <header class="border-b border-slate-200 bg-white/95 backdrop-blur">
      <div
        class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8"
      >
        <div class="flex items-center gap-3">
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 lg:hidden"
            on:click={() => (isNavOpen = true)}
            aria-label="Navigation öffnen"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
          </button>

          <BrandLogo size="sm" />
          <div class="hidden sm:flex flex-col">
            <span class="text-sm font-semibold text-slate-900">
              Hypnose-Stammtisch.de
            </span>
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
              Adminbereich
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
          <div class="flex flex-col items-end leading-tight">
            <span class="text-sm font-semibold text-slate-700">
              {currentUser?.username}
            </span>
            {#if roleDisplayName}
              <span
                class={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ${roleBadgeClass}`}
              >
                {roleDisplayName}
              </span>
            {/if}
          </div>

          <a
            href="/admin/profile"
            use:link
            class="hidden sm:inline-flex items-center rounded-lg border border-blue-100 px-3 py-2 text-sm font-medium text-blue-600 transition hover:border-blue-200 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
          >
            Profil
          </a>

          <button
            on:click={handleLogout}
            class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500"
          >
            Abmelden
          </button>
        </div>
      </div>

      <div class="border-t border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <AdminStatusBar className="border-0 px-0" dense />
        </div>
      </div>
    </header>

    <div
      class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 pb-10 pt-6 sm:px-6 lg:flex-row lg:px-8"
    >
      <aside class="order-2 hidden lg:order-1 lg:block lg:w-64 lg:flex-shrink-0">
        <div
          class="sticky top-28 space-y-6 rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur"
        >
          <AdminNavigationLinks {currentPath} {permissions} />
          {#if currentUser}
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Angemeldet als
              </p>
              <p class="mt-1 text-sm font-semibold text-slate-700">
                {currentUser.username}
              </p>
              {#if roleDisplayName}
                <span
                  class={`mt-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ${roleBadgeClass}`}
                >
                  {roleDisplayName}
                </span>
              {/if}
            </div>
          {/if}
        </div>
      </aside>

      <main
        id="main-content"
        class="order-1 min-h-[60vh] flex-1 lg:order-2"
        tabindex="-1"
      >
        <div
          class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur sm:p-6"
        >
          <slot />
        </div>
      </main>
    </div>

    <AdminNotifications />
  </div>
{:else}
  <div class="flex min-h-screen items-center justify-center bg-slate-50">
    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-lg">
      <div
        class="mx-auto h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600"
      ></div>
      <p class="mt-4 text-center text-sm text-slate-600">
        Überprüfe Anmeldestatus ...
      </p>
    </div>
  </div>
{/if}
