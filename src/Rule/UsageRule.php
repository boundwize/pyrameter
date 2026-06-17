<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Rule;

use Boundwize\Pyrameter\TestKind;

use function ltrim;
use function str_ends_with;
use function str_starts_with;
use function strtolower;

final readonly class UsageRule
{
    public function __construct(
        public string $classOrNamespace,
        public TestKind $kind,
        private bool $caseInsensitive = false,
    ) {
    }

    public function matches(string $consumedUsage): bool
    {
        $configuredUsage = $this->normalize($this->classOrNamespace);
        $consumedUsage   = $this->normalize($consumedUsage);

        if ($consumedUsage === $configuredUsage) {
            return true;
        }

        if (! str_ends_with($configuredUsage, '\\')) {
            return false;
        }

        return str_starts_with($consumedUsage, $configuredUsage);
    }

    private function normalize(string $usage): string
    {
        if (! $this->caseInsensitive) {
            return $usage;
        }

        return strtolower(ltrim($usage, '\\'));
    }
}
