<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Config;

use InvalidArgumentException;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\Framework\TestCase;
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\Config\PyrameterConfigLoader;

final class PyrameterConfigLoaderTest extends TestCase
{
    public function test_it_uses_default_configuration_when_file_is_missing(): void
    {
        $config = PyrameterConfigLoader::load(__DIR__ . '/missing-pyrameter.php');

        self::assertNotEmpty($config->usageRules());
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
    ->targetShape(
        unit: 60,
        functional: 30,
        integration: 5,
        e2e: 3,
        unknown: 2,
    );
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
    ->targetShape(
        unit: 55,
        functional: 35,
        integration: 5,
        e2e: 3,
        unknown: 2,
    );
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

    public function test_target_shape_must_total_100_percent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must total 100.0');

        PyrameterConfig::create()->targetShape(
            unit: 70,
            functional: 20,
            integration: 8,
            e2e: 2,
            unknown: 2,
        );
    }
}
