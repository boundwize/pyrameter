<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Report;

use PHPUnit\Framework\TestCase;
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\PyramidSummary;
use Pyrameter\Report\SuiteShape;
use Pyrameter\Report\SuiteShapeResolver;
use Pyrameter\Target\TargetEvaluator;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;

final class SuiteShapeResolverTest extends TestCase
{
    public function test_it_detects_empty_suite(): void
    {
        $shape = $this->shape([]);

        self::assertSame('Empty Suite', $shape->name);
        self::assertSame('No tests were collected.', $shape->verdict);
    }

    public function test_it_detects_unknown_swamp(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value    => 9,
            TestKind::Unknown->value => 1,
        ]);

        self::assertSame('Unknown Swamp', $shape->name);
        self::assertSame('Too many tests could not be inspected.', $shape->verdict);
    }

    public function test_it_detects_e2e_tower(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 80,
            TestKind::Functional->value  => 10,
            TestKind::Integration->value => 5,
            TestKind::E2E->value         => 5,
        ]);

        self::assertSame('E2E Tower', $shape->name);
        self::assertSame('Your E2E tests are growing beyond the target.', $shape->verdict);
    }

    public function test_it_detects_inverted_pyramid(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 35,
            TestKind::Integration->value => 45,
            TestKind::E2E->value         => 5,
        ]);

        self::assertSame('Inverted Pyramid', $shape->name);
        self::assertSame('Your heavier tests outnumber your unit tests.', $shape->verdict);
    }

    public function test_it_detects_integration_mountain(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 7,
            TestKind::Functional->value  => 2,
            TestKind::Integration->value => 1,
        ]);

        self::assertSame('Integration Mountain', $shape->name);
        self::assertSame('Your suite is getting heavier.', $shape->verdict);
    }

    public function test_it_detects_healthy_pyramid(): void
    {
        $shape = $this->shape([
            TestKind::Unit->value        => 72,
            TestKind::Functional->value  => 18,
            TestKind::Integration->value => 8,
            TestKind::E2E->value         => 2,
        ]);

        self::assertSame('Healthy Pyramid', $shape->name);
        self::assertSame('Your test pyramid target passed.', $shape->verdict);
        self::assertTrue($shape->healthy);
    }

    public function test_it_detects_wide_pyramid(): void
    {
        $config = PyrameterConfig::create()
            ->targetShape(
                unit: ['min' => 80],
                functional: ['max' => 10],
                integration: ['max' => 6],
                e2e: ['max' => 2],
                unknown: ['max' => 2],
            );

        $shape = $this->shape([
            TestKind::Unit->value        => 75,
            TestKind::Functional->value  => 20,
            TestKind::Integration->value => 5,
        ], $config);

        self::assertSame('Wide Pyramid', $shape->name);
        self::assertSame('Your suite is wider than the configured target.', $shape->verdict);
    }

    /**
     * @param array<string, int> $counts
     */
    private function shape(array $counts, ?PyrameterConfig $config = null): SuiteShape
    {
        $summary  = PyramidSummary::fromRecords($this->records($counts));
        $config ??= PyrameterConfig::defaults();
        $targets = (new TargetEvaluator($config->targetPercentages()))->evaluate($summary);

        return (new SuiteShapeResolver())->resolve($summary, $targets);
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
