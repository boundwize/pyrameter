<?php

declare(strict_types=1);

namespace Pyrameter\Config;

use PHPUnit\Runner\Extension\ParameterCollection;
use RuntimeException;

use function getcwd;
use function is_file;
use function sprintf;

use const DIRECTORY_SEPARATOR;

final class PyrameterConfigLoader
{
    public static function loadFromParametersOrDefaults(ParameterCollection $parameters): PyrameterConfig
    {
        return self::load(self::parameterValue($parameters, 'config'));
    }

    public static function load(?string $path = null): PyrameterConfig
    {
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

    private static function parameterValue(ParameterCollection $parameters, string $name): ?string
    {
        return $parameters->has($name) ? $parameters->get($name) : null;
    }
}
