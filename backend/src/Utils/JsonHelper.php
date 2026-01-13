<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

/**
 * Helper class for JSON operations
 */
class JsonHelper
{
  /**
   * Safely decode a JSON string that might be double-encoded.
   * Returns an empty array if decoding fails or result is not an array.
   *
   * @param mixed $json The JSON string to decode (may be null, string, or already decoded)
   * @return array The decoded array or empty array on failure
   */
  public static function decodeArray(mixed $json): array
  {
    // Handle null or empty
    if ($json === null || $json === '') {
      return [];
    }

    // Already an array
    if (is_array($json)) {
      return $json;
    }

    // Not a string, can't decode
    if (!is_string($json)) {
      return [];
    }

    // First decode attempt
    $decoded = json_decode($json, true);

    // If still a string after first decode, try decoding again (double-encoded)
    // This handles cases like '"[]"' -> '[]' -> []
    if (is_string($decoded)) {
      $decoded = json_decode($decoded, true);
    }

    // Return empty array if result is not an array
    return is_array($decoded) ? $decoded : [];
  }
}
