<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\TestCollector;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\TestRecord;
use PHPUnit\Framework\TestCase;

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
