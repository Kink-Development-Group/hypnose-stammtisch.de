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
    // Countries
    "country.de": "Deutschland",
    "country.at": "Österreich",
    "country.ch": "Schweiz",

    // Role names
    "role.headAdmin": "Head Admin",
    "role.admin": "Administrator",
    "role.moderator": "Moderator",
    "role.eventManager": "Event-Manager",
    "role.unknown": "Unbekannt",

    // Date/Time
    "datetime.never": "Nie",

    // Admin Security
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

    // Map
    "map.meta.title": "Stammtisch-Karte - Hypnose Stammtisch",
    "map.meta.description":
      "Interaktive Karte aller Hypnose-Stammtische in Deutschland, Österreich und der Schweiz.",
    "map.meta.keywords":
      "Hypnose Stammtisch, Karte, Deutschland, Österreich, Schweiz, DACH, Standorte, Treffen",
    "map.view.aria": "Interaktive Karte mit Stammtisch-Standorten",
    "map.loading.map": "Karte wird geladen...",
    "map.loading.locations": "Stammtische werden geladen...",
    "map.loading.error": "Fehler beim Laden der Stammtische: {{message}}",
    "map.hero.headline": "🗺️ Stammtisch-Standorte",
    "map.hero.lead":
      "Entdecke Hypnose-Stammtische in der DACH-Region und werde Teil unserer Community.",
    "map.stats.locationsLabel": "Standorte",
    "map.stats.regionLabel": "DACH-Region",
    "map.stats.liveUpdatesLabel": "Live-Updates",
    "map.filter.toggle": "Filter",
    "map.filter.activeCount": "{{count}} aktiv",
    "map.filter.aria.panel": "Filter-Optionen",
    "map.filter.section.countries": "Länder",
    "map.filter.section.regions": "Regionen",
    "map.filter.section.tags": "Tags",
    "map.filter.tagToggle": "Tag {{tag}} umschalten",
    "map.filter.activeOnly": "Nur aktive Stammtische anzeigen",
    "map.filter.reset": "Filter zurücksetzen",
    "map.popup.location": "📍 {{city}}, {{region}}",
    "map.popup.frequency": "🗓️ {{frequency}}",
    "map.popup.more": "Mehr Details →",
    "map.tooltip.clickForDetails": "Klicken für Details",
    "map.marker.ariaLabel": "Stammtisch {{name}} in {{city}}",
    "map.details.locationSummary": "📍 {{city}}, {{region}}, {{country}}",
    "map.details.close": "Schließen",
    "map.details.aboutTitle": "Über diesen Stammtisch",
    "map.details.meetingsTitle": "📅 Treffen",
    "map.details.labels.frequency": "Häufigkeit",
    "map.details.labels.location": "Ort",
    "map.details.labels.nextMeeting": "Nächstes Treffen",
    "map.details.frequencyUnknown": "Noch nicht bekannt",
    "map.details.locationUnknown": "Noch nicht bekannt",
    "map.details.nextMeetingUnknown": "Noch nicht bekannt",
    "map.details.nextMeetingInvalid": "Ungültiges Datum",
    "map.details.tagsTitle": "🏷️ Charakteristika",
    "map.details.contactTitle": "💬 Kontakt",
    "map.details.contact.email": "E-Mail schreiben",
    "map.details.contact.website": "Website besuchen",
    "map.details.contact.phone": "Anrufen",
    "map.details.contact.fetlife": "FetLife: {{handle}}",
    "map.details.contact.discord": "Discord: {{handle}}",
    "map.details.contact.discordCopied": "Discord-Handle kopiert: {{handle}}",
    "map.details.contact.copyFallback": "Handle manuell kopieren: {{handle}}",
    "map.details.status.active": "🟢 Aktiv",
    "map.details.status.inactive": "🔴 Inaktiv",
    "map.details.lastUpdated": "Letzte Aktualisierung: {{date}}",
    "map.details.lastUpdatedUnknown": "Unbekannt",
    "map.footer.single": "{{count}} Stammtisch auf der Karte",
    "map.footer.plural": "{{count}} Stammtische auf der Karte",
    "map.info.howTo.title": "🎯 So nutzt du die Karte",
    "map.info.howTo.step1": "Klicke auf Markierungen für Details",
    "map.info.howTo.step2": "Nutze die Filter für gezielte Suche",
    "map.info.howTo.step3": "Zoome für mehr Übersicht",
    "map.info.howTo.step4": "Kontaktiere Stammtische direkt",
    "map.info.add.title": "➕ Stammtisch hinzufügen",
    "map.info.add.description":
      "Dein Stammtisch fehlt? Melde dich bei uns oder reiche ein neues Event ein.",
    "map.info.add.contactButton": "📧 Kontakt aufnehmen",
    "map.info.add.submitButton": "📅 Event einreichen",
    "map.related.title": "🔗 Weitere Ressourcen",
    "map.related.events": "Events",
    "map.related.safety": "Sicherheit",
    "map.related.codeOfConduct": "Verhaltenskodex",
    "map.related.about": "Über uns",

    // Admin Stammtisch Locations
    "adminLocations.modal.titleCreate": "Neuen Standort erstellen",
    "adminLocations.modal.titleEdit": "Standort bearbeiten",
    "adminLocations.modal.close": "Schließen",
    "adminLocations.modal.reset": "Zurücksetzen",
    "adminLocations.form.nameLabel": "Name",
    "adminLocations.form.namePlaceholder": "Hypnose Stammtisch Berlin",
    "adminLocations.form.cityLabel": "Stadt",
    "adminLocations.form.cityPlaceholder": "Berlin",
    "adminLocations.form.regionLabel": "Region/Bundesland",
    "adminLocations.form.regionPlaceholder": "Berlin",
    "adminLocations.form.countryLabel": "Land",
    "adminLocations.form.latitudeLabel": "Breitengrad",
    "adminLocations.form.latitudePlaceholder": "52.52",
    "adminLocations.form.longitudeLabel": "Längengrad",
    "adminLocations.form.longitudePlaceholder": "13.405",
    "adminLocations.form.descriptionLabel": "Beschreibung",
    "adminLocations.form.descriptionPlaceholder":
      "Beschreibung des Stammtisches...",
    "adminLocations.form.contactSectionTitle": "Kontaktinformationen",
    "adminLocations.form.emailLabel": "E-Mail",
    "adminLocations.form.emailPlaceholder": "kontakt@example.com",
    "adminLocations.form.phoneLabel": "Telefon",
    "adminLocations.form.phonePlaceholder": "+49 30 12345678",
    "adminLocations.form.fetlifeLabel": "FetLife",
    "adminLocations.form.fetlifePlaceholder": "@HypnoseBerlin",
    "adminLocations.form.websiteLabel": "Website",
    "adminLocations.form.websitePlaceholder": "https://example.com",
    "adminLocations.form.meetingSectionTitle": "Treffen-Informationen",
    "adminLocations.form.frequencyLabel": "Häufigkeit",
    "adminLocations.form.frequencyPlaceholder": "Jeden 1. Samstag im Monat",
    "adminLocations.form.locationLabel": "Ort",
    "adminLocations.form.locationPlaceholder": "Kulturzentrum Mitte",
    "adminLocations.form.addressLabel": "Adresse",
    "adminLocations.form.addressPlaceholder": "Musterstraße 123, 10115 Berlin",
    "adminLocations.form.nextMeetingLabel": "Nächstes Treffen",
    "adminLocations.form.tagsSectionTitle": "Tags",
    "adminLocations.form.tagsAvailable": "Verfügbare Tags:",
    "adminLocations.form.tagInputPlaceholder": "Neuen Tag hinzufügen...",
    "adminLocations.form.tagAdd": "Hinzufügen",
    "adminLocations.form.statusLabel": "Status",
    "adminLocations.form.statusDraft": "Entwurf",
    "adminLocations.form.statusPublished": "Veröffentlicht",
    "adminLocations.form.statusArchived": "Archiviert",
    "adminLocations.form.isActiveLabel": "Aktiv",
    "adminLocations.form.cancel": "Abbrechen",
    "adminLocations.form.create": "Erstellen",
    "adminLocations.form.update": "Aktualisieren",
    "adminLocations.form.required": "Pflichtfeld",
  },
  en: {
    // Countries
    "country.de": "Germany",
    "country.at": "Austria",
    "country.ch": "Switzerland",

    // Role names
    "role.headAdmin": "Head Admin",
    "role.admin": "Administrator",
    "role.moderator": "Moderator",
    "role.eventManager": "Event Manager",
    "role.unknown": "Unknown",

    // Date/Time
    "datetime.never": "Never",

    // Admin Security
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

    // Map
    "map.meta.title": "Stammtisch Map - Hypnose Stammtisch",
    "map.meta.description":
      "Interactive map of all Hypnose Stammtisch meetups in Germany, Austria and Switzerland.",
    "map.meta.keywords":
      "Hypnose Stammtisch, map, Germany, Austria, Switzerland, DACH, locations, meetup",
    "map.view.aria": "Interactive map showing Stammtisch locations",
    "map.loading.map": "Loading map...",
    "map.loading.locations": "Loading meetups...",
    "map.loading.error": "Failed to load meetups: {{message}}",
    "map.hero.headline": "🗺️ Stammtisch Map",
    "map.hero.lead":
      "Discover Hypnose Stammtisch meetups across the DACH region and join the community.",
    "map.stats.locationsLabel": "Locations",
    "map.stats.regionLabel": "DACH region",
    "map.stats.liveUpdatesLabel": "Live updates",
    "map.filter.toggle": "Filter",
    "map.filter.activeCount": "{{count}} active",
    "map.filter.aria.panel": "Filter options",
    "map.filter.section.countries": "Countries",
    "map.filter.section.regions": "Regions",
    "map.filter.section.tags": "Tags",
    "map.filter.tagToggle": "Toggle tag {{tag}}",
    "map.filter.activeOnly": "Show only active meetups",
    "map.filter.reset": "Reset filters",
    "map.popup.location": "📍 {{city}}, {{region}}",
    "map.popup.frequency": "🗓️ {{frequency}}",
    "map.popup.more": "More details →",
    "map.tooltip.clickForDetails": "Click for details",
    "map.marker.ariaLabel": "Stammtisch {{name}} in {{city}}",
    "map.details.locationSummary": "📍 {{city}}, {{region}}, {{country}}",
    "map.details.close": "Close",
    "map.details.aboutTitle": "About this meetup",
    "map.details.meetingsTitle": "📅 Meetings",
    "map.details.labels.frequency": "Frequency",
    "map.details.labels.location": "Venue",
    "map.details.labels.nextMeeting": "Next meeting",
    "map.details.frequencyUnknown": "Not yet known",
    "map.details.locationUnknown": "Not yet known",
    "map.details.nextMeetingUnknown": "Not yet known",
    "map.details.nextMeetingInvalid": "Invalid date",
    "map.details.tagsTitle": "🏷️ Characteristics",
    "map.details.contactTitle": "💬 Contact",
    "map.details.contact.email": "Send email",
    "map.details.contact.website": "Visit website",
    "map.details.contact.phone": "Call",
    "map.details.contact.fetlife": "FetLife: {{handle}}",
    "map.details.contact.discord": "Discord: {{handle}}",
    "map.details.contact.discordCopied": "Discord handle copied: {{handle}}",
    "map.details.contact.copyFallback": "Copy handle manually: {{handle}}",
    "map.details.status.active": "🟢 Active",
    "map.details.status.inactive": "🔴 Inactive",
    "map.details.lastUpdated": "Last updated: {{date}}",
    "map.details.lastUpdatedUnknown": "Unknown",
    "map.footer.single": "{{count}} Stammtisch on the map",
    "map.footer.plural": "{{count}} Stammtische on the map",
    "map.info.howTo.title": "🎯 How to use the map",
    "map.info.howTo.step1": "Click on markers for details",
    "map.info.howTo.step2": "Use filters for targeted search",
    "map.info.howTo.step3": "Zoom for better overview",
    "map.info.howTo.step4": "Contact meetups directly",
    "map.info.add.title": "➕ Add a meetup",
    "map.info.add.description":
      "Missing your Stammtisch? Contact us or submit a new event.",
    "map.info.add.contactButton": "📧 Get in touch",
    "map.info.add.submitButton": "📅 Submit event",
    "map.related.title": "🔗 Additional resources",
    "map.related.events": "Events",
    "map.related.safety": "Safety",
    "map.related.codeOfConduct": "Code of Conduct",
    "map.related.about": "About us",

    // Admin Stammtisch Locations
    "adminLocations.modal.titleCreate": "Create new location",
    "adminLocations.modal.titleEdit": "Edit location",
    "adminLocations.modal.close": "Close",
    "adminLocations.modal.reset": "Reset",
    "adminLocations.form.nameLabel": "Name",
    "adminLocations.form.namePlaceholder": "Hypnose Stammtisch Berlin",
    "adminLocations.form.cityLabel": "City",
    "adminLocations.form.cityPlaceholder": "Berlin",
    "adminLocations.form.regionLabel": "Region/State",
    "adminLocations.form.regionPlaceholder": "Berlin",
    "adminLocations.form.countryLabel": "Country",
    "adminLocations.form.latitudeLabel": "Latitude",
    "adminLocations.form.latitudePlaceholder": "52.52",
    "adminLocations.form.longitudeLabel": "Longitude",
    "adminLocations.form.longitudePlaceholder": "13.405",
    "adminLocations.form.descriptionLabel": "Description",
    "adminLocations.form.descriptionPlaceholder":
      "Description of the meetup...",
    "adminLocations.form.contactSectionTitle": "Contact information",
    "adminLocations.form.emailLabel": "Email",
    "adminLocations.form.emailPlaceholder": "contact@example.com",
    "adminLocations.form.phoneLabel": "Phone",
    "adminLocations.form.phonePlaceholder": "+49 30 12345678",
    "adminLocations.form.fetlifeLabel": "FetLife",
    "adminLocations.form.fetlifePlaceholder": "@HypnoseBerlin",
    "adminLocations.form.websiteLabel": "Website",
    "adminLocations.form.websitePlaceholder": "https://example.com",
    "adminLocations.form.meetingSectionTitle": "Meeting information",
    "adminLocations.form.frequencyLabel": "Frequency",
    "adminLocations.form.frequencyPlaceholder":
      "Every 1st Saturday of the month",
    "adminLocations.form.locationLabel": "Location",
    "adminLocations.form.locationPlaceholder": "Community Center",
    "adminLocations.form.addressLabel": "Address",
    "adminLocations.form.addressPlaceholder": "Main Street 123, 10115 Berlin",
    "adminLocations.form.nextMeetingLabel": "Next meeting",
    "adminLocations.form.tagsSectionTitle": "Tags",
    "adminLocations.form.tagsAvailable": "Available tags:",
    "adminLocations.form.tagInputPlaceholder": "Add new tag...",
    "adminLocations.form.tagAdd": "Add",
    "adminLocations.form.statusLabel": "Status",
    "adminLocations.form.statusDraft": "Draft",
    "adminLocations.form.statusPublished": "Published",
    "adminLocations.form.statusArchived": "Archived",
    "adminLocations.form.isActiveLabel": "Active",
    "adminLocations.form.cancel": "Cancel",
    "adminLocations.form.create": "Create",
    "adminLocations.form.update": "Update",
    "adminLocations.form.required": "Required field",
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
