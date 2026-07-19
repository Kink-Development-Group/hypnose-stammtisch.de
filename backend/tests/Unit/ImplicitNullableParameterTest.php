<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards against "Implicitly marking parameter $x as nullable is deprecated"
 * (PHP 8.4+). Such a notice is printed while the class is being compiled, long
 * before any header() call, and therefore corrupts binary responses like the
 * ICS feed - so it has to stay at zero.
 *
 * Reflection cannot detect this: PHP applies the implicit nullability, so
 * `string $a = null` and `?string $a = null` are indistinguishable afterwards.
 * The only reliable oracle is PHP itself compiling the file, which is why this
 * runs in a fresh subprocess (the autoloader would already have compiled
 * everything in this one).
 */
class ImplicitNullableParameterTest extends TestCase
{
  private const DEPRECATION = 'Implicitly marking parameter';

  /**
   * Compile every file in the given directory in a fresh PHP process and
   * return whatever diagnostics it printed.
   */
  private function compileAndCaptureDiagnostics(string $directory): string
  {
    $runner = <<<'PHP'
      <?php
      $dir = $argv[1];
      $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
      foreach ($files as $file) {
          if ($file->getExtension() === 'php') {
              require_once $file->getPathname();
          }
      }
      PHP;

    $runnerPath = sys_get_temp_dir() . '/hs_compile_probe_' . getmypid() . '.php';
    file_put_contents($runnerPath, $runner);

    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    $command = sprintf(
      '%s -d error_reporting=-1 -d display_errors=1 -d auto_prepend_file=%s %s %s 2>&1',
      escapeshellarg(PHP_BINARY),
      escapeshellarg($autoload),
      escapeshellarg($runnerPath),
      escapeshellarg($directory)
    );

    $output = (string)shell_exec($command);
    @unlink($runnerPath);

    return $output;
  }

  public function testDetectionActuallyWorks(): void
  {
    // Without this the whole test could pass vacuously - a typo in the command
    // or a swallowed error stream would look exactly like "no deprecations".
    $probeDir = sys_get_temp_dir() . '/hs_nullable_probe_' . getmypid();
    @mkdir($probeDir, 0777, true);
    file_put_contents(
      $probeDir . '/Offender.php',
      "<?php\nclass HsProbeOffender { public function f(string \$a = null): void {} }\n"
    );

    $output = $this->compileAndCaptureDiagnostics($probeDir);

    @unlink($probeDir . '/Offender.php');
    @rmdir($probeDir);

    $this->assertStringContainsString(
      self::DEPRECATION,
      $output,
      'The probe should have been flagged - the detection itself is broken.'
    );
  }

  public function testNoSourceFileDeclaresAnImplicitlyNullableParameter(): void
  {
    $output = $this->compileAndCaptureDiagnostics(dirname(__DIR__, 2) . '/src');

    $offenders = array_values(array_filter(
      explode("\n", $output),
      fn(string $line): bool => str_contains($line, self::DEPRECATION)
    ));

    $this->assertSame([], $offenders, "Implicitly nullable parameters found:\n" . implode("\n", $offenders));
  }
}
