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

    /** @var array<string, TestKind> */
    private array $caseInsensitiveExactRules;

    /** @var list<array{usage: string, kind: TestKind}> */
    private array $namespaceRules;

    /** @var list<array{usage: string, kind: TestKind}> */
    private array $caseInsensitiveNamespaceRules;

    /**
     * @param list<UsageRule> $rules
     */
    public function __construct(array $rules)
    {
        $exactRules                    = [];
        $caseInsensitiveExactRules     = [];
        $namespaceRules                = [];
        $caseInsensitiveNamespaceRules = [];

        foreach ($rules as $rule) {
            if ($rule->isNamespaceRule()) {
                $namespaceRule = [
                    'usage' => $rule->normalizedUsage(),
                    'kind'  => $rule->kind,
                ];

                if ($rule->isCaseInsensitive()) {
                    $caseInsensitiveNamespaceRules[] = $namespaceRule;
                    continue;
                }

                $namespaceRules[] = $namespaceRule;
                continue;
            }

            if ($rule->isCaseInsensitive()) {
                $this->addExactRule($caseInsensitiveExactRules, $rule->normalizedUsage(), $rule->kind);
                continue;
            }

            $this->addExactRule($exactRules, $rule->normalizedUsage(), $rule->kind);
        }

        $this->exactRules                    = $exactRules;
        $this->caseInsensitiveExactRules     = $caseInsensitiveExactRules;
        $this->namespaceRules                = $namespaceRules;
        $this->caseInsensitiveNamespaceRules = $caseInsensitiveNamespaceRules;
    }

    /**
     * @param list<string> $consumedClasses
     */
    public function classify(array $consumedClasses): TestKind
    {
        $kind = TestKind::Unit;

        foreach ($consumedClasses as $consumedClass) {
            if (isset($this->exactRules[$consumedClass])) {
                $kind = $this->heaviest($kind, $this->exactRules[$consumedClass]);

                if ($kind === TestKind::E2E) {
                    return $kind;
                }
            }

            $caseInsensitiveConsumedClass = $this->caseInsensitiveConsumedClass($consumedClass);

            if (isset($this->caseInsensitiveExactRules[$caseInsensitiveConsumedClass])) {
                $kind = $this->heaviest($kind, $this->caseInsensitiveExactRules[$caseInsensitiveConsumedClass]);

                if ($kind === TestKind::E2E) {
                    return $kind;
                }
            }

            foreach ($this->namespaceRules as $rule) {
                if (! str_starts_with($consumedClass, $rule['usage'])) {
                    continue;
                }

                $kind = $this->heaviest($kind, $rule['kind']);

                if ($kind === TestKind::E2E) {
                    return $kind;
                }
            }

            foreach ($this->caseInsensitiveNamespaceRules as $caseInsensitiveNamespaceRule) {
                if (! str_starts_with($caseInsensitiveConsumedClass, $caseInsensitiveNamespaceRule['usage'])) {
                    continue;
                }

                $kind = $this->heaviest($kind, $caseInsensitiveNamespaceRule['kind']);

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

    private function caseInsensitiveConsumedClass(string $consumedClass): string
    {
        return strtolower(ltrim($consumedClass, '\\'));
    }
}
