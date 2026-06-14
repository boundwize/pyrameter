<?php

declare(strict_types=1);

namespace Pyrameter\Config;

use PHPUnit\Runner\Extension\ParameterCollection;
use RuntimeException;

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
        if (method_exists($parameters, 'has') && method_exists($parameters, 'get') && $parameters->has($name)) {
            $parameter = $parameters->get($name);

            if (is_object($parameter) && method_exists($parameter, 'value')) {
                return (string) $parameter->value();
            }

            return (string) $parameter;
        }

        foreach ($parameters as $parameter) {
            if (! is_object($parameter)) {
                continue;
            }

            if (! method_exists($parameter, 'name') || $parameter->name() !== $name) {
                continue;
            }

            if (method_exists($parameter, 'value')) {
                return (string) $parameter->value();
            }
        }

        return null;
    }
}
