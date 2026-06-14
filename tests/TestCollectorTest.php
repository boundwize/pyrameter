<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PHPUnit\Framework\TestCase;
use Pyrameter\TestCollector;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

final class TestCollectorTest extends TestCase
{
    public function testItCountsDataProviderExecutionsOncePerTestMethod(): void
    {
        $collector = new TestCollector();

        $collector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));
        $collector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));

        self::assertCount(1, $collector->all());
    }
}
