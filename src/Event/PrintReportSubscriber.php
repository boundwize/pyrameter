<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Event;

use Boundwize\Pyrameter\Analysis\TestCollector;
use Boundwize\Pyrameter\Report\PyramidReporter;
use Boundwize\Pyrameter\Report\SuiteShapeResolver;
use Boundwize\Pyrameter\Target\TargetEvaluator;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;

final readonly class PrintReportSubscriber implements ExecutionFinishedSubscriber
{
    private TargetEvaluator $targetEvaluator;

    private SuiteShapeResolver $suiteShapeResolver;

    /**
     * @param array<string, array{min: float, max: float}> $targets
     */
    public function __construct(
        private TestCollector $testCollector,
        array $targets,
        private PyramidReporter $pyramidReporter,
    ) {
        $this->targetEvaluator    = new TargetEvaluator($targets);
        $this->suiteShapeResolver = new SuiteShapeResolver();
    }

    public function notify(ExecutionFinished $event): void
    {
        $pyramidSummary   = $this->testCollector->summary();
        $targetEvaluation = $this->targetEvaluator->evaluate($pyramidSummary);
        $suiteShape       = $this->suiteShapeResolver->resolve($pyramidSummary, $targetEvaluation);

        $this->pyramidReporter->print($pyramidSummary, $targetEvaluation, $suiteShape);
    }
}
