<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Config;

use PHPUnit\Runner\Extension\ParameterCollection;
use RuntimeException;

use function getcwd;
use function is_file;
use function sprintf;

use const DIRECTORY_SEPARATOR;

final class PyrameterConfigLoader
{
    public static function loadFromParametersOrDefaults(ParameterCollection $parameterCollection): PyrameterConfig
    {
        return self::load(self::parameterValue($parameterCollection, 'config'));
    }

    public static function load(?string $path = null): PyrameterConfig
    {
        if ($path !== null && ! is_file($path)) {
            throw new RuntimeException(sprintf('Pyrameter config file "%s" does not exist.', $path));
        }

        $path ??= self::defaultConfigPath();

        if (! is_file($path)) {
            return PyrameterConfig::defaults();
        }

        $config = require $path;

        if (! $config instanceof PyrameterConfig) {
            throw new RuntimeException(sprintf(
                'Pyrameter config file "%s" must return an instance of %s.',
                $path,
                PyrameterConfig::class,
            ));
        }

        return $config;
    }

    private static function defaultConfigPath(): string
    {
        $cwd = getcwd();

        if ($cwd === false) {
            return 'pyrameter.php';
        }

        return $cwd . DIRECTORY_SEPARATOR . 'pyrameter.php';
    }

    private static function parameterValue(ParameterCollection $parameterCollection, string $name): ?string
    {
        return $parameterCollection->has($name) ? $parameterCollection->get($name) : null;
    }
}
