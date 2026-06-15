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
        $collector = new TestCollector();
        $collector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));

        $subscriber = new PrintReportSubscriber(
            collector: $collector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            reporter: new PyramidReporter(),
        );

        $subscriber->notify(new ExecutionFinished($this->telemetryInfo()));

        self::addToAssertionCount(1);
    }

    public function testItTerminatesWithFailureWhenTargetsAreViolatedAndFailOnViolationIsEnabled(): void
    {
        $collector = new TestCollector();
        $collector->add(new TestRecord(self::class, 'testUnit', [], TestKind::Unit));
        $collector->add(new TestRecord(self::class, 'testIntegration', [], TestKind::Integration));
        $exitStatus = null;

        $subscriber = new PrintReportSubscriber(
            collector: $collector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            reporter: new PyramidReporter(),
            failOnViolation: true,
            exit: static function (int $status) use (&$exitStatus): void {
                $exitStatus = $status;
            },
        );

        $subscriber->notify(new ExecutionFinished($this->telemetryInfo()));

        self::assertSame(1, $exitStatus);
    }

    private function telemetryInfo(): Info
    {
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $memory   = MemoryUsage::fromBytes(0);
        $snapshot = new Snapshot(
            HRTime::fromSecondsAndNanoseconds(0, 0),
            $memory,
            $memory,
            new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0),
        );

        return new Info($snapshot, $duration, $memory, $duration, $memory);
    }
}
