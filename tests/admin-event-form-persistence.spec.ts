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

  await page.route("**/api/admin/events", async (route) => {
    if (route.request().method() === "GET") {
      await fulfillJson(route, {
        success: true,
        data: {
          events: [],
          series: [],
          total: 0,
        },
      });
      return;
    }

    await route.fallback();
  });
}

async function openCreateEventModal(page: Page): Promise<void> {
  await page.goto("/admin/events");
  await dismissComplianceUiIfNeeded(page);
  await expect(
    page.getByRole("heading", { name: "Veranstaltungen", level: 1 }),
  ).toBeVisible();

  await page.getByRole("button", { name: "Neue Veranstaltung" }).click();
  await expect(
    page.getByRole("heading", { name: "Neue Veranstaltung erstellen" }),
  ).toBeVisible();
}

test.describe("Admin event creation form", () => {
  test("preserves time values when switching away from the Zeit tab before saving", async ({
    page,
    isMobile,
  }) => {
    test.skip(isMobile, "Der Preset-Flow wird hier nur auf Desktop validiert.");

    let createPayload: Record<string, unknown> | null = null;

    await bypassComplianceModals(page);
    await mockAdminSession(page);
    await page.route("**/api/admin/events", async (route) => {
      if (route.request().method() !== "POST") {
        await route.fallback();
        return;
      }

      createPayload = route.request().postDataJSON() as Record<string, unknown>;

      await fulfillJson(route, {
        success: true,
        data: {
          id: 999,
          ...createPayload,
        },
      });
    });

    await openCreateEventModal(page);

    await page.locator("#event-title").fill("Regressionstest Event");

    await page.getByRole("button", { name: "Zeit" }).click();

    await page.locator("#start-datetime").click();
    await page.getByRole("button", { name: "09:00" }).scrollIntoViewIfNeeded();
    await page.getByRole("button", { name: "09:00" }).click();
    await page.getByRole("button", { name: "Übernehmen" }).click();

    await page.locator("#end-datetime").click();
    await page.getByRole("button", { name: "12:00" }).scrollIntoViewIfNeeded();
    await page.getByRole("button", { name: "12:00" }).click();
    await page.getByRole("button", { name: "Übernehmen" }).click();

    await expect(page.locator("#start-datetime")).toContainText("09:00 Uhr");
    await expect(page.locator("#end-datetime")).toContainText("12:00 Uhr");

    await page.getByRole("button", { name: "Ort" }).click();
    await page.locator("#location-name").fill("Testlocation Berlin");

    await page.getByRole("button", { name: "Erstellen" }).click();

    await expect.poll(() => createPayload).not.toBeNull();
    expect(createPayload).toMatchObject({
      title: "Regressionstest Event",
      event_type: "single",
      location_name: "Testlocation Berlin",
      start_datetime: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T09:00$/),
      end_datetime: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T12:00$/),
    });
  });

  test("renders date picker popovers without clipping ancestors inside the modal", async ({
    page,
    isMobile,
  }) => {
    test.skip(
      isMobile,
      "Die Clipping-Prüfung ist für Desktop-Popover relevant.",
    );

    await bypassComplianceModals(page);
    await mockAdminSession(page);

    await openCreateEventModal(page);
    await page.getByRole("button", { name: "Zeit" }).click();
    await page.locator("#start-datetime").click();

    await expect(
      page.getByRole("dialog", { name: "Datum und Zeit auswählen" }),
    ).toBeVisible();

    const hasClippingAncestor = await page.evaluate(() => {
      const dialog = document.querySelector(
        '[role="dialog"][aria-label="Datum und Zeit auswählen"]',
      );

      if (!dialog) {
        return true;
      }

      let current = dialog.parentElement;
      while (current) {
        if (current.classList.contains("fixed")) {
          break;
        }

        const style = window.getComputedStyle(current);
        const overflowValues = [
          style.overflow,
          style.overflowX,
          style.overflowY,
        ].join(" ");

        if (/(auto|hidden|scroll)/.test(overflowValues)) {
          return true;
        }

        current = current.parentElement;
      }

      return false;
    });

    expect(hasClippingAncestor).toBe(false);
  });
});
