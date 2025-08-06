import AxeBuilder from "@axe-core/playwright";
import { expect, test } from "@playwright/test";

test.describe("Accessibility Tests", () => {
  test("Homepage should be accessible", async ({ page }) => {
    await page.goto("/");

    const accessibilityScanResults = await new AxeBuilder({ page })
      .include("main")
      .exclude("iframe") // Exclude any iframes from accessibility scan
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test("Events page should be accessible", async ({ page }) => {
    await page.goto("/events");

    const accessibilityScanResults = await new AxeBuilder({ page })
      .include("main")
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test("Contact form should be accessible", async ({ page }) => {
    await page.goto("/contact");

    const accessibilityScanResults = await new AxeBuilder({ page })
      .include("main")
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test("Event submission form should be accessible", async ({ page }) => {
    await page.goto("/events");

    // Wait for the "Event vorschlagen" button and click it
    await page.waitForSelector("text=Event vorschlagen");
    await page.click("text=Event vorschlagen");

    // Wait for form to load
    await page.waitForSelector("#event-title");

    const accessibilityScanResults = await new AxeBuilder({ page })
      .include("form")
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test("Calendar should be keyboard accessible", async ({ page }) => {
    await page.goto("/events");

    // Focus on calendar
    await page.focus('[role="application"]');

    // Test keyboard navigation
    await page.keyboard.press("ArrowRight");
    await page.keyboard.press("ArrowLeft");
    await page.keyboard.press("Home");

    // Check that focus is maintained
    const focusedElement = await page.locator(":focus");
    await expect(focusedElement).toBeVisible();
  });

  test("Contact form validation should be accessible", async ({ page }) => {
    await page.goto("/contact");

    // Try to submit empty form
    await page.click('button[type="submit"]');

    // Check that error messages are properly announced
    const errorMessages = page.locator('[role="alert"]');
    await expect(errorMessages.first()).toBeVisible();

    // Check that form fields have proper aria-invalid
    const invalidFields = page.locator('[aria-invalid="true"]');
    await expect(invalidFields.first()).toBeVisible();
  });

  test("High contrast mode support", async ({ page }) => {
    // Simulate high contrast mode
    await page.emulateMedia({ colorScheme: "dark", reducedMotion: "reduce" });
    await page.goto("/");

    const accessibilityScanResults = await new AxeBuilder({ page })
      .include("main")
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test("Color contrast should meet WCAG AA standards", async ({ page }) => {
    await page.goto("/");

    const accessibilityScanResults = await new AxeBuilder({ page })
      .include("main")
      .withTags(["wcag2a", "wcag2aa", "wcag21aa"])
      .analyze();

    // Specifically check for color contrast violations
    const contrastViolations = accessibilityScanResults.violations.filter(
      (violation) => violation.id === "color-contrast",
    );

    expect(contrastViolations).toEqual([]);
  });

  test("Focus management in modal dialogs", async ({ page }) => {
    await page.goto("/events");

    // Open an event modal (assuming there are events)
    const eventButton = page.locator('[data-testid="event-card"]').first();
    if ((await eventButton.count()) > 0) {
      await eventButton.click();

      // Check that focus is trapped in modal
      const modal = page.locator('[role="dialog"]');
      await expect(modal).toBeVisible();

      // Check that close button is focusable
      const closeButton = modal.locator('button[aria-label*="schließen"]');
      await expect(closeButton).toBeFocused();
    }
  });

  test("Screen reader compatibility", async ({ page }) => {
    await page.goto("/");

    // Check for proper headings structure
    const h1Elements = page.locator("h1");
    await expect(h1Elements).toHaveCount(1);

    // Check for proper landmarks
    const mainLandmark = page.locator("main");
    await expect(mainLandmark).toBeVisible();

    const navigationLandmark = page.locator("nav");
    await expect(navigationLandmark).toBeVisible();

    // Check for skip links
    const skipLink = page.locator('a[href="#main-content"]');
    if ((await skipLink.count()) > 0) {
      await expect(skipLink).toBeVisible();
    }
  });
});

test.describe("Form Accessibility", () => {
  test("Event submission form labels and descriptions", async ({ page }) => {
    await page.goto("/events");
    await page.click("text=Event vorschlagen");

    // Check that all form fields have proper labels
    const formFields = page.locator(
      "input:visible, textarea:visible, select:visible",
    );
    const fieldCount = await formFields.count();

    for (let i = 0; i < fieldCount; i++) {
      const field = formFields.nth(i);
      const fieldId = await field.getAttribute("id");

      if (fieldId) {
        // Check for associated label
        const label = page.locator(`label[for="${fieldId}"]`);
        await expect(label).toBeVisible();
      }
    }
  });

  test("Contact form error announcements", async ({ page }) => {
    await page.goto("/contact");

    // Fill form with invalid data
    await page.fill("#contact-name", "A"); // Too short
    await page.fill("#contact-email", "invalid-email");
    await page.fill("#contact-message", "Short"); // Too short

    await page.click('button[type="submit"]');

    // Check that errors are announced
    const errorElements = page.locator('[role="alert"]');
    await expect(errorElements.first()).toBeVisible();

    // Check aria-invalid attributes
    const invalidEmail = page.locator('#contact-email[aria-invalid="true"]');
    await expect(invalidEmail).toBeVisible();
  });
});

test.describe("Mobile Accessibility", () => {
  test.use({ viewport: { width: 375, height: 667 } }); // iPhone SE size

  test("Mobile navigation should be accessible", async ({ page }) => {
    await page.goto("/");

    // Check for mobile menu button
    const menuButton = page.locator(
      '[aria-label*="Menu"], [aria-label*="Navigation"]',
    );
    if ((await menuButton.count()) > 0) {
      await expect(menuButton).toBeVisible();
      await menuButton.click();

      // Check that menu opens and is accessible
      const mobileMenu = page.locator(
        '[role="menu"], nav[aria-expanded="true"]',
      );
      await expect(mobileMenu).toBeVisible();
    }
  });

  test("Mobile form interactions should be accessible", async ({ page }) => {
    await page.goto("/contact");

    const accessibilityScanResults = await new AxeBuilder({ page })
      .include("main")
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });
});
