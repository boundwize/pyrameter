<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Config;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ParameterCollection;
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\Config\PyrameterConfigLoader;
use RuntimeException;

use function chdir;
use function file_put_contents;
use function getcwd;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class PyrameterConfigLoaderTest extends TestCase
{
    public function test_it_uses_default_configuration_when_file_is_missing(): void
    {
        $config = PyrameterConfigLoader::load(__DIR__ . '/missing-pyrameter.php');

        self::assertNotEmpty($config->usageRules());
        self::assertSame(['min' => 70.0, 'max' => 100.0], $config->targetPercentages()['unit']);
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
        unit: ['min' => 60],
        functional: ['max' => 30],
        integration: ['max' => 5],
        e2e: ['max' => 3],
        unknown: ['max' => 2],
    );
PHP);

        try {
            $config = PyrameterConfigLoader::load($path);
        } finally {
            unlink($path);
        }

        self::assertSame(['min' => 60.0, 'max' => 100.0], $config->targetPercentages()['unit']);
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
        unit: ['min' => 55],
        functional: ['max' => 35],
        integration: ['max' => 5],
        e2e: ['max' => 3],
        unknown: ['max' => 2],
    );
PHP);

        try {
            $config = PyrameterConfigLoader::loadFromParametersOrDefaults(ParameterCollection::fromArray([
                'config' => $path,
            ]));
        } finally {
            unlink($path);
        }

        self::assertSame(['min' => 55.0, 'max' => 100.0], $config->targetPercentages()['unit']);
    }

    public function test_it_loads_default_configuration_path_from_current_working_directory(): void
    {
        $previousDirectory = getcwd();
        $directory         = sys_get_temp_dir() . '/pyrameter-config-cwd-' . uniqid();

        self::assertIsString($previousDirectory);

        mkdir($directory);
        file_put_contents($directory . '/pyrameter.php', <<<'PHP'
<?php

declare(strict_types=1);

use Pyrameter\Config\PyrameterConfig;

return PyrameterConfig::create()
    ->targetShape(unit: ['min' => 45]);
PHP);

        chdir($directory);

        try {
            $config = PyrameterConfigLoader::load();
        } finally {
            chdir($previousDirectory);
            unlink($directory . '/pyrameter.php');
            rmdir($directory);
        }

        self::assertSame(['min' => 45.0, 'max' => 100.0], $config->targetPercentages()['unit']);
    }

    public function test_it_uses_relative_default_path_when_current_working_directory_is_unavailable(): void
    {
        $previousDirectory = getcwd();
        $directory         = sys_get_temp_dir() . '/pyrameter-config-missing-cwd-' . uniqid();

        self::assertIsString($previousDirectory);

        mkdir($directory);
        chdir($directory);
        rmdir($directory);

        try {
            $config = PyrameterConfigLoader::load();
        } finally {
            chdir($previousDirectory);
        }

        self::assertNotEmpty($config->usageRules());
    }

    public function test_configuration_file_must_return_a_pyrameter_config(): void
    {
        $path = sys_get_temp_dir() . '/pyrameter-invalid-config-test.php';
        file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

return new stdClass();
PHP);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return an instance of ' . PyrameterConfig::class);

        try {
            PyrameterConfigLoader::load($path);
        } finally {
            unlink($path);
        }
    }

    public function test_target_shape_can_start_with_only_unit_minimum(): void
    {
        $config = PyrameterConfig::create()
            ->targetShape(
                unit: ['min' => 40],
            );

        self::assertSame(['min' => 40.0, 'max' => 100.0], $config->targetPercentages()['unit']);
        self::assertSame(['min' => 0.0, 'max' => 100.0], $config->targetPercentages()['functional']);
        self::assertSame(['min' => 0.0, 'max' => 100.0], $config->targetPercentages()['integration']);
        self::assertSame(['min' => 0.0, 'max' => 100.0], $config->targetPercentages()['e2e']);
        self::assertSame(['min' => 0.0, 'max' => 100.0], $config->targetPercentages()['unknown']);
    }

    public function test_target_shape_ranges_must_be_possible(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum percentages must allow 100.0');

        PyrameterConfig::create()->targetShape(
            unit: ['max' => 50],
            functional: ['max' => 20],
            integration: ['max' => 8],
            e2e: ['max' => 2],
            unknown: ['max' => 2],
        );
    }

    public function test_target_shape_minimum_cannot_exceed_maximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('minimum for Unit cannot be greater than its maximum');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => 80, 'max' => 70],
            functional: ['max' => 20],
            integration: ['max' => 8],
            e2e: ['max' => 2],
            unknown: ['max' => 2],
        );
    }

    public function test_fail_on_violation_is_disabled_by_default_and_can_be_enabled(): void
    {
        $config = PyrameterConfig::create();

        self::assertFalse($config->shouldFailOnViolation());
        self::assertTrue($config->failOnViolation()->shouldFailOnViolation());
    }
}
