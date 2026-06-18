<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter;

use Boundwize\Pyrameter\Rule\UsageRule;
use Boundwize\Pyrameter\Rule\UsageType;

use function array_keys;
use function ltrim;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

final readonly class UsageClassifier
{
    /** @var array<string, list<array{kind: TestKind, unless: list<string>}>> */
    private array $exactRules;

    /** @var list<array{usage: string, kind: TestKind, unless: list<string>}> */
    private array $namespaceRules;

    /**
     * @param list<UsageRule> $rules
     */
    public function __construct(array $rules)
    {
        $exactRules     = [];
        $namespaceRules = [];

        foreach ($rules as $rule) {
            if ($rule->isNamespaceRule()) {
                $namespaceRules[] = [
                    'usage'  => $rule->normalizedKey(),
                    'kind'   => $rule->kind,
                    'unless' => $rule->normalizedUnlessKeys(),
                ];

                continue;
            }

            $exactRules[$rule->normalizedKey()][] = [
                'kind'   => $rule->kind,
                'unless' => $rule->normalizedUnlessKeys(),
            ];
        }

        $this->exactRules     = $exactRules;
        $this->namespaceRules = $namespaceRules;
    }

    /**
     * @param list<string> $consumedUsages
     */
    public function classify(array $consumedUsages): TestKind
    {
        $kind                     = TestKind::Unit;
        $normalizedConsumedUsages = [];

        foreach ($consumedUsages as $consumedUsage) {
            $normalizedConsumedUsage                            = $this->normalizeConsumedUsage($consumedUsage);
            $normalizedConsumedUsages[$normalizedConsumedUsage] = true;
        }

        foreach (array_keys($normalizedConsumedUsages) as $normalizedConsumedUsage) {
            foreach ($this->exactRules[$normalizedConsumedUsage] ?? [] as $exactRule) {
                if ($this->isSuppressed($exactRule['unless'], $normalizedConsumedUsages)) {
                    continue;
                }

                $kind = $this->heaviest($kind, $exactRule['kind']);

                if ($kind === TestKind::E2E) {
                    return $kind;
                }
            }

            foreach ($this->namespaceRules as $namespaceRule) {
                if (
                    ! $this->matchesNamespaceRule($normalizedConsumedUsage, $namespaceRule['usage'])
                    || $this->isSuppressed($namespaceRule['unless'], $normalizedConsumedUsages)
                ) {
                    continue;
                }

                $kind = $this->heaviest($kind, $namespaceRule['kind']);

                if ($kind === TestKind::E2E) {
                    return $kind;
                }
            }
        }

        return $kind;
    }

    /**
     * @param list<string> $unless
     * @param array<string, true> $normalizedConsumedUsages
     */
    private function isSuppressed(array $unless, array $normalizedConsumedUsages): bool
    {
        foreach ($unless as $unlessUsage) {
            if (isset($normalizedConsumedUsages[$unlessUsage])) {
                return true;
            }
        }

        return false;
    }

    private function heaviest(TestKind $left, TestKind $right): TestKind
    {
        return $right->weight() > $left->weight() ? $right : $left;
    }

    private function matchesNamespaceRule(string $consumedUsage, string $namespaceUsage): bool
    {
        return str_ends_with($namespaceUsage, '\\')
            && strlen($consumedUsage) > strlen($namespaceUsage)
            && str_starts_with($consumedUsage, $namespaceUsage);
    }

    private function normalizeConsumedUsage(string $consumedUsage): string
    {
        if (! str_contains($consumedUsage, ':')) {
            return $this->usageKey(UsageType::ClassLike, $this->normalizeUsageName($consumedUsage));
        }

        foreach (UsageType::cases() as $usageType) {
            $prefix = $usageType->value . ':';

            if (str_starts_with($consumedUsage, $prefix)) {
                return $this->usageKey(
                    $usageType,
                    $this->normalizeUsageName(substr($consumedUsage, strlen($prefix)))
                );
            }
        }

        return $this->usageKey(UsageType::ClassLike, $this->normalizeUsageName($consumedUsage));
    }

    private function normalizeUsageName(string $usageName): string
    {
        return strtolower(ltrim($usageName, '\\'));
    }

    private function usageKey(UsageType $usageType, string $normalizedUsage): string
    {
        return sprintf('%s:%s', $usageType->value, $normalizedUsage);
    }
}
