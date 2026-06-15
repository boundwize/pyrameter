<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PHPUnit\Framework\TestCase;
use Pyrameter\PyramidSummary;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

use function array_sum;

final class PyramidSummaryTest extends TestCase
{
    public function testItCalculatesPercentages(): void
    {
        $summary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 7),
            ...$this->records(TestKind::Functional, 2),
            ...$this->records(TestKind::Integration, 1),
        ]);

        self::assertSame(10, $summary->total);
        self::assertSame(70.0, $summary->percentage(TestKind::Unit));
        self::assertSame(20.0, $summary->percentage(TestKind::Functional));
        self::assertSame(10.0, $summary->percentage(TestKind::Integration));
        self::assertSame(0.0, $summary->percentage(TestKind::E2E));
        self::assertSame(0.0, $summary->percentage(TestKind::Unknown));
    }

    public function testItBalancesRoundedPercentagesToOneHundred(): void
    {
        $summary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 37),
            ...$this->records(TestKind::Functional, 6),
            ...$this->records(TestKind::Integration, 3),
        ]);

        self::assertSame(100.0, array_sum($summary->percentages));
        self::assertSame(80.4, $summary->percentage(TestKind::Unit));
        self::assertSame(13.1, $summary->percentage(TestKind::Functional));
        self::assertSame(6.5, $summary->percentage(TestKind::Integration));
    }

    /**
     * @return list<TestRecord>
     */
    private function records(TestKind $kind, int $count): array
    {
        $records = [];

        for ($i = 1; $i <= $count; ++$i) {
            $records[] = new TestRecord('ExampleTest' . $kind->value, 'test_' . $i, [], $kind);
        }

        return $records;
    }
}
