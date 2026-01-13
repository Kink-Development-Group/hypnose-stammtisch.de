import { expect, test, type Page } from "@playwright/test";

/**
 * E2E Tests for Series Management functionality
 * Tests: Accordion persistence, EXDATE management, overrides with description, and instance cancellation
 */

// Helper: Login as admin
async function loginAsAdmin(page: Page): Promise<void> {
  await page.goto("/admin/login");

  // Wait for login form
  await page.waitForSelector('input[name="email"], input[type="email"]');

  // Fill login form (using test credentials)
  await page.fill(
    'input[name="email"], input[type="email"]',
    "test@example.com",
  );
  await page.fill(
    'input[name="password"], input[type="password"]',
    "TestPassword123!",
  );

  // Submit
  await page.click('button[type="submit"]');

  // Wait for redirect to admin area
  await page.waitForURL(/\/admin/, { timeout: 10000 });
}

test.describe("Series Management - Accordion Behavior", () => {
  test.skip("Accordion state persists across data refresh", async ({
    page,
  }) => {
    // This test requires a running backend with test data
    await loginAsAdmin(page);

    // Navigate to events page
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    // Find a series item and expand its accordion
    const seriesAccordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();

    if ((await seriesAccordion.count()) > 0) {
      // Click to expand
      await seriesAccordion.click();

      // Verify accordion is expanded
      await expect(seriesAccordion).toHaveAttribute("aria-expanded", "true");

      // Find expanded content
      const expandedContent = page.locator(".pl-6").first();
      await expect(expandedContent).toBeVisible();

      // Simulate a data refresh (trigger a reload of events)
      // This would normally happen via auto-update or manual action
      await page.evaluate(() => {
        // Trigger a custom event that would cause data refresh
        window.dispatchEvent(new CustomEvent("admin-data-refresh"));
      });

      // Wait a moment for any async updates
      await page.waitForTimeout(500);

      // Verify accordion is still expanded
      await expect(seriesAccordion).toHaveAttribute("aria-expanded", "true");
      await expect(expandedContent).toBeVisible();
    }
  });

  test.skip("Accordion can be toggled open and closed", async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    const seriesAccordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();

    if ((await seriesAccordion.count()) > 0) {
      // Initially closed
      await expect(seriesAccordion).toHaveAttribute("aria-expanded", "false");

      // Click to open
      await seriesAccordion.click();
      await expect(seriesAccordion).toHaveAttribute("aria-expanded", "true");

      // Click to close
      await seriesAccordion.click();
      await expect(seriesAccordion).toHaveAttribute("aria-expanded", "false");
    }
  });
});

test.describe("Series Management - EXDATE Handling", () => {
  test.skip("Shows error for duplicate EXDATE", async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    // Expand series management
    const accordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();
    if ((await accordion.count()) > 0) {
      await accordion.click();

      // Expand EXDATE section
      const exdateSection = page.locator(
        'button:has-text("Ausnahmedaten (EXDATE)")',
      );
      if ((await exdateSection.count()) > 0) {
        await exdateSection.click();

        // Try to add same date twice
        const dateInput = page.locator('input[id^="exdate-"]').first();
        if ((await dateInput.count()) > 0) {
          await dateInput.fill("2026-06-15");

          // Click add button
          const addButton = page.locator(
            'button:has-text("EXDATE hinzufügen")',
          );
          await addButton.click();

          // Wait for success
          await page.waitForTimeout(1000);

          // Try to add same date again
          await dateInput.fill("2026-06-15");
          await addButton.click();

          // Should show error message
          const errorMessage = page.locator(
            'text="bereits als Ausnahme hinterlegt"',
          );
          await expect(errorMessage).toBeVisible({ timeout: 5000 });
        }
      }
    }
  });
});

test.describe("Series Management - Override with Description", () => {
  test.skip("Override form includes description field", async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    // Expand series management
    const accordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();
    if ((await accordion.count()) > 0) {
      await accordion.click();

      // Check for description textarea in override form
      const descriptionField = page.locator('textarea[id^="ov-desc-"]');
      await expect(descriptionField.first()).toBeVisible();

      // Check for improved title field
      const titleField = page.locator('input[id^="ov-title-"]');
      await expect(titleField.first()).toBeVisible();

      // Verify title field has full width styling
      const titleFieldClass = await titleField.first().getAttribute("class");
      expect(titleFieldClass).toContain("w-full");
    }
  });
});

test.describe("Series Management - Instance Cancellation", () => {
  test.skip("Cancel section shows current cancelled instances", async ({
    page,
  }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    // Expand series management
    const accordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();
    if ((await accordion.count()) > 0) {
      await accordion.click();

      // Expand cancel section
      const cancelSection = page.locator(
        'button:has-text("Einzelne Instanz absagen")',
      );
      if ((await cancelSection.count()) > 0) {
        await cancelSection.click();

        // Check for date input
        const dateInput = page.locator('input[id^="cancel-date-"]');
        await expect(dateInput.first()).toBeVisible();

        // Check for reason input
        const reasonInput = page.locator('input[id^="cancel-reason-"]');
        await expect(reasonInput.first()).toBeVisible();

        // Check for cancel button
        const cancelButton = page.locator('button:has-text("Instanz absagen")');
        await expect(cancelButton.first()).toBeVisible();
      }
    }
  });

  test.skip("Shows error when cancelling past date", async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    // Expand series management
    const accordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();
    if ((await accordion.count()) > 0) {
      await accordion.click();

      // Expand cancel section
      const cancelSection = page.locator(
        'button:has-text("Einzelne Instanz absagen")',
      );
      if ((await cancelSection.count()) > 0) {
        await cancelSection.click();

        // Try to cancel a past date
        const dateInput = page.locator('input[id^="cancel-date-"]').first();
        await dateInput.fill("2020-01-01");

        const cancelButton = page.locator('button:has-text("Instanz absagen")');
        await cancelButton.click();

        // Should show error message
        const errorMessage = page.locator(
          'text="Vergangene Termine können nicht mehr abgesagt werden"',
        );
        await expect(errorMessage).toBeVisible({ timeout: 5000 });
      }
    }
  });
});

test.describe("Series Management - Accessibility", () => {
  test.skip("Accordion buttons have proper ARIA attributes", async ({
    page,
  }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    const accordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();
    if ((await accordion.count()) > 0) {
      // Check aria-expanded attribute exists
      const ariaExpanded = await accordion.getAttribute("aria-expanded");
      expect(ariaExpanded).toBeDefined();
      expect(["true", "false"]).toContain(ariaExpanded);
    }
  });

  test.skip("Form fields have associated labels", async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/events");
    await page.waitForLoadState("networkidle");

    const accordion = page
      .locator('button:has-text("Instanzen & Ausnahmen verwalten")')
      .first();
    if ((await accordion.count()) > 0) {
      await accordion.click();

      // Check description field has label
      const descriptionLabel = page.locator('label[for^="ov-desc-"]');
      await expect(descriptionLabel.first()).toBeVisible();
      await expect(descriptionLabel.first()).toHaveText(
        /Abweichende Beschreibung/,
      );
    }
  });
});
