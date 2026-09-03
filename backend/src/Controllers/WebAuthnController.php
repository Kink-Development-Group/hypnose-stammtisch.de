<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Controllers;

use HypnoseStammtisch\Database\Database;
use HypnoseStammtisch\Middleware\AdminAuth;
use HypnoseStammtisch\Utils\AuditLogger;
use HypnoseStammtisch\Utils\FailedLoginTracker;
use HypnoseStammtisch\Utils\IpBanManager;
use HypnoseStammtisch\Utils\RateLimiter;
use HypnoseStammtisch\Utils\Response;
use HypnoseStammtisch\Utils\WebAuthnCredentialRepository;
use HypnoseStammtisch\Utils\WebAuthnService;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;

/**
 * Passkey (WebAuthn/FIDO2) endpoints for the admin area.
 *
 * Passkeys are an *additional* login path. Password + TOTP keeps working
 * unchanged and stays the fallback for first-time passkey registration and for
 * lost devices — see GitHub issue #152.
 */
class WebAuthnController
{
    /** Generic message for every failed login attempt: never reveal which part failed. */
    private const LOGIN_FAILED_MESSAGE = 'Passkey-Anmeldung fehlgeschlagen';

    /**
     * Registration step 1 — options for `navigator.credentials.create()`.
     *
     * Requires a fully authenticated session: a passkey may only be added by
     * someone who already proved their identity via password + TOTP (or an
     * existing passkey).
     */
    public static function registerOptions(): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();

        $user = AdminAuth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
            return;
        }

        if (
            WebAuthnCredentialRepository::countForUser($user['id'])
            >= WebAuthnCredentialRepository::MAX_CREDENTIALS_PER_USER
        ) {
            Response::error(
                'Maximale Anzahl an Passkeys erreicht. Bitte zuerst einen bestehenden Passkey löschen.',
                409
            );
            return;
        }

        try {
            $options = WebAuthnService::createRegistrationOptions(
                $user,
                WebAuthnCredentialRepository::excludeDescriptorsForUser($user['id'])
            );
            $json = WebAuthnService::serializeOptions($options);
        } catch (Throwable $e) {
            error_log('WebAuthn registration options failed: ' . $e->getMessage());
            Response::error('Passkey-Registrierung konnte nicht gestartet werden', 500);
            return;
        }

        $_SESSION[WebAuthnService::SESSION_REGISTRATION] = [
            'options' => $json,
            'user_id' => (string)$user['id'],
            'created_at' => time(),
        ];

        Response::success(['options' => json_decode($json, true)], 'Registrierung vorbereitet');
    }

    /**
     * Registration step 2 — verify the attestation and store the credential.
     */
    public static function registerVerify(): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();

        $user = AdminAuth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
            return;
        }

        $input = self::decodeJsonBody();

        // Validate the name before consuming the challenge: a rejected name
        // would otherwise burn the pending ceremony and force a full restart.
        $nickname = self::sanitizeNickname($input['nickname'] ?? null);
        if ($nickname === null) {
            Response::error('Bitte einen Namen für den Passkey angeben (1–100 Zeichen).', 400);
            return;
        }

        $pending = self::takePendingCeremony(WebAuthnService::SESSION_REGISTRATION);
        if ($pending === null || ($pending['user_id'] ?? null) !== (string)$user['id']) {
            Response::error('Keine gültige Passkey-Registrierung offen. Bitte erneut starten.', 400);
            return;
        }

        $credentialJson = self::extractCredentialJson($input);
        if ($credentialJson === null) {
            Response::error('Ungültige Authenticator-Antwort', 400);
            return;
        }

        try {
            $credential = WebAuthnService::parseCredential($credentialJson);
            $response = $credential->response;
            if (!$response instanceof AuthenticatorAttestationResponse) {
                Response::error('Ungültige Authenticator-Antwort', 400);
                return;
            }

            $record = WebAuthnService::verifyRegistration(
                $response,
                WebAuthnService::deserializeRegistrationOptions((string)$pending['options']),
                WebAuthnService::currentHost()
            );
        } catch (Throwable $e) {
            AuditLogger::log('webauthn.register_failed', 'user', (string)$user['id'], [
                'reason' => $e->getMessage(),
            ]);
            Response::error('Passkey konnte nicht verifiziert werden', 400);
            return;
        }

        if (WebAuthnCredentialRepository::findByCredentialId($record->publicKeyCredentialId) !== null) {
            Response::error('Dieser Passkey ist bereits registriert.', 409);
            return;
        }

        $id = WebAuthnCredentialRepository::store($user['id'], $record, $nickname);
        AuditLogger::log('webauthn.register_success', 'user', (string)$user['id'], ['credential' => $id]);

        $row = Database::fetchOne('SELECT * FROM user_webauthn_credentials WHERE id = ?', [$id]);

        Response::success(
            ['credential' => $row ? WebAuthnCredentialRepository::toApiArray($row) : ['id' => $id]],
            'Passkey registriert'
        );
    }

    /**
     * Login step 1 — options for `navigator.credentials.get()`.
     *
     * No authentication required. The ceremony is discoverable ("usernameless"),
     * so the response is identical for every visitor and leaks no information
     * about which accounts or credentials exist.
     */
    public static function loginOptions(): void
    {
        AdminAuth::startSession();

        $ip = IpBanManager::getClientIP();
        if (IpBanManager::checkIPBanMiddleware($ip)['blocked']) {
            Response::error('Access denied', 403);
            return;
        }

        $rl = RateLimiter::attempt('webauthn_login_options:' . $ip, 20, 300);
        if (!$rl['allowed']) {
            Response::error('Too many login attempts. Try again later.', 429, ['reset' => $rl['reset']]);
            return;
        }

        try {
            $options = WebAuthnService::createRequestOptions();
            $json = WebAuthnService::serializeOptions($options);
        } catch (Throwable $e) {
            error_log('WebAuthn login options failed: ' . $e->getMessage());
            Response::error('Passkey-Anmeldung konnte nicht gestartet werden', 500);
            return;
        }

        $_SESSION[WebAuthnService::SESSION_LOGIN] = [
            'options' => $json,
            'created_at' => time(),
        ];

        Response::success(['options' => json_decode($json, true)], 'Anmeldung vorbereitet');
    }

    /**
     * Login step 2 — verify the assertion and open an authenticated session.
     *
     * A verified passkey replaces password + TOTP: the credential is bound to
     * the origin and required user verification, so it already covers both
     * factors (possession of the authenticator plus PIN/biometrics).
     */
    public static function loginVerify(): void
    {
        AdminAuth::startSession();

        $ip = IpBanManager::getClientIP();
        if (IpBanManager::checkIPBanMiddleware($ip)['blocked']) {
            Response::error('Access denied', 403);
            return;
        }

        $rl = RateLimiter::attempt('webauthn_login:' . $ip, 10, 300);
        if (!$rl['allowed']) {
            Response::error('Too many login attempts. Try again later.', 429, ['reset' => $rl['reset']]);
            return;
        }

        $pending = self::takePendingCeremony(WebAuthnService::SESSION_LOGIN);
        if ($pending === null) {
            Response::error('Keine gültige Passkey-Anmeldung offen. Bitte erneut starten.', 400);
            return;
        }

        $input = self::decodeJsonBody();
        $credentialJson = self::extractCredentialJson($input);
        if ($credentialJson === null) {
            Response::error('Ungültige Authenticator-Antwort', 400);
            return;
        }

        try {
            $credential = WebAuthnService::parseCredential($credentialJson);
            $response = $credential->response;
            if (!$response instanceof AuthenticatorAssertionResponse) {
                Response::error('Ungültige Authenticator-Antwort', 400);
                return;
            }
        } catch (Throwable $e) {
            AuditLogger::log('webauthn.login_failed', null, null, ['ip' => $ip, 'reason' => 'malformed']);
            Response::error(self::LOGIN_FAILED_MESSAGE, 400);
            return;
        }

        $row = WebAuthnCredentialRepository::findByCredentialId($credential->rawId);
        if ($row === null) {
            FailedLoginTracker::handleFailedLogin(null, null, $ip);
            AuditLogger::log('webauthn.login_failed', null, null, ['ip' => $ip, 'reason' => 'unknown_credential']);
            Response::error(self::LOGIN_FAILED_MESSAGE, 401);
            return;
        }

        $userId = (int)$row['user_id'];
        $user = Database::fetchOne(
            'SELECT id, username, email, role, is_active FROM users WHERE id = ? AND is_active = 1',
            [(string)$userId]
        );

        if (!$user) {
            AuditLogger::log('webauthn.login_failed', 'user', (string)$userId, [
                'ip' => $ip,
                'reason' => 'inactive_user',
            ]);
            Response::error(self::LOGIN_FAILED_MESSAGE, 401);
            return;
        }

        if (FailedLoginTracker::isAccountLocked($userId)) {
            Response::error('Account is temporarily locked due to security reasons', 401);
            return;
        }

        try {
            $record = WebAuthnService::verifyAssertion(
                WebAuthnCredentialRepository::toCredentialRecord($row),
                $response,
                WebAuthnService::deserializeRequestOptions((string)$pending['options']),
                WebAuthnService::currentHost()
            );
        } catch (Throwable $e) {
            FailedLoginTracker::handleFailedLogin($userId, (string)$user['email'], $ip);
            AuditLogger::log('webauthn.login_failed', 'user', (string)$userId, [
                'ip' => $ip,
                'reason' => $e->getMessage(),
            ]);
            Response::error(self::LOGIN_FAILED_MESSAGE, 401);
            return;
        }

        WebAuthnCredentialRepository::recordSuccessfulAssertion((string)$row['id'], $record);
        FailedLoginTracker::clearFailedAttemptsForAccount($userId);

        // Fresh session ID: the assertion is the point where the anonymous
        // visitor becomes an authenticated admin.
        session_regenerate_id(true);
        unset($_SESSION['admin_user_pending_id'], $_SESSION['admin_user_password_ok']);

        $final = AdminAuth::finalizeLogin($userId, 'passkey');
        if (!$final['success']) {
            Response::error($final['message'] ?? 'Unknown error', 500);
            return;
        }

        AuditLogger::log('webauthn.login_success', 'user', (string)$userId, ['credential' => (string)$row['id']]);
        Response::success($final['user'], 'Login successful');
    }

    /** List the passkeys of the current user. */
    public static function listCredentials(): void
    {
        AdminAuth::requireAuth();

        $user = AdminAuth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
            return;
        }

        $credentials = array_map(
            [WebAuthnCredentialRepository::class, 'toApiArray'],
            WebAuthnCredentialRepository::findByUserId($user['id'])
        );

        Response::success([
            'credentials' => $credentials,
            'max' => WebAuthnCredentialRepository::MAX_CREDENTIALS_PER_USER,
        ]);
    }

    /** Delete one of the current user's passkeys. */
    public static function deleteCredential(string $id): void
    {
        AdminAuth::requireAuth();
        AdminAuth::requireCSRF();

        $user = AdminAuth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
            return;
        }

        if (!WebAuthnCredentialRepository::deleteForUser($user['id'], $id)) {
            Response::notFound(['message' => 'Passkey nicht gefunden']);
            return;
        }

        AuditLogger::log('webauthn.credential_deleted', 'user', (string)$user['id'], ['credential' => $id]);
        Response::success(null, 'Passkey gelöscht');
    }

    /**
     * Read and consume a pending ceremony from the session.
     *
     * Challenges are single-use and expire after
     * {@see WebAuthnService::CHALLENGE_TTL} seconds.
     *
     * @return array<string, mixed>|null
     */
    private static function takePendingCeremony(string $sessionKey): ?array
    {
        $pending = $_SESSION[$sessionKey] ?? null;
        unset($_SESSION[$sessionKey]);

        if (!is_array($pending) || !isset($pending['options'], $pending['created_at'])) {
            return null;
        }

        if (time() - (int)$pending['created_at'] > WebAuthnService::CHALLENGE_TTL) {
            return null;
        }

        return $pending;
    }

    /**
     * Decode the JSON request body into an array.
     *
     * A body that is not a JSON object (a bare scalar, `null`, or malformed
     * JSON) decodes to a non-array value that `?? []` would not catch, which
     * would then hit the array-typed parameters below as a TypeError. Anything
     * that is not an array is treated as an empty body instead.
     *
     * @return array<string, mixed>
     */
    private static function decodeJsonBody(): array
    {
        $decoded = json_decode((string)file_get_contents('php://input'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The browser credential, accepted either as a nested object or as a JSON
     * string — `@simplewebauthn/browser` returns an object, hand-written
     * clients often send the raw JSON.
     *
     * @param array<string, mixed> $input
     */
    private static function extractCredentialJson(array $input): ?string
    {
        $credential = $input['credential'] ?? null;

        if (is_string($credential) && $credential !== '') {
            return $credential;
        }

        if (is_array($credential)) {
            $json = json_encode($credential);
            return $json === false ? null : $json;
        }

        return null;
    }

    private static function sanitizeNickname(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $nickname = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '');
        if ($nickname === '' || mb_strlen($nickname) > 100) {
            return null;
        }

        return $nickname;
    }
}
