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
        $scanResultCache = new ScanResultCache();
        $calls           = 0;

        $factory = function (string $testClassName) use (&$calls): ScanResult {
            ++$calls;

            return ScanResult::inspectable([$testClassName]);
        };

        $scanResult = $scanResultCache->get(self::class, $factory);
        $second     = $scanResultCache->get(self::class, $factory);

        $this->assertSame($scanResult, $second);
        $this->assertSame(1, $calls);
    }
}
