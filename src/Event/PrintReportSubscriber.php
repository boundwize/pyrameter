<?php

declare(strict_types=1);

namespace Pyrameter\Event;

use Closure;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use Pyrameter\PyramidSummary;
use Pyrameter\Report\PyramidReporter;
use Pyrameter\Report\SuiteShapeResolver;
use Pyrameter\Target\TargetEvaluator;
use Pyrameter\TestCollector;

use function fwrite;

use const PHP_EOL;
use const STDOUT;

final readonly class PrintReportSubscriber implements ExecutionFinishedSubscriber
{
    private TargetEvaluator $targetEvaluator;

    private SuiteShapeResolver $shapeResolver;

    /**
     * @param array<string, array{min: float, max: float}> $targets
     * @param null|Closure(int): void $exit
     */
    public function __construct(
        private TestCollector $collector,
        array $targets,
        private PyramidReporter $reporter,
        private bool $failOnViolation = false,
        private ?Closure $exit = null,
    ) {
        $this->targetEvaluator = new TargetEvaluator($targets);
        $this->shapeResolver   = new SuiteShapeResolver();
    }

    public function notify(ExecutionFinished $event): void
    {
        $summary      = PyramidSummary::fromRecords($this->collector->all());
        $targetResult = $this->targetEvaluator->evaluate($summary);
        $shape        = $this->shapeResolver->resolve($summary, $targetResult);

        $this->reporter->print($summary, $targetResult, $shape);

        if ($this->failOnViolation && ! $targetResult->allPassed()) {
            fwrite(STDOUT, PHP_EOL . 'Pyrameter target shape violated.' . PHP_EOL);

            $this->exit(1);
        }
    }

    private function exit(int $status): void
    {
        if ($this->exit !== null) {
            ($this->exit)($status);

            return;
        }

        // @codeCoverageIgnoreStart
        exit($status);
        // @codeCoverageIgnoreEnd
    }
}
