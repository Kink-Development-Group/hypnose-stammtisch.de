<script lang="ts">
  import { onDestroy, onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import { SvelteSet } from "svelte/reactivity";
  import User from "../../classes/User";
  import AdminLayout from "../../components/admin/AdminLayout.svelte";
  import SecurityCard from "../../components/admin/security/SecurityCard.svelte";
  import { adminAuth } from "../../stores/admin";
  import { adminSecurity } from "../../stores/adminSecurity";
  import type { Locale } from "../../utils/i18n";
  import { formatDateTime, locale, t } from "../../utils/i18n";

  let currentUser: User | null = null;
  let isPageLoading = true;
  let permissionError = "";

  let banIpInput = "";
  let banReasonInput = "";
  let banFormError = "";
  let isBanSubmitting = false;
  let isCleanupSubmitting = false;
  let isRefreshingAll = false;

  let unlockingIds = new SvelteSet<string>();
  let unbanningIps = new SvelteSet<string>();

  const updateSet = (set: SvelteSet<string>, value: string, add: boolean) => {
    const next = new SvelteSet(set);
    if (add) {
      next.add(value);
    } else {
      next.delete(value);
    }

    return next;
  };

  const buildUpdatedLabel = (
    date: Date | null,
    activeLocale: Locale,
  ): string | null => {
    if (!date) {
      return null;
    }

    return t("adminSecurity.lastUpdated", {
      locale: activeLocale,
      values: { value: formatDateTime(date, undefined, activeLocale) },
    });
  };

  const isValidIp = (value: string): boolean => {
    const ip = value.trim();
    if (!ip) return false;

    if (ip.includes(":")) {
      // Basic IPv6 validation
      const ipv6 = ip.split(":");
      if (ipv6.length < 3 || ipv6.length > 8) return false;
      return ipv6.every((part) => part === "" || /^[0-9a-f]{0,4}$/i.test(part));
    }

    const octets = ip.split(".");
    if (octets.length !== 4) return false;

    return octets.every((part) => {
      if (!/^\d+$/.test(part)) return false;
      const numeric = Number(part);
      return numeric >= 0 && numeric <= 255;
    });
  };

  onMount(async () => {
    try {
      const status = await adminAuth.checkStatus();
      if (!status.success || !status.data) {
        push("/admin/login");
        return;
      }

      currentUser = User.fromApiData(status.data);

      if (!currentUser.canManageSecurity()) {
        permissionError = t("adminSecurity.errors.permission");
        return;
      }

      await adminSecurity.initialize();
    } catch (error) {
      console.error("AdminSecurity: initialization failed", error);
      permissionError = t("adminSecurity.errors.load");
    } finally {
      isPageLoading = false;
    }
  });

  onDestroy(() => {
    adminSecurity.reset();
  });

  const refreshAll = async () => {
    isRefreshingAll = true;
    try {
      await adminSecurity.initialize();
    } finally {
      isRefreshingAll = false;
    }
  };

  const handleUnlock = async (accountId: string) => {
    unlockingIds = updateSet(unlockingIds, accountId, true);
    try {
      await adminSecurity.unlockAccount(accountId);
    } finally {
      unlockingIds = updateSet(unlockingIds, accountId, false);
    }
  };

  const handleRemoveIpBan = async (ipAddress: string) => {
    unbanningIps = updateSet(unbanningIps, ipAddress, true);
    try {
      await adminSecurity.removeIpBan(ipAddress);
    } finally {
      unbanningIps = updateSet(unbanningIps, ipAddress, false);
    }
  };

  const handleBanIp = async () => {
    banFormError = "";

    if (!isValidIp(banIpInput)) {
      banFormError = t("adminSecurity.form.invalidIp");
      return;
    }

    if (!banReasonInput.trim()) {
      banFormError = t("adminSecurity.form.required");
      return;
    }

    isBanSubmitting = true;
    try {
      const success = await adminSecurity.banIp(
        banIpInput.trim(),
        banReasonInput.trim(),
      );
      if (success) {
        banIpInput = "";
        banReasonInput = "";
      }
    } finally {
      isBanSubmitting = false;
    }
  };

  const handleCleanup = async () => {
    isCleanupSubmitting = true;
    try {
      await adminSecurity.cleanupExpiredBans();
    } finally {
      isCleanupSubmitting = false;
    }
  };

  $: statsUpdatedLabel = buildUpdatedLabel(
    $adminSecurity.meta.statsFetchedAt,
    $locale,
  );
  $: failedLoginsUpdatedLabel = buildUpdatedLabel(
    $adminSecurity.meta.failedLoginsFetchedAt,
    $locale,
  );
  $: ipBansUpdatedLabel = buildUpdatedLabel(
    $adminSecurity.meta.ipBansFetchedAt,
    $locale,
  );
  $: lockedAccountsUpdatedLabel = buildUpdatedLabel(
    $adminSecurity.meta.lockedAccountsFetchedAt,
    $locale,
  );
</script>

<svelte:head>
  <title>{t("adminSecurity.title")} - Admin</title>
  <meta
    name="description"
    content="Admin-Konsole zur Verwaltung von fehlgeschlagenen Logins, IP-Sperren und Kontosperren."
  />
</svelte:head>

<AdminLayout>
  {#if isPageLoading}
    <div class="flex items-center justify-center py-20">
      <div class="rounded-lg bg-white dark:bg-charcoal-800 p-6 shadow">
        <div class="mb-4 flex items-center justify-center">
          <div
            class="h-10 w-10 animate-spin rounded-full border-b-2 border-blue-600"
          ></div>
        </div>
        <p
          class="text-center text-sm font-medium text-slate-700 dark:text-smoke-300"
        >
          {t("adminSecurity.refresh")}
        </p>
      </div>
    </div>
  {:else if permissionError}
    <div
      class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-6 text-red-800 dark:text-red-200"
    >
      {permissionError}
    </div>
  {:else}
    <div class="space-y-8">
      <section
        class="rounded-xl border border-gray-200 dark:border-charcoal-700 bg-white dark:bg-charcoal-800 p-6 shadow"
      >
        <div
          class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-smoke-50">
              {t("adminSecurity.title")}
            </h1>
            <p class="text-sm text-slate-600 dark:text-smoke-400">
              {t("adminSecurity.subtitle")}
            </p>
            {#if statsUpdatedLabel}
              <p class="mt-1 text-xs text-slate-500 dark:text-smoke-500">
                {statsUpdatedLabel}
              </p>
            {/if}
          </div>
          <button
            class="inline-flex items-center rounded-lg border border-blue-600 dark:border-blue-500 px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30 disabled:cursor-not-allowed disabled:border-gray-300 dark:disabled:border-charcoal-600 disabled:text-gray-400 dark:disabled:text-smoke-600"
            on:click={refreshAll}
            disabled={isRefreshingAll}
          >
            {#if isRefreshingAll}
              <svg
                class="mr-2 h-4 w-4 animate-spin"
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
                  d="M4 12a8 8 0 018-8v4l3-3-3-3v4a12 12 0 1012 12h-4A8 8 0 014 12z"
                ></path>
              </svg>
            {/if}
            {t("adminSecurity.refresh")}
          </button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <div
            class="rounded-xl border border-gray-200 dark:border-blue-800 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-5 shadow-sm"
          >
            <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
              {t("adminSecurity.stats.failedLogins")}
            </p>
            <p
              class="mt-2 text-3xl font-semibold text-blue-900 dark:text-blue-100"
            >
              {$adminSecurity.stats?.failedLogins24h ?? 0}
            </p>
          </div>
          <div
            class="rounded-xl border border-gray-200 dark:border-emerald-800 bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 p-5 shadow-sm"
          >
            <p
              class="text-sm font-medium text-emerald-700 dark:text-emerald-300"
            >
              {t("adminSecurity.stats.activeBans")}
            </p>
            <p
              class="mt-2 text-3xl font-semibold text-emerald-900 dark:text-emerald-100"
            >
              {$adminSecurity.stats?.activeIpBans ?? 0}
            </p>
          </div>
          <div
            class="rounded-xl border border-gray-200 dark:border-amber-800 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/30 p-5 shadow-sm"
          >
            <p class="text-sm font-medium text-amber-700 dark:text-amber-300">
              {t("adminSecurity.stats.lockedAccounts")}
            </p>
            <p
              class="mt-2 text-3xl font-semibold text-amber-900 dark:text-amber-100"
            >
              {$adminSecurity.stats?.lockedAccounts ?? 0}
            </p>
          </div>
          <div
            class="rounded-xl border border-gray-200 dark:border-purple-800 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 p-5 shadow-sm"
          >
            <p class="text-sm font-medium text-purple-700 dark:text-purple-300">
              {t("adminSecurity.stats.uniqueIps")}
            </p>
            <p
              class="mt-2 text-3xl font-semibold text-purple-900 dark:text-purple-100"
            >
              {$adminSecurity.stats?.uniqueIpsFailed24h ?? 0}
            </p>
          </div>
        </div>
      </section>

      <SecurityCard
        title={t("adminSecurity.failedLogins.title")}
        description={t("adminSecurity.failedLogins.description")}
        lastUpdated={failedLoginsUpdatedLabel}
      >
        <svelte:fragment slot="actions">
          <button
            class="rounded-lg border border-slate-300 dark:border-charcoal-600 px-3 py-2 text-sm font-medium text-slate-700 dark:text-smoke-300 transition-colors hover:bg-slate-50 dark:hover:bg-charcoal-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:disabled:text-smoke-600"
            on:click={adminSecurity.loadFailedLogins}
            disabled={$adminSecurity.loading.failedLogins}
          >
            {t("adminSecurity.refresh")}
          </button>
        </svelte:fragment>

        {#if $adminSecurity.loading.failedLogins}
          <div
            class="flex items-center justify-center py-10 text-sm text-slate-600 dark:text-smoke-400"
          >
            <div
              class="h-5 w-5 animate-spin rounded-full border-b-2 border-blue-600"
            ></div>
            <span class="ml-2">{t("adminSecurity.refresh")}</span>
          </div>
        {:else if $adminSecurity.failedLogins.length === 0}
          <p class="text-sm text-slate-600 dark:text-smoke-400">
            {t("adminSecurity.failedLogins.empty")}
          </p>
        {:else}
          <div class="overflow-x-auto">
            <table
              class="min-w-full divide-y divide-slate-200 dark:divide-charcoal-700"
            >
              <thead class="bg-slate-50 dark:bg-charcoal-700">
                <tr>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.attempted")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.ip")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.username")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.email")}}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.createdAt")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.userAgent")}
                  </th>
                </tr>
              </thead>
              <tbody
                class="divide-y divide-slate-200 dark:divide-charcoal-700 bg-white dark:bg-charcoal-800"
              >
                {#each $adminSecurity.failedLogins as record (record.id)}
                  <tr>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {record.usernameEntered ?? "-"}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {record.ipAddress}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {record.username ?? "-"}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {record.email ?? "-"}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {record.formatCreatedAt($locale)}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-500 dark:text-smoke-400"
                    >
                      <span class="block max-w-xs break-words"
                        >{record.userAgent ?? "-"}</span
                      >
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </SecurityCard>

      <SecurityCard
        title={t("adminSecurity.lockedAccounts.title")}
        description={t("adminSecurity.lockedAccounts.description")}
        lastUpdated={lockedAccountsUpdatedLabel}
      >
        <svelte:fragment slot="actions">
          <button
            class="rounded-lg border border-slate-300 dark:border-charcoal-600 px-3 py-2 text-sm font-medium text-slate-700 dark:text-smoke-300 transition-colors hover:bg-slate-50 dark:hover:bg-charcoal-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:disabled:text-smoke-600"
            on:click={adminSecurity.loadLockedAccounts}
            disabled={$adminSecurity.loading.lockedAccounts}
          >
            {t("adminSecurity.refresh")}
          </button>
        </svelte:fragment>

        {#if $adminSecurity.loading.lockedAccounts}
          <div
            class="flex items-center justify-center py-10 text-sm text-slate-600 dark:text-smoke-400"
          >
            <div
              class="h-5 w-5 animate-spin rounded-full border-b-2 border-blue-600"
            ></div>
            <span class="ml-2">{t("adminSecurity.refresh")}</span>
          </div>
        {:else if $adminSecurity.lockedAccounts.length === 0}
          <p class="text-sm text-slate-600 dark:text-smoke-400">
            {t("adminSecurity.lockedAccounts.empty")}
          </p>
        {:else}
          <div class="overflow-x-auto">
            <table
              class="min-w-full divide-y divide-slate-200 dark:divide-charcoal-700"
            >
              <thead class="bg-slate-50 dark:bg-charcoal-700">
                <tr>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.username")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.email")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.role")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.lockedUntil")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.reason")}
                  </th>
                  <th
                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.actions")}
                  </th>
                </tr>
              </thead>
              <tbody
                class="divide-y divide-slate-200 dark:divide-charcoal-700 bg-white dark:bg-charcoal-800"
              >
                {#each $adminSecurity.lockedAccounts as account (account.id)}
                  <tr>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                      >{account.username}</td
                    >
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                      >{account.email}</td
                    >
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                      >{account.role}</td
                    >
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {account.formatLockedUntil($locale)}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {account.lockedReason ?? "-"}
                    </td>
                    <td class="px-4 py-3 text-right text-sm">
                      <button
                        class="inline-flex items-center rounded-lg bg-emerald-600 dark:bg-emerald-700 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 dark:hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300 dark:disabled:bg-emerald-800"
                        on:click={() => handleUnlock(account.id)}
                        disabled={unlockingIds.has(account.id)}
                      >
                        {t("adminSecurity.lockedAccounts.unlock")}
                      </button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </SecurityCard>

      <SecurityCard
        title={t("adminSecurity.ipBans.title")}
        description={t("adminSecurity.ipBans.description")}
        lastUpdated={ipBansUpdatedLabel}
      >
        <svelte:fragment slot="actions">
          <button
            class="rounded-lg border border-slate-300 dark:border-charcoal-600 px-3 py-2 text-sm font-medium text-slate-700 dark:text-smoke-300 transition-colors hover:bg-slate-50 dark:hover:bg-charcoal-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:disabled:text-smoke-600"
            on:click={adminSecurity.loadIpBans}
            disabled={$adminSecurity.loading.ipBans}
          >
            {t("adminSecurity.refresh")}
          </button>
        </svelte:fragment>

        {#if $adminSecurity.loading.ipBans}
          <div
            class="flex items-center justify-center py-10 text-sm text-slate-600 dark:text-smoke-400"
          >
            <div
              class="h-5 w-5 animate-spin rounded-full border-b-2 border-blue-600"
            ></div>
            <span class="ml-2">{t("adminSecurity.refresh")}</span>
          </div>
        {:else if $adminSecurity.ipBans.length === 0}
          <p class="text-sm text-slate-600 dark:text-smoke-400">
            {t("adminSecurity.ipBans.empty")}
          </p>
        {:else}
          <div class="overflow-x-auto">
            <table
              class="min-w-full divide-y divide-slate-200 dark:divide-charcoal-700"
            >
              <thead class="bg-slate-50 dark:bg-charcoal-700">
                <tr>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.ip")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.reason")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.bannedBy")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.createdAt")}
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.expiresAt")}
                  </th>
                  <th
                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-smoke-400"
                  >
                    {t("adminSecurity.table.actions")}
                  </th>
                </tr>
              </thead>
              <tbody
                class="divide-y divide-slate-200 dark:divide-charcoal-700 bg-white dark:bg-charcoal-800"
              >
                {#each $adminSecurity.ipBans as ban (ban.id)}
                  <tr class:opacity-60={ban.isExpired}>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                      >{ban.ipAddress}</td
                    >
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                      >{ban.reason}</td
                    >
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {ban.bannedByUsername ?? "-"}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {ban.formatCreatedAt($locale)}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-slate-900 dark:text-smoke-100"
                    >
                      {ban.formatExpiresAt($locale)}
                    </td>
                    <td class="px-4 py-3 text-right text-sm">
                      <button
                        class="inline-flex items-center rounded-lg bg-red-600 dark:bg-red-700 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 dark:hover:bg-red-600 disabled:cursor-not-allowed disabled:bg-red-300 dark:disabled:bg-red-800"
                        on:click={() => handleRemoveIpBan(ban.ipAddress)}
                        disabled={unbanningIps.has(ban.ipAddress)}
                      >
                        {t("adminSecurity.ipBans.remove")}
                      </button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}

        <div
          class="mt-6 grid gap-4 rounded-lg border border-dashed border-slate-300 dark:border-charcoal-600 bg-slate-50 dark:bg-charcoal-700/50 p-4 sm:grid-cols-2"
        >
          <div>
            <label
              class="block text-sm font-medium text-slate-700 dark:text-smoke-300"
              for="ban-ip"
            >
              {t("adminSecurity.ipBans.create.ip")}
            </label>
            <input
              id="ban-ip"
              type="text"
              class="mt-1 w-full rounded-md border border-slate-300 dark:border-charcoal-600 bg-white dark:bg-charcoal-700 text-slate-900 dark:text-smoke-100 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400 dark:placeholder:text-smoke-500"
              placeholder={t("adminSecurity.ipBans.create.ipPlaceholder")}
              bind:value={banIpInput}
            />
          </div>
          <div>
            <label
              class="block text-sm font-medium text-slate-700 dark:text-smoke-300"
              for="ban-reason"
            >
              {t("adminSecurity.ipBans.create.reason")}
            </label>
            <input
              id="ban-reason"
              type="text"
              class="mt-1 w-full rounded-md border border-slate-300 dark:border-charcoal-600 bg-white dark:bg-charcoal-700 text-slate-900 dark:text-smoke-100 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400 dark:placeholder:text-smoke-500"
              placeholder={t("adminSecurity.ipBans.create.reasonPlaceholder")}
              bind:value={banReasonInput}
            />
          </div>
          {#if banFormError}
            <div class="sm:col-span-2 text-sm text-red-600 dark:text-red-400">
              {banFormError}
            </div>
          {/if}
          <div class="sm:col-span-2">
            <button
              class="inline-flex items-center rounded-lg bg-blue-600 dark:bg-blue-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-blue-300 dark:disabled:bg-blue-800"
              on:click={handleBanIp}
              disabled={isBanSubmitting}
            >
              {#if isBanSubmitting}
                <svg
                  class="mr-2 h-4 w-4 animate-spin"
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
                    d="M4 12a8 8 0 018-8v4l3-3-3-3v4a12 12 0 1012 12h-4A8 8 0 014 12z"
                  ></path>
                </svg>
              {/if}
              {t("adminSecurity.ipBans.create.submit")}
            </button>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <button
            class="inline-flex items-center rounded-lg border border-amber-600 dark:border-amber-500 px-4 py-2 text-sm font-medium text-amber-700 dark:text-amber-400 transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/30 disabled:cursor-not-allowed disabled:border-slate-300 dark:disabled:border-charcoal-600 disabled:text-slate-400 dark:disabled:text-smoke-600"
            on:click={handleCleanup}
            disabled={isCleanupSubmitting}
          >
            {#if isCleanupSubmitting}
              <svg
                class="mr-2 h-4 w-4 animate-spin"
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
                  d="M4 12a8 8 0 018-8v4l3-3-3-3v4a12 12 0 1012 12h-4A8 8 0 014 12z"
                ></path>
              </svg>
            {/if}
            {t("adminSecurity.maintenance.cleanup")}
          </button>
        </div>
      </SecurityCard>
    </div>
  {/if}
</AdminLayout>
