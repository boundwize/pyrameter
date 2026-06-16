<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Config;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Config\PyrameterConfigLoader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ParameterCollection;
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
    public function testItUsesDefaultConfigurationWhenFileIsMissing(): void
    {
        $pyrameterConfig = PyrameterConfigLoader::load(__DIR__ . '/missing-pyrameter.php');

        $this->assertNotEmpty($pyrameterConfig->usageRules());
        $this->assertSame(['min' => 70.0, 'max' => 100.0], $pyrameterConfig->targetPercentages()['unit']);
    }

    public function testItLoadsAConfigurationFile(): void
    {
        $path = sys_get_temp_dir() . '/pyrameter-config-test.php';
        file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::create()
    ->usesClass(PDO::class, TestKind::Integration)
    ->targetShape(
        unit: ['min' => 60],
        functional: ['max' => 30],
        integration: ['max' => 5],
        e2e: ['max' => 3],
    );
PHP);

        try {
            $config = PyrameterConfigLoader::load($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(['min' => 60.0, 'max' => 100.0], $config->targetPercentages()['unit']);
        $this->assertCount(1, $config->usageRules());
    }

    public function testItLoadsAConfigurationFileFromPhpunitParameters(): void
    {
        $path = sys_get_temp_dir() . '/pyrameter-config-parameter-test.php';
        file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;

return PyrameterConfig::create()
    ->targetShape(
        unit: ['min' => 55],
        functional: ['max' => 35],
        integration: ['max' => 5],
        e2e: ['max' => 3],
    );
PHP);

        try {
            $config = PyrameterConfigLoader::loadFromParametersOrDefaults(ParameterCollection::fromArray([
                'config' => $path,
            ]));
        } finally {
            unlink($path);
        }

        $this->assertSame(['min' => 55.0, 'max' => 100.0], $config->targetPercentages()['unit']);
    }

    public function testItLoadsDefaultConfigurationPathFromCurrentWorkingDirectory(): void
    {
        $previousDirectory = getcwd();
        $directory         = sys_get_temp_dir() . '/pyrameter-config-cwd-' . uniqid();

        $this->assertIsString($previousDirectory);

        mkdir($directory);
        file_put_contents($directory . '/pyrameter.php', <<<'PHP'
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;

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

        $this->assertSame(['min' => 45.0, 'max' => 100.0], $config->targetPercentages()['unit']);
    }

    public function testItUsesRelativeDefaultPathWhenCurrentWorkingDirectoryIsUnavailable(): void
    {
        $previousDirectory = getcwd();
        $directory         = sys_get_temp_dir() . '/pyrameter-config-missing-cwd-' . uniqid();

        $this->assertIsString($previousDirectory);

        mkdir($directory);
        chdir($directory);
        @rmdir($directory);

        try {
            $config = PyrameterConfigLoader::load();
        } finally {
            chdir($previousDirectory);
            @rmdir($directory);
        }

        $this->assertNotEmpty($config->usageRules());
    }

    public function testConfigurationFileMustReturnAPyrameterConfig(): void
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

    public function testTargetShapeCanStartWithOnlyUnitMinimum(): void
    {
        $pyrameterConfig = PyrameterConfig::create()
            ->targetShape(
                unit: ['min' => 40],
            );

        $this->assertSame(['min' => 40.0, 'max' => 100.0], $pyrameterConfig->targetPercentages()['unit']);
        $this->assertSame(['min' => 0.0, 'max' => 100.0], $pyrameterConfig->targetPercentages()['functional']);
        $this->assertSame(['min' => 0.0, 'max' => 100.0], $pyrameterConfig->targetPercentages()['integration']);
        $this->assertSame(['min' => 0.0, 'max' => 100.0], $pyrameterConfig->targetPercentages()['e2e']);
    }

    public function testTargetShapeRangesMustBePossible(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum percentages must allow 100.0');

        PyrameterConfig::create()->targetShape(
            unit: ['max' => 50],
            functional: ['max' => 20],
            integration: ['max' => 8],
            e2e: ['max' => 2],
        );
    }

    public function testTargetShapeMinimumCannotExceedMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('minimum for Unit cannot be greater than its maximum');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => 80, 'max' => 70],
            functional: ['max' => 20],
            integration: ['max' => 8],
            e2e: ['max' => 2],
        );
    }

    public function testFailOnViolationIsDisabledByDefaultAndCanBeEnabled(): void
    {
        $pyrameterConfig = PyrameterConfig::create();

        $this->assertFalse($pyrameterConfig->shouldFailOnViolation());
        $this->assertTrue($pyrameterConfig->failOnViolation()->shouldFailOnViolation());
    }
}
