<?php

declare(strict_types=1);

namespace Pyrameter\Event;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use Pyrameter\Detection\TestUsageScanner;
use Pyrameter\TestCollector;
use Pyrameter\TestKind;
use Pyrameter\TestRecord;
use Pyrameter\UsageClassifier;

use function explode;
use function is_string;
use function method_exists;
use function str_contains;
use function strpos;
use function substr;

final readonly class CollectTestResultSubscriber implements FinishedSubscriber
{
    public function __construct(
        private TestCollector $collector,
        private TestUsageScanner $scanner,
        private UsageClassifier $classifier,
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

        $scanResult = $this->scanner->scan($testClassName);

        $kind = $scanResult->inspectable
            ? $this->classifier->classify($scanResult->consumedClasses)
            : TestKind::Unknown;

        $this->collector->add(new TestRecord(
            testClassName: $testClassName,
            testMethodName: $this->normalizeMethodName($testMethodName),
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
        if (method_exists($test, 'methodName')) {
            $methodName = $test->methodName();

            return is_string($methodName) ? $methodName : null;
        }

        $id = method_exists($test, 'id') ? $test->id() : null;

        if (! is_string($id) || ! str_contains($id, '::')) {
            return null;
        }

        return explode('::', $id, 2)[1];
    }

    private function normalizeMethodName(string $testMethodName): string
    {
        foreach ([' with data set ', '#'] as $marker) {
            $position = strpos($testMethodName, $marker);

            if ($position !== false) {
                return substr($testMethodName, 0, $position);
            }
        }

        return $testMethodName;
    }
}
