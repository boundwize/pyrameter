<?php

declare(strict_types=1);

namespace Pyrameter;

use PHPUnit\Runner\Extension\Extension as PHPUnitExtension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Pyrameter\Config\PyrameterConfigLoader;
use Pyrameter\Detection\TestUsageScanner;
use Pyrameter\Event\CollectTestResultSubscriber;
use Pyrameter\Event\PrintReportSubscriber;
use Pyrameter\Report\PyramidReporter;

final class Extension implements PHPUnitExtension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $pyrameterConfig = PyrameterConfigLoader::loadFromParametersOrDefaults($parameters);
        $collector = new TestCollector();
        $scanner = new TestUsageScanner();
        $classifier = new UsageClassifier(
            rules: $pyrameterConfig->usageRules(),
        );

        $facade->registerSubscriber(new CollectTestResultSubscriber(
            collector: $collector,
            scanner: $scanner,
            classifier: $classifier,
        ));

        $facade->registerSubscriber(new PrintReportSubscriber(
            collector: $collector,
            targets: $pyrameterConfig->targetPercentages(),
            reporter: new PyramidReporter(),
            failOnViolation: $pyrameterConfig->shouldFailOnViolation(),
        ));
    }
}
