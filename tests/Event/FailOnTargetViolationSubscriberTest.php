<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Event;

use Boundwize\Pyrameter\Analysis\TestCollector;
use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Event\FailOnTargetViolationSubscriber;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\Tests\Fixtures\TelemetryInfoFactory;
use Boundwize\Pyrameter\ValueObject\TestRecord;
use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Framework\TestCase;

final class FailOnTargetViolationSubscriberTest extends TestCase
{
    public function testItDoesNothingWhenFailOnViolationIsDisabled(): void
    {
        $testCollector = new TestCollector();
        $testCollector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));
        $testCollector->add(new TestRecord(self::class, 'testIntegration', [], TestKind::Integration));

        $exitStatus = null;

        $failOnTargetViolationSubscriber = new FailOnTargetViolationSubscriber(
            testCollector: $testCollector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            failOnViolation: false,
            exit: static function (int $status) use (&$exitStatus): void {
                $exitStatus = $status;
            },
        );

        $failOnTargetViolationSubscriber->notify(new Finished($this->telemetryInfo(), 0));

        $this->assertNull($exitStatus);
    }

    public function testItDoesNothingWhenTargetsPass(): void
    {
        $testCollector = new TestCollector();
        $testCollector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));

        $exitStatus = null;

        $failOnTargetViolationSubscriber = new FailOnTargetViolationSubscriber(
            testCollector: $testCollector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            failOnViolation: true,
            exit: static function (int $status) use (&$exitStatus): void {
                $exitStatus = $status;
            },
        );

        $failOnTargetViolationSubscriber->notify(new Finished($this->telemetryInfo(), 0));

        $this->assertNull($exitStatus);
    }

    public function testItTerminatesWithFailureWhenTargetsAreViolatedAndPhpunitWouldPass(): void
    {
        $testCollector = new TestCollector();
        $testCollector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));
        $testCollector->add(new TestRecord(self::class, 'testIntegration', [], TestKind::Integration));

        $exitStatus = null;

        $failOnTargetViolationSubscriber = new FailOnTargetViolationSubscriber(
            testCollector: $testCollector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            failOnViolation: true,
            exit: static function (int $status) use (&$exitStatus): void {
                $exitStatus = $status;
            },
        );

        $this->expectOutputRegex('/Pyrameter target shape violated\./');

        $failOnTargetViolationSubscriber->notify(new Finished($this->telemetryInfo(), 0));

        $this->assertSame(1, $exitStatus);
    }

    public function testItDoesNotOverrideExistingPhpunitFailureExitCode(): void
    {
        $testCollector = new TestCollector();
        $testCollector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));
        $testCollector->add(new TestRecord(self::class, 'testIntegration', [], TestKind::Integration));

        $exitStatus = null;

        $failOnTargetViolationSubscriber = new FailOnTargetViolationSubscriber(
            testCollector: $testCollector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            failOnViolation: true,
            exit: static function (int $status) use (&$exitStatus): void {
                $exitStatus = $status;
            },
        );

        $this->expectOutputRegex('/Pyrameter target shape violated\./');

        $failOnTargetViolationSubscriber->notify(new Finished($this->telemetryInfo(), 1));

        $this->assertNull($exitStatus);
    }

    private function telemetryInfo(): Info
    {
        return TelemetryInfoFactory::create();
    }
}
