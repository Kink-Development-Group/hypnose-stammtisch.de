import { Role } from "../enums/role";

/**
 * Represents an admin user.
 * This class is responsible for managing admin user data and behavior.
 */
export default class Admin {
  public readonly id: string;
  public username: string;
  public email: string;
  public role: Role;
  public created_at: Date;
  public updated_at: Date;

  constructor(
    id: string,
    username: string,
    email: string,
    role: Role,
    created_at: Date,
    updated_at: Date,
  ) {
    this.id = id;
    this.username = username;
    this.email = email;
    this.role = role;
    this.created_at = created_at;
    this.updated_at = updated_at;
  }
}
