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

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('OK (2 tests, 2 assertions)', $output);
        $this->assertStringContainsString('Pyrameter', $output);
        $this->assertStringContainsString('Shape:', $output);
        $this->assertStringContainsString('Total: 2 tests', $output);
    }

    public function testFailOnViolationChangesPhpunitExitCodeWithoutSubscriberWarning(): void
    {
        $configuration       = __DIR__ . '/Fixtures/SmokeProject/phpunit-fail.xml';
        [$exitCode, $output] = $this->runPhpUnit($configuration);

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('Pyrameter', $output);
        $this->assertStringContainsString('Result: Violated', $output);
        $this->assertStringContainsString('Pyrameter target shape violated.', $output);
        $this->assertStringNotContainsString('Exception in third-party event subscriber', $output);
    }

    public function testFailOnViolationDoesNotHidePhpunitFailureDetails(): void
    {
        $configuration       = __DIR__ . '/Fixtures/SmokeProject/phpunit-failing-test.xml';
        [$exitCode, $output] = $this->runPhpUnit($configuration);

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('Pyrameter', $output);
        $this->assertStringContainsString('Result: Violated', $output);
        $this->assertStringContainsString('Pyrameter target shape violated.', $output);
        $this->assertStringContainsString('There was 1 failure', $output);
        $this->assertStringContainsString('FailingPdoSmokeFixture::testFails', $output);
        $this->assertStringContainsString('Failed asserting', $output);
        $this->assertStringNotContainsString('Exception in third-party event subscriber', $output);
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
