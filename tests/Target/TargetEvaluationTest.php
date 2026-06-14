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
    public function testItReturnsStatusesForAllTestKinds(): void
    {
        $summary = PyramidSummary::fromRecords([
            new TestRecord(self::class, 'testUnit', [], TestKind::Unit),
        ]);

        $evaluation = (new TargetEvaluator([
            TestKind::Unit->value => ['min' => 100.0, 'max' => 100.0],
        ]))->evaluate($summary);

        self::assertCount(5, $evaluation->statuses());
        self::assertSame('No target', $evaluation->status(TestKind::Integration)->label());
    }

    public function testStatusLabelsIncludeRangesAndUnconstrainedTargets(): void
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
