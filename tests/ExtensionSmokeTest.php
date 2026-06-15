<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function exec;
use function implode;
use function sprintf;

use const PHP_BINARY;
use const PHP_EOL;

final class ExtensionSmokeTest extends TestCase
{
    public function testItRegistersWithPhpunitAndPrintsAReportAfterExecution(): void
    {
        $configuration       = __DIR__ . '/Fixtures/SmokeProject/phpunit.xml';
        [$exitCode, $output] = $this->runPhpUnit($configuration);

        self::assertSame(0, $exitCode, $output);
        self::assertStringContainsString('OK (2 tests, 2 assertions)', $output);
        self::assertStringContainsString('Pyrameter', $output);
        self::assertStringContainsString('Shape:', $output);
        self::assertStringContainsString('Total: 2 tests', $output);
    }

    public function testFailOnViolationChangesPhpunitExitCodeWithoutSubscriberWarning(): void
    {
        $configuration       = __DIR__ . '/Fixtures/SmokeProject/phpunit-fail.xml';
        [$exitCode, $output] = $this->runPhpUnit($configuration);

        self::assertSame(1, $exitCode, $output);
        self::assertStringContainsString('Pyrameter', $output);
        self::assertStringContainsString('Result: Violated', $output);
        self::assertStringContainsString('Pyrameter target shape violated.', $output);
        self::assertStringNotContainsString('Exception in third-party event subscriber', $output);
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
