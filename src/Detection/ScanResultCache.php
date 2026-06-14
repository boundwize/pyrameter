<?php

declare(strict_types=1);

namespace Pyrameter\Detection;

final class ScanResultCache
{
    /**
     * @var array<class-string, ScanResult>
     */
    private array $cache = [];

    /**
     * @param class-string $testClassName
     * @param callable(class-string): ScanResult $factory
     */
    public function get(string $testClassName, callable $factory): ScanResult
    {
        return $this->cache[$testClassName] ??= $factory($testClassName);
    }
}
