<script lang="ts">
  import { link } from "svelte-spa-router";

  type PermissionMap = Record<string, boolean>;

  export let permissions: PermissionMap = {};
  export let currentPath = "";
  export let onNavigate: (() => void) | undefined;

  interface NavItem {
    key: string;
    href: string;
    label: string;
    icon: readonly string[];
    permissionKey?: keyof PermissionMap;
    exact?: boolean;
  }

  const navItems: NavItem[] = [
    {
      key: "events",
      href: "/admin/events",
      label: "Veranstaltungen",
      icon: [
        "M8 7V3m8 4V3m-9 8h10",
        "M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
      ],
      permissionKey: "can_manage_events",
    },
    {
      key: "messages",
      href: "/admin/messages",
      label: "Nachrichten",
      icon: [
        "M3 8l7.89 4.26a2 2 0 002.22 0L21 8",
        "M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z",
      ],
      permissionKey: "can_manage_messages",
    },
    {
      key: "locations",
      href: "/admin/stammtisch-locations",
      label: "Stammtisch-Standorte",
      icon: [
        "M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z",
        "M15 11a3 3 0 11-6 0 3 3 0 016 0z",
      ],
      permissionKey: "can_manage_events",
    },
    {
      key: "users",
      href: "/admin/users",
      label: "Admin-Benutzer",
      icon: [
        "M12 4.354a4 4 0 110 5.292",
        "M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z",
      ],
      permissionKey: "can_manage_users",
    },
    {
      key: "security",
      href: "/admin/security",
      label: "Sicherheit",
      icon: [
        "M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4 9 5.567 9 7.5 10.343 11 12 11z",
        "M12 11v8m-6 3h12a2 2 0 002-2v-3.586a1 1 0 00-.293-.707l-5-5a1 1 0 00-1.414 0l-5 5A1 1 0 006 18.414V22a2 2 0 002 2z",
      ],
      permissionKey: "can_manage_security",
    },
    {
      key: "profile",
      href: "/admin/profile",
      label: "Mein Profil",
      icon: [
        "M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804",
        "M15 10a3 3 0 11-6 0 3 3 0 016 0z",
      ],
      exact: true,
    },
  ];

  function isActive(path: string, exact = false): boolean {
    if (!currentPath) return false;
    return exact ? currentPath === path : currentPath.startsWith(path);
  }

  function hasAccess(item: NavItem): boolean {
    if (!item.permissionKey) return true;
    return Boolean((permissions as PermissionMap)[item.permissionKey]);
  }

  function navClasses(active: boolean): string {
    const base =
      "group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors";
    return active
      ? `${base} bg-blue-50 text-blue-700`
      : `${base} text-gray-700 hover:bg-blue-50 hover:text-blue-700`;
  }
</script>

<nav class="flex flex-col gap-1">
  {#each navItems as item (item.key)}
    {#if hasAccess(item)}
      <a
        href={item.href}
        use:link
        class={navClasses(isActive(item.href, item.exact))}
        aria-current={isActive(item.href, item.exact) ? "page" : undefined}
        on:click={() => onNavigate?.()}
      >
        <span
          class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white"
        >
          <svg
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            {#each item.icon as path (path)}
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d={path}
              />
            {/each}
          </svg>
        </span>
        <span class="truncate">{item.label}</span>
      </a>
    {/if}
  {/each}
</nav>
