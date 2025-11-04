/**
 * Publication status of a Stammtisch location entry.
 */
export enum LocationStatus {
  DRAFT = "draft",
  PUBLISHED = "published",
  ARCHIVED = "archived",
}

/**
 * Status values that are visible to the public map.
 */
export const PUBLIC_LOCATION_STATUSES: readonly LocationStatus[] = [
  LocationStatus.PUBLISHED,
];
