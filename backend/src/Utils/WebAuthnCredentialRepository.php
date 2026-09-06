<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

use HypnoseStammtisch\Database\Database;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Persistence layer for registered passkeys (`user_webauthn_credentials`).
 *
 * Binary values (credential ID, COSE public key) are stored base64url encoded
 * so the table stays plain ASCII and survives dumps/imports on shared hosting.
 */
class WebAuthnCredentialRepository
{
    /** Maximum number of passkeys a single account may register. */
    public const MAX_CREDENTIALS_PER_USER = 10;

    /** Fallback AAGUID for authenticators that do not report one. */
    private const NIL_AAGUID = '00000000-0000-0000-0000-000000000000';

    /**
     * Look up a credential by its raw (binary) credential ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findByCredentialId(string $rawCredentialId): ?array
    {
        $row = Database::fetchOne(
            'SELECT * FROM user_webauthn_credentials WHERE credential_id = ?',
            [self::encode($rawCredentialId)]
        );

        return $row ?: null;
    }

    /**
     * All credentials of a user, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function findByUserId(int|string $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM user_webauthn_credentials WHERE user_id = ? ORDER BY created_at DESC',
            [(string)$userId]
        );
    }

    /** Number of passkeys registered for a user. */
    public static function countForUser(int|string $userId): int
    {
        $row = Database::fetchOne(
            'SELECT COUNT(*) AS c FROM user_webauthn_credentials WHERE user_id = ?',
            [(string)$userId]
        );

        return $row ? (int)$row['c'] : 0;
    }

    /**
     * Descriptors of the credentials a user already registered.
     *
     * Passed as `excludeCredentials` so an authenticator does not create a
     * second passkey for an account it already knows.
     *
     * @return PublicKeyCredentialDescriptor[]
     */
    public static function excludeDescriptorsForUser(int|string $userId): array
    {
        $descriptors = [];
        foreach (self::findByUserId($userId) as $row) {
            $descriptors[] = PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                self::decode((string)$row['credential_id']),
                self::decodeTransports($row['transports'] ?? null)
            );
        }

        return $descriptors;
    }

    /**
     * Persist a freshly registered credential.
     *
     * @return string The generated row ID
     */
    public static function store(int|string $userId, CredentialRecord $record, string $nickname): string
    {
        $id = self::uuid();

        Database::execute(
            'INSERT INTO user_webauthn_credentials
                (id, user_id, credential_id, public_key, sign_count, transports, aaguid, nickname,
                 user_handle, attestation_type, is_backup_eligible, is_backed_up)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $id,
                (string)$userId,
                self::encode($record->publicKeyCredentialId),
                self::encode($record->credentialPublicKey),
                (string)$record->counter,
                json_encode(array_values($record->transports)),
                $record->aaguid->toRfc4122(),
                $nickname,
                $record->userHandle,
                $record->attestationType,
                self::boolParam($record->backupEligible),
                self::boolParam($record->backupStatus),
            ]
        );

        return $id;
    }

    /** Update the stored counter/backup flags after a successful assertion. */
    public static function recordSuccessfulAssertion(string $id, CredentialRecord $record): void
    {
        Database::execute(
            'UPDATE user_webauthn_credentials
                SET sign_count = ?, is_backup_eligible = ?, is_backed_up = ?, last_used_at = CURRENT_TIMESTAMP
              WHERE id = ?',
            [
                (string)$record->counter,
                self::boolParam($record->backupEligible),
                self::boolParam($record->backupStatus),
                $id,
            ]
        );
    }

    /** Delete one of the user's own credentials. Returns false if it did not exist. */
    public static function deleteForUser(int|string $userId, string $id): bool
    {
        $statement = Database::execute(
            'DELETE FROM user_webauthn_credentials WHERE id = ? AND user_id = ?',
            [$id, (string)$userId]
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Rebuild the library's credential record from a database row.
     *
     * @param array<string, mixed> $row
     */
    public static function toCredentialRecord(array $row): CredentialRecord
    {
        return CredentialRecord::create(
            self::decode((string)$row['credential_id']),
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            self::decodeTransports($row['transports'] ?? null),
            (string)($row['attestation_type'] ?? 'none'),
            EmptyTrustPath::create(),
            Uuid::fromString(self::normalizeAaguid($row['aaguid'] ?? null)),
            self::decode((string)$row['public_key']),
            (string)$row['user_handle'],
            (int)$row['sign_count'],
            null,
            self::nullableBool($row['is_backup_eligible'] ?? null),
            self::nullableBool($row['is_backed_up'] ?? null),
            true,
        );
    }

    /**
     * Shape a row for the admin UI. Never exposes key material.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function toApiArray(array $row): array
    {
        return [
            'id' => (string)$row['id'],
            'nickname' => (string)$row['nickname'],
            'transports' => self::decodeTransports($row['transports'] ?? null),
            'aaguid' => $row['aaguid'] !== null ? (string)$row['aaguid'] : null,
            'created_at' => date('c', strtotime((string)$row['created_at'])),
            'last_used_at' => !empty($row['last_used_at'])
                ? date('c', strtotime((string)$row['last_used_at']))
                : null,
        ];
    }

    /** Base64url encode without padding. */
    public static function encode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    /** Base64url decode, tolerating missing padding. */
    public static function decode(string $encoded): string
    {
        $padded = strtr($encoded, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return (string)base64_decode($padded, true);
    }

    /**
     * @return string[]
     */
    private static function decodeTransports(mixed $stored): array
    {
        if (!is_string($stored) || $stored === '') {
            return [];
        }

        $decoded = json_decode($stored, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_string'));
    }

    private static function normalizeAaguid(mixed $stored): string
    {
        return is_string($stored) && $stored !== '' ? $stored : self::NIL_AAGUID;
    }

    private static function boolParam(?bool $value): ?int
    {
        return $value === null ? null : ($value ? 1 : 0);
    }

    private static function nullableBool(mixed $value): ?bool
    {
        return $value === null ? null : (bool)$value;
    }

    /** RFC 4122 v4 identifier, matching the `VARCHAR(36)` primary keys used elsewhere. */
    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
