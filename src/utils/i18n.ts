/**
 * Lightweight internationalization helper with typed translation keys.
 * Supports runtime locale switching and value interpolation.
 */
import { get, writable } from "svelte/store";

export type Locale = "de" | "en";

/**
 * Translation dictionary. Extend with additional locales/keys as needed.
 */
const translations: Record<Locale, Record<string, string>> = {
  de: {
    "adminSecurity.title": "Sicherheitsverwaltung",
    "adminSecurity.subtitle":
      "Überwache fehlgeschlagene Logins, IP-Sperren und gesperrte Konten.",
    "adminSecurity.lastUpdated": "Zuletzt aktualisiert: {{value}}",
    "adminSecurity.refresh": "Aktualisieren",
    "adminSecurity.stats.failedLogins": "Fehlgeschlagene Logins (24h)",
    "adminSecurity.stats.activeBans": "Aktive IP-Sperren",
    "adminSecurity.stats.lockedAccounts": "Gesperrte Konten",
    "adminSecurity.stats.uniqueIps": "Einzigartige IPs (24h)",
    "adminSecurity.failedLogins.title": "Fehlgeschlagene Login-Versuche",
    "adminSecurity.failedLogins.description":
      "Analysiere fehlgeschlagene Logins und erkenne Muster verdächtiger Aktivitäten.",
    "adminSecurity.failedLogins.empty":
      "Keine fehlgeschlagenen Logins gefunden.",
    "adminSecurity.lockedAccounts.title": "Gesperrte Benutzerkonten",
    "adminSecurity.lockedAccounts.description":
      "Verwalte gesperrte Konten und stelle den Zugriff bei Bedarf wieder her.",
    "adminSecurity.lockedAccounts.empty": "Aktuell sind keine Konten gesperrt.",
    "adminSecurity.lockedAccounts.unlock": "Konto entsperren",
    "adminSecurity.ipBans.title": "IP-Sperren",
    "adminSecurity.ipBans.description":
      "Überwache und verwalte IP-Sperren zum Schutz vor Brute-Force-Angriffen.",
    "adminSecurity.ipBans.empty": "Es sind keine aktiven IP-Sperren vorhanden.",
    "adminSecurity.ipBans.remove": "IP-Sperre aufheben",
    "adminSecurity.ipBans.create.title": "IP manuell sperren",
    "adminSecurity.ipBans.create.ip": "IP-Adresse",
    "adminSecurity.ipBans.create.reason": "Begründung",
    "adminSecurity.ipBans.create.submit": "IP sperren",
    "adminSecurity.ipBans.create.ipPlaceholder": "z. B. 203.0.113.42",
    "adminSecurity.ipBans.create.reasonPlaceholder": "Verdächtige Aktivität",
    "adminSecurity.maintenance.cleanup": "Abgelaufene Sperren bereinigen",
    "adminSecurity.notifications.unlockSuccess":
      "Konto wurde erfolgreich entsperrt.",
    "adminSecurity.notifications.unlockError":
      "Konto konnte nicht entsperrt werden.",
    "adminSecurity.notifications.unbanSuccess": "IP-Sperre wurde entfernt.",
    "adminSecurity.notifications.unbanError":
      "IP-Sperre konnte nicht entfernt werden.",
    "adminSecurity.notifications.banSuccess": "IP wurde gesperrt.",
    "adminSecurity.notifications.banError": "IP konnte nicht gesperrt werden.",
    "adminSecurity.notifications.cleanupSuccess": "Bereinigung abgeschlossen.",
    "adminSecurity.notifications.cleanupError": "Bereinigung fehlgeschlagen.",
    "adminSecurity.errors.permission":
      "Sie haben keine Berechtigung, die Sicherheitsverwaltung zu nutzen.",
    "adminSecurity.errors.load": "Daten konnten nicht geladen werden.",
    "adminSecurity.table.ip": "IP-Adresse",
    "adminSecurity.table.username": "Benutzername",
    "adminSecurity.table.email": "E-Mail",
    "adminSecurity.table.reason": "Grund",
    "adminSecurity.table.createdAt": "Erstellt am",
    "adminSecurity.table.expiresAt": "Ablauf",
    "adminSecurity.table.lockedUntil": "Gesperrt bis",
    "adminSecurity.table.userAgent": "User-Agent",
    "adminSecurity.table.actions": "Aktionen",
    "adminSecurity.table.role": "Rolle",
    "adminSecurity.table.attempted": "Versuchter Benutzer",
    "adminSecurity.table.bannedBy": "Gesperrt von",
    "adminSecurity.form.required": "Pflichtfeld",
    "adminSecurity.form.invalidIp": "Ungültige IP-Adresse.",
  },
  en: {
    "adminSecurity.title": "Security Management",
    "adminSecurity.subtitle":
      "Monitor failed logins, IP bans, and locked user accounts.",
    "adminSecurity.lastUpdated": "Last updated: {{value}}",
    "adminSecurity.refresh": "Refresh",
    "adminSecurity.stats.failedLogins": "Failed logins (24h)",
    "adminSecurity.stats.activeBans": "Active IP bans",
    "adminSecurity.stats.lockedAccounts": "Locked accounts",
    "adminSecurity.stats.uniqueIps": "Unique IPs (24h)",
    "adminSecurity.failedLogins.title": "Failed login attempts",
    "adminSecurity.failedLogins.description":
      "Analyze failed logins to uncover suspicious activity patterns.",
    "adminSecurity.failedLogins.empty": "No failed logins found.",
    "adminSecurity.lockedAccounts.title": "Locked user accounts",
    "adminSecurity.lockedAccounts.description":
      "Manage locked accounts and restore access when appropriate.",
    "adminSecurity.lockedAccounts.empty": "No accounts are locked right now.",
    "adminSecurity.lockedAccounts.unlock": "Unlock account",
    "adminSecurity.ipBans.title": "IP bans",
    "adminSecurity.ipBans.description":
      "Monitor and manage IP bans to protect against brute-force attacks.",
    "adminSecurity.ipBans.empty": "No active IP bans.",
    "adminSecurity.ipBans.remove": "Remove IP ban",
    "adminSecurity.ipBans.create.title": "Ban IP manually",
    "adminSecurity.ipBans.create.ip": "IP address",
    "adminSecurity.ipBans.create.reason": "Reason",
    "adminSecurity.ipBans.create.submit": "Ban IP",
    "adminSecurity.ipBans.create.ipPlaceholder": "e.g. 203.0.113.42",
    "adminSecurity.ipBans.create.reasonPlaceholder": "Suspicious activity",
    "adminSecurity.maintenance.cleanup": "Clean up expired bans",
    "adminSecurity.notifications.unlockSuccess":
      "Account unlocked successfully.",
    "adminSecurity.notifications.unlockError": "Account could not be unlocked.",
    "adminSecurity.notifications.unbanSuccess": "IP ban removed.",
    "adminSecurity.notifications.unbanError": "IP ban could not be removed.",
    "adminSecurity.notifications.banSuccess": "IP banned successfully.",
    "adminSecurity.notifications.banError": "IP could not be banned.",
    "adminSecurity.notifications.cleanupSuccess": "Cleanup completed.",
    "adminSecurity.notifications.cleanupError": "Cleanup failed.",
    "adminSecurity.errors.permission":
      "You do not have permission to access the security console.",
    "adminSecurity.errors.load": "Unable to load data.",
    "adminSecurity.table.ip": "IP address",
    "adminSecurity.table.username": "Username",
    "adminSecurity.table.email": "Email",
    "adminSecurity.table.reason": "Reason",
    "adminSecurity.table.createdAt": "Created",
    "adminSecurity.table.expiresAt": "Expires",
    "adminSecurity.table.lockedUntil": "Locked until",
    "adminSecurity.table.userAgent": "User agent",
    "adminSecurity.table.actions": "Actions",
    "adminSecurity.table.role": "Role",
    "adminSecurity.table.attempted": "Attempted user",
    "adminSecurity.table.bannedBy": "Banned by",
    "adminSecurity.form.required": "Required field",
    "adminSecurity.form.invalidIp": "Invalid IP address.",
  },
};

type TranslationDictionary = (typeof translations)[Locale];
export type TranslationKey = keyof TranslationDictionary;

/**
 * Writable store for the active locale.
 */
export const locale = writable<Locale>("de");

/**
 * Change the active locale.
 *
 * @param nextLocale - Locale identifier to activate
 */
export function setLocale(nextLocale: Locale): void {
  locale.set(nextLocale);
}

/**
 * Resolve a translation key for the currently active locale.
 *
 * @param key - Translation key defined in the dictionary
 * @param options - Optional interpolation values and overrides
 * @returns Translated and interpolated string
 */
export function t(
  key: TranslationKey,
  options?: {
    locale?: Locale;
    values?: Record<string, string | number>;
    fallback?: string;
  },
): string {
  const activeLocale = options?.locale ?? get(locale);
  const dictionary = translations[activeLocale] ?? translations.de;
  const template = dictionary[key] ?? options?.fallback ?? key;

  if (!options?.values) {
    return template;
  }

  return Object.entries(options.values).reduce<string>(
    (acc, [label, value]) => {
      const placeholder = `{{${label}}}`;
      return acc.split(placeholder).join(String(value));
    },
    template,
  );
}

/**
 * Format a date using the active locale.
 *
 * @param date - Date to format (null returns a dash)
 * @param options - Intl date-time formatting options
 */
export function formatDateTime(
  date: Date | null,
  options: Intl.DateTimeFormatOptions = {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  },
  localeOverride?: Locale,
): string {
  if (!date) {
    return "-";
  }

  const activeLocale = localeOverride ?? get(locale);
  return new Intl.DateTimeFormat(activeLocale, options).format(date);
}
