import { z } from "zod";
import { Role } from "../enums/role";

// Zod schemas for validation
export const UserSchema = z.object({
  id: z.union([z.string().uuid(), z.number()]).transform(String), // Allow both string UUID and number, convert to string
  username: z.string().min(1),
  email: z.string().email(),
  role: z.nativeEnum(Role),
  is_active: z.boolean().optional().default(true),
  last_login: z.string().datetime().nullable().optional(),
  created_at: z.string().datetime(),
  updated_at: z.string().datetime(),
});

export type UserData = z.infer<typeof UserSchema>;

/**
 * Represents a user.
 * This class is responsible for managing user data and behavior.
 */
export default class User {
  public readonly id: string;
  public username: string;
  public email: string;
  public role: Role;
  public is_active: boolean;
  public last_login: Date | null;
  public created_at: Date;
  public updated_at: Date;

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
   * Factory method to create a User instance from API data
   */
  static fromApiData(data: any): User {
    return new User(data);
  }

  /**
   * Factory method to create a User instance from raw object
   */
  static fromObject(data: any): User {
    return new User(data);
  }

  /**
   * Get the display name for the user's role
   */
  getRoleDisplayName(): string {
    switch (this.role) {
      case Role.HEADADMIN:
        return "Head Admin";
      case Role.ADMIN:
        return "Administrator";
      case Role.MODERATOR:
        return "Moderator";
      default:
        return "Unbekannt";
    }
  }

  /**
   * Get CSS classes for role badge
   */
  getRoleBadgeClass(): string {
    switch (this.role) {
      case Role.HEADADMIN:
        return "bg-purple-100 text-purple-800";
      case Role.ADMIN:
        return "bg-blue-100 text-blue-800";
      case Role.MODERATOR:
        return "bg-green-100 text-green-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  }

  /**
   * Check if user has permission to manage other users
   */
  canManageUsers(): boolean {
    return this.role === Role.HEADADMIN || this.role === Role.ADMIN;
  }

  /**
   * Check if user has permission to manage events
   */
  canManageEvents(): boolean {
    return (
      this.role === Role.HEADADMIN ||
      this.role === Role.ADMIN ||
      this.role === Role.MODERATOR
    );
  }

  /**
   * Check if user has permission to manage messages
   */
  canManageMessages(): boolean {
    return (
      this.role === Role.HEADADMIN ||
      this.role === Role.ADMIN ||
      this.role === Role.MODERATOR
    );
  }

  /**
   * Get formatted last login date
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
   * Get formatted creation date
   */
  getFormattedCreatedAt(): string {
    return this.created_at.toLocaleDateString("de-DE", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    });
  }

  /**
   * Convert to plain object for API calls
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
   * Create a copy of the user with updated data
   */
  update(updates: Partial<UserData>): User {
    return new User({
      ...this.toApiObject(),
      ...updates,
      updated_at: new Date().toISOString(),
    });
  }
}
