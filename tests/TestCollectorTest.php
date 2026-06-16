<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\TestCollector;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\TestRecord;
use PHPUnit\Framework\TestCase;

final class TestCollectorTest extends TestCase
{
    public function testItDeduplicatesIdenticalRecords(): void
    {
        $testCollector = new TestCollector();

        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));
        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));

        $this->assertCount(1, $testCollector->all());
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
    }
}
