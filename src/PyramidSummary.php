<?php

declare(strict_types=1);

namespace Pyrameter;

use function array_keys;
use function count;
use function intdiv;
use function usort;

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
        $percentages = self::percentagesFromCounts($counts, $total);

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

    /**
     * @param array<string, int> $counts
     * @return array<string, float>
     */
    private static function percentagesFromCounts(array $counts, int $total): array
    {
        if ($total === 0) {
            return [
                TestKind::Unit->value        => 0.0,
                TestKind::Functional->value  => 0.0,
                TestKind::Integration->value => 0.0,
                TestKind::E2E->value         => 0.0,
                TestKind::Unknown->value     => 0.0,
            ];
        }

        $percentages = [];
        $remainders  = [];
        $assigned    = 0;

        foreach ($counts as $kind => $count) {
            $scaledTotal        = $count * 1000;
            $percentages[$kind] = intdiv($scaledTotal, $total);
            $remainders[$kind]  = $scaledTotal % $total;
            $assigned          += $percentages[$kind];
        }

        $kinds = array_keys($counts);
        usort(
            $kinds,
            static fn (string $left, string $right): int => $remainders[$right] <=> $remainders[$left],
        );

        for ($i = 0, $remaining = 1000 - $assigned; $i < $remaining; ++$i) {
            ++$percentages[$kinds[$i]];
        }

        foreach ($percentages as $kind => $percentage) {
            $percentages[$kind] = $percentage / 10;
        }

        return $percentages;
    }
}
