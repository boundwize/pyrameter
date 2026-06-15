<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Report;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Report\PyramidReporter;
use Boundwize\Pyrameter\Report\SuiteShapeResolver;
use Boundwize\Pyrameter\Target\TargetEvaluator;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\TestRecord;
use PHPUnit\Framework\TestCase;

final class PyramidReporterTest extends TestCase
{
    public function testItRendersTheWarningReport(): void
    {
        $summary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 7),
            ...$this->records(TestKind::Functional, 2),
            ...$this->records(TestKind::Integration, 1),
        ]);
        $targets = (new TargetEvaluator(PyrameterConfig::defaults()->targetPercentages()))->evaluate($summary);
        $shape   = (new SuiteShapeResolver())->resolve($summary, $targets);

        $report = (new PyramidReporter())->render($summary, $targets, $shape);

        self::assertStringContainsString('Pyrameter', $report);
        self::assertStringContainsString('Shape: Integration Mountain', $report);
        self::assertStringContainsString('Result: Violated ⚠', $report);
        self::assertStringContainsString('Integration       1    10.0%   <=  8.0%     ✗', $report);
        self::assertStringContainsString('Total: 10 tests', $report);
        self::assertStringContainsString('Your suite is getting heavier.', $report);
    }

    public function testItRendersThePassedReport(): void
    {
        $summary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 72),
            ...$this->records(TestKind::Functional, 18),
            ...$this->records(TestKind::Integration, 8),
            ...$this->records(TestKind::E2E, 2),
        ]);
        $targets = (new TargetEvaluator(PyrameterConfig::defaults()->targetPercentages()))->evaluate($summary);
        $shape   = (new SuiteShapeResolver())->resolve($summary, $targets);

        $report = (new PyramidReporter())->render($summary, $targets, $shape);

        self::assertStringContainsString('Shape: Healthy Pyramid', $report);
        self::assertStringContainsString('Result: Passed ✓', $report);
        self::assertStringContainsString('Your test pyramid target passed.', $report);
    }

    public function testItRendersUnconstrainedDefaultRangesAsIgnoredTargets(): void
    {
        $summary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 8),
            ...$this->records(TestKind::Integration, 2),
        ]);
        $config  = PyrameterConfig::create()
            ->targetShape(
                unit: ['min' => 40],
            );
        $targets = (new TargetEvaluator($config->targetPercentages()))->evaluate($summary);
        $shape   = (new SuiteShapeResolver())->resolve($summary, $targets);

        $report = (new PyramidReporter())->render($summary, $targets, $shape);

        self::assertStringContainsString('Unit              8    80.0%   >= 40.0%     ✓', $report);
        self::assertStringContainsString('Integration       2    20.0%   No target    -', $report);
        self::assertStringNotContainsString('0.0%-100.0%', $report);
    }

    /**
     * @return list<TestRecord>
     */
    private function records(TestKind $kind, int $count): array
    {
        $records = [];

        for ($i = 1; $i <= $count; ++$i) {
            $records[] = new TestRecord($kind->value . 'Test', 'test_' . $i, [], $kind);
        }

        return $records;
    }
}
