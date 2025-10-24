/**
 * Store for admin security management data and actions.
 */
import { writable } from "svelte/store";
import {
  FailedLoginRecord,
  IpBanRecord,
  LockedAccount,
  SecurityStats,
} from "../classes/security";
import { t } from "../utils/i18n";
import { addNotification } from "./ui";

interface DataLoadingState {
  stats: boolean;
  failedLogins: boolean;
  ipBans: boolean;
  lockedAccounts: boolean;
}

interface DataErrorState {
  stats?: string;
  failedLogins?: string;
  ipBans?: string;
  lockedAccounts?: string;
}

interface DataMetaState {
  statsFetchedAt: Date | null;
  failedLoginsFetchedAt: Date | null;
  ipBansFetchedAt: Date | null;
  lockedAccountsFetchedAt: Date | null;
}

export interface AdminSecurityState {
  stats: SecurityStats | null;
  failedLogins: FailedLoginRecord[];
  ipBans: IpBanRecord[];
  lockedAccounts: LockedAccount[];
  loading: DataLoadingState;
  errors: DataErrorState;
  meta: DataMetaState;
}

const initialState: AdminSecurityState = {
  stats: null,
  failedLogins: [],
  ipBans: [],
  lockedAccounts: [],
  loading: {
    stats: false,
    failedLogins: false,
    ipBans: false,
    lockedAccounts: false,
  },
  errors: {},
  meta: {
    statsFetchedAt: null,
    failedLoginsFetchedAt: null,
    ipBansFetchedAt: null,
    lockedAccountsFetchedAt: null,
  },
};

interface JsonResponse<T> {
  success: boolean;
  message?: string;
  error?: string;
  data?: T;
}

const API_BASE = "/api/admin/security";

const buildErrorMessage = (status: number, fallback: string): string => {
  if (status === 401 || status === 403) {
    return t("adminSecurity.errors.permission");
  }
  return fallback;
};

function createAdminSecurityStore() {
  const { subscribe, update, set } = writable<AdminSecurityState>(initialState);

  const request = async <T>(path: string, init?: RequestInit): Promise<T> => {
    const response = await fetch(`${API_BASE}${path}`, {
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
      },
      ...init,
    });

    if (!response.ok) {
      throw new Error(
        buildErrorMessage(
          response.status,
          response.statusText || t("adminSecurity.errors.load"),
        ),
      );
    }

    const payload = (await response.json()) as JsonResponse<T>;
    if (!payload.success) {
      throw new Error(
        payload.error || payload.message || t("adminSecurity.errors.load"),
      );
    }

    if (payload.data === undefined) {
      throw new Error(t("adminSecurity.errors.load"));
    }

    return payload.data;
  };

  const loadStats = async () => {
    update((state) => ({
      ...state,
      loading: { ...state.loading, stats: true },
      errors: { ...state.errors, stats: undefined },
    }));

    try {
      const data = await request<Record<string, unknown>>("/stats", {
        method: "GET",
      });
      const stats = SecurityStats.fromApi(data);
      update((state) => ({
        ...state,
        stats,
        meta: { ...state.meta, statsFetchedAt: new Date() },
      }));
    } catch (error) {
      const message =
        error instanceof Error ? error.message : t("adminSecurity.errors.load");
      update((state) => ({
        ...state,
        errors: { ...state.errors, stats: message },
      }));
    } finally {
      update((state) => ({
        ...state,
        loading: { ...state.loading, stats: false },
      }));
    }
  };

  const loadFailedLogins = async () => {
    update((state) => ({
      ...state,
      loading: { ...state.loading, failedLogins: true },
      errors: { ...state.errors, failedLogins: undefined },
    }));

    try {
      const data = await request<{ failed_logins: unknown[] }>(
        "/failed-logins?limit=100",
        { method: "GET" },
      );
      const records = (data.failed_logins || []).map((entry) =>
        FailedLoginRecord.fromApi(entry),
      );
      update((state) => ({
        ...state,
        failedLogins: records,
        meta: { ...state.meta, failedLoginsFetchedAt: new Date() },
      }));
    } catch (error) {
      const message =
        error instanceof Error ? error.message : t("adminSecurity.errors.load");
      update((state) => ({
        ...state,
        errors: { ...state.errors, failedLogins: message },
      }));
    } finally {
      update((state) => ({
        ...state,
        loading: { ...state.loading, failedLogins: false },
      }));
    }
  };

  const loadIpBans = async () => {
    update((state) => ({
      ...state,
      loading: { ...state.loading, ipBans: true },
      errors: { ...state.errors, ipBans: undefined },
    }));

    try {
      const data = await request<{ ip_bans: unknown[] }>("/ip-bans", {
        method: "GET",
      });
      const bans = (data.ip_bans || []).map((entry) =>
        IpBanRecord.fromApi(entry),
      );
      update((state) => ({
        ...state,
        ipBans: bans,
        meta: { ...state.meta, ipBansFetchedAt: new Date() },
      }));
    } catch (error) {
      const message =
        error instanceof Error ? error.message : t("adminSecurity.errors.load");
      update((state) => ({
        ...state,
        errors: { ...state.errors, ipBans: message },
      }));
    } finally {
      update((state) => ({
        ...state,
        loading: { ...state.loading, ipBans: false },
      }));
    }
  };

  const loadLockedAccounts = async () => {
    update((state) => ({
      ...state,
      loading: { ...state.loading, lockedAccounts: true },
      errors: { ...state.errors, lockedAccounts: undefined },
    }));

    try {
      const data = await request<{ locked_accounts: unknown[] }>(
        "/locked-accounts",
        { method: "GET" },
      );
      const accounts = (data.locked_accounts || []).map((entry) =>
        LockedAccount.fromApi(entry),
      );
      update((state) => ({
        ...state,
        lockedAccounts: accounts,
        meta: { ...state.meta, lockedAccountsFetchedAt: new Date() },
      }));
    } catch (error) {
      const message =
        error instanceof Error ? error.message : t("adminSecurity.errors.load");
      update((state) => ({
        ...state,
        errors: { ...state.errors, lockedAccounts: message },
      }));
    } finally {
      update((state) => ({
        ...state,
        loading: { ...state.loading, lockedAccounts: false },
      }));
    }
  };

  const initialize = async () => {
    await Promise.allSettled([
      loadStats(),
      loadFailedLogins(),
      loadIpBans(),
      loadLockedAccounts(),
    ]);
  };

  const unlockAccount = async (accountId: string): Promise<boolean> => {
    try {
      await request(`/unlock-account`, {
        method: "POST",
        body: JSON.stringify({ account_id: Number(accountId) }),
      });
      addNotification({
        type: "success",
        title: t("adminSecurity.lockedAccounts.unlock"),
        message: t("adminSecurity.notifications.unlockSuccess"),
      });
      await Promise.allSettled([loadLockedAccounts(), loadStats()]);
      return true;
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : t("adminSecurity.notifications.unlockError");
      addNotification({
        type: "error",
        title: t("adminSecurity.lockedAccounts.unlock"),
        message,
      });
      return false;
    }
  };

  const removeIpBan = async (ipAddress: string): Promise<boolean> => {
    try {
      await request(`/remove-ip-ban`, {
        method: "POST",
        body: JSON.stringify({ ip_address: ipAddress }),
      });
      addNotification({
        type: "success",
        title: t("adminSecurity.ipBans.remove"),
        message: t("adminSecurity.notifications.unbanSuccess"),
      });
      await Promise.allSettled([loadIpBans(), loadStats()]);
      return true;
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : t("adminSecurity.notifications.unbanError");
      addNotification({
        type: "error",
        title: t("adminSecurity.ipBans.remove"),
        message,
      });
      return false;
    }
  };

  const banIp = async (ipAddress: string, reason: string): Promise<boolean> => {
    try {
      await request(`/ban-ip`, {
        method: "POST",
        body: JSON.stringify({ ip_address: ipAddress, reason }),
      });
      addNotification({
        type: "success",
        title: t("adminSecurity.ipBans.title"),
        message: t("adminSecurity.notifications.banSuccess"),
      });
      await Promise.allSettled([loadIpBans(), loadStats()]);
      return true;
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : t("adminSecurity.notifications.banError");
      addNotification({
        type: "error",
        title: t("adminSecurity.ipBans.title"),
        message,
      });
      return false;
    }
  };

  const cleanupExpiredBans = async (): Promise<boolean> => {
    try {
      await request(`/cleanup-expired-bans`, {
        method: "POST",
      });
      addNotification({
        type: "success",
        title: t("adminSecurity.maintenance.cleanup"),
        message: t("adminSecurity.notifications.cleanupSuccess"),
      });
      await Promise.allSettled([loadIpBans(), loadStats()]);
      return true;
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : t("adminSecurity.notifications.cleanupError");
      addNotification({
        type: "error",
        title: t("adminSecurity.maintenance.cleanup"),
        message,
      });
      return false;
    }
  };

  const reset = () => set(initialState);

  return {
    subscribe,
    initialize,
    loadStats,
    loadFailedLogins,
    loadIpBans,
    loadLockedAccounts,
    unlockAccount,
    removeIpBan,
    banIp,
    cleanupExpiredBans,
    reset,
  };
}

export const adminSecurity = createAdminSecurityStore();
