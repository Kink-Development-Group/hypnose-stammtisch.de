/**
 * Domain objects for admin security data with helper methods.
 */
import {
  mapFailedLoginRecord,
  mapIpBanRecord,
  mapLockedAccount,
  mapSecurityStats,
  RawFailedLoginRecordSchema,
  RawIpBanRecordSchema,
  RawLockedAccountSchema,
  RawSecurityStatsSchema,
  type FailedLoginRecordData,
  type IpBanRecordData,
  type LockedAccountData,
  type RawFailedLoginRecord,
  type RawIpBanRecord,
  type RawLockedAccount,
  type RawSecurityStats,
  type SecurityStatsData,
} from "../types/security";
import type { Locale } from "../utils/i18n";
import { formatDateTime } from "../utils/i18n";

/**
 * Convert unknown API payload to typed security stats.
 */
export class SecurityStats {
  public readonly failedLogins24h: number;
  public readonly activeIpBans: number;
  public readonly lockedAccounts: number;
  public readonly uniqueIpsFailed24h: number;

  private constructor(data: SecurityStatsData) {
    this.failedLogins24h = data.failedLogins24h;
    this.activeIpBans = data.activeIpBans;
    this.lockedAccounts = data.lockedAccounts;
    this.uniqueIpsFailed24h = data.uniqueIpsFailed24h;
  }

  /**
   * Parse API data into a {@link SecurityStats} instance.
   */
  public static fromApi(raw: unknown): SecurityStats {
    const parsed = RawSecurityStatsSchema.parse(raw as RawSecurityStats);
    return new SecurityStats(mapSecurityStats(parsed));
  }
}

/**
 * Represents a failed login event with helper accessors.
 */
export class FailedLoginRecord {
  private readonly data: FailedLoginRecordData;

  private constructor(data: FailedLoginRecordData) {
    this.data = data;
  }

  /**
   * Convert API payload into a {@link FailedLoginRecord}.
   */
  public static fromApi(
    raw: RawFailedLoginRecord | unknown,
  ): FailedLoginRecord {
    const parsed = RawFailedLoginRecordSchema.parse(raw);
    return new FailedLoginRecord(mapFailedLoginRecord(parsed));
  }

  /** Identifier of the failed login entry. */
  public get id(): string {
    return this.data.id;
  }

  /** Related account identifier (if available). */
  public get accountId(): string | null {
    return this.data.accountId;
  }

  /** Entered username or email during the attempt. */
  public get usernameEntered(): string | null {
    return this.data.usernameEntered;
  }

  /** IP address used for the attempt. */
  public get ipAddress(): string {
    return this.data.ipAddress;
  }

  /** Browser user agent string (if captured). */
  public get userAgent(): string | null {
    return this.data.userAgent;
  }

  /** Timestamp of the attempt. */
  public get createdAt(): Date | null {
    return this.data.createdAt;
  }

  /** Matched account username when available. */
  public get username(): string | null {
    return this.data.username;
  }

  /** Matched account email when available. */
  public get email(): string | null {
    return this.data.email;
  }

  /**
   * Format the timestamp for display purposes.
   */
  public formatCreatedAt(locale: Locale): string {
    return formatDateTime(
      this.data.createdAt,
      {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      },
      locale,
    );
  }

  /**
   * Return a plain JavaScript object representation.
   */
  public toJSON(): FailedLoginRecordData {
    return { ...this.data };
  }
}

/**
 * Represents an IP ban including metadata helpers.
 */
export class IpBanRecord {
  private readonly data: IpBanRecordData;

  private constructor(data: IpBanRecordData) {
    this.data = data;
  }

  /**
   * Convert API payload into an {@link IpBanRecord}.
   */
  public static fromApi(raw: RawIpBanRecord | unknown): IpBanRecord {
    const parsed = RawIpBanRecordSchema.parse(raw);
    return new IpBanRecord(mapIpBanRecord(parsed));
  }

  public get id(): string {
    return this.data.id;
  }

  public get ipAddress(): string {
    return this.data.ipAddress;
  }

  public get reason(): string {
    return this.data.reason;
  }

  public get bannedByUserId(): string | null {
    return this.data.bannedByUserId;
  }

  public get bannedByUsername(): string | null {
    return this.data.bannedByUsername;
  }

  public get createdAt(): Date | null {
    return this.data.createdAt;
  }

  public get expiresAt(): Date | null {
    return this.data.expiresAt;
  }

  public get isActive(): boolean {
    return this.data.isActive;
  }

  /** Whether the ban has an expiration date in the future. */
  public get isTemporary(): boolean {
    return Boolean(this.data.expiresAt);
  }

  /**
   * Indicates if the ban is already expired.
   */
  public get isExpired(): boolean {
    if (!this.data.expiresAt) {
      return false;
    }
    const now = Date.now();
    return this.data.expiresAt.getTime() <= now;
  }

  public formatCreatedAt(locale: Locale): string {
    return formatDateTime(this.data.createdAt, undefined, locale);
  }

  public formatExpiresAt(locale: Locale): string {
    return formatDateTime(this.data.expiresAt, undefined, locale);
  }

  public toJSON(): IpBanRecordData {
    return { ...this.data };
  }
}

/**
 * Represents a locked account entry.
 */
export class LockedAccount {
  private readonly data: LockedAccountData;

  private constructor(data: LockedAccountData) {
    this.data = data;
  }

  /**
   * Convert API payload into {@link LockedAccount}.
   */
  public static fromApi(raw: RawLockedAccount | unknown): LockedAccount {
    const parsed = RawLockedAccountSchema.parse(raw);
    return new LockedAccount(mapLockedAccount(parsed));
  }

  public get id(): string {
    return this.data.id;
  }

  public get username(): string {
    return this.data.username;
  }

  public get email(): string {
    return this.data.email;
  }

  public get role(): string {
    return this.data.role;
  }

  public get lockedUntil(): Date | null {
    return this.data.lockedUntil;
  }

  public get lockedReason(): string | null {
    return this.data.lockedReason;
  }

  public get createdAt(): Date | null {
    return this.data.createdAt;
  }

  public formatLockedUntil(locale: Locale): string {
    return formatDateTime(this.data.lockedUntil, undefined, locale);
  }

  public formatCreatedAt(locale: Locale): string {
    return formatDateTime(
      this.data.createdAt,
      {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
      },
      locale,
    );
  }

  public toJSON(): LockedAccountData {
    return { ...this.data };
  }
}
