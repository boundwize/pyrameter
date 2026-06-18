<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Target;

use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\ValueObject\PyramidSummary;

final readonly class TargetEvaluator
{
    /**
     * @param array<string, array{min: float, max: float}> $targets
     */
    public function __construct(
        private array $targets,
    ) {
    }

    public function evaluate(PyramidSummary $pyramidSummary): TargetEvaluation
    {
        $statuses = [];

        foreach (TestKind::ordered() as $testKind) {
            $actual = $pyramidSummary->percentage($testKind);
            $target = $this->targets[$testKind->value] ?? null;

            if ($target === null) {
                $statuses[$testKind->value] = TargetStatus::ignored($testKind, $actual);
                continue;
            }

            if ($target['min'] === 0.0 && $target['max'] === 100.0) {
                $statuses[$testKind->value] = TargetStatus::ignored($testKind, $actual);
                continue;
            }

            $statuses[$testKind->value] = TargetStatus::fromTarget(
                testKind: $testKind,
                actual: $actual,
                min: $target['min'],
                max: $target['max'],
            );
        }

        return new TargetEvaluation($statuses);
    }
}
