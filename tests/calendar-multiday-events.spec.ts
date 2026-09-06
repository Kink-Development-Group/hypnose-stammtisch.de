import dayjs from "dayjs";
import { expect, test, type Page } from "@playwright/test";
import { bypassComplianceModals, fulfillJson } from "./helpers/ui";

// Die App nutzt die deutsche Locale, deren Woche am Montag beginnt.
const startOfGermanWeek = (date: dayjs.Dayjs) =>
  date.subtract((date.day() + 6) % 7, "day").startOf("day");

// Mehrtägiges Event, das sicher innerhalb einer Kalenderwoche (Mo–So) liegt:
// Dienstag bis Freitag der Woche des 15. im aktuellen Monat.
const spanStart = startOfGermanWeek(
  dayjs().startOf("month").add(14, "day"),
).add(1, "day");
const spanEnd = spanStart.add(3, "day");

const SPAN_TITLE = "Mehrtägiger Hypnose-Workshop";
const SINGLE_TITLE = "Einzelner Abendtermin";

async function mockMultiDayEvents(page: Page): Promise<void> {
  const apiEvents = [
    {
      id: 1,
      title: SPAN_TITLE,
      description: "Workshop über mehrere Tage",
      start_datetime: `${spanStart.format("YYYY-MM-DD")}T10:00:00`,
      end_datetime: `${spanEnd.format("YYYY-MM-DD")}T16:00:00`,
      timezone: "Europe/Berlin",
      location_type: "physical",
      location_address: "Berlin",
      tags: ["workshop"],
      difficulty_level: "beginner",
      created_at: "2026-03-01T08:00:00Z",
      updated_at: "2026-03-01T08:00:00Z",
    },
    {
      id: 2,
      title: SINGLE_TITLE,
      description: "Einzelner Termin am dritten Tag",
      start_datetime: `${spanStart.add(2, "day").format("YYYY-MM-DD")}T19:00:00`,
      end_datetime: `${spanStart.add(2, "day").format("YYYY-MM-DD")}T21:00:00`,
      timezone: "Europe/Berlin",
      location_type: "online",
      tags: ["stammtisch"],
      difficulty_level: "beginner",
      created_at: "2026-03-01T08:00:00Z",
      updated_at: "2026-03-01T08:00:00Z",
    },
  ];

  await page.route("**/api/events?view=expanded**", async (route) => {
    await fulfillJson(route, { success: true, data: apiEvents });
  });
}

test.describe("Mehrtägige Events in der Monatsansicht", () => {
  test.use({ viewport: { width: 1280, height: 900 } });

  test.beforeEach(async ({ page }) => {
    await bypassComplianceModals(page);
    await mockMultiDayEvents(page);
    await page.goto("/events");
    await page.waitForLoadState("networkidle");
  });

  test("markiert jeden Tag des Zeitraums", async ({ page }) => {
    const segments = page.getByRole("button", { name: new RegExp(SPAN_TITLE) });
    await expect(segments).toHaveCount(4);

    for (let dayOffset = 0; dayOffset < 4; dayOffset++) {
      const day = spanStart.add(dayOffset, "day");
      const cell = page.locator(".calendar-day", {
        has: page.getByRole("button", {
          name: new RegExp(`${SPAN_TITLE}.*Tag ${dayOffset + 1} von 4`),
        }),
      });
      await expect(
        cell,
        `Tageszelle für ${day.format("DD.MM.")} enthält das Event`,
      ).toHaveCount(1);
    }
  });

  test("benennt Fortsetzungstage für Screenreader eindeutig", async ({
    page,
  }) => {
    await expect(
      page.getByRole("button", {
        name: new RegExp(`${SPAN_TITLE}.*Tag 1 von 4`),
      }),
    ).toBeVisible();
    await expect(
      page.getByRole("button", {
        name: new RegExp(`${SPAN_TITLE}.*Tag 4 von 4`),
      }),
    ).toBeVisible();
  });

  test("zeichnet den Balken ohne Lücke über die Tageszellen", async ({
    page,
  }) => {
    const boxes = [];

    for (let dayOffset = 0; dayOffset < 4; dayOffset++) {
      const segment = page.getByRole("button", {
        name: new RegExp(`${SPAN_TITLE}.*Tag ${dayOffset + 1} von 4`),
      });
      const box = await segment.boundingBox();
      expect(box).not.toBeNull();
      boxes.push(box!);
    }

    for (let index = 1; index < boxes.length; index++) {
      const previous = boxes[index - 1];
      const current = boxes[index];

      // Segmente liegen auf derselben Zeile …
      expect(Math.abs(current.y - previous.y)).toBeLessThanOrEqual(1);
      // … und schließen ohne sichtbare Lücke aneinander an.
      const gap = current.x - (previous.x + previous.width);
      expect(gap).toBeLessThanOrEqual(1);
    }
  });

  test("hält den mehrtägigen Balken über einzelnen Terminen auf einer Zeile", async ({
    page,
  }) => {
    const spanSegment = page.getByRole("button", {
      name: new RegExp(`${SPAN_TITLE}.*Tag 3 von 4`),
    });
    const singleEvent = page.getByRole("button", {
      name: new RegExp(SINGLE_TITLE),
    });

    const spanBox = await spanSegment.boundingBox();
    const singleBox = await singleEvent.boundingBox();

    expect(spanBox).not.toBeNull();
    expect(singleBox).not.toBeNull();
    expect(spanBox!.y).toBeLessThan(singleBox!.y);
  });

  test("öffnet das Event-Modal auch von einem Fortsetzungstag aus", async ({
    page,
  }) => {
    await page
      .getByRole("button", {
        name: new RegExp(`${SPAN_TITLE}.*Tag 3 von 4`),
      })
      .click();

    const modal = page.getByRole("dialog").first();
    await expect(modal).toBeVisible();
    await expect(modal).toContainText(SPAN_TITLE);
  });
});

test.describe("Mehrtägige Events in der Wochenansicht", () => {
  test.use({ viewport: { width: 1280, height: 900 } });

  test("zeigt das Event an jedem betroffenen Wochentag", async ({ page }) => {
    await bypassComplianceModals(page);
    await mockMultiDayEvents(page);
    await page.goto("/events");
    await page.waitForLoadState("networkidle");

    await page.getByRole("button", { name: "Woche", exact: true }).click();

    // Von der aktuellen Woche zur Woche des Zeitraums navigieren. Je nach
    // Testlaufdatum liegt sie vor oder hinter der aktuellen Woche.
    const weekOffset = startOfGermanWeek(spanStart).diff(
      startOfGermanWeek(dayjs()),
      "week",
    );
    const stepButton = page.getByRole("button", {
      name: weekOffset < 0 ? /Vorherige Woche/ : /Nächste Woche/,
    });

    for (let step = 0; step < Math.abs(weekOffset); step++) {
      await stepButton.click();
    }

    await expect(
      page.getByRole("button", { name: new RegExp(SPAN_TITLE) }),
    ).toHaveCount(4);
    await expect(page.getByText("Tag 2 von 4")).toBeVisible();
  });
});

const weekMonday = startOfGermanWeek(dayjs().startOf("month").add(14, "day"));

const OVERLAP_A = "Wochenend-Intensivkurs";
const OVERLAP_B = "Parallele Peer-Gruppe";

test.describe("Überlappende mehrtägige Events", () => {
  test.use({ viewport: { width: 1280, height: 900 } });

  test("hält jedes Event über den gesamten Zeitraum auf derselben Zeile", async ({
    page,
  }) => {
    await bypassComplianceModals(page);

    await page.route("**/api/events?view=expanded**", async (route) => {
      await fulfillJson(route, {
        success: true,
        data: [
          {
            id: 10,
            title: OVERLAP_A,
            description: "Montag bis Mittwoch",
            start_datetime: `${weekMonday.format("YYYY-MM-DD")}T10:00:00`,
            end_datetime: `${weekMonday.add(2, "day").format("YYYY-MM-DD")}T16:00:00`,
            timezone: "Europe/Berlin",
            location_type: "physical",
            tags: [],
            created_at: "2026-03-01T08:00:00Z",
            updated_at: "2026-03-01T08:00:00Z",
          },
          {
            id: 11,
            title: OVERLAP_B,
            description: "Dienstag bis Donnerstag",
            start_datetime: `${weekMonday.add(1, "day").format("YYYY-MM-DD")}T18:00:00`,
            end_datetime: `${weekMonday.add(3, "day").format("YYYY-MM-DD")}T20:00:00`,
            timezone: "Europe/Berlin",
            location_type: "online",
            tags: [],
            created_at: "2026-03-01T08:00:00Z",
            updated_at: "2026-03-01T08:00:00Z",
          },
        ],
      });
    });

    await page.goto("/events");
    await page.waitForLoadState("networkidle");

    const rowY = async (title: string, dayIndex: number) => {
      const box = await page
        .getByRole("button", {
          name: new RegExp(`${title}.*Tag ${dayIndex} von 3`),
        })
        .boundingBox();
      expect(box).not.toBeNull();
      return box!.y;
    };

    const aY = await Promise.all([1, 2, 3].map((day) => rowY(OVERLAP_A, day)));
    const bY = await Promise.all([1, 2, 3].map((day) => rowY(OVERLAP_B, day)));

    // Beide Balken bleiben über ihren gesamten Zeitraum auf einer Zeile …
    for (const y of aY) expect(Math.abs(y - aY[0])).toBeLessThanOrEqual(1);
    for (const y of bY) expect(Math.abs(y - bY[0])).toBeLessThanOrEqual(1);

    // … und liegen dabei auf unterschiedlichen Zeilen, auch am Donnerstag,
    // an dem das erste Event bereits vorbei ist.
    expect(bY[0]).toBeGreaterThan(aY[0]);
  });
});

test.describe("Tageszellen mit vielen Lanes", () => {
  test.use({ viewport: { width: 1280, height: 900 } });

  test("verdrängt einzelne Termine nicht durch leere Lane-Platzhalter", async ({
    page,
  }) => {
    await bypassComplianceModals(page);

    // Drei überlappende mehrtägige Events belegen Mo–Mi drei Lanes. Am Freitag
    // sind alle drei Lanes leer; der Einzeltermin dort darf nicht hinter
    // unsichtbaren Platzhaltern verschwinden.
    const spans = [0, 1, 2].map((index) => ({
      id: 20 + index,
      title: `Paralleler Kurs ${index + 1}`,
      description: "Montag bis Mittwoch",
      start_datetime: `${weekMonday.format("YYYY-MM-DD")}T${10 + index}:00:00`,
      end_datetime: `${weekMonday.add(2, "day").format("YYYY-MM-DD")}T16:00:00`,
      timezone: "Europe/Berlin",
      location_type: "physical",
      tags: [],
      created_at: "2026-03-01T08:00:00Z",
      updated_at: "2026-03-01T08:00:00Z",
    }));

    await page.route("**/api/events?view=expanded**", async (route) => {
      await fulfillJson(route, {
        success: true,
        data: [
          ...spans,
          {
            id: 30,
            title: "Freitagstermin",
            description: "Einzelner Termin am Freitag",
            start_datetime: `${weekMonday.add(4, "day").format("YYYY-MM-DD")}T19:00:00`,
            end_datetime: `${weekMonday.add(4, "day").format("YYYY-MM-DD")}T21:00:00`,
            timezone: "Europe/Berlin",
            location_type: "online",
            tags: [],
            created_at: "2026-03-01T08:00:00Z",
            updated_at: "2026-03-01T08:00:00Z",
          },
        ],
      });
    });

    await page.goto("/events");
    await page.waitForLoadState("networkidle");

    const fridayEvent = page.getByRole("button", { name: /Freitagstermin/ });
    await expect(fridayEvent).toBeVisible();
    await expect(fridayEvent).toBeEnabled();

    // Kein "+N weitere" in der Freitagszelle – es gibt nichts zu verbergen.
    const fridayCell = page
      .locator(".calendar-day", { has: fridayEvent })
      .first();
    await expect(fridayCell.getByText(/weitere/)).toHaveCount(0);

    // Alle drei Balken bleiben am Montag sichtbar.
    for (const index of [1, 2, 3]) {
      await expect(
        page.getByRole("button", {
          name: new RegExp(`Paralleler Kurs ${index}.*Tag 1 von 3`),
        }),
      ).toBeVisible();
    }
  });
});
