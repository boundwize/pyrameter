<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PHPUnit\Framework\TestCase;

final class ExtensionSmokeTest extends TestCase
{
    public function test_it_registers_with_phpunit_and_prints_a_report_after_execution(): void
    {
        $phpunit = __DIR__ . '/../vendor/bin/phpunit';
        $configuration = __DIR__ . '/Fixtures/SmokeProject/phpunit.xml';
        $command = sprintf(
            '%s %s -c %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($phpunit),
            escapeshellarg($configuration),
        );

        exec($command, $lines, $exitCode);

        $output = implode(PHP_EOL, $lines);

        self::assertSame(0, $exitCode, $output);
        self::assertStringContainsString('OK (2 tests, 2 assertions)', $output);
        self::assertStringContainsString('Pyrameter', $output);
        self::assertStringContainsString('Shape:', $output);
        self::assertStringContainsString('Total: 2 tests', $output);
    }
}
