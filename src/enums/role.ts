/**
 * Represents the different roles an admin user can have.
 * Each role has its own set of permissions and responsibilities.
 * The roles are hierarchical, with HEADADMIN having the most privileges.
 */
export enum Role {
  HEADADMIN = "head",
  ADMIN = "admin",
  MODERATOR = "moderator",
  EVENTMANAGER = "event_manager", // Neue Rolle: darf nur Events erstellen/bearbeiten und vergangene löschen
}
