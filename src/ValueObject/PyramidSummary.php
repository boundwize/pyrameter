<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\ValueObject;

use Boundwize\Pyrameter\TestKind;

use function array_keys;
use function array_sum;
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
        $counts = self::emptyCounts();

        foreach ($records as $record) {
            ++$counts[$record->kind->value];
        }

        return self::fromCounts($counts);
    }

    /**
     * @param array<string, int> $counts
     */
    public static function fromCounts(array $counts): self
    {
        $counts      = self::normalizeCounts($counts);
        $total       = array_sum($counts);
        $percentages = self::percentagesFromCounts($counts, $total);

        return new self($total, $counts, $percentages);
    }

    public function count(TestKind $testKind): int
    {
        return $this->counts[$testKind->value] ?? 0;
    }

    public function percentage(TestKind $testKind): float
    {
        return $this->percentages[$testKind->value] ?? 0.0;
    }

    /**
     * @return array<string, int>
     */
    private static function emptyCounts(): array
    {
        return [
            TestKind::Unit->value        => 0,
            TestKind::Functional->value  => 0,
            TestKind::Integration->value => 0,
            TestKind::E2E->value         => 0,
        ];
    }

    /**
     * @param array<string, int> $counts
     * @return array<string, int>
     */
    private static function normalizeCounts(array $counts): array
    {
        $normalizedCounts = self::emptyCounts();

        foreach (TestKind::ordered() as $testKind) {
            $normalizedCounts[$testKind->value] = $counts[$testKind->value] ?? 0;
        }

        return $normalizedCounts;
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
