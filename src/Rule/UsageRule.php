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
    ) {
    }

    public function matches(string $consumedUsage): bool
    {
        $configuredUsage = $this->normalizedUsage();
        $consumedUsage   = $this->normalize($consumedUsage);

        if ($consumedUsage === $configuredUsage) {
            return true;
        }

        if (! str_ends_with($configuredUsage, '\\')) {
            return false;
        }

        return str_starts_with($consumedUsage, $configuredUsage);
    }

    public function normalizedUsage(): string
    {
        return $this->normalize($this->classOrNamespace);
    }

    public function isNamespaceRule(): bool
    {
        return str_ends_with($this->normalizedUsage(), '\\');
    }

    private function normalize(string $usage): string
    {
        return strtolower(ltrim($usage, '\\'));
    }
}
