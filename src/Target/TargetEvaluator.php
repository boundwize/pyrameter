<?php

declare(strict_types=1);

namespace Pyrameter\Target;

use Pyrameter\PyramidSummary;
use Pyrameter\TestKind;

final readonly class TargetEvaluator
{
    /**
     * @param array<string, array{min?: float, max?: float}> $targets
     */
    public function __construct(
        private array $targets,
    ) {
    }

    public function evaluate(PyramidSummary $summary): TargetEvaluation
    {
        $statuses = [];

        foreach (TestKind::ordered() as $kind) {
            $actual = $summary->percentage($kind);
            $target = $this->targets[$kind->value] ?? null;

            if ($target === null) {
                $statuses[$kind->value] = TargetStatus::ignored($kind, $actual);
                continue;
            }

            $statuses[$kind->value] = TargetStatus::fromTarget(
                kind: $kind,
                actual: $actual,
                min: $target['min'] ?? null,
                max: $target['max'] ?? null,
            );
        }

        return new TargetEvaluation($statuses);
    }
}
