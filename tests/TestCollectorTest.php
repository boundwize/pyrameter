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
        $testCollector = new TestCollector();

        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));
        $testCollector->add(new TestRecord('PriceTest', 'testItCalculatesPrice', [], TestKind::Unit));

        $this->assertCount(1, $testCollector->all());
    }
}
