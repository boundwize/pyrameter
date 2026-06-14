<?php

declare(strict_types=1);

namespace Pyrameter\Detection;

final class ScanResultCache
{
    /**
     * @var array<string, ScanResult>
     */
    private array $cache = [];

    /**
     * @param callable(string): ScanResult $factory
     */
    public function get(string $testClassName, callable $factory): ScanResult
    {
        return $this->cache[$testClassName] ??= $factory($testClassName);
    }
}
