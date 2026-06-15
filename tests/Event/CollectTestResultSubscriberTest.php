<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Event;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Detection\TestUsageScanner;
use Boundwize\Pyrameter\Event\CollectTestResultSubscriber;
use Boundwize\Pyrameter\TestCollector;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\Tests\Fixtures\SimpleUnitFixture;
use Boundwize\Pyrameter\UsageClassifier;
use PHPUnit\Event\Code\Phpt;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;
use stdClass;

final class CollectTestResultSubscriberTest extends TestCase
{
    public function testItCollectsFinishedTestMethods(): void
    {
        $collector  = new TestCollector();
        $subscriber = $this->subscriber($collector);

        $subscriber->notify($this->finishedTestMethod(
            SimpleUnitFixture::class,
            'testItWorks with data set "one"',
        ));

        $records = $collector->all();

        self::assertCount(1, $records);
        self::assertSame(SimpleUnitFixture::class, $records[0]->testClassName);
        self::assertSame('testItWorks', $records[0]->testMethodName);
        self::assertSame(TestKind::Unit, $records[0]->kind);
    }

    public function testItMarksUninspectableTestsAsUnknown(): void
    {
        $collector  = new TestCollector();
        $subscriber = $this->subscriber($collector);

        $subscriber->notify($this->finishedTestMethod(stdClass::class, 'testUnknown'));

        $records = $collector->all();

        self::assertCount(1, $records);
        self::assertSame('testUnknown', $records[0]->testMethodName);
        self::assertSame(TestKind::Unknown, $records[0]->kind);
    }

    public function testItExtractsTestNamesFromEventIdsWhenNeeded(): void
    {
        $collector  = new TestCollector();
        $subscriber = $this->subscriber($collector);

        $subscriber->notify(new Finished(
            $this->telemetryInfo(),
            new IdOnlyEventCode(SimpleUnitFixture::class . '::testFromId#1'),
            1,
        ));

        $records = $collector->all();

        self::assertCount(1, $records);
        self::assertSame(SimpleUnitFixture::class, $records[0]->testClassName);
        self::assertSame('testFromId', $records[0]->testMethodName);
    }

    public function testItIgnoresEventsThatDoNotIdentifyTestMethods(): void
    {
        $collector  = new TestCollector();
        $subscriber = $this->subscriber($collector);

        $subscriber->notify(new Finished($this->telemetryInfo(), new Phpt(__FILE__), 0));

        self::assertSame([], $collector->all());
    }

    private function subscriber(TestCollector $collector): CollectTestResultSubscriber
    {
        $config = PyrameterConfig::defaults();

        return new CollectTestResultSubscriber(
            collector: $collector,
            scanner: new TestUsageScanner(),
            classifier: new UsageClassifier($config->usageRules()),
        );
    }

    /**
     * @param class-string $className
     * @param non-empty-string $methodName
     */
    private function finishedTestMethod(string $className, string $methodName): Finished
    {
        return new Finished(
            $this->telemetryInfo(),
            new TestMethod(
                $className,
                $methodName,
                __FILE__,
                1,
                new TestDox('', '', ''),
                MetadataCollection::fromArray([]),
                TestDataCollection::fromArray([]),
            ),
            1,
        );
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
