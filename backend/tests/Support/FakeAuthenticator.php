<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Support;

use RuntimeException;

/**
 * Minimal software authenticator for the WebAuthn tests.
 *
 * Produces the exact JSON structures a browser hands to
 * `/auth/webauthn/*` — attestation for registration, assertion for login —
 * signed with a real ES256 key pair, so the ceremony validation runs against
 * genuine cryptography instead of fixtures.
 *
 * Only what the tests need is implemented: packed CBOR for the attestation
 * object and the COSE key, attestation format `none`, algorithm ES256.
 */
final class FakeAuthenticator
{
    public const FLAG_USER_PRESENT = 0x01;
    public const FLAG_USER_VERIFIED = 0x04;
    public const FLAG_ATTESTED_CREDENTIAL_DATA = 0x40;

    private const AAGUID = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    /** @var \OpenSSLAsymmetricKey */
    private $privateKey;

    private string $credentialId;

    private string $coseKey;

    public function __construct(?string $credentialId = null)
    {
        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if ($key === false) {
            throw new RuntimeException('Unable to generate an EC key pair for the fake authenticator');
        }

        $this->privateKey = $key;
        $this->credentialId = $credentialId ?? random_bytes(32);

        $details = openssl_pkey_get_details($key);
        if (!is_array($details) || !isset($details['ec']['x'], $details['ec']['y'])) {
            throw new RuntimeException('Unable to read the EC key coordinates');
        }

        $this->coseKey = self::encodeCoseEc2Key(
            str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT),
            str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT)
        );
    }

    public function credentialId(): string
    {
        return $this->credentialId;
    }

    /**
     * Build the response of `navigator.credentials.create()`.
     *
     * @return array<string, mixed>
     */
    public function createAttestation(
        string $rpId,
        string $origin,
        string $challenge,
        int $signCount = 0,
        int $extraFlags = 0
    ): array {
        $flags = self::FLAG_USER_PRESENT | self::FLAG_USER_VERIFIED | self::FLAG_ATTESTED_CREDENTIAL_DATA
            | $extraFlags;

        $attestedCredentialData = self::AAGUID
            . pack('n', strlen($this->credentialId))
            . $this->credentialId
            . $this->coseKey;

        $authData = self::authenticatorData($rpId, $flags, $signCount) . $attestedCredentialData;

        $attestationObject = self::cborMap([
            [self::cborTextString('fmt'), self::cborTextString('none')],
            [self::cborTextString('attStmt'), self::cborHeader(5, 0)],
            [self::cborTextString('authData'), self::cborByteString($authData)],
        ]);

        $clientDataJson = self::clientDataJson('webauthn.create', $challenge, $origin);

        return [
            'id' => self::base64url($this->credentialId),
            'rawId' => self::base64url($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::base64url($clientDataJson),
                'attestationObject' => self::base64url($attestationObject),
                'transports' => ['internal'],
            ],
            'clientExtensionResults' => [],
        ];
    }

    /**
     * Build the response of `navigator.credentials.get()`.
     *
     * @return array<string, mixed>
     */
    public function createAssertion(
        string $rpId,
        string $origin,
        string $challenge,
        string $userHandle,
        int $signCount = 1,
        int $extraFlags = 0
    ): array {
        $flags = self::FLAG_USER_PRESENT | self::FLAG_USER_VERIFIED | $extraFlags;
        $authData = self::authenticatorData($rpId, $flags, $signCount);
        $clientDataJson = self::clientDataJson('webauthn.get', $challenge, $origin);

        $signature = '';
        $signed = openssl_sign(
            $authData . hash('sha256', $clientDataJson, true),
            $signature,
            $this->privateKey,
            OPENSSL_ALGO_SHA256
        );

        if ($signed === false) {
            throw new RuntimeException('Unable to sign the assertion');
        }

        return [
            'id' => self::base64url($this->credentialId),
            'rawId' => self::base64url($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::base64url($clientDataJson),
                'authenticatorData' => self::base64url($authData),
                'signature' => self::base64url($signature),
                'userHandle' => self::base64url($userHandle),
            ],
            'clientExtensionResults' => [],
        ];
    }

    public static function base64url(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public static function base64urlDecode(string $encoded): string
    {
        $padded = strtr($encoded, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return (string)base64_decode($padded, true);
    }

    private static function authenticatorData(string $rpId, int $flags, int $signCount): string
    {
        return hash('sha256', $rpId, true) . chr($flags) . pack('N', $signCount);
    }

    private static function clientDataJson(string $type, string $challenge, string $origin): string
    {
        return (string)json_encode([
            'type' => $type,
            'challenge' => self::base64url($challenge),
            'origin' => $origin,
            'crossOrigin' => false,
        ]);
    }

    /** COSE_Key for an EC2/P-256 public key usable with ES256. */
    private static function encodeCoseEc2Key(string $x, string $y): string
    {
        return self::cborMap([
            [self::cborInt(1), self::cborInt(2)],      // kty: EC2
            [self::cborInt(3), self::cborInt(-7)],     // alg: ES256
            [self::cborInt(-1), self::cborInt(1)],     // crv: P-256
            [self::cborInt(-2), self::cborByteString($x)],
            [self::cborInt(-3), self::cborByteString($y)],
        ]);
    }

    /**
     * @param array<int, array{0: string, 1: string}> $pairs Already encoded key/value pairs
     */
    private static function cborMap(array $pairs): string
    {
        $out = self::cborHeader(5, count($pairs));
        foreach ($pairs as [$key, $value]) {
            $out .= $key . $value;
        }

        return $out;
    }

    private static function cborTextString(string $value): string
    {
        return self::cborHeader(3, strlen($value)) . $value;
    }

    private static function cborByteString(string $value): string
    {
        return self::cborHeader(2, strlen($value)) . $value;
    }

    private static function cborInt(int $value): string
    {
        return $value >= 0
            ? self::cborHeader(0, $value)
            : self::cborHeader(1, -1 - $value);
    }

    /** CBOR initial byte plus the additional argument for `$value`. */
    private static function cborHeader(int $majorType, int $value): string
    {
        $major = $majorType << 5;

        if ($value < 24) {
            return chr($major | $value);
        }
        if ($value < 0x100) {
            return chr($major | 24) . chr($value);
        }
        if ($value < 0x10000) {
            return chr($major | 25) . pack('n', $value);
        }

        return chr($major | 26) . pack('N', $value);
    }
}
