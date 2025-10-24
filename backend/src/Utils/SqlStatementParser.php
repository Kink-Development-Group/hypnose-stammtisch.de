<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Utils;

/**
 * Utility to split SQL files into executable statements while respecting custom delimiters.
 */
final class SqlStatementParser
{
  /**
   * Parse raw SQL content into individual statements ready for execution.
   *
   * @param string $sql Raw SQL string (may include DELIMITER directives)
   *
   * @return array<int, string> List of executable SQL statements
   */
  public static function parse(string $sql): array
  {
    $delimiter = ';';
    $statements = [];
    $buffer = '';

    $lines = preg_split('/\R/', $sql) ?: [];

    foreach ($lines as $line) {
      $trimmedLine = trim($line);

      if ($trimmedLine === '') {
        $buffer .= $line . "\n";
        continue;
      }

      if (stripos($trimmedLine, 'DELIMITER ') === 0) {
        $newDelimiter = trim(substr($trimmedLine, 10));
        $delimiter = $newDelimiter !== '' ? $newDelimiter : ';';
        continue;
      }

      if (str_starts_with($trimmedLine, '--') || str_starts_with($trimmedLine, '#')) {
        continue;
      }

      $buffer .= $line . "\n";

      while (($position = self::findDelimiterPosition($buffer, $delimiter)) !== false) {
        $statement = substr($buffer, 0, $position);
        $buffer = substr($buffer, $position + strlen($delimiter));
        $statement = trim($statement);

        if ($statement !== '') {
          $statements[] = $statement;
        }
      }
    }

    $remaining = trim($buffer);
    if ($remaining !== '') {
      $statements[] = $remaining;
    }

    return $statements;
  }

  /**
   * Find the next delimiter position within the buffer.
   */
  private static function findDelimiterPosition(string $buffer, string $delimiter): int|false
  {
    if ($delimiter === '') {
      return false;
    }

    return strpos($buffer, $delimiter);
  }
}
