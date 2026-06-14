<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PHPUnit\Framework\TestCase;

final class ExtensionSmokeTest extends TestCase
{
    public function test_it_registers_with_phpunit_and_prints_a_report_after_execution(): void
    {
        $configuration = __DIR__ . '/Fixtures/SmokeProject/phpunit.xml';
        [$exitCode, $output] = $this->runPhpUnit($configuration);

        self::assertSame(0, $exitCode, $output);
        self::assertStringContainsString('OK (2 tests, 2 assertions)', $output);
        self::assertStringContainsString('Pyrameter', $output);
        self::assertStringContainsString('Shape:', $output);
        self::assertStringContainsString('Total: 2 tests', $output);
    }

    public function test_fail_on_violation_changes_phpunit_exit_code(): void
    {
        $configuration = __DIR__ . '/Fixtures/SmokeProject/phpunit-fail.xml';
        [$exitCode, $output] = $this->runPhpUnit($configuration);

        self::assertNotSame(0, $exitCode, $output);
        self::assertStringContainsString('Pyrameter', $output);
        self::assertStringContainsString('Shape: Inverted Pyramid', $output);
        self::assertStringContainsString('Pyrameter target shape violated.', $output);
    }

    /**
     * @return array{int, string}
     */
    private function runPhpUnit(string $configuration): array
    {
        $phpunit = __DIR__ . '/../vendor/bin/phpunit';
        $command = sprintf(
            '%s %s -c %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($phpunit),
            escapeshellarg($configuration),
        );

        exec($command, $lines, $exitCode);

        return [$exitCode, implode(PHP_EOL, $lines)];
    }
}
