/**
 * Factory utilities to normalize Stammtisch location payloads coming from the API.
 */
import { z } from "zod";
import { CountryCode } from "../enums/countryCode";
import { LocationStatus } from "../enums/locationStatus";
import type {
  StammtischContact,
  StammtischLocation,
  StammtischMeetingInfo,
} from "../types/stammtisch";
import { CountryMetadata } from "./CountryMetadata";

const CoordinatesSchema = z.object({
  lat: z.number(),
  lng: z.number(),
});

const sanitizeOptionalString = <T extends z.ZodTypeAny>(schema: T) =>
  z.preprocess((value) => {
    if (value === null || value === undefined) {
      return undefined;
    }

    if (typeof value === "string") {
      const trimmed = value.trim();
      return trimmed.length > 0 ? trimmed : undefined;
    }

    return value;
  }, schema);

const ContactSchema = z
  .object({
    email: sanitizeOptionalString(z.string().email().optional()),
    phone: sanitizeOptionalString(z.string().optional()),
    website: sanitizeOptionalString(z.string().optional()),
    telegram: sanitizeOptionalString(z.string().optional()),
    discord: sanitizeOptionalString(z.string().optional()),
  })
  .transform((contact) => contact as StammtischContact)
  .optional()
  .default({});

const MeetingInfoSchema = z
  .object({
    frequency: sanitizeOptionalString(z.string().optional()),
    location: sanitizeOptionalString(z.string().optional()),
    address: sanitizeOptionalString(z.string().optional()),
    nextMeeting: sanitizeOptionalString(z.string().optional()),
  })
  .transform((meeting) => meeting as StammtischMeetingInfo)
  .optional()
  .default({});

const LocationSchema = z.object({
  id: z.union([z.string(), z.number()]).transform((value) => String(value)),
  name: z.string(),
  slug: z.string().optional(),
  city: z.string(),
  region: z.string(),
  country: z.union([z.nativeEnum(CountryCode), z.string()]),
  coordinates: CoordinatesSchema,
  description: z.string().optional().default(""),
  contact: ContactSchema,
  meetingInfo: MeetingInfoSchema,
  tags: z.array(z.string()).optional().default([]),
  isActive: z.boolean().optional().default(true),
  status: z.union([z.nativeEnum(LocationStatus), z.string()]).optional(),
  createdBy: z.number().optional(),
  createdByUsername: z.string().optional(),
  createdAt: z.string().optional(),
  lastUpdated: z.string().optional(),
});

type RawLocation = z.infer<typeof LocationSchema>;

/**
 * Converts API payloads into strongly typed frontend entities.
 */
export class StammtischLocationFactory {
  /**
   * Attempt to normalize a single API response object.
   */
  static fromApi(data: unknown): StammtischLocation | null {
    const parsed = LocationSchema.safeParse(data);
    if (!parsed.success) {
      console.warn("Invalid Stammtisch location payload skipped", parsed.error);
      return null;
    }

    return this.map(parsed.data);
  }

  /**
   * Normalize an array of API payloads.
   */
  static fromApiArray(data: unknown): StammtischLocation[] {
    if (!Array.isArray(data)) {
      return [];
    }

    return data
      .map((item) => this.fromApi(item))
      .filter((item): item is StammtischLocation => Boolean(item));
  }

  private static map(raw: RawLocation): StammtischLocation {
    const country = CountryMetadata.normalizeCode(raw.country);
    const status = this.normalizeStatus(raw.status);

    return {
      id: raw.id,
      name: raw.name,
      slug: raw.slug,
      city: raw.city,
      region: raw.region,
      country,
      coordinates: raw.coordinates,
      description: raw.description ?? "",
      contact: raw.contact ?? {},
      meetingInfo: raw.meetingInfo ?? {},
      tags: this.uniqueTags(raw.tags ?? []),
      isActive: raw.isActive ?? true,
      status,
      createdBy: raw.createdBy,
      createdByUsername: raw.createdByUsername,
      createdAt: raw.createdAt,
      lastUpdated: raw.lastUpdated,
    };
  }

  private static normalizeStatus(value: unknown): LocationStatus {
    if (typeof value === "string") {
      const normalized = value.toLowerCase();
      const match = (Object.values(LocationStatus) as string[]).find(
        (status) => status === normalized,
      );
      if (match) {
        return match as LocationStatus;
      }
    }
    return LocationStatus.PUBLISHED;
  }

  private static uniqueTags(tags: string[]): string[] {
    return Array.from(new Set(tags.filter(Boolean))).sort();
  }
}
