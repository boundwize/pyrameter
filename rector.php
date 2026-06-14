<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/Detection/TestUsageScanner.php',
        ],
    ])
    ->withPhpSets();
