/**
 * Type definitions and validation schemas for admin security data structures.
 */
import { z } from "zod";

const numberLike = z.coerce.number();

const parseDate = (value: string | null | undefined): Date | null => {
  if (!value) {
    return null;
  }
  const timestamp = new Date(value);
  return Number.isNaN(timestamp.getTime()) ? null : timestamp;
};

export const RawSecurityStatsSchema = z.object({
  failed_logins_24h: numberLike.nonnegative(),
  active_ip_bans: numberLike.nonnegative(),
  locked_accounts: numberLike.nonnegative(),
  unique_ips_failed_24h: numberLike.nonnegative(),
});

export type RawSecurityStats = z.infer<typeof RawSecurityStatsSchema>;

export interface SecurityStatsData {
  failedLogins24h: number;
  activeIpBans: number;
  lockedAccounts: number;
  uniqueIpsFailed24h: number;
}

export const mapSecurityStats = (raw: RawSecurityStats): SecurityStatsData => ({
  failedLogins24h: raw.failed_logins_24h,
  activeIpBans: raw.active_ip_bans,
  lockedAccounts: raw.locked_accounts,
  uniqueIpsFailed24h: raw.unique_ips_failed_24h,
});

export const RawFailedLoginRecordSchema = z.object({
  id: z.string(),
  account_id: z.union([numberLike, z.null()]).optional().nullable(),
  username_entered: z.string().optional().nullable(),
  ip_address: z.string(),
  user_agent: z.string().optional().nullable(),
  created_at: z.string(),
  username: z.string().optional().nullable(),
  email: z.string().optional().nullable(),
});

export type RawFailedLoginRecord = z.infer<typeof RawFailedLoginRecordSchema>;

export interface FailedLoginRecordData {
  id: string;
  accountId: string | null;
  usernameEntered: string | null;
  ipAddress: string;
  userAgent: string | null;
  createdAt: Date | null;
  username: string | null;
  email: string | null;
}

export const mapFailedLoginRecord = (
  raw: RawFailedLoginRecord,
): FailedLoginRecordData => ({
  id: raw.id,
  accountId:
    raw.account_id !== null && raw.account_id !== undefined
      ? String(raw.account_id)
      : null,
  usernameEntered: raw.username_entered ?? null,
  ipAddress: raw.ip_address,
  userAgent: raw.user_agent ?? null,
  createdAt: parseDate(raw.created_at),
  username: raw.username ?? null,
  email: raw.email ?? null,
});

export const RawIpBanRecordSchema = z.object({
  id: z.string(),
  ip_address: z.string(),
  reason: z.string(),
  banned_by: z.union([numberLike, z.null()]).optional().nullable(),
  created_at: z.string(),
  expires_at: z.string().optional().nullable(),
  is_active: z.union([numberLike, z.boolean()]).optional().nullable(),
  banned_by_username: z.string().optional().nullable(),
});

export type RawIpBanRecord = z.infer<typeof RawIpBanRecordSchema>;

export interface IpBanRecordData {
  id: string;
  ipAddress: string;
  reason: string;
  bannedByUserId: string | null;
  bannedByUsername: string | null;
  createdAt: Date | null;
  expiresAt: Date | null;
  isActive: boolean;
}

export const mapIpBanRecord = (raw: RawIpBanRecord): IpBanRecordData => ({
  id: raw.id,
  ipAddress: raw.ip_address,
  reason: raw.reason,
  bannedByUserId:
    raw.banned_by !== null && raw.banned_by !== undefined
      ? String(raw.banned_by)
      : null,
  bannedByUsername: raw.banned_by_username ?? null,
  createdAt: parseDate(raw.created_at),
  expiresAt: parseDate(raw.expires_at ?? undefined),
  isActive: (() => {
    if (typeof raw.is_active === "boolean") {
      return raw.is_active;
    }
    if (raw.is_active === null || raw.is_active === undefined) {
      return true;
    }
    return Number(raw.is_active) === 1;
  })(),
});

export const RawLockedAccountSchema = z.object({
  id: z.union([z.string(), numberLike]).transform(String),
  username: z.string(),
  email: z.string(),
  role: z.string(),
  locked_until: z.string().nullable().optional(),
  locked_reason: z.string().nullable().optional(),
  created_at: z.string(),
});

export type RawLockedAccount = z.infer<typeof RawLockedAccountSchema>;

export interface LockedAccountData {
  id: string;
  username: string;
  email: string;
  role: string;
  lockedUntil: Date | null;
  lockedReason: string | null;
  createdAt: Date | null;
}

export const mapLockedAccount = (raw: RawLockedAccount): LockedAccountData => ({
  id: raw.id,
  username: raw.username,
  email: raw.email,
  role: raw.role,
  lockedUntil: parseDate(raw.locked_until ?? undefined),
  lockedReason: raw.locked_reason ?? null,
  createdAt: parseDate(raw.created_at),
});
