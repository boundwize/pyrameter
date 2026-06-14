<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Detection;

use PHPUnit\Framework\TestCase;
use Pyrameter\Detection\ScanResult;
use Pyrameter\Detection\ScanResultCache;

final class ScanResultCacheTest extends TestCase
{
    public function test_it_scans_a_test_class_once(): void
    {
        $cache = new ScanResultCache();
        $calls = 0;

        $factory = function (string $testClassName) use (&$calls): ScanResult {
            ++$calls;

            return ScanResult::inspectable([$testClassName]);
        };

        $first = $cache->get(self::class, $factory);
        $second = $cache->get(self::class, $factory);

        self::assertSame($first, $second);
        self::assertSame(1, $calls);
    }
}
