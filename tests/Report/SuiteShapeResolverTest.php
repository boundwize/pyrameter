<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Report;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Report\SuiteShape;
use Boundwize\Pyrameter\Report\SuiteShapeResolver;
use Boundwize\Pyrameter\Target\TargetEvaluator;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\TestRecord;
use PHPUnit\Framework\TestCase;

final class SuiteShapeResolverTest extends TestCase
{
    public function testItDetectsEmptySuite(): void
    {
        $shape = $this->shape([]);

        $this->assertSame('Empty Suite', $shape->name);
        $this->assertSame('No tests were collected.', $shape->verdict);
    }

    public function testItDetectsE2ETower(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 80,
            TestKind::Functional->value  => 10,
            TestKind::Integration->value => 5,
            TestKind::E2E->value         => 5,
        ]);

        $this->assertSame('E2E Tower', $shape->name);
        $this->assertSame('Your E2E tests are growing beyond the target.', $shape->verdict);
    }

    public function testItDetectsInvertedPyramid(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 35,
            TestKind::Integration->value => 45,
            TestKind::E2E->value         => 5,
        ]);

        $this->assertSame('Inverted Pyramid', $shape->name);
        $this->assertSame('Your heavier tests outnumber your unit tests.', $shape->verdict);
    }

    public function testItDetectsIntegrationMountain(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 7,
            TestKind::Functional->value  => 2,
            TestKind::Integration->value => 1,
        ]);

        $this->assertSame('Integration Mountain', $shape->name);
        $this->assertSame('Your suite is getting heavier.', $shape->verdict);
    }

    public function testItDetectsHealthyPyramid(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 72,
            TestKind::Functional->value  => 18,
            TestKind::Integration->value => 8,
            TestKind::E2E->value         => 2,
        ]);

        $this->assertSame('Healthy Pyramid', $shape->name);
        $this->assertSame('Your test pyramid target passed.', $shape->verdict);
        $this->assertTrue($shape->healthy);
    }

    public function testItDetectsWidePyramid(): void
    {
        $pyrameterConfig = PyrameterConfig::create()
            ->targetShape(
                unit: ['min' => 80],
                functional: ['max' => 10],
                integration: ['max' => 6],
                e2e: ['max' => 2],
            );

        $shape = $this->shape([
            TestKind::Unit->value        => 75,
            TestKind::Functional->value  => 20,
            TestKind::Integration->value => 5,
        ], $pyrameterConfig);

        $this->assertSame('Wide Pyramid', $shape->name);
        $this->assertSame('Your suite is wider than the configured target.', $shape->verdict);
    }

    /**
     * @param array<string, int> $counts
     */
    private function shape(array $counts, ?PyrameterConfig $pyrameterConfig = null): SuiteShape
    {
        $pyramidSummary    = PyramidSummary::fromRecords($this->records($counts));
        $pyrameterConfig ??= PyrameterConfig::defaults();
        $targetEvaluation = (new TargetEvaluator($pyrameterConfig->targetPercentages()))->evaluate($pyramidSummary);

        return (new SuiteShapeResolver())->resolve($pyramidSummary, $targetEvaluation);
    }

    /**
     * @param array<string, int> $counts
     * @return list<TestRecord>
     */
    private function records(array $counts): array
    {
        $records = [];

        foreach ($counts as $kindValue => $count) {
            $kind = TestKind::from($kindValue);

            for ($i = 1; $i <= $count; ++$i) {
                $records[] = new TestRecord($kind->value . 'Test', 'test_' . $i, [], $kind);
            }
        }

        return $records;
    }
}
