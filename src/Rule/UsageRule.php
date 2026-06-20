<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Rule;

use Boundwize\Pyrameter\TestKind;

use function ltrim;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

final readonly class UsageRule
{
    /**
     * @param list<string> $unless
     */
    public function __construct(
        public string $usage,
        public TestKind $kind,
        private UsageType $usageType = UsageType::ClassLike,
        private array $unless = [],
    ) {
    }

    public function matches(string $consumedUsage): bool
    {
        $configuredUsage                     = $this->normalizedUsage();
        [$consumedUsageType, $consumedUsage] = $this->normalizeConsumedUsage($consumedUsage);

        if ($consumedUsageType !== $this->usageType) {
            return false;
        }

        if (! str_ends_with($configuredUsage, '\\')) {
            return $consumedUsage === $configuredUsage;
        }

        return $this->matchesNamespacePrefix($consumedUsage, $configuredUsage);
    }

    public function normalizedUsage(): string
    {
        return $this->normalize($this->usage);
    }

    public function isNamespaceRule(): bool
    {
        return $this->usageType === UsageType::ClassLike && str_ends_with($this->normalizedUsage(), '\\');
    }

    public function normalizedKey(): string
    {
        return $this->usageKey($this->usageType, $this->normalizedUsage());
    }

    /**
     * @return list<string>
     */
    public function normalizedUnlessKeys(): array
    {
        $normalizedUnlessKeys = [];

        foreach ($this->unless as $unlessUsage) {
            $normalizedUnlessKeys[] = $this->usageKey(UsageType::ClassLike, $this->normalize($unlessUsage));
        }

        return $normalizedUnlessKeys;
    }

    private function normalize(string $usage): string
    {
        return strtolower(ltrim($usage, '\\'));
    }

    private function matchesNamespacePrefix(string $consumedUsage, string $namespaceUsage): bool
    {
        return str_ends_with($namespaceUsage, '\\')
            && strlen($consumedUsage) > strlen($namespaceUsage)
            && str_starts_with($consumedUsage, $namespaceUsage);
    }

    /**
     * @return array{UsageType, string}
     */
    private function normalizeConsumedUsage(string $consumedUsage): array
    {
        if (! str_contains($consumedUsage, ':')) {
            return [$this->usageType, $this->normalize($consumedUsage)];
        }

        foreach (UsageType::cases() as $usageType) {
            $prefix = $usageType->value . ':';

            if (str_starts_with($consumedUsage, $prefix)) {
                return [$usageType, $this->normalize(substr($consumedUsage, strlen($prefix)))];
            }
        }

        return [$this->usageType, $this->normalize($consumedUsage)];
    }

    private function usageKey(UsageType $usageType, string $normalizedUsage): string
    {
        return sprintf('%s:%s', $usageType->value, $normalizedUsage);
    }
}
