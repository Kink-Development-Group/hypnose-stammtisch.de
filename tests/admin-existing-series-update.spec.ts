import { expect, test, type Page } from "@playwright/test";
import {
  bypassComplianceModals,
  dismissComplianceUiIfNeeded,
  fulfillJson,
} from "./helpers/ui";

const authenticatedAdmin = {
  id: 1,
  username: "admin",
  email: "admin@example.com",
  role: "admin",
  is_active: true,
  last_login: new Date("2026-03-07T10:00:00.000Z").toISOString(),
  created_at: new Date("2026-03-01T10:00:00.000Z").toISOString(),
  updated_at: new Date("2026-03-07T10:00:00.000Z").toISOString(),
};

const baseSeries = {
  id: "series-1",
  event_type: "series",
  title: "Weekly Series",
  description: "Wiederkehrender Termin",
  rrule: "FREQ=WEEKLY;BYDAY=FR",
  start_date: "2026-01-02",
  end_date: "2026-12-31",
  start_time: "19:00",
  end_time: "21:00",
  location_type: "physical",
  location_name: "Test Space",
  location_address: "Beispielstraße 1",
  category: "stammtisch",
  max_participants: null,
  requires_registration: true,
  status: "published",
  tags: [],
  generated_events_count: 0,
};

async function mockAdminSession(page: Page): Promise<void> {
  await page.route("**/api/admin/auth/status", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: authenticatedAdmin,
    });
  });

  await page.route("**/api/admin/auth/csrf", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: { csrf_token: "test-csrf-token" },
    });
  });
}

test.describe("Admin existing series update", () => {
  test("reloads the series list after saving an existing series", async ({
    page,
  }) => {
    let listRequestCount = 0;
    let updatePayload: Record<string, unknown> | null = null;

    await bypassComplianceModals(page);
    await mockAdminSession(page);

    await page.route("**/api/admin/events", async (route) => {
      if (route.request().method() !== "GET") {
        await route.fallback();
        return;
      }

      listRequestCount += 1;
      const title =
        listRequestCount >= 2 ? "Weekly Series Updated" : "Weekly Series";

      await fulfillJson(route, {
        success: true,
        data: {
          events: [],
          series: [{ ...baseSeries, title }],
          total: 1,
        },
      });
    });

    await page.route("**/api/admin/events/series-1", async (route) => {
      if (route.request().method() !== "PUT") {
        await route.fallback();
        return;
      }

      updatePayload = route.request().postDataJSON() as Record<string, unknown>;

      await fulfillJson(route, {
        success: true,
        data: null,
      });
    });

    await page.goto("/admin/events");
    await dismissComplianceUiIfNeeded(page);

    await expect(
      page.getByRole("heading", { name: "Veranstaltungen", level: 1 }),
    ).toBeVisible();

    const seriesRow = page
      .locator("li")
      .filter({ has: page.getByText("Weekly Series") })
      .first();

    await seriesRow.getByRole("button", { name: "Bearbeiten" }).click();

    await expect(
      page.getByRole("heading", { name: "Veranstaltung bearbeiten" }),
    ).toBeVisible();

    await page.locator("#event-title").fill("Weekly Series Updated");
    await page.getByRole("button", { name: "Speichern" }).click();

    await expect.poll(() => updatePayload).not.toBeNull();
    expect(updatePayload).toMatchObject({
      title: "Weekly Series Updated",
      event_type: "series",
    });

    await expect(page.getByText("Weekly Series Updated")).toBeVisible();
    await expect(page.locator("#event-title")).not.toBeVisible();
    expect(listRequestCount).toBeGreaterThanOrEqual(2);
  });
});
