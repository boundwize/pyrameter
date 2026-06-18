<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\ValueObject\PyramidSummary;
use Boundwize\Pyrameter\ValueObject\TestRecord;
use PHPUnit\Framework\TestCase;

use function array_sum;

use const PHP_FLOAT_EPSILON;

final class PyramidSummaryTest extends TestCase
{
    public function testItCalculatesPercentages(): void
    {
        $pyramidSummary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 7),
            ...$this->records(TestKind::Functional, 2),
            ...$this->records(TestKind::Integration, 1),
        ]);

        $this->assertSame(10, $pyramidSummary->total);
        $this->assertEqualsWithDelta(70.0, $pyramidSummary->percentage(TestKind::Unit), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(20.0, $pyramidSummary->percentage(TestKind::Functional), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(10.0, $pyramidSummary->percentage(TestKind::Integration), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $pyramidSummary->percentage(TestKind::E2E), PHP_FLOAT_EPSILON);
    }

    public function testItBalancesRoundedPercentagesToOneHundred(): void
    {
        $pyramidSummary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 37),
            ...$this->records(TestKind::Functional, 6),
            ...$this->records(TestKind::Integration, 3),
        ]);

        $this->assertEqualsWithDelta(100.0, array_sum($pyramidSummary->percentages), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(80.4, $pyramidSummary->percentage(TestKind::Unit), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(13.1, $pyramidSummary->percentage(TestKind::Functional), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(6.5, $pyramidSummary->percentage(TestKind::Integration), PHP_FLOAT_EPSILON);
    }

    /**
     * @return list<TestRecord>
     */
    private function records(TestKind $testKind, int $count): array
    {
        $records = [];

        for ($i = 1; $i <= $count; ++$i) {
            $records[] = new TestRecord('ExampleTest' . $testKind->value, 'test_' . $i, [], $testKind);
        }

        return $records;
    }
}
