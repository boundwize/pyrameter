<?php

declare(strict_types=1);

namespace Pyrameter;

use function count;
use function round;

final readonly class PyramidSummary
{
    /**
     * @param array<string, int> $counts
     * @param array<string, float> $percentages
     */
    public function __construct(
        public int $total,
        public array $counts,
        public array $percentages,
    ) {
    }

    /**
     * @param list<TestRecord> $records
     */
    public static function fromRecords(array $records): self
    {
        $counts = [
            TestKind::Unit->value        => 0,
            TestKind::Functional->value  => 0,
            TestKind::Integration->value => 0,
            TestKind::E2E->value         => 0,
            TestKind::Unknown->value     => 0,
        ];

        foreach ($records as $record) {
            ++$counts[$record->kind->value];
        }

        $total       = count($records);
        $percentages = [];

        foreach ($counts as $kind => $count) {
            $percentages[$kind] = $total > 0
                ? round(($count / $total) * 100, 1)
                : 0.0;
        }

        return new self($total, $counts, $percentages);
    }

    public function count(TestKind $kind): int
    {
        return $this->counts[$kind->value] ?? 0;
    }

    public function percentage(TestKind $kind): float
    {
        return $this->percentages[$kind->value] ?? 0.0;
    }
}
