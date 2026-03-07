import { expect, test, type Page } from "@playwright/test";
import { bypassComplianceModals, fulfillJson } from "./helpers/ui";

async function mockMapApi(page: Page): Promise<void> {
  await page.route("**/api/stammtisch-locations/meta", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: {
        regions: ["Berlin"],
        tags: ["anfängerfreundlich"],
        countries: [
          {
            code: "DE",
            name: "Deutschland",
            flag: "🇩🇪",
          },
        ],
      },
    });
  });

  await page.route("**/api/stammtisch-locations", async (route) => {
    await fulfillJson(route, {
      success: true,
      data: [
        {
          id: "berlin-1",
          name: "Hypnose Stammtisch Berlin",
          city: "Berlin",
          region: "Berlin",
          country: "DE",
          coordinates: { lat: 52.52, lng: 13.405 },
          description: "Monatlicher Stammtisch in Berlin.",
          contact: { email: "berlin@example.com" },
          meetingInfo: {
            frequency: "Jeden 2. Mittwoch",
            location: "Berlin Mitte",
          },
          tags: ["anfängerfreundlich"],
          isActive: true,
          status: "published",
        },
      ],
    });
  });
}

test.describe("Map popup theme", () => {
  test("renders the Stammtisch preview popup with dark popup styling", async ({
    page,
  }) => {
    await bypassComplianceModals(page);
    await mockMapApi(page);

    await page.goto("/map");
    await page.waitForLoadState("networkidle");

    const marker = page.locator(".custom-stammtisch-marker").first();
    await expect(marker).toBeVisible();
    await marker.click();

    const popupWrapper = page.locator(".leaflet-popup-content-wrapper").first();
    await expect(popupWrapper).toBeVisible();

    const popupStyles = await popupWrapper.evaluate((element) => {
      const styles = window.getComputedStyle(element);
      return {
        backgroundImage: styles.backgroundImage,
        borderColor: styles.borderColor,
      };
    });

    expect(popupStyles.backgroundImage).toContain("31, 41, 55");
    expect(popupStyles.borderColor).not.toBe("rgb(255, 255, 255)");

    const titleColor = await page
      .locator(".stammtisch-popup .popup-title")
      .evaluate((element) => window.getComputedStyle(element).color);
    expect(titleColor).toBe("rgb(243, 244, 246)");
  });
});
