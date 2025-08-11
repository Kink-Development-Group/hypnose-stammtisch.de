import User from "../classes/User";
import { Role } from "../enums/role";

/**
 * Helper functions for working with User objects
 */
export class UserHelpers {
  /**
   * Create a User array from API response data
   */
  static fromApiArray(apiData: any[]): User[] {
    return apiData.map((userData) => User.fromApiData(userData));
  }

  /**
   * Get role display name
   */
  static getRoleDisplayName(role: Role | string): string {
    switch (role) {
      case Role.HEADADMIN:
      case "head":
        return "Head Admin";
      case Role.ADMIN:
      case "admin":
        return "Administrator";
      case Role.MODERATOR:
      case "moderator":
        return "Moderator";
      case Role.EVENTMANAGER:
      case "event_manager":
        return "Event-Manager";
      default:
        return "Unbekannt";
    }
  }

  /**
   * Get role badge CSS classes
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
   * Format date for display
   */
  static formatDate(date: Date | string | null): string {
    if (!date) return "Nie";

    const dateObj = typeof date === "string" ? new Date(date) : date;

    return dateObj.toLocaleDateString("de-DE", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  /**
   * Check if user has specific permission
   */
  static hasPermission(
    user: User | null,
    permission: "manage_users" | "manage_events" | "manage_messages",
  ): boolean {
    if (!user) return false;

    switch (permission) {
      case "manage_users":
        return user.canManageUsers();
      case "manage_events":
        return user.canManageEvents();
      case "manage_messages":
        return user.canManageMessages();
      default:
        return false;
    }
  }

  /**
   * Get user permissions object
   */
  static getPermissions(user: User | null): {
    can_manage_users: boolean;
    can_manage_events: boolean;
    can_manage_messages: boolean;
  } {
    return {
      can_manage_users: this.hasPermission(user, "manage_users"),
      can_manage_events: this.hasPermission(user, "manage_events"),
      can_manage_messages: this.hasPermission(user, "manage_messages"),
    };
  }
}
