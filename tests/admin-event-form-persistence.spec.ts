import { expect, test, type Page, type Route } from "@playwright/test";

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

async function fulfillJson(route: Route, body: unknown): Promise<void> {
  await route.fulfill({
    status: 200,
    contentType: "application/json",
    body: JSON.stringify(body),
  });
}

async function bypassComplianceModals(page: Page): Promise<void> {
  const consentRecord = {
    timestamp: new Date().toISOString(),
    version: "1.0.0",
    consent: {
      essential: true,
      preferences: true,
      statistics: false,
      marketing: false,
    },
  };

  const consentValue = encodeURIComponent(JSON.stringify(consentRecord));

  await page.addInitScript(({ encodedConsent }) => {
    document.cookie = "age_verified=true; path=/; SameSite=Lax";
    document.cookie = `cookie_consent=${encodedConsent}; path=/; SameSite=Lax`;
  }, { encodedConsent: consentValue });

  await page.context().addCookies([
    {
      name: "age_verified",
      value: "true",
      url: "http://127.0.0.1:5173",
    },
    {
      name: "cookie_consent",
      value: consentValue,
      url: "http://127.0.0.1:5173",
    },
  ]);
}

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

async function dismissComplianceUiIfNeeded(page: Page): Promise<void> {
  await page.waitForLoadState("networkidle");

  const ageVerificationButton = page.getByRole("button", {
    name: /Ja, ich bin 18\+.*Seite betreten/i,
  });
  if (await ageVerificationButton.count()) {
    await ageVerificationButton.click();
  }

  const acceptCookiesButton = page.getByRole("button", {
    name: /Alle Akzeptieren/i,
  });
  if (await acceptCookiesButton.count()) {
    await acceptCookiesButton.click();
  }
}

test.describe("Admin event creation form", () => {
  test("preserves time values when switching away from the Zeit tab before saving", async ({
    page,
  }) => {
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

    await page.goto("/admin/events");
    await dismissComplianceUiIfNeeded(page);
    await expect(
      page.getByRole("heading", { name: "Veranstaltungen", level: 1 }),
    ).toBeVisible();

    await page.getByRole("button", { name: "Neue Veranstaltung" }).click();
    await expect(
      page.getByRole("heading", { name: "Neue Veranstaltung erstellen" }),
    ).toBeVisible();

    await page.locator("#event-title").fill("Regressionstest Event");

    await page.getByRole("button", { name: "Zeit" }).click();

    await page.locator("#start-datetime").click();
    await page.getByRole("button", { name: "09:00" }).click();
    await page.getByRole("button", { name: "Übernehmen" }).click();

    await page.locator("#end-datetime").click();
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
});