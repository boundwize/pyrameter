<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PHPUnit\Framework\TestCase;
use Pyrameter\PyramidSummary;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

final class PyramidSummaryTest extends TestCase
{
    public function test_it_calculates_percentages(): void
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
