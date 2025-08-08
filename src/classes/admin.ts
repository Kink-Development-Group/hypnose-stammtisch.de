import { ZodDate, ZodEmail, ZodGUID } from "zod";
import { Role } from "../enums/role";

/**
 * Represents an admin user.
 * This class is responsible for managing admin user data and behavior.
 */
export default class Admin {
  public readonly id: ZodGUID;
  public username: string;
  public email: ZodEmail;
  public role: Role;
  public created_at: ZodDate;
  public updated_at: ZodDate;

  constructor(
    id: ZodGUID,
    username: string,
    email: ZodEmail,
    role: Role,
    created_at: ZodDate,
    updated_at: ZodDate,
  ) {
    this.id = id;
    this.username = username;
    this.email = email;
    this.role = role;
    this.created_at = created_at;
    this.updated_at = updated_at;
  }
}
