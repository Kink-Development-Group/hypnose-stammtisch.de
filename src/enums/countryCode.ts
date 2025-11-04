/**
 * ISO 3166-1 alpha-2 country codes used within the DACH map module.
 */
export enum CountryCode {
  GERMANY = "DE",
  AUSTRIA = "AT",
  SWITZERLAND = "CH",
}

/**
 * Ordered list of supported DACH countries.
 */
export const DACH_COUNTRIES: readonly CountryCode[] = [
  CountryCode.GERMANY,
  CountryCode.AUSTRIA,
  CountryCode.SWITZERLAND,
];
