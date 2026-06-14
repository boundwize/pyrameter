<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Facade as ExtensionFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Pyrameter\Extension;
use ReflectionClass;
use ReflectionProperty;

use function file_put_contents;
use function sys_get_temp_dir;
use function unlink;

final class ExtensionTest extends TestCase
{
    public function test_it_bootstraps_the_phpunit_subscribers(): void
    {
        $configFile = sys_get_temp_dir() . '/pyrameter-extension-test.php';
        file_put_contents($configFile, <<<'PHP'
<?php

declare(strict_types=1);

use Pyrameter\Config\PyrameterConfig;

return PyrameterConfig::create();
PHP);

        $eventFacadeInstance = new ReflectionProperty(EventFacade::class, 'instance');
        $originalEventFacade = $eventFacadeInstance->getValue();
        $eventFacadeInstance->setValue(null, new EventFacade());

        try {
            (new Extension())->bootstrap(
                (new ReflectionClass(Configuration::class))->newInstanceWithoutConstructor(),
                new ExtensionFacade(),
                ParameterCollection::fromArray(['config' => $configFile]),
            );
        } finally {
            $eventFacadeInstance->setValue(null, $originalEventFacade);
            unlink($configFile);
        }

        self::addToAssertionCount(1);
    }
}
