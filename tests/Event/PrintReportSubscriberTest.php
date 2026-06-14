<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Event;

use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Framework\TestCase;
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\Event\PrintReportSubscriber;
use Pyrameter\Report\PyramidReporter;
use Pyrameter\TestCollector;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

final class PrintReportSubscriberTest extends TestCase
{
    public function test_it_prints_the_report_when_execution_finishes(): void
    {
        $collector = new TestCollector();
        $collector->add(new TestRecord(self::class, 'test_unit', [], TestKind::Unit));

        $subscriber = new PrintReportSubscriber(
            collector: $collector,
            targets: PyrameterConfig::defaults()->targetPercentages(),
            reporter: new PyramidReporter(),
        );

        $subscriber->notify(new ExecutionFinished($this->telemetryInfo()));

        self::addToAssertionCount(1);
    }

    public function test_it_terminates_with_failure_when_targets_are_violated_and_fail_on_violation_is_enabled(): void
    {
        $collector = new TestCollector();
        $collector->add(new TestRecord(self::class, 'test_unit', [], TestKind::Unit));
        $collector->add(new TestRecord(self::class, 'test_integration', [], TestKind::Integration));
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
