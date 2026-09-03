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

const eventManager = {
  ...headAdmin,
  id: 2,
  username: "eventmanager",
  email: "event@example.com",
  role: "event_manager",
};

const seriesFixtures = [
  {
    id: "11111111-1111-4111-8111-111111111111",
    title: "Hamburger Hypnose Munch",
    slug: "hamburger-hypnose-munch",
    location: "Hamburg • Club Catonium",
    frequency: "Monatlich • Jeden 1. Freitag",
    description: "Monatliche Treffen im Club Catonium.",
    formats: ["Hypnose 101/102"],
    price: "10€ Eintritt",
    tags: ["Alle Levels"],
    detailUrl: "/events",
    nextEventSource: "none",
    nextEventText: null,
    linkedSeriesId: null,
    linkedSeriesTitle: null,
    sortOrder: 1,
    status: "published",
    isActive: true,
    createdBy: 1,
    createdByUsername: "headadmin",
    createdAt: new Date("2026-03-01T10:00:00.000Z").toISOString(),
    lastUpdated: new Date("2026-03-01T10:00:00.000Z").toISOString(),
  },
  {
    id: "22222222-2222-4222-8222-222222222222",
    title: "Hypno Study Frankfurt",
    slug: "hypno-study-frankfurt",
    location: "Frankfurt • Eventspace",
    frequency: "Monatlich • Jeden 3. Mittwoch",
    description: "Deeptalk-Stammtisch.",
    formats: [],
    price: null,
    tags: [],
    detailUrl: "/events",
    nextEventSource: "manual",
    nextEventText: "Mi, 18. Nov 2026",
    linkedSeriesId: null,
    linkedSeriesTitle: null,
    sortOrder: 2,
    status: "draft",
    isActive: true,
    createdBy: 1,
    createdByUsername: "headadmin",
    createdAt: new Date("2026-03-02T10:00:00.000Z").toISOString(),
    lastUpdated: new Date("2026-03-02T10:00:00.000Z").toISOString(),
  },
];

async function mockAdminSession(
  page: Page,
  user: Record<string, unknown>,
): Promise<void> {
  await page.route("**/api/admin/auth/status", async (route) => {
    await fulfillJson(route, { success: true, data: user });
  });

  await page.route("**/api/admin/auth/csrf", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: { csrf_token: "test-csrf-token" },
    });
  });

  await page.route(
    "**/api/admin/known-event-series/linkable-series",
    async (route) => {
      await fulfillJson(route, { success: true, data: [] });
    },
  );
}

test.describe("Admin-Verwaltung der bekannten Event-Reihen", () => {
  test("zeigt einem Head-Admin die gepflegten Reihen", async ({
    page,
    isMobile,
  }) => {
    test.skip(
      isMobile,
      "Die Tabellen-Interaktion wird als Desktop-Flow geprüft.",
    );

    await bypassComplianceModals(page);
    await mockAdminSession(page, headAdmin);
    await page.route("**/api/admin/known-event-series", async (route) => {
      await fulfillJson(route, { success: true, data: seriesFixtures });
    });

    await page.goto("/admin/known-event-series");
    await dismissComplianceUiIfNeeded(page);

    await expect(
      page.getByRole("heading", { name: "Bekannte Event-Reihen", level: 1 }),
    ).toBeVisible();
    await expect(page.getByText("Hamburger Hypnose Munch")).toBeVisible();
    await expect(page.getByText("Hypno Study Frankfurt")).toBeVisible();
  });

  test("sortiert per Tastatur und schickt die neue Reihenfolge an das Backend", async ({
    page,
    isMobile,
  }) => {
    test.skip(
      isMobile,
      "Die Tabellen-Interaktion wird als Desktop-Flow geprüft.",
    );

    let reorderPayload: Record<string, unknown> | null = null;

    await bypassComplianceModals(page);
    await mockAdminSession(page, headAdmin);
    await page.route("**/api/admin/known-event-series", async (route) => {
      await fulfillJson(route, { success: true, data: seriesFixtures });
    });
    await page.route(
      "**/api/admin/known-event-series/reorder",
      async (route) => {
        reorderPayload = route.request().postDataJSON() as Record<
          string,
          unknown
        >;
        await fulfillJson(route, { success: true, data: seriesFixtures });
      },
    );

    await page.goto("/admin/known-event-series");
    await dismissComplianceUiIfNeeded(page);

    // Sorting must work without drag-and-drop.
    await page
      .getByRole("button", {
        name: "„Hypno Study Frankfurt“ nach oben verschieben",
      })
      .click();

    await expect.poll(() => reorderPayload).not.toBeNull();
    expect(reorderPayload).toMatchObject({
      ids: [seriesFixtures[1].id, seriesFixtures[0].id],
    });
  });

  test("verweigert einem Event-Manager den Zugriff", async ({ page }) => {
    await bypassComplianceModals(page);
    await mockAdminSession(page, eventManager);
    await page.route("**/api/admin/known-event-series", async (route) => {
      await fulfillJson(
        route,
        {
          success: false,
          error: "Insufficient permissions. Head admin or admin role required.",
        },
        403,
      );
    });
    await page.route("**/api/admin/events**", async (route) => {
      await fulfillJson(route, { success: true, data: [] });
    });

    await page.goto("/admin/known-event-series");
    await dismissComplianceUiIfNeeded(page);

    // The guard sends them to a page they may use instead of rendering the table.
    await expect(
      page.getByRole("heading", { name: "Bekannte Event-Reihen", level: 1 }),
    ).toHaveCount(0);
    await expect(page).toHaveURL(/#\/admin\/events/);
  });
});
