<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Target;

use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Target\TargetEvaluator;
use Boundwize\Pyrameter\Target\TargetStatus;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\TestRecord;
use PHPUnit\Framework\TestCase;

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
