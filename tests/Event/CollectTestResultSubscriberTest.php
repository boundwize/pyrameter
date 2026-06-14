<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Event;

use PHPUnit\Event\Code\Phpt;
use PHPUnit\Event\Code\Test as EventTest;
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
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\Detection\TestUsageScanner;
use Pyrameter\Event\CollectTestResultSubscriber;
use Pyrameter\TestCollector;
use Pyrameter\TestKind;
use Pyrameter\Tests\Fixtures\SimpleUnitFixture;
use Pyrameter\UsageClassifier;
use stdClass;

final class CollectTestResultSubscriberTest extends TestCase
{
    public function test_it_collects_finished_test_methods(): void
    {
        $collector  = new TestCollector();
        $subscriber = $this->subscriber($collector);

        $subscriber->notify($this->finishedTestMethod(
            SimpleUnitFixture::class,
            'test_it_works with data set "one"',
        ));

        $records = $collector->all();

        self::assertCount(1, $records);
        self::assertSame(SimpleUnitFixture::class, $records[0]->testClassName);
        self::assertSame('test_it_works', $records[0]->testMethodName);
        self::assertSame(TestKind::Unit, $records[0]->kind);
    }

    public function test_it_marks_uninspectable_tests_as_unknown(): void
    {
        $collector  = new TestCollector();
        $subscriber = $this->subscriber($collector);

        $subscriber->notify($this->finishedTestMethod(stdClass::class, 'test_unknown'));

        $records = $collector->all();

        self::assertCount(1, $records);
        self::assertSame('test_unknown', $records[0]->testMethodName);
        self::assertSame(TestKind::Unknown, $records[0]->kind);
    }

    public function test_it_extracts_test_names_from_event_ids_when_needed(): void
    {
        $collector  = new TestCollector();
        $subscriber = $this->subscriber($collector);

        $subscriber->notify(new Finished(
            $this->telemetryInfo(),
            new IdOnlyEventTest(SimpleUnitFixture::class . '::test_from_id#1'),
            1,
        ));

        $records = $collector->all();

        self::assertCount(1, $records);
        self::assertSame(SimpleUnitFixture::class, $records[0]->testClassName);
        self::assertSame('test_from_id', $records[0]->testMethodName);
    }

    public function test_it_ignores_events_that_do_not_identify_test_methods(): void
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

final readonly class IdOnlyEventTest extends EventTest
{
    /**
     * @param non-empty-string $identifier
     */
    public function __construct(
        private string $identifier,
    ) {
        parent::__construct(__FILE__);
    }

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->identifier;
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->identifier;
    }
}
