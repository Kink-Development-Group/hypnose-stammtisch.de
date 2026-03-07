import { expect, test, type Page, type Route } from "@playwright/test";

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
}

async function mockCalendarEvents(page: Page): Promise<void> {
  const baseDate = "2026-03-18";
  const apiEvents = Array.from({ length: 6 }, (_, index) => ({
    id: index + 1,
    title: `Overflow Test ${index + 1}`,
    description: "Layout-Regressionstest für schmale Monatsansichten",
    start_datetime: `${baseDate}T1${index}:00:00`,
    end_datetime: `${baseDate}T1${index}:45:00`,
    timezone: "Europe/Berlin",
    location_type: "physical",
    location_address: "Berlin",
    tags: ["layout"],
    difficulty_level: "beginner",
    created_at: "2026-03-01T08:00:00Z",
    updated_at: "2026-03-01T08:00:00Z",
  }));

  await page.route("**/api/events?view=expanded**", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: apiEvents,
    });
  });
}

test.describe("Calendar month layout", () => {
  test.use({ viewport: { width: 880, height: 900 } });

  test("keeps populated month cells from overflowing at narrower widths", async ({
    page,
  }) => {
    await bypassComplianceModals(page);
    await mockCalendarEvents(page);

    await page.goto("/events");
    await page.waitForLoadState("networkidle");

    const eventButton = page.getByRole("button", {
      name: /Overflow Test 1/,
    });
    await expect(eventButton).toBeVisible();

    const dayCell = page.locator(".calendar-day", { has: eventButton }).first();
    await expect(dayCell).toBeVisible();

    const cellMetrics = await dayCell.evaluate((element) => ({
      clientHeight: element.clientHeight,
      scrollHeight: element.scrollHeight,
      clientWidth: element.clientWidth,
    }));

    expect(cellMetrics.scrollHeight).toBeLessThanOrEqual(
      cellMetrics.clientHeight + 2,
    );
    expect(cellMetrics.clientHeight).toBeGreaterThan(cellMetrics.clientWidth);
  });
});