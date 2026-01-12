import { describe, expect, it, vi } from "vitest";
import User from "./User";
import { Role } from "../enums/role";
import * as i18n from "../utils/i18n";

// Mock the i18n module
vi.mock("../utils/i18n", () => ({
  t: vi.fn((key: string) => key),
  formatDateTime: vi.fn(),
}));

describe("User.getRoleDisplayName()", () => {
  const baseUserData = {
    id: "123e4567-e89b-12d3-a456-426614174000",
    username: "testuser",
    email: "test@example.com",
    is_active: true,
    last_login: null,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  };

  it("should return correct translation key for HEADADMIN role", () => {
    const user = new User({
      ...baseUserData,
      role: Role.HEADADMIN,
    });

    const displayName = user.getRoleDisplayName();
    expect(displayName).toBe("role.headAdmin");
  });

  it("should return correct translation key for ADMIN role", () => {
    const user = new User({
      ...baseUserData,
      role: Role.ADMIN,
    });

    const displayName = user.getRoleDisplayName();
    expect(displayName).toBe("role.admin");
  });

  it("should return correct translation key for MODERATOR role", () => {
    const user = new User({
      ...baseUserData,
      role: Role.MODERATOR,
    });

    const displayName = user.getRoleDisplayName();
    expect(displayName).toBe("role.moderator");
  });

  it("should return correct translation key for EVENTMANAGER role", () => {
    const user = new User({
      ...baseUserData,
      role: Role.EVENTMANAGER,
    });

    const displayName = user.getRoleDisplayName();
    expect(displayName).toBe("role.eventManager");
  });

  it("should call t() function with correct key for each role type", () => {
    const tSpy = vi.mocked(i18n.t);

    const roles = [
      { role: Role.HEADADMIN, expectedKey: "role.headAdmin" },
      { role: Role.ADMIN, expectedKey: "role.admin" },
      { role: Role.MODERATOR, expectedKey: "role.moderator" },
      { role: Role.EVENTMANAGER, expectedKey: "role.eventManager" },
    ];

    roles.forEach(({ role, expectedKey }) => {
      tSpy.mockClear();
      const user = new User({
        ...baseUserData,
        role,
      });
      user.getRoleDisplayName();

      expect(tSpy).toHaveBeenCalledWith(expectedKey);
    });
  });
});
