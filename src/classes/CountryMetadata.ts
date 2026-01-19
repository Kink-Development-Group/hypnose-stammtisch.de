/**
 * Utilities for dealing with country metadata within the DACH map module.
 */
import { CountryCode, DACH_COUNTRIES } from "../enums/countryCode";
import { t, type TranslationKey } from "../utils/i18n";

/**
 * Metadata that describes a supported country entry.
 */
export interface CountryInfo {
  code: CountryCode;
  flag: string;
  /**
   * Translation key that resolves to the localized country name.
   */
  labelKey: TranslationKey;
  /**
   * Optional human readable fallback provided by the API.
   */
  name?: string;
}

const COUNTRY_DATA: Record<CountryCode, CountryInfo> = {
  [CountryCode.GERMANY]: {
    code: CountryCode.GERMANY,
    flag: "🇩🇪",
    labelKey: "country.de",
    name: "Deutschland",
  },
  [CountryCode.AUSTRIA]: {
    code: CountryCode.AUSTRIA,
    flag: "🇦🇹",
    labelKey: "country.at",
    name: "Österreich",
  },
  [CountryCode.SWITZERLAND]: {
    code: CountryCode.SWITZERLAND,
    flag: "🇨🇭",
    labelKey: "country.ch",
    name: "Schweiz",
  },
};

/**
 * Helper functions for normalizing and presenting country metadata.
 */
export class CountryMetadata {
  /**
   * Return all supported countries in their default ordering.
   */
  static getSupportedCountries(): CountryInfo[] {
    return DACH_COUNTRIES.map((code) => this.getCountryInfo(code));
  }

  /**
   * Return the localized label for the provided country code.
   */
  static getDisplayName(code: CountryCode, fallbackName?: string): string {
    const info = this.getCountryInfo(code);
    return t(info.labelKey as TranslationKey, {
      fallback: fallbackName ?? info.name ?? code,
    });
  }

  /**
   * Normalize arbitrary input to a supported {@link CountryCode}.
   * Defaults to Germany when the value cannot be mapped safely.
   */
  static normalizeCode(value: unknown): CountryCode {
    if (typeof value === "string") {
      const upper = value.trim().toUpperCase();

      const directMatch = (Object.values(CountryCode) as string[]).find(
        (code) => code === upper,
      );
      if (directMatch) {
        return directMatch as CountryCode;
      }

      const enumKeyMatch = upper as keyof typeof CountryCode;
      if (enumKeyMatch in CountryCode) {
        return CountryCode[enumKeyMatch];
      }
    }
    return CountryCode.GERMANY;
  }

  /**
   * Try to build a {@link CountryInfo} structure from API data.
   */
  static resolveInfo(
    entry: Partial<CountryInfo> & { code?: string | CountryCode },
  ): CountryInfo | null {
    if (!entry || !entry.code) {
      return null;
    }

    const code = this.normalizeCode(entry.code);
    const defaults = this.getCountryInfo(code);

    return {
      code,
      flag: entry.flag ?? defaults.flag,
      labelKey: entry.labelKey ?? defaults.labelKey,
      name: entry.name ?? defaults.name,
    };
  }

  /**
   * Provide metadata for a single country code.
   */
  static getCountryInfo(code: CountryCode): CountryInfo {
    return COUNTRY_DATA[code] ?? COUNTRY_DATA[CountryCode.GERMANY];
  }

  /**
   * Provide the default list of country codes.
   */
  static getDefaultCountryCodes(): CountryCode[] {
    return [...DACH_COUNTRIES];
  }
}
