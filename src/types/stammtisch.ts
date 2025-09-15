/**
 * Stammtisch location data types
 */

export interface StammtischLocation {
  id: string;
  name: string;
  city: string;
  region: string; // Bundesland/Kanton
  country: "DE" | "AT" | "CH";
  coordinates: {
    lat: number;
    lng: number;
  };
  description: string;
  contact: {
    email?: string;
    website?: string;
    telegram?: string;
    discord?: string;
  };
  meetingInfo: {
    frequency: string; // e.g., "Jeden 2. Samstag im Monat"
    location: string; // Meeting venue
    nextMeeting?: string; // Next meeting date if known
  };
  tags: string[]; // e.g., ['anfängerfreundlich', 'erfahren', 'praxis', 'theorie']
  isActive: boolean;
  lastUpdated: string; // ISO date string
}

export interface MapViewport {
  center: {
    lat: number;
    lng: number;
  };
  zoom: number;
}

export interface MapFilter {
  countries: string[];
  regions: string[];
  tags: string[];
  activeOnly: boolean;
}
