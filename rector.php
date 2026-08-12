<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;

return RectorConfig::configure()
    ->withSkip([
        '**/Fixtures/**',
        RenameMethodRector::class => [
            __DIR__ . '/tests/Config/PyrameterConfigLoaderTest.php',
            __DIR__ . '/tests/Config/PyrameterConfigTest.php',
        ],
    ])
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        codeQuality: true,
        codingStyle: true,
        deadCode: true,
        naming: true,
        privatization: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        phpunitCodeQuality: true
    )
    ->withComposerBased(phpunit: true)
    ->withImportNames(removeUnusedImports: true);
