import { expect, test, type Page } from "@playwright/test";
import { bypassComplianceModals, fulfillJson } from "./helpers/ui";

async function mockCalendarEvents(page: Page): Promise<void> {
  const today = new Date();
  const visibleDate = new Date(
    today.getFullYear(),
    today.getMonth(),
    Math.min(today.getDate(), 28),
  );
  const baseDate = [
    visibleDate.getFullYear(),
    String(visibleDate.getMonth() + 1).padStart(2, "0"),
    String(visibleDate.getDate()).padStart(2, "0"),
  ].join("-");
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

  await page.route("**/api/events**", async (route) => {
    const requestUrl = new URL(route.request().url());
    if (requestUrl.searchParams.get("view") !== "expanded") {
      await route.fallback();
      return;
    }

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
