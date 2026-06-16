<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Target;

use Boundwize\Pyrameter\TestKind;

use function array_map;

final readonly class TargetEvaluation
{
    /**
     * @param array<string, TargetStatus> $statuses
     */
    public function __construct(
        private array $statuses,
    ) {
    }

    public function status(TestKind $testKind): TargetStatus
    {
        return $this->statuses[$testKind->value] ?? TargetStatus::ignored($testKind, 0.0);
    }

    /**
     * @return list<TargetStatus>
     */
    public function statuses(): array
    {
        return array_map($this->status(...), TestKind::ordered());
    }

    public function allPassed(): bool
    {
        foreach ($this->statuses as $status) {
            if (! $status->passed) {
                return false;
            }
        }

        return true;
    }
}
