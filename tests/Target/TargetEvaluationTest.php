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
        $pyramidSummary = PyramidSummary::fromRecords([
            new TestRecord(self::class, 'testUnit', [], TestKind::Unit),
        ]);

        $targetEvaluation = (new TargetEvaluator([
            TestKind::Unit->value => ['min' => 100.0, 'max' => 100.0],
        ]))->evaluate($pyramidSummary);

        $this->assertCount(4, $targetEvaluation->statuses());
        $this->assertSame('No target', $targetEvaluation->status(TestKind::Integration)->label());
    }

    public function testStatusLabelsIncludeRangesAndUnconstrainedTargets(): void
    {
        $this->assertSame('10.0%-90.0%', TargetStatus::fromTarget(TestKind::Unit, 50.0, 10.0, 90.0)->label());

        $this->assertSame('-', TargetStatus::fromTarget(TestKind::Unit, 50.0, 0.0, 100.0)->label());
    }
}
