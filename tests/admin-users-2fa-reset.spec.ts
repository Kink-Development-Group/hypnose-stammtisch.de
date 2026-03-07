import { expect, test, type Page } from "@playwright/test";
import {
  bypassComplianceModals,
  dismissComplianceUiIfNeeded,
  fulfillJson,
} from "./helpers/ui";

const headAdmin = {
  id: 1,
  username: "headadmin",
  email: "head@example.com",
  role: "head",
  is_active: true,
  last_login: new Date("2026-03-07T10:00:00.000Z").toISOString(),
  created_at: new Date("2026-03-01T10:00:00.000Z").toISOString(),
  updated_at: new Date("2026-03-07T10:00:00.000Z").toISOString(),
};

const managedUser = {
  id: 2,
  username: "eventmanager",
  email: "event@example.com",
  role: "event_manager",
  is_active: true,
  last_login: new Date("2026-03-06T10:00:00.000Z").toISOString(),
  created_at: new Date("2026-03-02T10:00:00.000Z").toISOString(),
  updated_at: new Date("2026-03-06T10:00:00.000Z").toISOString(),
};

async function mockAdminUsersSession(page: Page): Promise<void> {
  await page.route("**/api/admin/auth/status", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: headAdmin,
    });
  });

  await page.route("**/api/admin/auth/csrf", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: { csrf_token: "test-csrf-token" },
    });
  });

  await page.route("**/api/admin/users", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: [headAdmin, managedUser],
    });
  });
}

test.describe("Admin users 2FA reset", () => {
  test("allows a head admin to queue a 2FA reset for another user", async ({
    page,
    isMobile,
  }) => {
    test.skip(
      isMobile,
      "Die Tabellen-/Dialog-Interaktion wird hier als Desktop-Flow geprüft.",
    );

    let updatePayload: Record<string, unknown> | null = null;

    await bypassComplianceModals(page);
    await mockAdminUsersSession(page);

    await page.route("**/api/admin/users/2", async (route) => {
      updatePayload = route.request().postDataJSON() as Record<string, unknown>;

      await fulfillJson(route, {
        success: true,
        data: {
          updated: true,
          user: managedUser,
        },
      });
    });

    await page.goto("/admin/users");
    await dismissComplianceUiIfNeeded(page);

    await expect(
      page.getByRole("heading", { name: "Admin-Benutzer", level: 1 }),
    ).toBeVisible();

    const managedUserCard = page
      .locator("tr, article")
      .filter({ hasText: managedUser.username })
      .first();
    await managedUserCard.getByRole("button", { name: "Bearbeiten" }).click();

    await expect(
      page.getByLabel("2FA für diesen Benutzer zurücksetzen"),
    ).toBeVisible();

    await page.getByLabel("2FA für diesen Benutzer zurücksetzen").check();
    await expect(
      page.getByText(
        "Der Reset wird erst beim Speichern ausgeführt. Bestehende Backup-Codes werden sofort ungültig.",
      ),
    ).toBeVisible();

    await page.getByRole("button", { name: "Benutzer aktualisieren" }).click();

    await expect.poll(() => updatePayload).not.toBeNull();
    expect(updatePayload).toMatchObject({
      username: managedUser.username,
      email: managedUser.email,
      role: managedUser.role,
      is_active: true,
      reset_twofa: true,
    });
  });

  test("does not show the admin reset option when editing the current head admin", async ({
    page,
    isMobile,
  }) => {
    test.skip(
      isMobile,
      "Die Tabellen-/Dialog-Interaktion wird hier als Desktop-Flow geprüft.",
    );

    await bypassComplianceModals(page);
    await mockAdminUsersSession(page);

    await page.goto("/admin/users");
    await dismissComplianceUiIfNeeded(page);

    const ownUserCard = page
      .locator("tr, article")
      .filter({ hasText: headAdmin.username })
      .first();
    await ownUserCard.getByRole("button", { name: "Bearbeiten" }).click();

    await expect(
      page.getByLabel("2FA für diesen Benutzer zurücksetzen"),
    ).toHaveCount(0);
  });
});
