<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Report;

use Boundwize\Pyrameter\Target\TargetEvaluation;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\ValueObject\PyramidSummary;

final readonly class SuiteShapeResolver
{
    public function resolve(PyramidSummary $pyramidSummary, TargetEvaluation $targetEvaluation): SuiteShape
    {
        if ($pyramidSummary->total === 0) {
            return new SuiteShape('Empty Suite', 'No tests were collected.', false);
        }

        $heavy = $pyramidSummary->percentage(TestKind::Integration) + $pyramidSummary->percentage(TestKind::E2E);

        if ($heavy > $pyramidSummary->percentage(TestKind::Unit)) {
            return new SuiteShape('Inverted Pyramid', 'Your heavier tests outnumber your unit tests.', false);
        }

        $e2eMax = $targetEvaluation->status(TestKind::E2E)->max;

        if ($e2eMax !== null && $pyramidSummary->percentage(TestKind::E2E) > $e2eMax) {
            return new SuiteShape('E2E Tower', 'Your E2E tests are growing beyond the target.', false);
        }

        $integrationMax = $targetEvaluation->status(TestKind::Integration)->max;

        if ($integrationMax !== null && $pyramidSummary->percentage(TestKind::Integration) > $integrationMax) {
            return new SuiteShape('Integration Mountain', 'Your suite is getting heavier.', false);
        }

        if ($targetEvaluation->allPassed()) {
            return new SuiteShape('Healthy Pyramid', 'Your test pyramid target passed.', true);
        }

        return new SuiteShape('Wide Pyramid', 'Your suite is wider than the configured target.', false);
    }
}
