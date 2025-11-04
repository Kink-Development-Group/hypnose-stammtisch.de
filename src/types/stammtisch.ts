import type { CountryInfo } from "../classes/CountryMetadata";
import { CountryCode } from "../enums/countryCode";
import { LocationStatus } from "../enums/locationStatus";

/**
 * Geographic coordinates using WGS84 latitude/longitude.
 */
export interface MapCoordinates {
  lat: number;
  lng: number;
}

/**
 * Contact channels associated with a Stammtisch location.
 */
export interface StammtischContact {
  email?: string;
  phone?: string;
  website?: string;
  telegram?: string;
  discord?: string;
}

/**
 * Meeting details for a Stammtisch location.
 */
export interface StammtischMeetingInfo {
  /** Describes the recurrence, e.g. "Jeden 2. Samstag im Monat" */
  frequency?: string;
  /** Venue or general place description */
  location?: string;
  /** Optional street level address */
  address?: string;
  /** ISO string representing the next planned meeting */
  nextMeeting?: string;
}

/**
 * Normalized Stammtisch location entity used on the frontend.
 */
export interface StammtischLocation {
  id: string;
  name: string;
  slug?: string;
  city: string;
  /** Bundesland (DE/AT) bzw. Kanton (CH) */
  region: string;
  country: CountryCode;
  coordinates: MapCoordinates;
  description: string;
  contact: StammtischContact;
  meetingInfo: StammtischMeetingInfo;
  /** Taxonomy tags such as "anfängerfreundlich" */
  tags: string[];
  isActive: boolean;
  status: LocationStatus;
  createdBy?: number;
  createdByUsername?: string;
  createdAt?: string;
  lastUpdated?: string;
}

/**
 * Viewport information for the Leaflet map.
 */
export interface MapViewport {
  center: {
    lat: number;
    lng: number;
  };
  zoom: number;
}

export interface MapFilter {
  countries: CountryCode[];
  regions: string[];
  tags: string[];
  activeOnly: boolean;
}

/**
 * Metadata required to populate filter dropdowns on the map view.
 */
export interface StammtischLocationMeta {
  regions: string[];
  tags: string[];
  countries: CountryInfo[];
}
