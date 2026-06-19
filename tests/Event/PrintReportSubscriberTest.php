<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Event;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Event\PrintReportSubscriber;
use Boundwize\Pyrameter\Report\PyramidReporter;
use Boundwize\Pyrameter\TestCollector;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\Tests\Fixtures\TelemetryInfoFactory;
use Boundwize\Pyrameter\ValueObject\TestRecord;
use PHPUnit\Event\Telemetry\Info;
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

        $this->expectOutputRegex('/Pyrameter.*Total: 1 tests/s');

        $printReportSubscriber->notify(new ExecutionFinished($this->telemetryInfo()));
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

        $this->expectOutputRegex('/Pyrameter.*Pyrameter target shape violated\./s');

        $printReportSubscriber->notify(new ExecutionFinished($this->telemetryInfo()));

        $this->assertSame(1, $exitStatus);
    }

    private function telemetryInfo(): Info
    {
        return TelemetryInfoFactory::create();
    }
}
