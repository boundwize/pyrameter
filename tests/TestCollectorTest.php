<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\TestCollector;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\ValueObject\TestRecord;
use PHPUnit\Framework\TestCase;

final class TestCollectorTest extends TestCase
{
    public function testItDeduplicatesIdenticalRecords(): void
    {
        $testCollector = new TestCollector();

        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));
        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));

        $this->assertCount(1, $testCollector->all());
        $this->assertSame(1, $testCollector->summary()->total);
        $this->assertSame(1, $testCollector->summary()->count(TestKind::Unit));
    }

    public function testItKeepsSeparateDataProviderExecutions(): void
    {
        $testCollector = new TestCollector();

        $testCollector->add(new TestRecord(
            'PriceTest',
            'testItCalculatesPrice with data set "small"',
            [],
            TestKind::Unit,
        ));
        $testCollector->add(new TestRecord(
            'PriceTest',
            'testItCalculatesPrice with data set "large"',
            [],
            TestKind::Unit,
        ));

        $this->assertCount(2, $testCollector->all());
        $this->assertSame(2, $testCollector->summary()->total);
        $this->assertSame(2, $testCollector->summary()->count(TestKind::Unit));
    }

    public function testItReplacesCountsWhenTheSameTestIdChangesKind(): void
    {
        $testCollector = new TestCollector();

        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));
        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Integration));

        $summary = $testCollector->summary();

        $this->assertCount(1, $testCollector->all());
        $this->assertSame(1, $summary->total);
        $this->assertSame(0, $summary->count(TestKind::Unit));
        $this->assertSame(1, $summary->count(TestKind::Integration));
    }
}
