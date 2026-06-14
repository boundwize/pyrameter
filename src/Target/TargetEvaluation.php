<?php

declare(strict_types=1);

namespace Pyrameter\Target;

use Pyrameter\TestKind;

final readonly class TargetEvaluation
{
    /**
     * @param array<string, TargetStatus> $statuses
     */
    public function __construct(
        private array $statuses,
    ) {
    }

    public function status(TestKind $kind): TargetStatus
    {
        return $this->statuses[$kind->value] ?? TargetStatus::ignored($kind, 0.0);
    }

    /**
     * @return list<TargetStatus>
     */
    public function statuses(): array
    {
        return array_map(fn (TestKind $kind): TargetStatus => $this->status($kind), TestKind::ordered());
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
