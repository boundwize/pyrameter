<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Event;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Event\PrintReportSubscriber;
use Boundwize\Pyrameter\Report\PyramidReporter;
use Boundwize\Pyrameter\TestCollector;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\TestRecord;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Framework\TestCase;

final class PrintReportSubscriberTest extends TestCase
{
    public function testItPrintsTheReportWhenExecutionFinishes(): void
    {
        $testCollector = new TestCollector();
        $testCollector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));

        $printReportSubscriber = new PrintReportSubscriber(
            testCollector: $testCollector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            pyramidReporter: new PyramidReporter(),
        );

        $printReportSubscriber->notify(new ExecutionFinished($this->telemetryInfo()));

        self::addToAssertionCount(1);
    }

    public function testItTerminatesWithFailureWhenTargetsAreViolatedAndFailOnViolationIsEnabled(): void
    {
        $testCollector = new TestCollector();
        $testCollector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));
        $testCollector->add(new TestRecord(self::class, 'testIntegration', [], TestKind::Integration));

        $exitStatus = null;

        $printReportSubscriber = new PrintReportSubscriber(
            testCollector: $testCollector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            pyramidReporter: new PyramidReporter(),
            failOnViolation: true,
            exit: static function (int $status) use (&$exitStatus): void {
                $exitStatus = $status;
            },
        );

        $printReportSubscriber->notify(new ExecutionFinished($this->telemetryInfo()));

        $this->assertSame(1, $exitStatus);
    }

    private function telemetryInfo(): Info
    {
        $duration    = Duration::fromSecondsAndNanoseconds(0, 0);
        $memoryUsage = MemoryUsage::fromBytes(0);
        $snapshot    = new Snapshot(
            HRTime::fromSecondsAndNanoseconds(0, 0),
            $memoryUsage,
            $memoryUsage,
            new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0),
        );

        return new Info($snapshot, $duration, $memoryUsage, $duration, $memoryUsage);
    }
}
