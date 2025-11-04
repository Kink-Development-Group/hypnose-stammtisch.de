import User from "../classes/User";
import { Role } from "../enums/role";
import { formatDateTime, t } from "../utils/i18n";

/**
 * Static utility class providing helper functions for User objects.
 *
 * @remarks
 * This class provides centralized utility methods for:
 * - Converting API data to User instances
 * - Formatting role names and badges
 * - Checking permissions
 * - Formatting dates
 *
 * All methods are static and should be called directly on the class.
 *
 * @example
 * ```typescript
 * // Convert API array to User instances
 * const users = UserHelpers.fromApiArray(apiResponse);
 *
 * // Check permissions
 * if (UserHelpers.hasPermission(user, 'manage_users')) {
 *   // Show admin UI
 * }
 *
 * // Format dates
 * const formatted = UserHelpers.formatDate(user.last_login);
 * ```
 */
export class UserHelpers {
  /**
   * Create an array of User instances from API response data.
   *
   * @param apiData - Array of raw user data from API
   * @returns Array of validated User instances
   * @throws {ZodError} If any user data doesn't match expected schema
   *
   * @example
   * ```typescript
   * const response = await fetch('/api/admin/users');
   * const data = await response.json();
   * const users = UserHelpers.fromApiArray(data);
   * ```
   */
  static fromApiArray(apiData: any[]): User[] {
    return apiData.map((userData) => User.fromApiData(userData));
  }

  /**
   * Get localized display name for a role.
   *
   * @param role - Role enum value or string representation
   * @returns Human-readable role name
   *
   * @example
   * ```typescript
   * UserHelpers.getRoleDisplayName(Role.HEADADMIN); // "Head Admin"
   * UserHelpers.getRoleDisplayName('admin'); // "Administrator"
   * ```
   *
   * @remarks
   * Accepts both Role enum values and string representations for flexibility.
   * Uses the i18n system for multi-language support.
   */
  static getRoleDisplayName(role: Role | string): string {
    switch (role) {
      case Role.HEADADMIN:
      case "head":
        return t("role.headAdmin");
      case Role.ADMIN:
      case "admin":
        return t("role.admin");
      case Role.MODERATOR:
      case "moderator":
        return t("role.moderator");
      case Role.EVENTMANAGER:
      case "event_manager":
        return t("role.eventManager");
      default:
        return t("role.unknown");
    }
  }

  /**
   * Get Tailwind CSS classes for a role badge.
   *
   * @param role - Role enum value or string representation
   * @returns Tailwind CSS class string for badge styling
   *
   * @example
   * ```svelte
   * <span class="{UserHelpers.getRoleBadgeClass(user.role)}">
   *   {UserHelpers.getRoleDisplayName(user.role)}
   * </span>
   * ```
   *
   * @remarks
   * Returns Tailwind utility classes for background and text colors.
   * Each role has a distinct color scheme for easy visual identification.
   */
  static getRoleBadgeClass(role: Role | string): string {
    switch (role) {
      case Role.HEADADMIN:
      case "head":
        return "bg-purple-100 text-purple-800";
      case Role.ADMIN:
      case "admin":
        return "bg-blue-100 text-blue-800";
      case Role.MODERATOR:
      case "moderator":
        return "bg-green-100 text-green-800";
      case Role.EVENTMANAGER:
      case "event_manager":
        return "bg-indigo-100 text-indigo-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  }

  /**
   * Format a date for display.
   *
   * @param date - Date object, ISO string, or null
   * @returns Localized datetime string or "Nie"/"Never" if null
   *
   * @example
   * ```typescript
   * UserHelpers.formatDate(new Date()); // "04.11.2025, 14:30"
   * UserHelpers.formatDate(null); // "Nie"
   * UserHelpers.formatDate('2025-01-15T10:30:00Z'); // "15.01.2025, 10:30"
   * ```
   *
   * @remarks
   * Uses the i18n system for locale-aware formatting.
   */
  static formatDate(date: Date | string | null): string {
    if (!date) return t("datetime.never");

    const dateObj = typeof date === "string" ? new Date(date) : date;
    return formatDateTime(dateObj);
  }

  /**
   * Check if a user has a specific permission.
   *
   * @param user - User instance or null
   * @param permission - Permission to check
   * @returns `true` if user has the permission, `false` otherwise
   *
   * @example
   * ```typescript
   * if (UserHelpers.hasPermission(currentUser, 'manage_users')) {
   *   // Show user management UI
   * }
   * ```
   *
   * @remarks
   * Returns `false` if user is null for safe null checking.
   * Delegates to the corresponding `canManage*` method on the User class.
   */
  static hasPermission(
    user: User | null,
    permission:
      | "manage_users"
      | "manage_events"
      | "manage_messages"
      | "manage_security",
  ): boolean {
    if (!user) return false;

    switch (permission) {
      case "manage_users":
        return user.canManageUsers();
      case "manage_events":
        return user.canManageEvents();
      case "manage_messages":
        return user.canManageMessages();
      case "manage_security":
        return user.canManageSecurity();
      default:
        return false;
    }
  }

  /**
   * Get a complete permissions object for a user.
   *
   * @param user - User instance or null
   * @returns Object containing all permission flags
   *
   * @example
   * ```typescript
   * const perms = UserHelpers.getPermissions(currentUser);
   * if (perms.can_manage_users) {
   *   // Show user management
   * }
   * if (perms.can_manage_events) {
   *   // Show event management
   * }
   * ```
   *
   * @remarks
   * Provides a convenient way to check multiple permissions at once.
   * All permissions default to `false` if user is null.
   */
  static getPermissions(user: User | null): {
    can_manage_users: boolean;
    can_manage_events: boolean;
    can_manage_messages: boolean;
    can_manage_security: boolean;
  } {
    return {
      can_manage_users: this.hasPermission(user, "manage_users"),
      can_manage_events: this.hasPermission(user, "manage_events"),
      can_manage_messages: this.hasPermission(user, "manage_messages"),
      can_manage_security: this.hasPermission(user, "manage_security"),
    };
  }
}
