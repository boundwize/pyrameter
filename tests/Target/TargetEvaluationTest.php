<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Target;

use PHPUnit\Framework\TestCase;
use Pyrameter\PyramidSummary;
use Pyrameter\Target\TargetEvaluator;
use Pyrameter\Target\TargetStatus;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

final class TargetEvaluationTest extends TestCase
{
    public function test_it_returns_statuses_for_all_test_kinds(): void
    {
        $summary = PyramidSummary::fromRecords([
            new TestRecord(self::class, 'test_unit', [], TestKind::Unit),
        ]);

        $evaluation = (new TargetEvaluator([
            TestKind::Unit->value => ['min' => 100.0, 'max' => 100.0],
        ]))->evaluate($summary);

        self::assertCount(5, $evaluation->statuses());
        self::assertSame('No target', $evaluation->status(TestKind::Integration)->label());
    }

    public function test_status_labels_include_ranges_and_unconstrained_targets(): void
    {
        self::assertSame(
            '10.0%-90.0%',
            TargetStatus::fromTarget(TestKind::Unit, 50.0, 10.0, 90.0)->label(),
        );

        self::assertSame(
            '-',
            TargetStatus::fromTarget(TestKind::Unit, 50.0, 0.0, 100.0)->label(),
        );
    }
}
