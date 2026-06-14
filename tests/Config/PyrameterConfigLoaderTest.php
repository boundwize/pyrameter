<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Config;

use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\Framework\TestCase;
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\Config\PyrameterConfigLoader;
use Pyrameter\TestKind;

final class PyrameterConfigLoaderTest extends TestCase
{
    public function test_it_uses_default_configuration_when_file_is_missing(): void
    {
        $config = PyrameterConfigLoader::load(__DIR__ . '/missing-pyrameter.php');

        self::assertNotEmpty($config->usageRules());
        self::assertSame(TestKind::Unit, $config->defaultTestKind());
        self::assertSame(['min' => 70.0], $config->targetPercentages()['unit']);
    }

    public function test_it_loads_a_configuration_file(): void
    {
        $path = sys_get_temp_dir() . '/pyrameter-config-test.php';
        file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

use Pyrameter\Config\PyrameterConfig;
use Pyrameter\TestKind;

return PyrameterConfig::create()
    ->usesClass(PDO::class, TestKind::Integration)
    ->targets()
        ->unit(min: 60);
PHP);

        try {
            $config = PyrameterConfigLoader::load($path);
        } finally {
            unlink($path);
        }

        self::assertSame(['min' => 60.0], $config->targetPercentages()['unit']);
        self::assertCount(1, $config->usageRules());
    }

    public function test_it_loads_a_configuration_file_from_phpunit_parameters(): void
    {
        $path = sys_get_temp_dir() . '/pyrameter-config-parameter-test.php';
        file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

use Pyrameter\Config\PyrameterConfig;

return PyrameterConfig::create()
    ->targets()
        ->unit(min: 55);
PHP);

        try {
            $config = PyrameterConfigLoader::loadFromParametersOrDefaults(ParameterCollection::fromArray([
                'config' => $path,
            ]));
        } finally {
            unlink($path);
        }

        self::assertSame(['min' => 55.0], $config->targetPercentages()['unit']);
    }
}
