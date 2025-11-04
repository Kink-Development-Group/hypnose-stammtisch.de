/**
 * User role enumeration defining permission levels in the system.
 *
 * @remarks
 * Roles are hierarchical with HEADADMIN having the highest privileges.
 * Each role has specific permissions:
 *
 * - **HEADADMIN**: Full system access including user management
 * - **ADMIN**: Event and message management
 * - **MODERATOR**: Message management only
 * - **EVENTMANAGER**: Event management only (cannot delete past events)
 *
 * @example
 * ```typescript
 * import { Role } from '../enums/role';
 *
 * const user = {
 *   id: '123',
 *   username: 'admin',
 *   role: Role.ADMIN
 * };
 * ```
 */
export enum Role {
  /** Head Administrator - Full system access including user management */
  HEADADMIN = "head",

  /** Administrator - Event and message management access */
  ADMIN = "admin",

  /** Moderator - Message management access only */
  MODERATOR = "moderator",

  /** Event Manager - Event creation/editing, can delete only past events */
  EVENTMANAGER = "event_manager",
}
