<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

/**
 * Minimal TOTP (RFC 6238) helper without external dependency.
 */
class Totp
{
  /**
   * Generate a random Base32 secret.
   */
    public static function generateSecret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

  /**
   * Verify a user provided TOTP code.
   */
    public static function verify(string $secret, string $code, int $window = 1, int $digits = 6, int $period = 30): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{' . $digits . '}$/', $code)) {
            return false;
        }
        $currentTime = time();
        $timeStep = intdiv($currentTime, $period);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::totpAt($secret, $timeStep + $i, $digits, $period), $code)) {
                return true;
            }
        }
        return false;
    }

  /**
   * Get provisioning URI for QR code (otpauth://)
   */
    public static function provisioningUri(string $secret, string $email, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $issuerEnc = rawurlencode($issuer);
        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30', $label, $secret, $issuerEnc);
    }

    private static function totpAt(string $secret, int $timeStep, int $digits, int $period): string
    {
        $key = self::base32Decode($secret);
        $binaryTime = pack('N*', 0) . pack('N*', $timeStep); // 64-bit integer
        $hash = hash_hmac('sha1', $binaryTime, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = (ord($hash[$offset]) & 0x7F) << 24 |
        (ord($hash[$offset + 1]) & 0xFF) << 16 |
        (ord($hash[$offset + 2]) & 0xFF) << 8 |
        (ord($hash[$offset + 3]) & 0xFF);
        $mod = 10 ** $digits;
        return str_pad((string)($value % $mod), $digits, '0', STR_PAD_LEFT);
    }

  /**
   * Decode Base32 (RFC 4648)
   */
    private static function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper($b32);
        $bits = '';
        for ($i = 0; $i < strlen($b32); $i++) {
            $char = $b32[$i];
            if ($char === '=') {
                break;
            }
            $val = strpos($alphabet, $char);
            if ($val === false) {
                continue;
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }
        return $output;
    }
}
