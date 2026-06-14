<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PHPUnit\Framework\TestCase;
use Pyrameter\TestCollector;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

final class TestCollectorTest extends TestCase
{
    public function test_it_counts_data_provider_executions_once_per_test_method(): void
    {
        $collector = new TestCollector();

        $collector->add(new TestRecord('PriceTest', 'test_it_calculates_price', [], TestKind::Unit));
        $collector->add(new TestRecord('PriceTest', 'test_it_calculates_price', [], TestKind::Unit));

        self::assertCount(1, $collector->all());
    }
}
