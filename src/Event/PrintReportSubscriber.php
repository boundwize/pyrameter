<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Event;

use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Report\PyramidReporter;
use Boundwize\Pyrameter\Report\SuiteShapeResolver;
use Boundwize\Pyrameter\Target\TargetEvaluator;
use Boundwize\Pyrameter\TestCollector;
use Closure;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;

use function fwrite;

use const PHP_EOL;
use const STDOUT;

final readonly class PrintReportSubscriber implements ExecutionFinishedSubscriber
{
    private TargetEvaluator $targetEvaluator;

    private SuiteShapeResolver $suiteShapeResolver;

    /**
     * @param array<string, array{min: float, max: float}> $targets
     * @param null|Closure(int): void $exit
     */
    public function __construct(
        private TestCollector $testCollector,
        array $targets,
        private PyramidReporter $pyramidReporter,
        private bool $failOnViolation = false,
        private ?Closure $exit = null,
    ) {
        $this->targetEvaluator    = new TargetEvaluator($targets);
        $this->suiteShapeResolver = new SuiteShapeResolver();
    }

    public function notify(ExecutionFinished $event): void
    {
        $pyramidSummary   = PyramidSummary::fromRecords($this->testCollector->all());
        $targetEvaluation = $this->targetEvaluator->evaluate($pyramidSummary);
        $suiteShape       = $this->suiteShapeResolver->resolve($pyramidSummary, $targetEvaluation);

        $this->pyramidReporter->print($pyramidSummary, $targetEvaluation, $suiteShape);

        if ($this->failOnViolation && ! $targetEvaluation->allPassed()) {
            fwrite(STDOUT, PHP_EOL . 'Pyrameter target shape violated.' . PHP_EOL);

            $this->exit(1);
        }
    }

    private function exit(int $status): void
    {
        if ($this->exit instanceof Closure) {
            ($this->exit)($status);

            return;
        }

        // @codeCoverageIgnoreStart
        exit($status);
        // @codeCoverageIgnoreEnd
    }
}
