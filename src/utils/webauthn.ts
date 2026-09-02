/**
 * Passkey (WebAuthn) helpers for the admin area.
 *
 * Passkeys are an additional, alternative login path — password + TOTP keeps
 * working unchanged and stays the fallback for registering the first passkey
 * and for lost devices (see GitHub issue #152).
 *
 * The heavy lifting (base64url handling, `navigator.credentials` quirks) is
 * done by `@simplewebauthn/browser`; this module only talks to the backend and
 * turns browser errors into German messages.
 */

import {
  browserSupportsWebAuthn,
  startAuthentication,
  startRegistration,
} from "@simplewebauthn/browser";
import type {
  PublicKeyCredentialCreationOptionsJSON,
  PublicKeyCredentialRequestOptionsJSON,
} from "@simplewebauthn/browser";
import { adminApi, adminDelete, adminGet, adminPost } from "./adminApi";

/** A registered passkey as returned by the admin API. Never contains key material. */
export interface PasskeyCredential {
  id: string;
  nickname: string;
  transports: string[];
  aaguid: string | null;
  created_at: string;
  last_used_at: string | null;
}

export interface PasskeyResult<T = undefined> {
  success: boolean;
  message: string;
  data?: T;
}

const BASE = "/api/admin/auth/webauthn";

/** Whether this browser can talk to an authenticator at all. */
export function isPasskeySupported(): boolean {
  return typeof window !== "undefined" && browserSupportsWebAuthn();
}

/**
 * List the passkeys of the signed-in user.
 */
export async function listPasskeys(): Promise<
  PasskeyResult<{ credentials: PasskeyCredential[]; max: number }>
> {
  const result = await adminGet<{
    credentials: PasskeyCredential[];
    max: number;
  }>(`${BASE}/credentials`);

  return {
    success: !!result.success,
    message: result.message ?? "",
    data: result.data,
  };
}

/**
 * Register a new passkey for the signed-in user.
 *
 * Requires an already authenticated session, so a passkey can only ever be
 * added by someone who passed password + TOTP (or an existing passkey).
 */
export async function registerPasskey(
  nickname: string,
): Promise<PasskeyResult<PasskeyCredential>> {
  if (!isPasskeySupported()) {
    return { success: false, message: UNSUPPORTED_MESSAGE };
  }

  const optionsResult = await adminPost<{
    options: PublicKeyCredentialCreationOptionsJSON;
  }>(`${BASE}/register/options`);

  if (!optionsResult.success || !optionsResult.data?.options) {
    return {
      success: false,
      message:
        optionsResult.message ?? "Passkey-Registrierung konnte nicht starten.",
    };
  }

  let credential;
  try {
    credential = await startRegistration({
      optionsJSON: optionsResult.data.options,
    });
  } catch (error) {
    return {
      success: false,
      message: describeCeremonyError(error, "register"),
    };
  }

  const verifyResult = await adminPost<{ credential: PasskeyCredential }>(
    `${BASE}/register/verify`,
    { credential, nickname },
  );

  return {
    success: !!verifyResult.success,
    message:
      verifyResult.message ??
      (verifyResult.success
        ? "Passkey registriert."
        : "Passkey konnte nicht gespeichert werden."),
    data: verifyResult.data?.credential,
  };
}

/** Remove one of the signed-in user's passkeys. */
export async function deletePasskey(id: string): Promise<PasskeyResult> {
  const result = await adminDelete(
    `${BASE}/credentials/${encodeURIComponent(id)}`,
  );

  return {
    success: !!result.success,
    message:
      result.message ??
      (result.success
        ? "Passkey gelöscht."
        : "Passkey konnte nicht gelöscht werden."),
  };
}

/**
 * Sign in with a passkey.
 *
 * The ceremony is discoverable ("usernameless"): the authenticator offers the
 * matching passkey itself, so no e-mail address has to be typed first. On
 * success the session is fully authenticated — no TOTP step follows.
 */
export async function loginWithPasskey(): Promise<
  PasskeyResult<Record<string, unknown>>
> {
  if (!isPasskeySupported()) {
    return { success: false, message: UNSUPPORTED_MESSAGE };
  }

  // No CSRF token needed: these endpoints are anonymous by design and the
  // challenge stored in the session is what binds the two requests together.
  const optionsResult = await adminApi<{
    options: PublicKeyCredentialRequestOptionsJSON;
  }>(`${BASE}/login/options`, { method: "POST", skipCsrf: true });

  if (!optionsResult.success || !optionsResult.data?.options) {
    return {
      success: false,
      message:
        optionsResult.message ?? "Passkey-Anmeldung konnte nicht starten.",
    };
  }

  let credential;
  try {
    credential = await startAuthentication({
      optionsJSON: optionsResult.data.options,
    });
  } catch (error) {
    return { success: false, message: describeCeremonyError(error, "login") };
  }

  const verifyResult = await adminApi<Record<string, unknown>>(
    `${BASE}/login/verify`,
    { method: "POST", body: { credential }, skipCsrf: true },
  );

  return {
    success: !!verifyResult.success,
    message:
      verifyResult.message ??
      (verifyResult.success
        ? "Anmeldung erfolgreich."
        : "Passkey-Anmeldung fehlgeschlagen."),
    data: verifyResult.data,
  };
}

const UNSUPPORTED_MESSAGE =
  "Dieser Browser unterstützt keine Passkeys. Bitte melde dich mit Passwort und 2FA-Code an.";

/**
 * Turn a `navigator.credentials` rejection into a German message.
 *
 * The browser deliberately reports very little, so the wording stays generic
 * apart from the cases a user can actually act on.
 */
function describeCeremonyError(
  error: unknown,
  ceremony: "register" | "login",
): string {
  const name =
    error && typeof error === "object" && "name" in error
      ? String((error as { name: unknown }).name)
      : "";

  switch (name) {
    case "NotAllowedError":
      return ceremony === "register"
        ? "Registrierung abgebrochen oder Zeitlimit überschritten."
        : "Anmeldung abgebrochen oder Zeitlimit überschritten.";
    case "InvalidStateError":
      return "Für dieses Konto ist auf diesem Gerät bereits ein Passkey hinterlegt.";
    case "SecurityError":
      return "Die Domain erlaubt keine Passkeys. Bitte die Seite über die offizielle Adresse aufrufen.";
    case "NotSupportedError":
      return UNSUPPORTED_MESSAGE;
    default:
      return ceremony === "register"
        ? "Passkey konnte nicht erstellt werden."
        : "Passkey-Anmeldung fehlgeschlagen.";
  }
}
