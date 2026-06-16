<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Event;

use Boundwize\Pyrameter\Detection\TestUsageScanner;
use Boundwize\Pyrameter\TestCollector;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\TestRecord;
use Boundwize\Pyrameter\UsageClassifier;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

use function explode;
use function is_string;
use function method_exists;
use function str_contains;

final readonly class CollectTestResultSubscriber implements FinishedSubscriber
{
    public function __construct(
        private TestCollector $testCollector,
        private TestUsageScanner $testUsageScanner,
        private UsageClassifier $usageClassifier,
    ) {
    }

    public function notify(Finished $event): void
    {
        $test           = $event->test();
        $testClassName  = $this->extractClassName($test);
        $testMethodName = $this->extractMethodName($test);

        if ($testClassName === null || $testMethodName === null) {
            return;
        }

        $scanResult = $this->testUsageScanner->scan($testClassName);

        $kind = $scanResult->inspectable
            ? $this->usageClassifier->classify($scanResult->consumedClasses)
            : TestKind::Unit;

        $this->testCollector->add(new TestRecord(
            testClassName: $testClassName,
            testMethodName: $testMethodName,
            consumedClasses: $scanResult->consumedClasses,
            kind: $kind,
        ));
    }

    private function extractClassName(object $test): ?string
    {
        if (method_exists($test, 'className')) {
            $className = $test->className();

            return is_string($className) ? $className : null;
        }

        $id = method_exists($test, 'id') ? $test->id() : null;

        if (! is_string($id) || ! str_contains($id, '::')) {
            return null;
        }

        return explode('::', $id, 2)[0];
    }

    private function extractMethodName(object $test): ?string
    {
        $id = method_exists($test, 'id') ? $test->id() : null;

        if (is_string($id) && str_contains($id, '::')) {
            return explode('::', $id, 2)[1];
        }

        if (method_exists($test, 'methodName')) {
            $methodName = $test->methodName();

            return is_string($methodName) ? $methodName : null;
        }

        return null;
    }
}
