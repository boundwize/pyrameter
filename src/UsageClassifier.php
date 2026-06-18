<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter;

use Boundwize\Pyrameter\Rule\UsageRule;

use function ltrim;
use function str_starts_with;
use function strtolower;

final readonly class UsageClassifier
{
    /** @var array<string, TestKind> */
    private array $exactRules;

    /** @var list<array{usage: string, kind: TestKind}> */
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
                    'usage' => $rule->normalizedUsage(),
                    'kind'  => $rule->kind,
                ];

                continue;
            }

            $this->addExactRule($exactRules, $rule->normalizedUsage(), $rule->kind);
        }

        $this->exactRules     = $exactRules;
        $this->namespaceRules = $namespaceRules;
    }

    /**
     * @param list<string> $consumedClasses
     */
    public function classify(array $consumedClasses): TestKind
    {
        $kind = TestKind::Unit;

        foreach ($consumedClasses as $consumedClass) {
            $normalizedConsumedClass = $this->normalizeConsumedClass($consumedClass);

            if (isset($this->exactRules[$normalizedConsumedClass])) {
                $kind = $this->heaviest($kind, $this->exactRules[$normalizedConsumedClass]);

                if ($kind === TestKind::E2E) {
                    return $kind;
                }
            }

            foreach ($this->namespaceRules as $namespaceRule) {
                if (! str_starts_with($normalizedConsumedClass, $namespaceRule['usage'])) {
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
     * @param array<string, TestKind> $rules
     */
    private function addExactRule(array &$rules, string $usage, TestKind $testKind): void
    {
        $rules[$usage] = isset($rules[$usage])
            ? $this->heaviest($rules[$usage], $testKind)
            : $testKind;
    }

    private function heaviest(TestKind $left, TestKind $right): TestKind
    {
        return $right->weight() > $left->weight() ? $right : $left;
    }

    private function normalizeConsumedClass(string $consumedClass): string
    {
        return strtolower(ltrim($consumedClass, '\\'));
    }
}
