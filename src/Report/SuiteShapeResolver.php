<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Report;

use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Target\TargetEvaluation;
use Boundwize\Pyrameter\TestKind;

final readonly class SuiteShapeResolver
{
    public function resolve(PyramidSummary $summary, TargetEvaluation $targets): SuiteShape
    {
        if ($summary->total === 0) {
            return new SuiteShape('Empty Suite', 'No tests were collected.', false);
        }

        $unknownMax = $targets->status(TestKind::Unknown)->max;

        if ($unknownMax !== null && $summary->percentage(TestKind::Unknown) > $unknownMax) {
            return new SuiteShape('Unknown Swamp', 'Too many tests could not be inspected.', false);
        }

        $heavy = $summary->percentage(TestKind::Integration) + $summary->percentage(TestKind::E2E);

        if ($heavy > $summary->percentage(TestKind::Unit)) {
            return new SuiteShape('Inverted Pyramid', 'Your heavier tests outnumber your unit tests.', false);
        }

        $e2eMax = $targets->status(TestKind::E2E)->max;

        if ($e2eMax !== null && $summary->percentage(TestKind::E2E) > $e2eMax) {
            return new SuiteShape('E2E Tower', 'Your E2E tests are growing beyond the target.', false);
        }

        $integrationMax = $targets->status(TestKind::Integration)->max;

        if ($integrationMax !== null && $summary->percentage(TestKind::Integration) > $integrationMax) {
            return new SuiteShape('Integration Mountain', 'Your suite is getting heavier.', false);
        }

        if ($targets->allPassed()) {
            return new SuiteShape('Healthy Pyramid', 'Your test pyramid target passed.', true);
        }

        return new SuiteShape('Wide Pyramid', 'Your suite is wider than the configured target.', false);
    }
}
