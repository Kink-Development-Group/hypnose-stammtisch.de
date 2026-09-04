import { expect, test, type Page } from "@playwright/test";
import {
  bypassComplianceModals,
  dismissComplianceUiIfNeeded,
  fulfillJson,
} from "./helpers/ui";

const publishedSeries = [
  {
    id: "11111111-1111-4111-8111-111111111111",
    title: "Hamburger Hypnose Munch",
    slug: "hamburger-hypnose-munch",
    location: "Hamburg • Club Catonium",
    frequency: "Monatlich • Jeden 1. Freitag",
    description: "Monatliche Treffen im Club Catonium.",
    formats: ["Hypnose 101/102", "Koalabox"],
    price: "10€ Eintritt",
    tags: ["Alle Levels", "Community"],
    detailUrl: "/events",
    nextEvent: {
      source: "auto",
      text: null,
      datetime: "2026-11-06T19:00:00+01:00",
    },
  },
  {
    id: "22222222-2222-4222-8222-222222222222",
    title: "Hypno Study Frankfurt",
    slug: "hypno-study-frankfurt",
    location: "Frankfurt • Eventspace",
    frequency: "Monatlich • Jeden 3. Mittwoch",
    description: "Deeptalk-Stammtisch mit Fokus auf Techniken.",
    formats: ["Technischer Austausch"],
    price: "12€ Unkostenbeitrag",
    tags: ["Fortgeschritten"],
    detailUrl: "/events",
    nextEvent: {
      source: "manual",
      text: "Mi, 18. Nov 2026",
      datetime: null,
    },
  },
];

async function mockKnownEventSeries(
  page: Page,
  data: unknown[],
): Promise<void> {
  await page.route("**/api/known-event-series", async (route) => {
    await fulfillJson(route, { success: true, data });
  });

  // The home page also loads upcoming events; keep that request out of the way.
  await page.route("**/api/events/upcoming*", async (route) => {
    await fulfillJson(route, { success: true, data: [] });
  });
}

test.describe("Bekannte Event-Reihen auf der Startseite", () => {
  test("zeigt die veröffentlichten Reihen mit aufgelöstem Termin", async ({
    page,
  }) => {
    await bypassComplianceModals(page);
    await mockKnownEventSeries(page, publishedSeries);

    await page.goto("/");
    await dismissComplianceUiIfNeeded(page);

    const section = page.getByRole("region", {
      name: "Bekannte Event-Reihen",
    });
    await expect(section).toBeVisible();

    await expect(
      section.getByRole("heading", { name: "Hamburger Hypnose Munch" }),
    ).toBeVisible();
    await expect(
      section.getByRole("heading", { name: "Hypno Study Frankfurt" }),
    ).toBeVisible();

    // An automatic date is formatted from the ISO timestamp the backend resolved.
    await expect(
      section.getByText("Nächster Termin: Fr, 6. Nov. 2026"),
    ).toBeVisible();
    // A manual date is shown as typed, with the same prefix.
    await expect(
      section.getByText("Nächster Termin: Mi, 18. Nov 2026"),
    ).toBeVisible();
  });

  test("leitet die Städte im Untertitel aus den Reihen ab", async ({
    page,
  }) => {
    await bypassComplianceModals(page);
    await mockKnownEventSeries(page, publishedSeries);

    await page.goto("/");
    await dismissComplianceUiIfNeeded(page);

    await expect(
      page.getByText(
        "Regelmäßige Stammtische und Events in Hamburg und Frankfurt",
      ),
    ).toBeVisible();
  });

  test("blendet die gesamte Sektion aus, wenn keine Reihe veröffentlicht ist", async ({
    page,
  }) => {
    await bypassComplianceModals(page);
    await mockKnownEventSeries(page, []);

    await page.goto("/");
    await dismissComplianceUiIfNeeded(page);

    // Neither the cards nor the heading may leave an empty band behind.
    await expect(
      page.getByRole("heading", { name: "Bekannte Event-Reihen" }),
    ).toHaveCount(0);
  });

  test("blendet die Sektion auch bei einem API-Fehler aus", async ({
    page,
  }) => {
    await bypassComplianceModals(page);
    await page.route("**/api/known-event-series", async (route) => {
      await fulfillJson(route, { success: false, error: "boom" }, 500);
    });
    await page.route("**/api/events/upcoming*", async (route) => {
      await fulfillJson(route, { success: true, data: [] });
    });

    await page.goto("/");
    await dismissComplianceUiIfNeeded(page);

    await expect(
      page.getByRole("heading", { name: "Bekannte Event-Reihen" }),
    ).toHaveCount(0);
    // The rest of the landing page still renders.
    await expect(
      page
        .getByRole("heading", { name: /Sicher.*Hypnose|Willkommen/i })
        .first(),
    ).toBeVisible();
  });
});
