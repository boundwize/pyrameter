<?php

declare(strict_types=1);

namespace Pyrameter\Event;

use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use Pyrameter\PyramidSummary;
use Pyrameter\Report\PyramidReporter;
use Pyrameter\Report\SuiteShapeResolver;
use Pyrameter\Target\TargetEvaluator;
use Pyrameter\TestCollector;

final readonly class PrintReportSubscriber implements ExecutionFinishedSubscriber
{
    private TargetEvaluator $targetEvaluator;

    private SuiteShapeResolver $shapeResolver;

    public function __construct(
        private TestCollector $collector,
        array $targets,
        private PyramidReporter $reporter,
    ) {
        $this->targetEvaluator = new TargetEvaluator($targets);
        $this->shapeResolver = new SuiteShapeResolver();
    }

    public function notify(ExecutionFinished $event): void
    {
        $summary = PyramidSummary::fromRecords($this->collector->all());
        $targetResult = $this->targetEvaluator->evaluate($summary);
        $shape = $this->shapeResolver->resolve($summary, $targetResult);

        $this->reporter->print($summary, $targetResult, $shape);
    }
}
