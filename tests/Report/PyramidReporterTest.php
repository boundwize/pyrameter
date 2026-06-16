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

use function strpos;

final class PyramidReporterTest extends TestCase
{
    public function testItRendersTheWarningReport(): void
    {
        $pyramidSummary   = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 7),
            ...$this->records(TestKind::Functional, 2),
            ...$this->records(TestKind::Integration, 1),
        ]);
        $targetEvaluation = (new TargetEvaluator(PyrameterConfig::defaults()->targetPercentages()))
            ->evaluate($pyramidSummary);
        $suiteShape       = (new SuiteShapeResolver())->resolve($pyramidSummary, $targetEvaluation);

        $report = (new PyramidReporter())->render($pyramidSummary, $targetEvaluation, $suiteShape);

        $this->assertStringContainsString('Pyrameter', $report);
        $this->assertStringContainsString('Shape:  Integration Mountain', $report);
        $this->assertStringContainsString('Result: Violated ⚠', $report);
        $this->assertStringContainsString('▲  E2E', $report);
        $this->assertStringContainsString('▄▄▄▄▄  Integration', $report);
        $this->assertStringContainsString('▄▄▄▄▄▄▄▄▄▄▄▄▄  Unit', $report);
        $this->assertStringContainsString('|    KIND     | TESTS | ACTUAL |   TARGET   |', $report);
        $this->assertStringContainsString('+=============+=======+========+============+', $report);
        $this->assertStringContainsString('| Integration |     1 |  10.0% | <=  8.0% ✗ |', $report);
        $this->assertStringNotContainsString('Unknown', $report);
        $this->assertStringContainsString('Total: 10 tests', $report);
        $this->assertStringContainsString('Your suite is getting heavier.', $report);
    }

    public function testItRendersThePassedReport(): void
    {
        $pyramidSummary   = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 72),
            ...$this->records(TestKind::Functional, 18),
            ...$this->records(TestKind::Integration, 8),
            ...$this->records(TestKind::E2E, 2),
        ]);
        $targetEvaluation = (new TargetEvaluator(PyrameterConfig::defaults()
            ->targetPercentages()))
            ->evaluate($pyramidSummary);
        $suiteShape       = (new SuiteShapeResolver())->resolve($pyramidSummary, $targetEvaluation);

        $report = (new PyramidReporter())->render($pyramidSummary, $targetEvaluation, $suiteShape);

        $this->assertStringContainsString('Shape:  Healthy Pyramid', $report);
        $this->assertStringContainsString('Result: Passed ✓', $report);
        $this->assertStringContainsString('Your test pyramid target passed.', $report);
    }

    public function testItRendersUnconstrainedDefaultRangesAsIgnoredTargets(): void
    {
        $pyramidSummary   = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 8),
            ...$this->records(TestKind::Integration, 2),
        ]);
        $pyrameterConfig  = PyrameterConfig::create()
            ->targetShape(
                unit: ['min' => 40],
            );
        $targetEvaluation = (new TargetEvaluator($pyrameterConfig->targetPercentages()))->evaluate($pyramidSummary);
        $suiteShape       = (new SuiteShapeResolver())->resolve($pyramidSummary, $targetEvaluation);

        $report = (new PyramidReporter())->render($pyramidSummary, $targetEvaluation, $suiteShape);

        $this->assertStringContainsString('| Unit        |     8 |  80.0% | >= 40.0% ✓ |', $report);
        $this->assertStringContainsString('| Integration |     2 |  20.0% |  No target |', $report);
        $this->assertStringNotContainsString('0.0%-100.0%', $report);
    }

    public function testItSortsUntargetedLayersAboveTargetedLayersInThePyramid(): void
    {
        $pyramidSummary   = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 8),
            ...$this->records(TestKind::Integration, 2),
        ]);
        $pyrameterConfig  = PyrameterConfig::create()
            ->targetShape(
                unit: ['min' => 40],
                integration: ['min' => 10],
            );
        $targetEvaluation = (new TargetEvaluator($pyrameterConfig->targetPercentages()))->evaluate($pyramidSummary);
        $suiteShape       = (new SuiteShapeResolver())->resolve($pyramidSummary, $targetEvaluation);

        $report = (new PyramidReporter())->render($pyramidSummary, $targetEvaluation, $suiteShape);

        $e2ePosition        = strpos($report, 'E2E');
        $functionalPosition = strpos($report, 'Functional');
        $unitPosition       = strpos($report, 'Unit');

        $this->assertNotFalse($e2ePosition);
        $this->assertNotFalse($functionalPosition);
        $this->assertNotFalse($unitPosition);

        $integrationPosition = strpos($report, 'Integration', $functionalPosition);

        $this->assertNotFalse($integrationPosition);
        $this->assertLessThan($integrationPosition, $e2ePosition);
        $this->assertLessThan($unitPosition, $functionalPosition);
        $this->assertLessThan($integrationPosition, $functionalPosition);
        $this->assertLessThan($unitPosition, $integrationPosition);
    }

    /**
     * @return list<TestRecord>
     */
    private function records(TestKind $testKind, int $count): array
    {
        $records = [];

        for ($i = 1; $i <= $count; ++$i) {
            $records[] = new TestRecord($testKind->value . 'Test', 'test_' . $i, [], $testKind);
        }

        return $records;
    }
}
