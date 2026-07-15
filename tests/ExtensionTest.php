<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\Extension;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Facade as ExtensionFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

use function class_exists;
use function file_put_contents;
use function getenv;
use function putenv;
use function str_replace;
use function sys_get_temp_dir;
use function unlink;

final class ExtensionTest extends TestCase
{
    public function testItDoesNotBootstrapWhenDisabledByEnvironmentVariable(): void
    {
        $originalValue = getenv('PYRAMETER_DISABLED');
        putenv('PYRAMETER_DISABLED=1');

        try {
            (new Extension())->bootstrap(
                (new ReflectionClass(Configuration::class))->newInstanceWithoutConstructor(),
                $this->extensionFacade(),
                ParameterCollection::fromArray(['config' => '/missing/pyrameter.php']),
            );
        } finally {
            if ($originalValue === false) {
                putenv('PYRAMETER_DISABLED');
            } else {
                putenv('PYRAMETER_DISABLED=' . $originalValue);
            }
        }

        self::addToAssertionCount(1);
    }

    public function testItBootstrapsThePhpunitSubscribers(): void
    {
        $configFile = sys_get_temp_dir() . '/pyrameter-extension-test.php';
        file_put_contents($configFile, <<<'PHP'
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;

return PyrameterConfig::create();
PHP);

        $reflectionProperty  = new ReflectionProperty(EventFacade::class, 'instance');
        $originalEventFacade = $reflectionProperty->getValue();
        $reflectionProperty->setValue(null, new EventFacade());

        try {
            (new Extension())->bootstrap(
                (new ReflectionClass(Configuration::class))->newInstanceWithoutConstructor(),
                $this->extensionFacade(),
                ParameterCollection::fromArray(['config' => $configFile]),
            );
        } finally {
            $reflectionProperty->setValue(null, $originalEventFacade);
            unlink($configFile);
        }

        self::addToAssertionCount(1);
    }

    private function extensionFacade(): ExtensionFacade
    {
        $reflection = new ReflectionClass(ExtensionFacade::class);

        if ($reflection->isInterface()) {
            $concreteFacadeClass = str_replace('Facade', 'ExtensionFacade', ExtensionFacade::class);

            if (! class_exists($concreteFacadeClass)) {
                throw new RuntimeException('Could not find the PHPUnit extension facade');
            }

            $reflection = new ReflectionClass($concreteFacadeClass);
        }

        $facade = $reflection->newInstanceWithoutConstructor();

        if (! $facade instanceof ExtensionFacade) {
            throw new RuntimeException('Could not create a PHPUnit extension facade');
        }

        return $facade;
    }
}
