<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Config\Config;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * WebAuthn/Passkey helper.
 *
 * Wraps `web-auth/webauthn-lib` and holds the relying party configuration that
 * both ceremonies (registration and authentication) need. Passkeys are an
 * additional login path next to password + TOTP, never a replacement.
 *
 * Relying party configuration (all optional, sensible defaults are derived from
 * `FRONTEND_URL` / `APP_URL`):
 *
 * - `WEBAUTHN_RP_ID`   – the relying party ID, e.g. `hypnose-stammtisch.de`
 * - `WEBAUTHN_RP_NAME` – display name shown by the authenticator
 * - `WEBAUTHN_ORIGINS` – comma separated list of allowed origins
 *
 * @see https://www.w3.org/TR/webauthn-3/
 */
class WebAuthnService
{
    /** Session key holding the pending registration options (JSON). */
    public const SESSION_REGISTRATION = 'webauthn_registration';

    /** Session key holding the pending login options (JSON). */
    public const SESSION_LOGIN = 'webauthn_login';

    /** How long a generated challenge stays valid (seconds). */
    public const CHALLENGE_TTL = 300;

    /** Ceremony timeout communicated to the browser (milliseconds). */
    private const CEREMONY_TIMEOUT_MS = 60000;

    private static ?SerializerInterface $serializer = null;

    /**
     * Relying party ID — the domain the credential is bound to.
     *
     * Defaults to the host of the frontend URL with a leading `www.` stripped,
     * so credentials created on `www.example.org` keep working on `example.org`
     * and vice versa.
     */
    public static function rpId(): string
    {
        $configured = trim((string)($_ENV['WEBAUTHN_RP_ID'] ?? ''));
        if ($configured !== '') {
            return strtolower($configured);
        }

        $host = self::hostFromUrl((string)Config::get('app.frontend_url', ''))
            ?? self::hostFromUrl((string)Config::get('app.url', ''));

        if ($host === null) {
            return 'localhost';
        }

        return preg_replace('/^www\./', '', $host) ?? $host;
    }

    /** Human readable relying party name shown in the authenticator prompt. */
    public static function rpName(): string
    {
        $configured = trim((string)($_ENV['WEBAUTHN_RP_NAME'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        return (string)Config::get('app.name', 'Hypnose Stammtisch');
    }

    /**
     * Origins accepted during a ceremony.
     *
     * Derived from the configured frontend/app URLs; for each derived origin the
     * `www.` counterpart is accepted as well so both spellings of the site work.
     *
     * @return string[]
     */
    public static function allowedOrigins(): array
    {
        $configured = trim((string)($_ENV['WEBAUTHN_ORIGINS'] ?? ''));
        if ($configured !== '') {
            $origins = array_filter(array_map('trim', explode(',', $configured)));
            return array_values(array_unique($origins));
        }

        $origins = [];
        foreach ([Config::get('app.frontend_url', ''), Config::get('app.url', '')] as $url) {
            $origin = self::originFromUrl((string)$url);
            if ($origin === null) {
                continue;
            }
            $origins[] = $origin;

            // Accept the apex/www counterpart of the same origin.
            $parts = parse_url($origin);
            $host = $parts['host'] ?? '';
            if ($host === '') {
                continue;
            }
            $counterpart = str_starts_with($host, 'www.')
                ? substr($host, 4)
                : 'www.' . $host;
            $origins[] = str_replace('//' . $host, '//' . $counterpart, $origin);
        }

        return array_values(array_unique($origins));
    }

    /**
     * The user handle stored inside the authenticator.
     *
     * The numeric user ID is stable, opaque to third parties and contains no
     * personal data, which is exactly what the specification asks for.
     */
    public static function userHandle(int|string $userId): string
    {
        return (string)$userId;
    }

    /** Shared serializer able to (de)serialize all WebAuthn structures. */
    public static function serializer(): SerializerInterface
    {
        if (self::$serializer === null) {
            self::$serializer = (new WebauthnSerializerFactory(self::attestationSupportManager()))->create();
        }

        return self::$serializer;
    }

    /**
     * Build the options for registering a new passkey.
     *
     * @param array<string, mixed> $user Row from the `users` table
     * @param PublicKeyCredentialDescriptor[] $excludeCredentials Already registered credentials
     */
    public static function createRegistrationOptions(
        array $user,
        array $excludeCredentials = []
    ): PublicKeyCredentialCreationOptions {
        $userEntity = PublicKeyCredentialUserEntity::create(
            (string)$user['email'],
            self::userHandle($user['id']),
            (string)($user['username'] ?? $user['email'])
        );

        return PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create(self::rpName(), self::rpId()),
            $userEntity,
            random_bytes(32),
            [
                PublicKeyCredentialParameters::createPk(-7),    // ES256
                PublicKeyCredentialParameters::createPk(-257),  // RS256
            ],
            AuthenticatorSelectionCriteria::create(
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            ),
            // No attestation statement is requested: we do not evaluate
            // authenticator models, so asking for one would only collect data.
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials,
            self::CEREMONY_TIMEOUT_MS,
        );
    }

    /**
     * Build the options for a passkey login.
     *
     * `allowCredentials` stays empty: the login is discoverable ("usernameless"),
     * so the authenticator itself offers the matching passkey and we never leak
     * which credentials exist for a given account.
     */
    public static function createRequestOptions(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            rpId: self::rpId(),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: self::CEREMONY_TIMEOUT_MS,
        );
    }

    /** Serialize ceremony options to the JSON the browser API expects. */
    public static function serializeOptions(
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options
    ): string {
        return self::serializer()->serialize($options, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
    }

    /** Restore registration options previously stored in the session. */
    public static function deserializeRegistrationOptions(string $json): PublicKeyCredentialCreationOptions
    {
        /** @var PublicKeyCredentialCreationOptions $options */
        $options = self::serializer()->deserialize($json, PublicKeyCredentialCreationOptions::class, 'json');

        return $options;
    }

    /** Restore login options previously stored in the session. */
    public static function deserializeRequestOptions(string $json): PublicKeyCredentialRequestOptions
    {
        /** @var PublicKeyCredentialRequestOptions $options */
        $options = self::serializer()->deserialize($json, PublicKeyCredentialRequestOptions::class, 'json');

        return $options;
    }

    /** Parse the credential the browser returned. */
    public static function parseCredential(string $json): PublicKeyCredential
    {
        /** @var PublicKeyCredential $credential */
        $credential = self::serializer()->deserialize($json, PublicKeyCredential::class, 'json');

        return $credential;
    }

    /**
     * Validate a registration ceremony and return the record to persist.
     *
     * @throws \Throwable when the attestation response is not acceptable
     */
    public static function verifyRegistration(
        AuthenticatorAttestationResponse $response,
        PublicKeyCredentialCreationOptions $options,
        string $host
    ): CredentialRecord {
        $factory = self::ceremonyFactory();
        $validator = AuthenticatorAttestationResponseValidator::create($factory->creationCeremony());

        return $validator->check($response, $options, $host);
    }

    /**
     * Validate a login ceremony and return the updated record.
     *
     * @throws \Throwable when the assertion is not acceptable
     */
    public static function verifyAssertion(
        CredentialRecord $record,
        AuthenticatorAssertionResponse $response,
        PublicKeyCredentialRequestOptions $options,
        string $host,
        ?string $userHandle = null
    ): CredentialRecord {
        $factory = self::ceremonyFactory();
        $validator = AuthenticatorAssertionResponseValidator::create($factory->requestCeremony());

        return $validator->check($record, $response, $options, $host, $userHandle);
    }

    /** Host of the current request, used as fallback inside the ceremony steps. */
    public static function currentHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!is_string($host) || $host === '') {
            return self::rpId();
        }

        return strtolower(preg_replace('/:\d+$/', '', $host) ?? $host);
    }

    /** Ceremony factory pinned to the configured origins. */
    private static function ceremonyFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAttestationStatementSupportManager(self::attestationSupportManager());

        $origins = self::allowedOrigins();
        if ($origins !== []) {
            $factory->setAllowedOrigins($origins);
        }

        return $factory;
    }

    private static function attestationSupportManager(): AttestationStatementSupportManager
    {
        $manager = AttestationStatementSupportManager::create();
        $manager->add(NoneAttestationStatementSupport::create());

        return $manager;
    }

    private static function hostFromUrl(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    private static function originFromUrl(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
        $port = $parts['port'] ?? null;
        $defaultPorts = ['http' => 80, 'https' => 443];
        if ($port !== null && ($defaultPorts[strtolower($parts['scheme'])] ?? null) !== $port) {
            $origin .= ':' . $port;
        }

        return $origin;
    }
}
