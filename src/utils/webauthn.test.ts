import { beforeEach, describe, expect, it, vi } from "vitest";

const adminApi = vi.hoisted(() => vi.fn());
const adminGet = vi.hoisted(() => vi.fn());
const adminPost = vi.hoisted(() => vi.fn());
const adminDelete = vi.hoisted(() => vi.fn());
const browserSupportsWebAuthn = vi.hoisted(() => vi.fn(() => true));
const startRegistration = vi.hoisted(() => vi.fn());
const startAuthentication = vi.hoisted(() => vi.fn());

vi.mock("./adminApi", () => ({ adminApi, adminGet, adminPost, adminDelete }));
vi.mock("@simplewebauthn/browser", () => ({
  browserSupportsWebAuthn,
  startRegistration,
  startAuthentication,
}));

const {
  deletePasskey,
  isPasskeySupported,
  listPasskeys,
  loginWithPasskey,
  registerPasskey,
} = await import("./webauthn");

const REGISTRATION_OPTIONS = {
  challenge: "Y2hhbGxlbmdl",
  rp: { id: "hypnose-stammtisch.de", name: "Hypnose Stammtisch" },
};
const REQUEST_OPTIONS = {
  challenge: "Y2hhbGxlbmdl",
  rpId: "hypnose-stammtisch.de",
};

describe("webauthn helpers", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    browserSupportsWebAuthn.mockReturnValue(true);
  });

  it("reports support based on the browser capability check", () => {
    expect(isPasskeySupported()).toBe(true);

    browserSupportsWebAuthn.mockReturnValue(false);
    expect(isPasskeySupported()).toBe(false);
  });

  it("lists the registered passkeys", async () => {
    adminGet.mockResolvedValue({
      success: true,
      data: { credentials: [{ id: "abc", nickname: "Laptop" }], max: 10 },
    });

    const result = await listPasskeys();

    expect(adminGet).toHaveBeenCalledWith(
      "/api/admin/auth/webauthn/credentials",
    );
    expect(result.success).toBe(true);
    expect(result.data?.credentials).toHaveLength(1);
    expect(result.data?.max).toBe(10);
  });

  it("runs the registration ceremony and posts the credential with its name", async () => {
    adminPost
      .mockResolvedValueOnce({
        success: true,
        data: { options: REGISTRATION_OPTIONS },
      })
      .mockResolvedValueOnce({
        success: true,
        message: "Passkey registriert",
        data: { credential: { id: "cred-1", nickname: "Laptop" } },
      });
    startRegistration.mockResolvedValue({ id: "cred-1", type: "public-key" });

    const result = await registerPasskey("Laptop");

    expect(startRegistration).toHaveBeenCalledWith({
      optionsJSON: REGISTRATION_OPTIONS,
    });
    expect(adminPost).toHaveBeenLastCalledWith(
      "/api/admin/auth/webauthn/register/verify",
      { credential: { id: "cred-1", type: "public-key" }, nickname: "Laptop" },
    );
    expect(result.success).toBe(true);
    expect(result.data?.id).toBe("cred-1");
  });

  it("does not send anything when the user aborts the registration", async () => {
    adminPost.mockResolvedValueOnce({
      success: true,
      data: { options: REGISTRATION_OPTIONS },
    });
    startRegistration.mockRejectedValue(
      Object.assign(new Error("aborted"), { name: "NotAllowedError" }),
    );

    const result = await registerPasskey("Laptop");

    expect(result.success).toBe(false);
    expect(result.message).toBe(
      "Registrierung abgebrochen oder Zeitlimit überschritten.",
    );
    // Only the options call happened – nothing was sent to the verify endpoint.
    expect(adminPost).toHaveBeenCalledTimes(1);
  });

  it("explains an already registered authenticator", async () => {
    adminPost.mockResolvedValueOnce({
      success: true,
      data: { options: REGISTRATION_OPTIONS },
    });
    startRegistration.mockRejectedValue(
      Object.assign(new Error("exists"), { name: "InvalidStateError" }),
    );

    const result = await registerPasskey("Laptop");

    expect(result.success).toBe(false);
    expect(result.message).toContain("bereits ein Passkey");
  });

  it("refuses to start a ceremony in a browser without WebAuthn", async () => {
    browserSupportsWebAuthn.mockReturnValue(false);

    const registration = await registerPasskey("Laptop");
    const login = await loginWithPasskey();

    expect(registration.success).toBe(false);
    expect(login.success).toBe(false);
    expect(adminPost).not.toHaveBeenCalled();
    expect(adminApi).not.toHaveBeenCalled();
  });

  it("runs the login ceremony without a CSRF token", async () => {
    adminApi
      .mockResolvedValueOnce({
        success: true,
        data: { options: REQUEST_OPTIONS },
      })
      .mockResolvedValueOnce({ success: true, data: { id: 42, role: "head" } });
    startAuthentication.mockResolvedValue({ id: "cred-1", type: "public-key" });

    const result = await loginWithPasskey();

    expect(adminApi).toHaveBeenNthCalledWith(
      1,
      "/api/admin/auth/webauthn/login/options",
      { method: "POST", skipCsrf: true },
    );
    expect(startAuthentication).toHaveBeenCalledWith({
      optionsJSON: REQUEST_OPTIONS,
    });
    expect(adminApi).toHaveBeenNthCalledWith(
      2,
      "/api/admin/auth/webauthn/login/verify",
      {
        method: "POST",
        body: { credential: { id: "cred-1", type: "public-key" } },
        skipCsrf: true,
      },
    );
    expect(result.success).toBe(true);
    expect(result.data).toEqual({ id: 42, role: "head" });
  });

  it("passes the backend error message through on a rejected login", async () => {
    adminApi
      .mockResolvedValueOnce({
        success: true,
        data: { options: REQUEST_OPTIONS },
      })
      .mockResolvedValueOnce({
        success: false,
        message: "Passkey-Anmeldung fehlgeschlagen",
      });
    startAuthentication.mockResolvedValue({ id: "cred-1", type: "public-key" });

    const result = await loginWithPasskey();

    expect(result.success).toBe(false);
    expect(result.message).toBe("Passkey-Anmeldung fehlgeschlagen");
  });

  it("url-encodes the credential ID when deleting", async () => {
    adminDelete.mockResolvedValue({
      success: true,
      message: "Passkey gelöscht",
    });

    const result = await deletePasskey("a b/c");

    expect(adminDelete).toHaveBeenCalledWith(
      "/api/admin/auth/webauthn/credentials/a%20b%2Fc",
    );
    expect(result.success).toBe(true);
  });
});
