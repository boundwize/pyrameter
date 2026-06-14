<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Report;

use PHPUnit\Framework\TestCase;
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\PyramidSummary;
use Pyrameter\Report\PyramidReporter;
use Pyrameter\Report\SuiteShapeResolver;
use Pyrameter\Target\TargetEvaluator;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

final class PyramidReporterTest extends TestCase
{
    public function test_it_renders_the_warning_report(): void
    {
        $summary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 7),
            ...$this->records(TestKind::Functional, 2),
            ...$this->records(TestKind::Integration, 1),
        ]);
        $targets = (new TargetEvaluator(PyrameterConfig::defaults()->targetPercentages()))->evaluate($summary);
        $shape = (new SuiteShapeResolver())->resolve($summary, $targets);

        $report = (new PyramidReporter())->render($summary, $targets, $shape);

        self::assertStringContainsString('Pyrameter', $report);
        self::assertStringContainsString('Shape: Integration Mountain ⚠', $report);
        self::assertStringContainsString('Integration       1    10.0%   <=  8.0%    ✗', $report);
        self::assertStringContainsString('Total: 10 tests', $report);
        self::assertStringContainsString('Your suite is getting heavier.', $report);
    }

    public function test_it_renders_the_passed_report(): void
    {
        $summary = PyramidSummary::fromRecords([
            ...$this->records(TestKind::Unit, 72),
            ...$this->records(TestKind::Functional, 18),
            ...$this->records(TestKind::Integration, 8),
            ...$this->records(TestKind::E2E, 2),
        ]);
        $targets = (new TargetEvaluator(PyrameterConfig::defaults()->targetPercentages()))->evaluate($summary);
        $shape = (new SuiteShapeResolver())->resolve($summary, $targets);

        $report = (new PyramidReporter())->render($summary, $targets, $shape);

        self::assertStringContainsString('Shape: Healthy Pyramid ✓', $report);
        self::assertStringContainsString('Your test pyramid target passed.', $report);
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
