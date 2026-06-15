<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Detection;

use Boundwize\Pyrameter\Detection\ScanResult;
use Boundwize\Pyrameter\Detection\ScanResultCache;
use PHPUnit\Framework\TestCase;

final class ScanResultCacheTest extends TestCase
{
    public function testItScansATestClassOnce(): void
    {
        $cache = new ScanResultCache();
        $calls = 0;

        $factory = function (string $testClassName) use (&$calls): ScanResult {
            ++$calls;

            return ScanResult::inspectable([$testClassName]);
        };

        $first  = $cache->get(self::class, $factory);
        $second = $cache->get(self::class, $factory);

        self::assertSame($first, $second);
        self::assertSame(1, $calls);
    }
}
