<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter;

use Boundwize\Pyrameter\Analysis\TestCollector;
use Boundwize\Pyrameter\Analysis\UsageClassifier;
use Boundwize\Pyrameter\Config\PyrameterConfigLoader;
use Boundwize\Pyrameter\Detection\TestUsageScanner;
use Boundwize\Pyrameter\Event\CollectTestResultSubscriber;
use Boundwize\Pyrameter\Event\PrintReportSubscriber;
use Boundwize\Pyrameter\Report\PyramidReporter;
use PHPUnit\Runner\Extension\Extension as PHPUnitExtension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class Extension implements PHPUnitExtension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $pyrameterConfig  = PyrameterConfigLoader::loadFromParametersOrDefaults($parameters);
        $testCollector    = new TestCollector();
        $testUsageScanner = new TestUsageScanner();
        $usageClassifier  = new UsageClassifier(
            rules: $pyrameterConfig->usageRules(),
        );

        $facade->registerSubscriber(new CollectTestResultSubscriber(
            testCollector: $testCollector,
            testUsageScanner: $testUsageScanner,
            usageClassifier: $usageClassifier,
        ));

        $facade->registerSubscriber(new PrintReportSubscriber(
            testCollector: $testCollector,
            targets: $pyrameterConfig->targetPercentages(),
            pyramidReporter: new PyramidReporter(),
            failOnViolation: $pyrameterConfig->shouldFailOnViolation(),
        ));
    }
}
