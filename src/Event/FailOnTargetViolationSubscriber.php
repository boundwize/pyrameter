<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Event;

use Boundwize\Pyrameter\Analysis\TestCollector;
use Boundwize\Pyrameter\Target\TargetEvaluator;
use Closure;
use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;

use const PHP_EOL;

final readonly class FailOnTargetViolationSubscriber implements FinishedSubscriber
{
    private TargetEvaluator $targetEvaluator;

    /**
     * @param array<string, array{min: float, max: float}> $targets
     * @param null|Closure(int): void $exit
     */
    public function __construct(
        private TestCollector $testCollector,
        array $targets,
        private bool $failOnViolation = false,
        private ?Closure $exit = null,
    ) {
        $this->targetEvaluator = new TargetEvaluator($targets);
    }

    public function notify(Finished $event): void
    {
        if (! $this->failOnViolation) {
            return;
        }

        $targetEvaluation = $this->targetEvaluator->evaluate($this->testCollector->summary());

        if ($targetEvaluation->allPassed()) {
            return;
        }

        echo PHP_EOL . 'Pyrameter target shape violated.' . PHP_EOL;

        if ($event->shellExitCode() !== 0) {
            return;
        }

        $this->exit(1);
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
