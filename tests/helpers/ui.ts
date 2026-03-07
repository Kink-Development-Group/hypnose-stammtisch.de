import type { Page, Route } from "@playwright/test";

const TEST_URLS = ["http://127.0.0.1:5173", "http://localhost:5173"];

export async function fulfillJson(
  route: Route,
  body: unknown,
  status = 200,
): Promise<void> {
  await route.fulfill({
    status,
    contentType: "application/json",
    body: JSON.stringify(body),
  });
}

export async function bypassComplianceModals(page: Page): Promise<void> {
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

  await page.addInitScript(
    ({ encodedConsent }) => {
      document.cookie = "age_verified=true; path=/; SameSite=Lax";
      document.cookie = `cookie_consent=${encodedConsent}; path=/; SameSite=Lax`;
    },
    { encodedConsent: consentValue },
  );

  await page.context().addCookies(
    TEST_URLS.flatMap((url) => [
      {
        name: "age_verified",
        value: "true",
        url,
      },
      {
        name: "cookie_consent",
        value: consentValue,
        url,
      },
    ]),
  );
}

export async function dismissComplianceUiIfNeeded(page: Page): Promise<void> {
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
