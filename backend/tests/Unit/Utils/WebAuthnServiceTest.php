<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Utils;

use HypnoseStammtisch\Config\Config;
use HypnoseStammtisch\Tests\Support\FakeAuthenticator;
use HypnoseStammtisch\Utils\WebAuthnCredentialRepository;
use HypnoseStammtisch\Utils\WebAuthnService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;

/**
 * End-to-end coverage of both WebAuthn ceremonies against a software
 * authenticator with real ES256 keys — no database access involved.
 */
#[CoversClass(WebAuthnService::class)]
#[CoversClass(WebAuthnCredentialRepository::class)]
final class WebAuthnServiceTest extends TestCase
{
    private const RP_ID = 'hypnose-stammtisch.de';
    private const ORIGIN = 'https://hypnose-stammtisch.de';
    private const USER_HANDLE = '42';

    /** @var array<string, string|null> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['WEBAUTHN_RP_ID', 'WEBAUTHN_RP_NAME', 'WEBAUTHN_ORIGINS', 'FRONTEND_URL', 'APP_URL'] as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? null;
        }

        $_ENV['WEBAUTHN_RP_ID'] = self::RP_ID;
        $_ENV['WEBAUTHN_RP_NAME'] = 'Hypnose Stammtisch';
        $_ENV['WEBAUTHN_ORIGINS'] = self::ORIGIN;
        Config::reset();
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
        Config::reset();

        parent::tearDown();
    }

    public function testRegistrationOptionsAreBrowserCompatible(): void
    {
        $options = $this->registrationOptions();
        $payload = json_decode(WebAuthnService::serializeOptions($options), true);

        self::assertIsArray($payload);
        self::assertSame(self::RP_ID, $payload['rp']['id']);
        self::assertSame('Hypnose Stammtisch', $payload['rp']['name']);
        self::assertSame('required', $payload['authenticatorSelection']['userVerification']);
        self::assertSame('required', $payload['authenticatorSelection']['residentKey']);
        self::assertSame('none', $payload['attestation']);
        self::assertContains(['type' => 'public-key', 'alg' => -7], $payload['pubKeyCredParams']);
        self::assertContains(['type' => 'public-key', 'alg' => -257], $payload['pubKeyCredParams']);

        // The challenge must survive the base64url round trip with its full entropy.
        self::assertSame(32, strlen(FakeAuthenticator::base64urlDecode($payload['challenge'])));
        // The user handle is the opaque account ID, not the e-mail address.
        self::assertSame(self::USER_HANDLE, FakeAuthenticator::base64urlDecode($payload['user']['id']));
    }

    public function testAlreadyRegisteredCredentialsAreExcluded(): void
    {
        $known = random_bytes(16);
        $options = WebAuthnService::createRegistrationOptions(
            ['id' => self::USER_HANDLE, 'email' => 'admin@hypnose-stammtisch.de', 'username' => 'admin'],
            [PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $known,
                ['internal']
            )]
        );

        $payload = json_decode(WebAuthnService::serializeOptions($options), true);

        // The browser needs the credential ID base64url encoded to refuse a
        // second passkey for the same account on the same authenticator.
        self::assertSame(
            [['type' => 'public-key', 'id' => FakeAuthenticator::base64url($known), 'transports' => ['internal']]],
            $payload['excludeCredentials']
        );
    }

    public function testLoginOptionsRequestDiscoverableCredentials(): void
    {
        $payload = json_decode(WebAuthnService::serializeOptions(WebAuthnService::createRequestOptions()), true);

        self::assertIsArray($payload);
        self::assertSame(self::RP_ID, $payload['rpId']);
        self::assertSame('required', $payload['userVerification']);
        // Empty allowCredentials keeps the ceremony usernameless and avoids
        // leaking which credentials exist for an account.
        self::assertSame([], $payload['allowCredentials'] ?? []);
    }

    public function testRelyingPartyIdFallsBackToFrontendHostWithoutWww(): void
    {
        unset($_ENV['WEBAUTHN_RP_ID'], $_ENV['WEBAUTHN_ORIGINS']);
        $_ENV['FRONTEND_URL'] = 'https://www.hypnose-stammtisch.de';
        $_ENV['APP_URL'] = 'https://www.hypnose-stammtisch.de';
        Config::reset();

        self::assertSame(self::RP_ID, WebAuthnService::rpId());
        // Both spellings of the host are accepted as origins.
        self::assertEqualsCanonicalizing(
            ['https://www.hypnose-stammtisch.de', 'https://hypnose-stammtisch.de'],
            WebAuthnService::allowedOrigins()
        );
    }

    public function testRegistrationAcceptsValidAttestation(): void
    {
        $authenticator = new FakeAuthenticator();
        $options = $this->registrationOptions();

        $record = $this->register($authenticator, $options);

        self::assertSame($authenticator->credentialId(), $record->publicKeyCredentialId);
        self::assertSame(self::USER_HANDLE, $record->userHandle);
        self::assertSame(['internal'], $record->transports);
        self::assertSame('none', $record->attestationType);
    }

    public function testRegistrationRejectsForeignOrigin(): void
    {
        $authenticator = new FakeAuthenticator();
        $options = $this->registrationOptions();

        $this->expectException(Throwable::class);
        $this->register($authenticator, $options, 'https://evil.example.com');
    }

    public function testRegistrationRejectsWrongRelyingPartyId(): void
    {
        $authenticator = new FakeAuthenticator();
        $options = $this->registrationOptions();

        $this->expectException(Throwable::class);
        $this->register($authenticator, $options, self::ORIGIN, 'evil.example.com');
    }

    public function testRegistrationRejectsForeignChallenge(): void
    {
        $authenticator = new FakeAuthenticator();
        $options = $this->registrationOptions();

        $response = $this->attestationResponse(
            $authenticator->createAttestation(self::RP_ID, self::ORIGIN, random_bytes(32))
        );

        $this->expectException(Throwable::class);
        WebAuthnService::verifyRegistration($response, $options, self::RP_ID);
    }

    public function testAssertionSucceedsForStoredCredential(): void
    {
        $authenticator = new FakeAuthenticator();
        $stored = $this->registerAndPersist($authenticator);

        $requestOptions = WebAuthnService::createRequestOptions();
        $assertion = $authenticator->createAssertion(
            self::RP_ID,
            self::ORIGIN,
            $requestOptions->challenge,
            self::USER_HANDLE,
            5
        );

        $record = WebAuthnService::verifyAssertion(
            WebAuthnCredentialRepository::toCredentialRecord($stored),
            $this->assertionResponse($assertion),
            $requestOptions,
            self::RP_ID
        );

        self::assertSame(5, $record->counter);
    }

    public function testAssertionRejectsNonIncreasingSignCount(): void
    {
        $authenticator = new FakeAuthenticator();
        $stored = $this->registerAndPersist($authenticator);
        $stored['sign_count'] = 7;

        $requestOptions = WebAuthnService::createRequestOptions();
        // A cloned authenticator replays an older counter value.
        $assertion = $authenticator->createAssertion(
            self::RP_ID,
            self::ORIGIN,
            $requestOptions->challenge,
            self::USER_HANDLE,
            3
        );

        $this->expectException(Throwable::class);
        WebAuthnService::verifyAssertion(
            WebAuthnCredentialRepository::toCredentialRecord($stored),
            $this->assertionResponse($assertion),
            $requestOptions,
            self::RP_ID
        );
    }

    public function testAssertionRejectsTamperedSignature(): void
    {
        $authenticator = new FakeAuthenticator();
        $stored = $this->registerAndPersist($authenticator);

        $requestOptions = WebAuthnService::createRequestOptions();
        $assertion = $authenticator->createAssertion(
            self::RP_ID,
            self::ORIGIN,
            $requestOptions->challenge,
            self::USER_HANDLE,
            9
        );

        $signature = FakeAuthenticator::base64urlDecode($assertion['response']['signature']);
        $signature[strlen($signature) - 1] = chr(ord($signature[strlen($signature) - 1]) ^ 0xFF);
        $assertion['response']['signature'] = FakeAuthenticator::base64url($signature);

        $this->expectException(Throwable::class);
        WebAuthnService::verifyAssertion(
            WebAuthnCredentialRepository::toCredentialRecord($stored),
            $this->assertionResponse($assertion),
            $requestOptions,
            self::RP_ID
        );
    }

    public function testAssertionRejectsMismatchedUserHandle(): void
    {
        $authenticator = new FakeAuthenticator();
        $stored = $this->registerAndPersist($authenticator);

        $requestOptions = WebAuthnService::createRequestOptions();
        $assertion = $authenticator->createAssertion(
            self::RP_ID,
            self::ORIGIN,
            $requestOptions->challenge,
            '999',
            4
        );

        $this->expectException(Throwable::class);
        WebAuthnService::verifyAssertion(
            WebAuthnCredentialRepository::toCredentialRecord($stored),
            $this->assertionResponse($assertion),
            $requestOptions,
            self::RP_ID
        );
    }

    public function testCredentialIdEncodingRoundTrip(): void
    {
        $raw = random_bytes(64);
        $encoded = WebAuthnCredentialRepository::encode($raw);

        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $encoded);
        self::assertSame($raw, WebAuthnCredentialRepository::decode($encoded));
    }

    public function testApiArrayNeverExposesKeyMaterial(): void
    {
        $authenticator = new FakeAuthenticator();
        $stored = $this->registerAndPersist($authenticator);

        $api = WebAuthnCredentialRepository::toApiArray($stored);

        self::assertSame(['id', 'nickname', 'transports', 'aaguid', 'created_at', 'last_used_at'], array_keys($api));
        self::assertSame('Test-Passkey', $api['nickname']);
        self::assertSame(['internal'], $api['transports']);
    }

    private function registrationOptions(): PublicKeyCredentialCreationOptions
    {
        return WebAuthnService::createRegistrationOptions([
            'id' => self::USER_HANDLE,
            'email' => 'admin@hypnose-stammtisch.de',
            'username' => 'admin',
        ]);
    }

    private function register(
        FakeAuthenticator $authenticator,
        PublicKeyCredentialCreationOptions $options,
        string $origin = self::ORIGIN,
        string $rpId = self::RP_ID
    ): CredentialRecord {
        $response = $this->attestationResponse(
            $authenticator->createAttestation($rpId, $origin, $options->challenge)
        );

        return WebAuthnService::verifyRegistration($response, $options, self::RP_ID);
    }

    /**
     * Register a credential and shape it like a `user_webauthn_credentials` row.
     *
     * @return array<string, mixed>
     */
    private function registerAndPersist(FakeAuthenticator $authenticator): array
    {
        $record = $this->register($authenticator, $this->registrationOptions());

        return [
            'id' => '11111111-2222-3333-4444-555555555555',
            'user_id' => (int)self::USER_HANDLE,
            'credential_id' => WebAuthnCredentialRepository::encode($record->publicKeyCredentialId),
            'public_key' => WebAuthnCredentialRepository::encode($record->credentialPublicKey),
            'sign_count' => $record->counter,
            'transports' => json_encode($record->transports),
            'aaguid' => $record->aaguid->toRfc4122(),
            'nickname' => 'Test-Passkey',
            'user_handle' => $record->userHandle,
            'attestation_type' => $record->attestationType,
            'is_backup_eligible' => $record->backupEligible === null ? null : (int)$record->backupEligible,
            'is_backed_up' => $record->backupStatus === null ? null : (int)$record->backupStatus,
            'created_at' => '2026-09-02 10:00:00',
            'last_used_at' => null,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function attestationResponse(array $payload): AuthenticatorAttestationResponse
    {
        $credential = WebAuthnService::parseCredential((string)json_encode($payload));
        self::assertInstanceOf(AuthenticatorAttestationResponse::class, $credential->response);

        return $credential->response;
    }

    /** @param array<string, mixed> $payload */
    private function assertionResponse(array $payload): AuthenticatorAssertionResponse
    {
        $credential = WebAuthnService::parseCredential((string)json_encode($payload));
        self::assertInstanceOf(AuthenticatorAssertionResponse::class, $credential->response);

        return $credential->response;
    }
}
