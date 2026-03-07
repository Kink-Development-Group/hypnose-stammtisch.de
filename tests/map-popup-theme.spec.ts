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

    const titleColor = await page.locator(".stammtisch-popup .popup-title").evaluate((element) =>
      window.getComputedStyle(element).color,
    );
    expect(titleColor).toBe("rgb(243, 244, 246)");
  });
});