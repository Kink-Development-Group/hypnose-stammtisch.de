import { z } from "zod";
import { Role } from "../enums/role";

/**
 * Zod validation schema for user data.
 * Handles validation and transformation of API data to internal format.
 *
 * @remarks
 * - Accepts both UUID strings and numeric IDs, normalizing them to strings
 * - Transforms datetime strings to ISO format
 * - Provides default values for optional fields
 *
 * @example
 * ```typescript
 * const validatedData = UserSchema.parse(apiResponse);
 * ```
 */
export const UserSchema = z.object({
  id: z.union([z.string().uuid(), z.number()]).transform(String),
  username: z.string().min(1),
  email: z.string().email(),
  role: z.nativeEnum(Role),
  is_active: z.boolean().optional().default(true),
  last_login: z
    .string()
    .nullable()
    .optional()
    .transform((val) => {
      if (!val) return null;
      const date = new Date(val);
      return date.toISOString();
    }),
  created_at: z.string().transform((val) => {
    const date = new Date(val);
    return date.toISOString();
  }),
  updated_at: z.string().transform((val) => {
    const date = new Date(val);
    return date.toISOString();
  }),
});

/**
 * Type definition for validated user data.
 * Inferred from UserSchema to ensure type safety.
 */
export type UserData = z.infer<typeof UserSchema>;

/**
 * Represents a user in the system with role-based permissions.
 *
 * @remarks
 * This class encapsulates user data and provides methods for:
 * - Permission checking based on roles
 * - Data formatting and display
 * - Type-safe API interactions
 *
 * All instances are validated through Zod schemas to ensure data integrity.
 *
 * @example
 * ```typescript
 * // Create from API data
 * const user = User.fromApiData(apiResponse);
 *
 * // Check permissions
 * if (user.canManageUsers()) {
 *   // Show admin UI
 * }
 *
 * // Get formatted data
 * const displayName = user.getRoleDisplayName();
 * const lastLogin = user.getFormattedLastLogin();
 * ```
 */
export default class User {
  /** Unique identifier (UUID or numeric ID converted to string) */
  public readonly id: string;

  /** Username for display and authentication */
  public username: string;

  /** User's email address */
  public email: string;

  /** User's role determining permissions */
  public role: Role;

  /** Whether the user account is active */
  public is_active: boolean;

  /** Timestamp of last successful login, null if never logged in */
  public last_login: Date | null;

  /** Account creation timestamp */
  public created_at: Date;

  /** Last update timestamp */
  public updated_at: Date;

  /**
   * Creates a new User instance with validated data.
   *
   * @param data - Raw user data to be validated
   * @throws {ZodError} If data validation fails
   *
   * @remarks
   * The constructor validates all input data through UserSchema
   * and converts datetime strings to Date objects.
   */
  constructor(data: UserData) {
    const validated = UserSchema.parse(data);

    this.id = validated.id;
    this.username = validated.username;
    this.email = validated.email;
    this.role = validated.role;
    this.is_active = validated.is_active;
    this.last_login = validated.last_login
      ? new Date(validated.last_login)
      : null;
    this.created_at = new Date(validated.created_at);
    this.updated_at = new Date(validated.updated_at);
  }

  /**
   * Factory method to create a User instance from API response data.
   *
   * @param data - Raw API response data
   * @returns A validated User instance
   * @throws {ZodError} If API data doesn't match expected schema
   *
   * @example
   * ```typescript
   * const response = await fetch('/api/admin/users');
   * const data = await response.json();
   * const user = User.fromApiData(data);
   * ```
   */
  static fromApiData(data: any): User {
    return new User(data);
  }

  /**
   * Factory method to create a User instance from a plain object.
   *
   * @param data - Plain JavaScript object with user data
   * @returns A validated User instance
   * @throws {ZodError} If object data doesn't match expected schema
   *
   * @example
   * ```typescript
   * const userObj = { id: 1, username: 'admin', ... };
   * const user = User.fromObject(userObj);
   * ```
   */
  static fromObject(data: any): User {
    return new User(data);
  }

  /**
   * Get the localized display name for the user's role.
   *
   * @returns Human-readable role name in German
   *
   * @example
   * ```typescript
   * user.getRoleDisplayName(); // "Head Admin", "Administrator", etc.
   * ```
   *
   * @remarks
   * Currently returns German strings. Should be migrated to i18n system.
   * @todo Integrate with i18n.ts for multi-language support
   */
  getRoleDisplayName(): string {
    switch (this.role) {
      case Role.HEADADMIN:
        return "Head Admin";
      case Role.ADMIN:
        return "Administrator";
      case Role.MODERATOR:
        return "Moderator";
      case Role.EVENTMANAGER:
        return "Event-Manager";
      default:
        return "Unbekannt";
    }
  }

  /**
   * Get Tailwind CSS classes for displaying a role badge.
   *
   * @returns Tailwind CSS class string for styling role badges
   *
   * @example
   * ```svelte
   * <span class="{user.getRoleBadgeClass()}">
   *   {user.getRoleDisplayName()}
   * </span>
   * ```
   */
  getRoleBadgeClass(): string {
    switch (this.role) {
      case Role.HEADADMIN:
        return "bg-purple-100 text-purple-800";
      case Role.ADMIN:
        return "bg-blue-100 text-blue-800";
      case Role.MODERATOR:
        return "bg-green-100 text-green-800";
      case Role.EVENTMANAGER:
        return "bg-indigo-100 text-indigo-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  }

  /**
   * Check if user has permission to manage other users.
   *
   * @returns `true` if user is a Head Admin, `false` otherwise
   *
   * @remarks
   * Only Head Admins can create, edit, or delete other admin users.
   *
   * @example
   * ```typescript
   * if (currentUser.canManageUsers()) {
   *   // Show user management UI
   * }
   * ```
   */
  canManageUsers(): boolean {
    return this.role === Role.HEADADMIN;
  }

  /**
   * Check if user has permission to manage events.
   *
   * @returns `true` if user is Head Admin, Admin, or Event Manager
   *
   * @remarks
   * Event management includes creating, editing, and deleting events and series.
   *
   * @example
   * ```typescript
   * if (currentUser.canManageEvents()) {
   *   // Show event management features
   * }
   * ```
   */
  canManageEvents(): boolean {
    return (
      this.role === Role.HEADADMIN ||
      this.role === Role.ADMIN ||
      this.role === Role.EVENTMANAGER
    );
  }

  /**
   * Check if user has permission to manage messages.
   *
   * @returns `true` if user is Head Admin, Admin, or Moderator
   *
   * @remarks
   * Message management includes viewing and responding to contact form submissions.
   *
   * @example
   * ```typescript
   * if (currentUser.canManageMessages()) {
   *   // Show message management UI
   * }
   * ```
   */
  canManageMessages(): boolean {
    return (
      this.role === Role.HEADADMIN ||
      this.role === Role.ADMIN ||
      this.role === Role.MODERATOR
    );
  }

  /**
   * Check if user has access to security management features.
   *
   * @returns `true` if user is Head Admin or Admin
   *
   * @remarks
   * Security management includes viewing failed logins, managing IP bans,
   * and unlocking user accounts.
   *
   * @example
   * ```typescript
   * if (currentUser.canManageSecurity()) {
   *   // Show security dashboard
   * }
   * ```
   */
  canManageSecurity(): boolean {
    return this.role === Role.HEADADMIN || this.role === Role.ADMIN;
  }

  /**
   * Get formatted last login timestamp.
   *
   * @returns Localized datetime string or "Nie" if never logged in
   *
   * @example
   * ```typescript
   * user.getFormattedLastLogin(); // "15.01.2024, 10:30"
   * ```
   *
   * @remarks
   * Uses German locale (de-DE) for formatting.
   * @todo Integrate with i18n system for locale-aware formatting
   */
  getFormattedLastLogin(): string {
    if (!this.last_login) return "Nie";
    return this.last_login.toLocaleDateString("de-DE", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  /**
   * Get formatted account creation date.
   *
   * @returns Localized date string
   *
   * @example
   * ```typescript
   * user.getFormattedCreatedAt(); // "01.12.2023"
   * ```
   *
   * @remarks
   * Uses German locale (de-DE) for formatting.
   * @todo Integrate with i18n system for locale-aware formatting
   */
  getFormattedCreatedAt(): string {
    return this.created_at.toLocaleDateString("de-DE", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    });
  }

  /**
   * Convert User instance to a plain object for API requests.
   *
   * @returns Plain object with user data in API format
   *
   * @example
   * ```typescript
   * const apiPayload = user.toApiObject();
   * await fetch('/api/admin/users/123', {
   *   method: 'PUT',
   *   body: JSON.stringify(apiPayload)
   * });
   * ```
   */
  toApiObject(): any {
    return {
      id: this.id,
      username: this.username,
      email: this.email,
      role: this.role,
      is_active: this.is_active,
      last_login: this.last_login?.toISOString() || null,
      created_at: this.created_at.toISOString(),
      updated_at: this.updated_at.toISOString(),
    };
  }

  /**
   * Create a new User instance with updated properties.
   *
   * @param updates - Partial user data to merge with current data
   * @returns New User instance with updated properties
   *
   * @example
   * ```typescript
   * const updatedUser = user.update({
   *   username: 'new_username',
   *   email: 'new@email.com'
   * });
   * ```
   *
   * @remarks
   * This method creates a new instance rather than mutating the current one,
   * following immutability principles. The `updated_at` timestamp is
   * automatically set to the current time.
   */
  update(updates: Partial<UserData>): User {
    return new User({
      ...this.toApiObject(),
      ...updates,
      updated_at: new Date().toISOString(),
    });
  }
}
