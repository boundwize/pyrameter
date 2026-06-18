<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter;

use Boundwize\Pyrameter\Rule\UsageRule;

final readonly class UsageClassifier
{
    /**
     * @param list<UsageRule> $rules
     */
    public function __construct(
        private array $rules,
    ) {
    }

    /**
     * @param list<string> $consumedClasses
     */
    public function classify(array $consumedClasses): TestKind
    {
        $kind = TestKind::Unit;

        foreach ($consumedClasses as $consumedClass) {
            foreach ($this->rules as $rule) {
                if (! $rule->matches($consumedClass)) {
                    continue;
                }

                if ($rule->kind->weight() > $kind->weight()) {
                    $kind = $rule->kind;

                    if ($kind === TestKind::E2E) {
                        return $kind;
                    }
                }
            }
        }

        return $kind;
    }
}
